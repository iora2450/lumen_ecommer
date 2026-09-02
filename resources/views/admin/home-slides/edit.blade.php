@extends('layouts.admin')
@section('title', $slide->exists ? 'Editar banner' : 'Nuevo banner')
@section('subtitle', 'Carga imágenes de promociones, campañas o artículos nuevos para el carrusel del inicio.')

@section('content')
<form method="POST" action="{{ $slide->exists ? route('admin.home-slides.update', $slide) : route('admin.home-slides.store') }}" class="grid gap-6 lg:grid-cols-[1fr_360px]">
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
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Imagen y enlace</h2>
            <div class="grid gap-4">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    URL de imagen
                    <input name="image_url" value="{{ old('image_url', $slide->image_url) }}" required placeholder="https://... o /images/banner.jpg"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Enlace del banner
                    <input name="link_url" value="{{ old('link_url', $slide->link_url) }}" placeholder="{{ route('catalog.index') }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Texto del botón
                    <input name="button_text" value="{{ old('button_text', $slide->button_text) }}" placeholder="Ver productos"
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

        @if (old('image_url', $slide->image_url))
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                <img src="{{ old('image_url', $slide->image_url) }}" alt="Vista previa" class="aspect-video w-full object-cover">
                <div class="p-4">
                    <p class="text-sm font-bold text-[#203749]">{{ old('title', $slide->title) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ old('subtitle', $slide->subtitle) }}</p>
                </div>
            </section>
        @endif

        <div class="flex gap-3">
            <button class="rounded-lg bg-[#203749] px-5 py-3 text-sm font-bold text-white">Guardar</button>
            <a href="{{ route('admin.home-slides.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Volver</a>
        </div>

        @if ($slide->exists)
            <form method="POST" action="{{ route('admin.home-slides.destroy', $slide) }}" onsubmit="return confirm('¿Eliminar este banner?')">
                @csrf
                @method('DELETE')
                <button class="text-sm font-semibold text-rose-600 hover:text-rose-700">Eliminar banner</button>
            </form>
        @endif
    </aside>
</form>
@endsection
