<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\HomeSlide;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HomeSlideAdminController extends Controller
{
    public function index()
    {
        $slides = HomeSlide::with('category:id,name,slug')->orderBy('sort_order')
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->paginate(20);

        return view('admin.home-slides.index', compact('slides'));
    }

    public function create()
    {
        return view('admin.home-slides.edit', [
            'slide' => new HomeSlide([
                'is_active' => true,
                'sort_order' => 0,
                'button_text' => 'Ver productos',
            ]),
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request)
    {
        HomeSlide::create($this->validatedData($request));

        return redirect()
            ->route('admin.home-slides.index')
            ->with('success', 'Banner creado.');
    }

    public function edit(HomeSlide $homeSlide)
    {
        return view('admin.home-slides.edit', [
            'slide' => $homeSlide,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, HomeSlide $homeSlide)
    {
        $homeSlide->update($this->validatedData($request, $homeSlide));

        return redirect()
            ->route('admin.home-slides.index')
            ->with('success', 'Banner actualizado.');
    }

    public function destroy(HomeSlide $homeSlide)
    {
        $homeSlide->delete();

        return redirect()
            ->route('admin.home-slides.index')
            ->with('success', 'Banner eliminado.');
    }

    private function validatedData(Request $request, ?HomeSlide $homeSlide = null): array
    {
        $request->merge([
            'destination' => $request->input('destination')
                ?: (filled($request->input('category_id'))
                    ? 'category'
                    : (filled($request->input('link_url')) ? 'custom' : 'catalog')),
        ]);

        $data = $request->validate([
            'badge' => ['nullable', 'string', 'max:80'],
            'destination' => ['required', 'in:offers,category,catalog,custom'],
            'category_id' => ['nullable', 'required_if:destination,category', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'link_url' => ['nullable', 'string', 'max:2048'],
            'button_text' => ['nullable', 'string', 'max:60'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        unset($data['image']);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('', 'banners');

            if (! $path) {
                throw ValidationException::withMessages(['image' => 'No se pudo guardar la imagen. Inténtalo nuevamente.']);
            }

            $data['image_url'] = '/images/banners/'.$path;
        } elseif (empty($data['image_url'])) {
            if (! $homeSlide?->image_url) {
                throw ValidationException::withMessages(['image' => 'Selecciona una imagen para el banner.']);
            }

            $data['image_url'] = $homeSlide->image_url;
        }

        $data['link_url'] = match ($data['destination']) {
            'offers' => route('offers.index', [], false),
            'category' => route('catalog.index', [
                'category' => Category::findOrFail($data['category_id'])->slug,
            ], false),
            'catalog' => route('catalog.index', [], false),
            default => filled($data['link_url'] ?? null)
                ? trim($data['link_url'])
                : route('catalog.index', [], false),
        };

        if ($data['destination'] !== 'category') {
            $data['category_id'] = null;
        }

        $data['is_active'] = $request->boolean('is_active');
        unset($data['destination']);

        return $data;
    }

    private function categories()
    {
        return Category::active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }
}
