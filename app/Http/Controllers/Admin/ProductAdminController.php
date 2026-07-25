<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category:id,name', 'brand:id,name']);

        if ($request->filled('q')) {
            $term = '%' . $request->q . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('sku', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'featured' => $query->where('is_featured', true),
                'promotion' => $query->where('is_promotion', true),
                default => null,
            };
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $products = $query
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug,' . $product->id],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'promotion_price' => ['nullable', 'numeric', 'min:0'],
            'promotion_starts_at' => ['nullable', 'date'],
            'promotion_ends_at' => ['nullable', 'date', 'after_or_equal:promotion_starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_promotion' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_promotion'] = $request->boolean('is_promotion');
        $data['promotion_price'] = $data['is_promotion'] ? ($data['promotion_price'] ?? null) : null;
        $data['promotion_starts_at'] = $data['is_promotion'] ? ($data['promotion_starts_at'] ?? null) : null;
        $data['promotion_ends_at'] = $data['is_promotion'] ? ($data['promotion_ends_at'] ?? null) : null;

        $product->update($data);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Producto actualizado.');
    }
}
