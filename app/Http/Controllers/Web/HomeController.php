<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HeroSlide;
use App\Models\HomeSlide;
use App\Models\Product;
use App\Models\Setting;

class HomeController extends Controller
{
    public function home()
    {
        $featuredProducts = Product::with(['category:id,name,slug', 'brand:id,name,slug', 'primaryImage'])
            ->featured()
            ->visibleOnWeb()
            ->limit(6)
            ->get();

        $saleProducts = Product::with(['category:id,name,slug', 'brand:id,name,slug', 'primaryImage'])
            ->visibleOnWeb()
            ->onSale()
            ->orderByDesc('promotion_starts_at')
            ->limit(3)
            ->get();

        $homeSlides = HomeSlide::active()
            ->where(function ($query) {
                $query->whereNull('category_id')
                    ->orWhereHas('category', fn ($category) => $category->active());
            })
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $heroSlides = HeroSlide::active()
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        $homeSettings = [
            'hero_title' => Setting::get('home_hero_title', 'Iluminando con calidad'),
            'hero_subtitle' => Setting::get('home_hero_subtitle', 'Soluciones LED certificadas para proyectos comerciales, industriales y residenciales.'),
        ];

        return view('home', compact('featuredProducts', 'saleProducts', 'heroSlides', 'homeSlides', 'homeSettings'));
    }

    public function catalog()
    {
        $products = Product::with(['category:id,name,slug', 'brand:id,name,slug', 'primaryImage'])
            ->visibleOnWeb()
            ->paginate(12);

        $categories = Category::active()
            ->withCount(['products' => fn ($q) => $q->visibleOnWeb()])
            ->orderBy('sort_order')
            ->get();

        $brands = Brand::active()
            ->withCount(['products' => fn ($q) => $q->visibleOnWeb()])
            ->orderBy('name')
            ->get();

        return view('catalog.index', compact('products', 'categories', 'brands'));
    }

    public function product(string $slug)
    {
        $product = Product::with(['category', 'brand', 'images', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->where('slug', $slug)
            ->visibleOnWeb()
            ->firstOrFail();

        $related = Product::with(['primaryImage'])
            ->visibleOnWeb()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get();

        return view('catalog.product', compact('product', 'related'));
    }
}
