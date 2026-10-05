@extends('layouts.admin')
@section('title', $slide->exists ? 'Editar banner' : 'Nuevo banner')
@section('subtitle', 'Crea promociones interactivas con imagen, mensaje, botón y enlace para el carrusel del inicio.')

@section('content')
@php
    $currentLink = old('link_url', $slide->link_url);
    $destination = old('destination', match (true) {
        filled($slide->category_id) => 'category',
        $currentLink === route('offers.index', [], false), $currentLink === route('offers.index') => 'offers',
        blank($currentLink), $currentLink === route('catalog.index', [], false), $currentLink === route('catalog.index') => 'catalog',
        default => 'custom',
    });
@endphp
<form method="POST" enctype="multipart/form-data" action="{{ $slide->exists ? route('admin.home-slides.update', $slide) : route('admin.home-slides.store') }}" class="grid gap-6 lg:grid-cols-[1fr_360px]">
    @csrf
    @if ($slide->exists)
        @method('PATCH')
    @endif

    <div class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Contenido</h2>
            <div class="grid gap-4">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Etiqueta
                    <input name="badge" value="{{ old('badge', $slide->badge) }}" placeholder="Promoción, Nuevo, Temporada..."
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Título
                    <input name="title" value="{{ old('title', $slide->title) }}" required
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Texto
                    <textarea name="subtitle" rows="4" class="rounded-lg border border-slate-300 px-3 py-2 font-normal leading-relaxed focus:border-[#FFAE00] focus:outline-none">{{ old('subtitle', $slide->subtitle) }}</textarea>
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-1 text-sm font-bold uppercase tracking-wide text-slate-500">Imagen del banner</h2>
            <p class="mb-4 text-xs text-slate-500">Sube una imagen horizontal en JPG, PNG o WebP, de hasta 5 MB.</p>
            <div class="grid gap-4">
                <label class="grid cursor-pointer gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-6 text-center transition hover:border-[#FFAE00] hover:bg-amber-50/40">
                    <span class="mx-auto grid size-12 place-items-center rounded-full bg-white text-[#203749] shadow-sm" aria-hidden="true">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4-4 4 4m-4-4V4m8 8h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2h-3"/></svg>
                    </span>
                    <span class="text-sm font-bold text-[#203749]">Seleccionar imagen</span>
                    <span class="text-xs font-normal text-slate-500" data-image-name>{{ $slide->image_url ? 'Puedes conservar la imagen actual o subir una nueva.' : 'Recomendado: 1600 × 700 px.' }}</span>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="sr-only" data-image-input>
                </label>

                @error('image')
                    <p class="text-sm font-semibold text-rose-600">{{ $message }}</p>
                @enderror
                @error('image_url')
                    <p class="text-sm font-semibold text-rose-600">{{ $message }}</p>
                @enderror

                <details class="rounded-lg border border-slate-200 bg-white p-3">
                    <summary class="cursor-pointer text-sm font-semibold text-slate-600">Opción avanzada: usar URL de imagen</summary>
                    <label class="mt-3 grid gap-1 text-sm font-semibold text-slate-700">
                        URL externa o ruta existente
                        <input name="image_url" value="{{ old('image_url', $slide->image_url) }}" placeholder="https://... o /images/banner.jpg"
                               class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                    </label>
                </details>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-1 text-sm font-bold uppercase tracking-wide text-slate-500">Destino del banner</h2>
            <p class="mb-4 text-xs text-slate-500">Elige a dónde llevará el banner cuando el cliente lo seleccione.</p>
            <div class="grid gap-4">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Destino
                    <select id="news-destination" name="destination" class="rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                        <option value="offers" @selected($destination === 'offers')>Ofertas activas — productos con descuento</option>
                        <option value="category" @selected($destination === 'category')>Una categoría específica</option>
                        <option value="catalog" @selected($destination === 'catalog')>Catálogo completo</option>
                        <option value="custom" @selected($destination === 'custom')>Enlace personalizado</option>
                    </select>
                </label>
                <div id="news-category" @class(['hidden' => $destination !== 'category'])>
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">
                        Categoría promocionada
                        <select name="category_id" class="rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                            <option value="">Selecciona una categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $slide->category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                        </select>
                    </label>
                </div>
                <div id="news-custom-link" @class(['hidden' => $destination !== 'custom'])>
                    <label class="grid gap-1 text-sm font-semibold text-slate-700">
                        Enlace personalizado
                        <input name="link_url" value="{{ $currentLink }}" placeholder="https://... o /pagina"
                               class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                    </label>
                </div>
                <div id="news-offers-help" @class(['rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900', 'hidden' => $destination !== 'offers'])>
                    <p class="font-bold">Este banner abrirá la página de ofertas.</p>
                    <p class="mt-1 text-xs">Los productos se actualizan automáticamente según sus precios y fechas promocionales.</p>
                </div>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Texto del botón
                    <input id="news-button-text" name="button_text" value="{{ old('button_text', $slide->button_text) }}" placeholder="Ver ofertas"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
            </div>
        </section>
    </div>

    <aside class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Publicación</h2>
            <div class="grid gap-4">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Orden
                    <input type="number" min="0" max="65535" name="sort_order" value="{{ old('sort_order', $slide->sort_order ?? 0) }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700">
                    Visible en inicio
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $slide->is_active ?? true)) class="size-4 accent-[#FFAE00]">
                </label>
            </div>
        </section>

        <section id="banner-preview" @class(['overflow-hidden rounded-xl border border-slate-200 bg-white', 'hidden' => !old('image_url', $slide->image_url)])>
                <img id="banner-preview-image" src="{{ old('image_url', $slide->image_url) }}" alt="Vista previa" class="aspect-video w-full object-cover">
                <div class="p-4">
                    <p class="text-sm font-bold text-[#203749]">{{ old('title', $slide->title) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ old('subtitle', $slide->subtitle) }}</p>
                </div>
        </section>

        <div class="flex gap-3">
            <button class="rounded-lg bg-[#203749] px-5 py-3 text-sm font-bold text-white">Guardar</button>
            <a href="{{ route('admin.home-slides.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Volver</a>
        </div>

    </aside>
