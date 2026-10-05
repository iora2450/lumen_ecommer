<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quote;
use App\Models\SyncLog;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_products' => Product::count(),
            'active_products' => Product::active()->count(),
            'featured_products' => Product::featured()->count(),
            'promotion_products' => Product::active()->onSale()->count(),
            'promotion_categories' => Category::active()->where('is_promotion', true)->count(),
            'total_quotes' => Quote::where('request_type', Quote::TYPE_PURCHASE)->count(),
            'pending_quotes' => Quote::where('request_type', Quote::TYPE_PURCHASE)->pending()->count(),
            'low_stock_products' => Product::active()->where('qty', '<', 10)->where('qty', '>', 0)->count(),
            'out_of_stock' => Product::active()->where('qty', '<=', 0)->count(),
        ];

        $recentQuotes = Quote::where('request_type', Quote::TYPE_PURCHASE)->latest()->limit(5)->get();
        $lastSync = SyncLog::orderByDesc('id')->first();

        return view('admin.dashboard', compact('stats', 'recentQuotes', 'lastSync'));
    }
}
