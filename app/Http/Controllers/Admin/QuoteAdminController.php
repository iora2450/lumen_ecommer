<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Services\Erp\WebOrderDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class QuoteAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Quote::with('items')->where('request_type', Quote::TYPE_PURCHASE);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('quote_number', 'like', $term)
                  ->orWhere('customer_name', 'like', $term)
                  ->orWhere('customer_email', 'like', $term);
            });
        }

        $quotes = $query->latest()->paginate(15)->withQueryString();
        return view('admin.quotes.index', compact('quotes'));
    }

    public function show(Quote $quote)
    {
        abort_unless($quote->request_type === Quote::TYPE_PURCHASE, 404);

        $quote->load('items', 'erpDelivery');
        return view('admin.quotes.show', compact('quote'));
    }

    public function syncErpDelivery(Quote $quote, WebOrderDelivery $service)
    {
        abort_unless($quote->request_type === Quote::TYPE_PURCHASE, 404);

        if (! config('web_orders.enabled')) {
            return back()->withErrors(['erp_delivery' => 'La integración con el ERP está desactivada.']);
        }

        try {
            $service->enqueue($quote);
            $delivery = $quote->erpDelivery()->firstOrFail();

            if ($delivery->delivered_at) {
                return back()->with('success', 'El recibo ya fue recibido por el ERP.');
            }

            $lock = Cache::lock('web-order-delivery-'.$delivery->id, 60);
            if (! $lock->get()) {
                return back()->withErrors(['erp_delivery' => 'El envío ya se está actualizando. Intenta nuevamente en unos segundos.']);
            }

            try {
                $service->sync($delivery);
            } finally {
                $lock->release();
            }

            $delivery->refresh();
        } catch (\Throwable $e) {
            return back()->withErrors(['erp_delivery' => mb_substr($e->getMessage(), 0, 500)]);
        }

        if ($delivery->delivered_at) {
            return back()->with('success', 'Recibo enviado y confirmado por el ERP.');
        }

        return back()->withErrors([
            'erp_delivery' => $delivery->last_error ?: 'El ERP todavía no ha confirmado la recepción.',
        ]);
    }

    public function updateStatus(Request $request, Quote $quote)
    {
        abort_unless($quote->request_type === Quote::TYPE_PURCHASE, 404);

        $request->validate([
            'status' => 'required|in:pending,reviewed,responded,closed',
        ]);

        $quote->update(['status' => $request->status]);

        return back()->with('success', 'Estado actualizado.');
    }
}
