<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeSlide;
use Illuminate\Http\Request;

class HomeSlideAdminController extends Controller
{
    public function index()
    {
        $slides = HomeSlide::orderBy('sort_order')
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
        return view('admin.home-slides.edit', ['slide' => $homeSlide]);
    }

    public function update(Request $request, HomeSlide $homeSlide)
    {
        $homeSlide->update($this->validatedData($request));

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

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'badge' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'image_url' => ['required', 'string', 'max:2048'],
            'link_url' => ['nullable', 'string', 'max:2048'],
            'button_text' => ['nullable', 'string', 'max:60'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
