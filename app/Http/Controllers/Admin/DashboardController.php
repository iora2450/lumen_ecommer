<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Quote;
use App\Models\SyncLog;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_products'      => Product::count(),
            'active_products'     => Product::active()->count(),
            'featured_products'   => Product::featured()->count(),
            'total_quotes'        => Quote::count(),
            'pending_quotes'      => Quote::pending()->count(),
            'low_stock_products'  => Product::active()->where('qty', '<', 10)->where('qty', '>', 0)->count(),
            'out_of_stock'        => Product::active()->where('qty', '<=', 0)->count(),
            'total_value'         => Product::active()->sum(\DB::raw('price * qty')),
        ];

        $recentQuotes = Quote::latest()->limit(5)->get();
        $lastSync     = SyncLog::orderByDesc('id')->first();

        return view('admin.dashboard', compact('stats', 'recentQuotes', 'lastSync'));
    }
}