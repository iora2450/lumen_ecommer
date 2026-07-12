<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    public function home()
    {
        $featuredProducts = Product::with(['category:id,name,slug', 'brand:id,name,slug', 'primaryImage'])
            ->featured()
            ->limit(6)
            ->get();

        $categories = Category::active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->get();

        return view('home', compact('featuredProducts', 'categories'));
    }

    public function catalog()
    {
        $products = Product::with(['category:id,name,slug', 'brand:id,name,slug', 'primaryImage'])
            ->active()
            ->paginate(12);

        $categories = Category::active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->get();

        $brands = Brand::active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('name')
            ->get();

        return view('catalog.index', compact('products', 'categories', 'brands'));
    }

    public function product(string $slug)
    {
        $product = Product::with(['category', 'brand', 'images', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        $related = Product::with(['primaryImage'])
            ->active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return view('catalog.product', compact('product', 'related'));
    }
}