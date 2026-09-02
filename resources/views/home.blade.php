@extends('layouts.app')
@section('title', 'Lumens | Soluciones de iluminación comercial e industrial')
@section('description', 'Catálogo de iluminación LED comercial e industrial. High Bay, Paneles, Emergencias, Reflectores y más.')

@section('content')
{{-- Hero --}}
<section class="bg-brand text-white soft-grid">
    <div class="mx-auto max-w-7xl px-4 py-20 text-center">
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">
            {{ $homeSettings['hero_title'] }}
        </h1>
        <p class="mt-5 text-lg text-mist max-w-2xl mx-auto">
            {{ $homeSettings['hero_subtitle'] }}
        </p>
        <div class="mt-8 flex justify-center gap-3 flex-wrap">
            <a href="{{ route('catalog.index') }}"
               class="inline-flex h-12 items-center rounded-full bg-accent px-6 text-sm font-bold text-brand shadow-sm transition hover:-translate-y-0.5">
                Ver catálogo
            </a>
            <a href="{{ route('cart.index') }}"
               class="inline-flex h-12 items-center rounded-full border border-white/30 px-6 text-sm font-bold text-white transition hover:bg-white/10">
                Ver carrito
            </a>
        </div>
    </div>
</section>

{{-- Promociones --}}
@if ($homeSlides->isNotEmpty())
<section id="promociones" class="bg-white">
    <div class="mx-auto max-w-7xl px-4 py-16">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wider text-accent">Promociones y novedades</p>
                <h2 class="mt-2 text-3xl font-bold text-brand">Lo nuevo de Lumens</h2>
                <p class="mt-2 lumens-muted">Banners administrables para campañas, promociones y artículos nuevos.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" data-slide-prev
                        class="grid size-11 place-items-center rounded-full border border-mist text-brand transition hover:border-accent hover:text-accent"
                        aria-label="Banner anterior">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <button type="button" data-slide-next
                        class="grid size-11 place-items-center rounded-full bg-brand text-white transition hover:bg-black"
                        aria-label="Banner siguiente">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>

        <div data-home-slides class="-mx-4 flex snap-x gap-5 overflow-x-auto px-4 pb-4 scroll-smooth">
            @foreach ($homeSlides as $slide)
                <a href="{{ $slide->link_url ?: route('catalog.index') }}"
                   class="group relative flex min-h-[22rem] min-w-[20rem] snap-start overflow-hidden rounded-2xl bg-brand p-6 text-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl sm:min-w-[34rem] lg:min-w-[44rem]">
                    <img src="{{ $slide->display_image_url }}" alt="{{ $slide->title }}" class="absolute inset-0 size-full object-cover transition duration-500 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-r from-brand/95 via-brand/65 to-brand/10"></div>
                    <div class="absolute inset-x-0 bottom-0 h-2 bg-accent"></div>
                    <div class="relative z-10 flex max-w-md flex-col justify-end">
                        @if ($slide->badge)
                            <span class="mb-4 self-start rounded-full bg-accent px-3 py-1 text-xs font-black uppercase tracking-wider text-brand">{{ $slide->badge }}</span>
                        @endif
                        <h3 class="text-3xl font-extrabold leading-tight">{{ $slide->title }}</h3>
                        @if ($slide->subtitle)
                            <p class="mt-3 text-sm leading-relaxed text-mist">{{ $slide->subtitle }}</p>
                        @endif
                        <span class="mt-6 inline-flex items-center text-sm font-bold text-accent">
                            {{ $slide->button_text ?: 'Ver productos' }}
                            <svg class="ml-2 size-4 transition group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Productos destacados --}}
<section id="productos" class="bg-white border-y border-mist/60">
    <div class="mx-auto max-w-7xl px-4 py-16">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-brand">Productos Destacados</h2>
                <p class="mt-2 lumens-muted">Selección de nuestros productos más populares</p>
            </div>
            <a href="{{ route('catalog.index') }}" class="hidden sm:inline text-sm font-semibold text-brand hover:text-accent">
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
<section class="bg-brand text-white">
    <div class="mx-auto max-w-7xl px-4 py-16 text-center">
        <h2 class="text-3xl font-bold">Prepara tu pedido en el carrito</h2>
        <p class="mt-3 text-mist max-w-xl mx-auto">
            Agrega productos, revisa cantidades y envía la solicitud para que ventas le dé seguimiento.
        </p>
        <a href="{{ route('cart.index') }}"
           class="mt-6 inline-flex h-12 items-center rounded-full bg-accent px-6 text-sm font-bold text-brand shadow-sm transition hover:-translate-y-0.5">
            Ir al carrito
        </a>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const carousel = document.querySelector('[data-home-slides]');
        const prev = document.querySelector('[data-slide-prev]');
        const next = document.querySelector('[data-slide-next]');

        if (!carousel || !prev || !next) {
            return;
        }

        const scrollByCard = (direction) => {
            const card = carousel.querySelector('a');
            const amount = card ? card.getBoundingClientRect().width + 16 : 360;
            carousel.scrollBy({ left: amount * direction, behavior: 'smooth' });
        };

        prev.addEventListener('click', () => scrollByCard(-1));
        next.addEventListener('click', () => scrollByCard(1));
    });
</script>
@endsection
