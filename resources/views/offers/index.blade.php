@extends('layouts.app')
@section('title', 'Ofertas en iluminación | Lumens')
@section('description', 'Descubre productos de iluminación Lumens con precios promocionales vigentes.')

@section('content')
<section class="overflow-hidden bg-brand text-white">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-14 lg:grid-cols-[1fr_auto] lg:items-center">
        <div>
            <span class="inline-flex rounded-full bg-accent px-3 py-1 text-xs font-black uppercase tracking-wider text-brand">Ofertas vigentes</span>
            <h1 class="mt-5 text-4xl font-extrabold tracking-tight sm:text-5xl">Ilumina más, paga menos</h1>
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-mist">Aquí solo aparecen promociones activas. El precio rebajado se aplica automáticamente al carrito; no necesitas ningún código.</p>
        </div>
        <div class="rounded-3xl border border-white/15 bg-white/10 px-8 py-6 text-center backdrop-blur">
            <p class="text-4xl font-black text-accent">{{ $products->total() }}</p>
            <p class="mt-1 text-sm font-semibold text-white/80">productos en oferta</p>
        </div>
    </div>
</section>

<div class="mx-auto max-w-7xl px-4 py-10">
    <nav class="mb-7 text-sm text-brand/60" aria-label="Migas de pan">
        <a href="{{ route('home') }}" class="hover:text-accent">Inicio</a>
        <span class="mx-2">/</span>
        <span class="font-semibold text-brand">Ofertas</span>
    </nav>

    @if ($categories->isNotEmpty())
        <div class="mb-8 flex flex-wrap gap-2" aria-label="Filtrar ofertas por categoría">
            <a href="{{ route('offers.index') }}"
               class="rounded-full px-4 py-2 text-sm font-bold transition {{ request('category') ? 'border border-mist bg-white text-brand hover:border-accent' : 'bg-brand text-white' }}">
                Todas
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('offers.index', ['category' => $category->slug]) }}"
                   class="rounded-full px-4 py-2 text-sm font-bold transition {{ request('category') === $category->slug ? 'bg-brand text-white' : 'border border-mist bg-white text-brand hover:border-accent' }}">
                    {{ $category->promotion_label ?: $category->name }} <span class="ml-1 opacity-60">{{ $category->products_count }}</span>
                </a>
            @endforeach
        </div>
    @endif

    @if ($products->isNotEmpty())
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($products as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-9">{{ $products->links() }}</div>
    @else
        <div class="rounded-3xl border-2 border-dashed border-mist bg-white px-6 py-16 text-center">
            <h2 class="text-2xl font-bold text-brand">No hay ofertas activas en este momento</h2>
            <p class="mx-auto mt-2 max-w-lg text-brand/60">Puedes explorar el catálogo completo mientras preparamos nuevas promociones.</p>
            <a href="{{ route('catalog.index') }}" class="mt-6 inline-flex h-12 items-center rounded-full bg-accent px-6 text-sm font-bold text-brand">Ver catálogo</a>
        </div>
    @endif
</div>
@endsection
