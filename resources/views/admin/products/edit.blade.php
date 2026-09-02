@extends('layouts.admin')
@section('title', 'Editar Producto')
@section('subtitle', $product->sku . ' · ' . $product->name)

@section('content')
<form method="POST" action="{{ route('admin.products.update', $product) }}" class="grid gap-6 lg:grid-cols-[1fr_360px]">
    @csrf
    @method('PATCH')

    <div class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Contenido comercial</h2>
            <div class="grid gap-4">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Nombre
                    <input name="name" value="{{ old('name', $product->name) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Slug
                    <input name="slug" value="{{ old('slug', $product->slug) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Resumen corto
                    <input name="short_description" value="{{ old('short_description', $product->short_description) }}" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Descripción
                    <textarea name="description" rows="7" class="rounded-lg border border-slate-300 px-3 py-2 font-normal leading-relaxed focus:border-[#FFAE00] focus:outline-none">{{ old('description', $product->description) }}</textarea>
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Imagen y categoría</h2>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-1 text-sm font-semibold text-slate-700 md:col-span-2">
                    Imagen principal
                    <input name="image_url" value="{{ old('image_url', $product->image_url) }}" placeholder="URL completa o archivo legacy"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Categoría
                    <select name="category_id" class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                        <option value="">Sin categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Precio base
                    <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Especificaciones técnicas</h2>
            <label class="grid gap-2 text-sm font-semibold text-slate-700">
                Ficha técnica
                <textarea name="technical_specs" rows="8"
                          placeholder="Potencia: 40W&#10;Voltaje: 120-277V&#10;Temperatura: 4000K&#10;Protección: IP65"
                          class="rounded-lg border border-slate-300 px-3 py-2 font-normal leading-relaxed focus:border-[#FFAE00] focus:outline-none">{{ old('technical_specs', collect($product->specs ?? [])->map(fn ($value, $key) => $key . ': ' . $value)->implode("\n")) }}</textarea>
            </label>
            <p class="mt-2 text-xs text-slate-500">
                Deja este espacio preparado aunque todavía no tengas la información. Luego se puede completar desde aquí o desde la sincronización.
            </p>
        </section>
    </div>

    <aside class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Estado en la web</h2>
            <div class="space-y-3 text-sm">
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                    <span>Visible en catálogo</span>
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="size-4 accent-[#FFAE00]">
                </label>
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                    <span>Producto destacado</span>
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured)) class="size-4 accent-[#FFAE00]">
                </label>
                <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                    <span>En oferta</span>
                    <input type="checkbox" name="is_promotion" value="1" @checked(old('is_promotion', $product->is_promotion)) class="size-4 accent-[#FFAE00]">
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Oferta</h2>
            <div class="grid gap-4">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Precio de oferta
                    <input type="number" step="0.01" min="0" name="promotion_price" value="{{ old('promotion_price', $product->promotion_price) }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Inicio
                    <input type="datetime-local" name="promotion_starts_at" value="{{ old('promotion_starts_at', optional($product->promotion_starts_at)->format('Y-m-d\TH:i')) }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Fin
                    <input type="datetime-local" name="promotion_ends_at" value="{{ old('promotion_ends_at', optional($product->promotion_ends_at)->format('Y-m-d\TH:i')) }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
            </div>
        </section>

        <div class="flex gap-3">
            <button class="rounded-lg bg-[#203749] px-5 py-3 text-sm font-bold text-white">Guardar</button>
            <a href="{{ route('admin.products.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Volver</a>
        </div>
    </aside>
</form>
@endsection
