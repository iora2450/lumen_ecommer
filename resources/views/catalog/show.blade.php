@extends('layouts.app')
@section('title', $product->name . ' | Lumens')
@section('description', Illuminate\Support\Str::limit($product->display_description ?? 'Detalles del producto ' . $product->name, 160))

@section('content')
@php
    $imageUrl = $product->display_image_url;
    $description = $product->display_description;
@endphp
<div class="mx-auto max-w-7xl px-4 py-8">
    {{-- Breadcrumb --}}
    <nav class="text-sm text-brand/60 mb-6">
        <a href="{{ route('home') }}" class="hover:text-accent">Inicio</a>
        <span class="mx-2">/</span>
        <a href="{{ route('catalog.index') }}" class="hover:text-accent">Catálogo</a>
        @if ($product->category)
            <span class="mx-2">/</span>
            <a href="{{ route('catalog.index', ['category' => $product->category->slug]) }}" class="hover:text-accent">{{ $product->category->name }}</a>
        @endif
        <span class="mx-2">/</span>
        <span class="text-brand">{{ $product->name }}</span>
    </nav>

    <div class="grid gap-10 lg:grid-cols-2">
        {{-- Imagen --}}
        <div class="rounded-2xl border border-mist/70 bg-white p-8 aspect-square flex items-center justify-center">
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $product->name }}" class="size-full object-contain"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <svg class="hidden size-32 text-mist" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            @else
                <svg class="size-32 text-mist" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            @endif
        </div>

        {{-- Info --}}
        <div>
            @if ($product->brand)
                <p class="text-sm font-semibold text-accent uppercase tracking-wide">{{ $product->brand->name }}</p>
            @endif
            <h1 class="mt-2 text-3xl font-bold text-brand">{{ $product->name }}</h1>
            <p class="mt-1 text-sm text-brand/60 font-mono">SKU: {{ $product->sku }}</p>

            {{-- Badges --}}
            <div class="mt-4 flex flex-wrap gap-2">
                @if ($product->isOnSale)
                    <span class="rounded-full bg-brand text-white text-xs font-bold px-3 py-1">EN PROMOCIÓN</span>
                @endif
                @if ($product->is_featured)
                    <span class="rounded-full bg-accent text-brand text-xs font-bold px-3 py-1">DESTACADO</span>
                @endif
                @if ($product->qty > 0)
                    <span class="rounded-full bg-mist text-brand text-xs font-bold px-3 py-1">EN STOCK ({{ $product->qty }})</span>
                @else
                    <span class="rounded-full bg-black text-white text-xs font-bold px-3 py-1">SIN STOCK</span>
                @endif
                @if (!empty($product->certifications))
                    @foreach ($product->certifications as $cert)
                        <span class="rounded-full border border-mist bg-white text-brand text-xs font-bold px-3 py-1">{{ $cert }}</span>
                    @endforeach
                @endif
            </div>

            {{-- Precio --}}
            <div class="mt-6 flex items-baseline gap-3">
                <span class="text-4xl font-extrabold text-brand">${{ number_format((float) $product->effective_price, 2) }}</span>
                @if ($product->isOnSale)
                    <span class="text-xl text-brand/45 line-through">${{ number_format((float) $product->price, 2) }}</span>
                    <span class="rounded-full bg-accent/20 text-brand text-xs font-bold px-2 py-1">
                        -{{ round((1 - $product->promotion_price / $product->price) * 100) }}%
                    </span>
                @endif
            </div>

            {{-- Descripción --}}
            @if ($description)
                <p class="mt-6 text-brand/80 leading-relaxed">{{ $description }}</p>
            @endif

            {{-- Variantes --}}
            @if ($product->variants->count() > 0)
                <div class="mt-8">
                    <h3 class="text-sm font-semibold text-brand mb-3">Variantes disponibles</h3>
                    <div class="grid gap-2">
                        @foreach ($product->variants as $variant)
                            <div class="flex items-center justify-between rounded-lg border border-mist/70 bg-white px-4 py-2">
                                <div>
                                    <p class="text-sm font-medium text-brand">{{ $variant->name }}</p>
                                    <p class="text-xs text-brand/60 font-mono">{{ $variant->sku }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-bold text-brand">${{ number_format((float) $variant->price, 2) }}</p>
                                    <p class="text-xs text-brand/60">{{ $variant->qty }} en stock</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- CTA --}}
            <form method="POST" action="{{ route('cart.add', $product) }}" class="mt-8 rounded-2xl border border-mist/70 bg-white p-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-[1fr_120px]">
                    @if ($product->variants->count() > 0)
                        <label class="grid gap-1 text-sm font-semibold text-brand">
                            Variante
                            <select name="variant_id" class="rounded-lg border border-mist px-3 py-2 font-normal focus:border-accent focus:outline-none">
                                <option value="">Producto base</option>
                                @foreach ($product->variants as $variant)
                                    <option value="{{ $variant->id }}">{{ $variant->name }} - {{ $variant->sku }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif
                    <label class="grid gap-1 text-sm font-semibold text-brand">
                        Cantidad
                        <input type="number" name="qty" min="1" value="1" class="rounded-lg border border-mist px-3 py-2 font-normal focus:border-accent focus:outline-none">
                    </label>
                </div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="submit" class="inline-flex h-12 items-center rounded-full bg-accent px-6 text-sm font-bold text-brand shadow-sm transition hover:-translate-y-0.5">
                        Agregar al carrito
                    </button>
                    <a href="{{ route('cart.index') }}"
                       class="inline-flex h-12 items-center rounded-full bg-brand px-6 text-sm font-bold text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-black">
                        Ir al carrito
                    </a>
                    <button type="button" onclick="copyToClipboard('{{ $product->sku }}')"
                            class="inline-flex h-12 items-center rounded-full border border-mist px-6 text-sm font-semibold text-brand transition hover:bg-mist/25">
                        Copiar SKU
                    </button>
                </div>
            </form>

            {{-- Especificaciones --}}
            <div class="mt-8">
                <h3 class="text-sm font-semibold text-brand mb-3">Especificaciones técnicas</h3>
                @if (!empty($product->specs))
                    <dl class="rounded-lg border border-mist/70 bg-white divide-y divide-mist/70">
                        @foreach ($product->specs as $key => $value)
                            <div class="grid grid-cols-2 px-4 py-2 text-sm">
                                <dt class="font-medium text-brand/60">{{ ucfirst($key) }}</dt>
                                <dd class="text-brand">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @else
                    <div class="rounded-lg border border-dashed border-mist bg-white/70 px-4 py-6 text-sm text-brand/60">
                        Especificaciones técnicas por completar.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Relacionados --}}
    @if ($related->count() > 0)
        <section class="mt-16">
            <h2 class="text-2xl font-bold text-brand mb-6">Productos relacionados</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($related as $rel)
                    @include('partials.product-card', ['product' => $rel])
                @endforeach
            </div>
        </section>
    @endif
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => alert('SKU copiado: ' + text));
}
</script>
@endsection
