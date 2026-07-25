@extends('layouts.app')
@section('title', 'Lumens | Soluciones de iluminación comercial e industrial')
@section('description', 'Catálogo de iluminación LED comercial e industrial. High Bay, Paneles, Emergencias, Reflectores y más.')

@section('content')
{{-- Hero --}}
<section class="bg-gradient-to-br from-[#203749] to-[#1a2c3a] text-white">
    <div class="mx-auto max-w-7xl px-4 py-20 text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">
            {{ $homeSettings['hero_title'] }}
        </h1>
        <p class="mt-5 text-lg text-slate-200 max-w-2xl mx-auto">
            {{ $homeSettings['hero_subtitle'] }}
        </p>
        <div class="mt-8 flex justify-center gap-3 flex-wrap">
            <a href="{{ route('catalog.index') }}"
               class="inline-flex h-12 items-center rounded-full bg-[#FFAE00] px-6 text-sm font-bold text-[#203749] shadow-sm transition hover:-translate-y-0.5">
                Ver catálogo
            </a>
            <a href="{{ route('quote.create') }}"
               class="inline-flex h-12 items-center rounded-full border border-white/30 px-6 text-sm font-bold text-white transition hover:bg-white/10">
                Solicitar cotización
            </a>
        </div>
    </div>
</section>

{{-- Categorías --}}
<section id="categorias" class="mx-auto max-w-7xl px-4 py-16">
    <div class="text-center mb-10">
        <h2 class="text-3xl font-bold text-[#203749]">Nuestras Categorías</h2>
        <p class="mt-2 text-slate-600">Explora nuestras soluciones por familia de productos</p>
    </div>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($categories as $cat)
            <a href="{{ route('catalog.index', ['category' => $cat->slug]) }}"
               class="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-[#FFAE00] hover:shadow-lg">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-[#203749] group-hover:text-amber-600">{{ $cat->name }}</h3>
                        <div class="mt-2 flex flex-wrap gap-1">
                            @if ($cat->is_featured)
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-[11px] font-bold text-amber-800">Destacada</span>
                            @endif
                            @if ($cat->is_promotion)
                                <span class="rounded-full bg-rose-100 px-2 py-1 text-[11px] font-bold text-rose-800">{{ $cat->promotion_label ?: 'Oferta' }}</span>
                            @endif
                        </div>
                    </div>
                    <span class="shrink-0 text-xs font-semibold text-slate-500 bg-slate-100 rounded-full px-2 py-1">{{ $cat->products_count }}</span>
                </div>
                <p class="mt-2 text-sm text-slate-600">{{ $cat->description }}</p>
            </a>
        @endforeach
    </div>
</section>

{{-- Productos destacados --}}
<section id="productos" class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-[#203749]">Productos Destacados</h2>
                <p class="mt-2 text-slate-600">Selección de nuestros productos más populares</p>
            </div>
            <a href="{{ route('catalog.index') }}" class="hidden sm:inline text-sm font-semibold text-[#203749] hover:text-amber-600">
                Ver todos →
            </a>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($featuredProducts as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="bg-[#203749] text-white">
    <div class="mx-auto max-w-7xl px-4 py-16 text-center">
        <h2 class="text-3xl font-bold">¿Necesitas una cotización a medida?</h2>
        <p class="mt-3 text-slate-300 max-w-xl mx-auto">
            Cuéntanos tu proyecto y te enviamos una propuesta personalizada en menos de 24 horas.
        </p>
        <a href="{{ route('quote.create') }}"
           class="mt-6 inline-flex h-12 items-center rounded-full bg-[#FFAE00] px-6 text-sm font-bold text-[#203749] shadow-sm transition hover:-translate-y-0.5">
            Solicitar cotización
        </a>
    </div>
</section>
@endsection
