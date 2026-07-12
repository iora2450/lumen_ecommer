<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category:id,name,slug', 'brand:id,name,slug', 'primaryImage'])
            ->active();

        // Filtros
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }
        if ($request->filled('brand')) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $request->brand));
        }
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhere('sku', 'like', $term);
            });
        }
        if ($request->boolean('on_sale')) {
            $query->where('is_promotion', true)->whereNotNull('promotion_price');
        }
        if ($request->boolean('in_stock')) {
            $query->where('qty', '>', 0);
        }

        // Ordenamiento
        $sort = $request->input('sort', 'name');
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($sort === 'newest') {
            $query->orderByDesc('created_at');
        } else {
            $query->orderBy('name');
        }

        $products = $query->paginate(12)->withQueryString();

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

    public function show(string $slug)
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

        return view('catalog.show', compact('product', 'related'));
    }
}