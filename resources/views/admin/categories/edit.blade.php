@extends('layouts.admin')
@section('title', 'Editar Categoría')
@section('subtitle', $category->name)

@section('content')
<form method="POST" action="{{ route('admin.categories.update', $category) }}" class="grid gap-6 lg:grid-cols-[1fr_360px]">
    @csrf
    @method('PATCH')

    <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Contenido</h2>
        <div class="grid gap-4">
            <label class="grid gap-1 text-sm font-semibold text-slate-700">
                Nombre
                <input name="name" value="{{ old('name', $category->name) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
            </label>
            <label class="grid gap-1 text-sm font-semibold text-slate-700">
                Slug
                <input name="slug" value="{{ old('slug', $category->slug) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
            </label>
            <label class="grid gap-1 text-sm font-semibold text-slate-700">
                Descripción
                <textarea name="description" rows="6" class="rounded-lg border border-slate-300 px-3 py-2 font-normal leading-relaxed focus:border-[#FFAE00] focus:outline-none">{{ old('description', $category->description) }}</textarea>
            </label>
            <label class="grid gap-1 text-sm font-semibold text-slate-700">
                Imagen o banner
                <input name="image_url" value="{{ old('image_url', $category->image_url) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
            </label>
        </div>
    </section>

    <aside class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Control web</h2>
            <div class="grid gap-4">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Orden
                    <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <span>
                        <span class="block font-semibold">Visible en web</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Al ocultarla, tampoco aparecerán sus productos.</span>
                    </span>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active)) class="size-4 accent-[#FFAE00]">
                </label>
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <span>Destacar en home</span>
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $category->is_featured)) class="size-4 accent-[#FFAE00]">
                </label>
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm">
                    <span>
                        <span class="block font-semibold">Categoría en oferta</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Destaca la categoría; no modifica los precios de sus productos.</span>
                    </span>
                    <input type="checkbox" name="is_promotion" value="1" @checked(old('is_promotion', $category->is_promotion)) class="size-4 accent-[#FFAE00]">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Leyenda de oferta
                    <input name="promotion_label" value="{{ old('promotion_label', $category->promotion_label) }}" placeholder="Ej. Ofertas de temporada"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
            </div>
        </section>

        <div class="flex gap-3">
            <button class="rounded-lg bg-[#203749] px-5 py-3 text-sm font-bold text-white">Guardar</button>
            <a href="{{ route('admin.categories.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Volver</a>
        </div>
    </aside>
</form>
@endsection