</form>

@if ($slide->exists)
    <form method="POST" action="{{ route('admin.home-slides.destroy', $slide) }}" class="mt-5" onsubmit="return confirm('¿Eliminar este banner?')">
        @csrf
        @method('DELETE')
        <button class="text-sm font-semibold text-rose-600 hover:text-rose-700">Eliminar banner</button>
    </form>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.querySelector('[data-image-input]');
    const fileName = document.querySelector('[data-image-name]');
    const preview = document.getElementById('banner-preview');
    const previewImage = document.getElementById('banner-preview-image');
    const destination = document.getElementById('news-destination');
    const category = document.getElementById('news-category');
    const customLink = document.getElementById('news-custom-link');
    const offersHelp = document.getElementById('news-offers-help');
    const buttonText = document.getElementById('news-button-text');

    input?.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file) return;

        if (fileName) fileName.textContent = file.name;
        if (previewImage) previewImage.src = URL.createObjectURL(file);
        preview?.classList.remove('hidden');
    });

    destination?.addEventListener('change', () => {
        category?.classList.toggle('hidden', destination.value !== 'category');
        customLink?.classList.toggle('hidden', destination.value !== 'custom');
        offersHelp?.classList.toggle('hidden', destination.value !== 'offers');

        if (destination.value === 'offers' && (!buttonText.value || buttonText.value === 'Ver productos')) {
            buttonText.value = 'Ver ofertas';
        } else if (destination.value !== 'offers' && (!buttonText.value || buttonText.value === 'Ver ofertas')) {
            buttonText.value = 'Ver productos';
        }
    });
});
</script>
@endsection
