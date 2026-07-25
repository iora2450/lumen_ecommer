<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

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
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $homeSettings = [
            'hero_title' => Setting::get('home_hero_title', 'Iluminación que transforma tus espacios'),
            'hero_subtitle' => Setting::get('home_hero_subtitle', 'Soluciones LED certificadas para proyectos comerciales, industriales y residenciales.'),
        ];

        return view('home', compact('featuredProducts', 'categories', 'homeSettings'));
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
