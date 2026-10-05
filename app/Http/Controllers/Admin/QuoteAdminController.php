<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;

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

        $quote->load('items');
        return view('admin.quotes.show', compact('quote'));
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
