<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
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
        if ($request->boolean('featured')) {
            $query->featured();
        }
        if ($request->boolean('on_sale')) {
            $query->where('is_promotion', true)->whereNotNull('promotion_price');
        }
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        // Búsqueda
        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhere('sku', 'like', $term);
            });
        }

        // Ordenamiento
        $sort = $request->input('sort', 'name');
        $query->when($sort === 'price_asc',  fn ($q) => $q->orderBy('price', 'asc'))
              ->when($sort === 'price_desc', fn ($q) => $q->orderBy('price', 'desc'))
              ->when($sort === 'name',        fn ($q) => $q->orderBy('name', 'asc'))
              ->when($sort === 'newest',      fn ($q) => $q->orderByDesc('created_at'));

        $perPage = min((int) $request->input('per_page', 12), 50);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::with(['category', 'brand', 'images', 'variants' => fn ($q) => $q->where('is_active', true)])
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        return response()->json([
            'data' => $product,
        ]);
    }

    public function featured(): JsonResponse
    {
        $products = Product::with(['category:id,name,slug', 'brand:id,name,slug', 'primaryImage'])
            ->featured()
            ->limit(8)
            ->get();

        return response()->json(['data' => $products]);
    }
}