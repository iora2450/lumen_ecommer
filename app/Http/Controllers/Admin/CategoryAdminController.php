<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::withCount(['products' => fn ($q) => $q->active()]);

        if ($request->filled('q')) {
            $term = '%'.$request->q.'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
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

        $categories = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug,'.$category->id],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'promotion_label' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_promotion' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_promotion'] = $request->boolean('is_promotion');
        $data['promotion_label'] = $data['is_promotion'] ? ($data['promotion_label'] ?? null) : null;

        $category->update($data);

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Categoría actualizada.');
    }

    public function updateVisibility(Request $request, Category $category)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $category->update(['is_active' => $data['is_active']]);

        return back()->with(
            'success',
            $category->is_active
                ? "La categoría {$category->name} ya aparece en la web."
                : "La categoría {$category->name} y sus productos se ocultaron de la web."
        );
    }
}
