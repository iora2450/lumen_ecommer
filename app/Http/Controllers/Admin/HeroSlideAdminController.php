<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class HeroSlideAdminController extends Controller
{
    public function index()
    {
        $slides = HeroSlide::orderBy('sort_order')
            ->orderByDesc('is_active')
            ->orderBy('title')
            ->paginate(20);

        return view('admin.hero-slides.index', compact('slides'));
    }

    public function create()
    {
        return view('admin.hero-slides.edit', [
            'slide' => new HeroSlide([
                'is_active' => true,
                'sort_order' => 0,
                'button_text' => 'Ver catálogo',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        HeroSlide::create($this->validatedData($request));

        return redirect()
            ->route('admin.hero-slides.index')
            ->with('success', 'Banner principal creado.');
    }

    public function edit(HeroSlide $heroSlide)
    {
        return view('admin.hero-slides.edit', ['slide' => $heroSlide]);
    }

    public function update(Request $request, HeroSlide $heroSlide)
    {
        $heroSlide->update($this->validatedData($request, $heroSlide));

        return redirect()
            ->route('admin.hero-slides.index')
            ->with('success', 'Banner principal actualizado.');
    }

    public function destroy(HeroSlide $heroSlide)
    {
        $heroSlide->delete();

        return redirect()
            ->route('admin.hero-slides.index')
            ->with('success', 'Banner principal eliminado.');
    }

    private function validatedData(Request $request, ?HeroSlide $heroSlide = null): array
    {
        $request->merge([
            'destination' => $request->input('destination')
                ?: (filled($request->input('link_url')) ? 'custom' : 'catalog'),
        ]);

        $data = $request->validate([
            'badge' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'destination' => ['required', 'in:offers,catalog,custom'],
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
            if (! $heroSlide?->image_url) {
                throw ValidationException::withMessages(['image' => 'Selecciona una imagen para el banner principal.']);
            }

            $data['image_url'] = $heroSlide->image_url;
        }

        $data['link_url'] = match ($data['destination']) {
            'offers' => route('offers.index', [], false),
            'catalog' => route('catalog.index', [], false),
            default => filled($data['link_url'] ?? null)
                ? trim($data['link_url'])
                : route('catalog.index', [], false),
        };

        $data['is_active'] = $request->boolean('is_active');
        unset($data['destination']);

        return $data;
    }
}
