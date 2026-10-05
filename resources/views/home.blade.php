@extends('layouts.app')
@section('title', 'Lumens | Soluciones de iluminación comercial e industrial')
@section('description', 'Catálogo de iluminación LED comercial e industrial. High Bay, Paneles, Emergencias, Reflectores y más.')

@section('content')
{{-- Hero --}}
@if ($heroSlides->isNotEmpty())
<section class="relative overflow-hidden bg-brand text-white" data-hero-carousel aria-label="Banner principal">
    <div class="grid">
        @foreach ($heroSlides as $slide)
            <article data-hero-slide
                     @class([
                        'relative col-start-1 row-start-1 min-h-[30rem] transition-opacity duration-700 sm:min-h-[36rem]',
                        'z-10 opacity-100' => $loop->first,
                        'pointer-events-none opacity-0' => ! $loop->first,
                     ])
                     aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                <img src="{{ $slide->display_image_url }}" alt="" class="absolute inset-0 size-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-brand/95 via-brand/70 to-brand/20"></div>
                <div class="relative mx-auto flex min-h-[30rem] max-w-7xl items-center px-14 py-20 sm:min-h-[36rem] sm:px-20 lg:px-24">
                    <div class="max-w-2xl">
                        @if ($slide->badge)
                            <span class="inline-flex rounded-full bg-accent px-4 py-1.5 text-xs font-black uppercase tracking-wider text-brand">{{ $slide->badge }}</span>
                        @endif
                        <h1 class="mt-5 text-4xl font-extrabold tracking-tight sm:text-5xl lg:text-6xl">{{ $slide->title }}</h1>
                        @if ($slide->subtitle)
                            <p class="mt-5 max-w-xl text-base leading-relaxed text-white/85 sm:text-lg">{{ $slide->subtitle }}</p>
                        @endif
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ $slide->link_url ?: route('catalog.index') }}"
                               class="inline-flex h-12 items-center rounded-full bg-accent px-6 text-sm font-bold text-brand shadow-sm transition hover:-translate-y-0.5">
                                {{ $slide->button_text ?: 'Ver catálogo' }}
                            </a>
                            <a href="{{ route('cart.index') }}"
                               class="inline-flex h-12 items-center rounded-full border border-white/40 bg-brand/20 px-6 text-sm font-bold text-white backdrop-blur-sm transition hover:bg-white/10">
                                Ver carrito
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    @if ($heroSlides->count() > 1)
        <button type="button" data-hero-prev class="absolute left-3 top-1/2 z-20 grid size-11 -translate-y-1/2 place-items-center rounded-full border border-white/30 bg-brand/50 text-white backdrop-blur transition hover:bg-brand sm:left-6" aria-label="Banner principal anterior">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <button type="button" data-hero-next class="absolute right-3 top-1/2 z-20 grid size-11 -translate-y-1/2 place-items-center rounded-full border border-white/30 bg-brand/50 text-white backdrop-blur transition hover:bg-brand sm:right-6" aria-label="Banner principal siguiente">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        <div class="absolute inset-x-0 bottom-6 z-20 flex justify-center gap-2" aria-label="Seleccionar banner principal">
            @foreach ($heroSlides as $slide)
                <button type="button" data-hero-dot="{{ $loop->index }}"
                        @class(['h-2.5 rounded-full transition-all', 'w-8 bg-accent' => $loop->first, 'w-2.5 bg-white/60' => ! $loop->first])
                        aria-label="Mostrar banner {{ $loop->iteration }}" aria-current="{{ $loop->first ? 'true' : 'false' }}"></button>
            @endforeach
        </div>
    @endif
</section>
@else
<section class="bg-brand text-white soft-grid">
    <div class="mx-auto max-w-7xl px-4 py-20 text-center">
        <h1 class="text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $homeSettings['hero_title'] }}</h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg text-mist">{{ $homeSettings['hero_subtitle'] }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('catalog.index') }}" class="inline-flex h-12 items-center rounded-full bg-accent px-6 text-sm font-bold text-brand shadow-sm transition hover:-translate-y-0.5">Ver catálogo</a>
            <a href="{{ route('cart.index') }}" class="inline-flex h-12 items-center rounded-full border border-white/30 px-6 text-sm font-bold text-white transition hover:bg-white/10">Ver carrito</a>
        </div>
    </div>
</section>
@endif

{{-- Cómo comprar --}}
<section id="como-comprar" class="scroll-mt-28 overflow-hidden bg-mist/20">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:py-20">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-black uppercase tracking-[0.2em] text-accent">Compra en línea sin complicaciones</p>
            <h2 class="mt-3 text-3xl font-extrabold text-brand sm:text-4xl">Cómo comprar en Lumens</h2>
            <p class="mx-auto mt-4 max-w-2xl text-sm leading-relaxed text-brand/65 sm:text-base">
                Sigue estos cuatro pasos. La guía avanzará contigo para mostrarte lo fácil que es preparar y confirmar tu pedido.
            </p>
        </div>

        <div class="mt-10" data-how-to-buy>
            <div class="mx-auto mb-8 max-w-4xl" aria-hidden="true">
                <div class="h-1.5 overflow-hidden rounded-full bg-white shadow-inner">
                    <div class="how-buy-progress h-full rounded-full bg-accent" data-how-progress></div>
                </div>
                <div class="mt-2 flex justify-between text-[10px] font-black uppercase tracking-wider text-brand/45">
                    <span>Comienza aquí</span>
                    <span>Pedido listo</span>
                </div>
            </div>

            <ol class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <li>
                    <button type="button" class="how-buy-step is-active h-full w-full rounded-3xl border border-mist/70 bg-white p-5 text-left shadow-sm" data-how-step aria-pressed="true">
                        <span class="how-buy-step-number grid size-12 place-items-center rounded-2xl bg-brand text-lg font-black text-white">1</span>
                        <span class="mt-5 block text-lg font-extrabold text-brand">Explora el catálogo</span>
                        <span class="mt-2 block text-sm leading-relaxed text-brand/60">Busca productos, revisa sus detalles y elige la iluminación que necesitas.</span>
                        <span class="mt-5 inline-flex items-center text-xs font-black text-accent">Ver productos <span class="ml-1" aria-hidden="true">→</span></span>
                    </button>
                </li>
                <li>
                    <button type="button" class="how-buy-step h-full w-full rounded-3xl border border-mist/70 bg-white p-5 text-left shadow-sm" data-how-step aria-pressed="false">
                        <span class="how-buy-step-number grid size-12 place-items-center rounded-2xl bg-brand text-lg font-black text-white">2</span>
                        <span class="mt-5 block text-lg font-extrabold text-brand">Agrega al carrito</span>
                        <span class="mt-2 block text-sm leading-relaxed text-brand/60">Selecciona las cantidades y reúne todos tus productos en un solo pedido.</span>
                        <span class="mt-5 inline-flex items-center text-xs font-black text-accent">Arma tu compra <span class="ml-1" aria-hidden="true">→</span></span>
                    </button>
                </li>
                <li>
                    <button type="button" class="how-buy-step h-full w-full rounded-3xl border border-mist/70 bg-white p-5 text-left shadow-sm" data-how-step aria-pressed="false">
                        <span class="how-buy-step-number grid size-12 place-items-center rounded-2xl bg-brand text-lg font-black text-white">3</span>
                        <span class="mt-5 block text-lg font-extrabold text-brand">Completa tus datos</span>
                        <span class="mt-2 block text-sm leading-relaxed text-brand/60">Indica tus datos de contacto, la entrega y si necesitas crédito fiscal.</span>
                        <span class="mt-5 inline-flex items-center text-xs font-black text-accent">Datos protegidos <span class="ml-1" aria-hidden="true">✓</span></span>
                    </button>
                </li>
                <li>
                    <button type="button" class="how-buy-step h-full w-full rounded-3xl border border-mist/70 bg-white p-5 text-left shadow-sm" data-how-step aria-pressed="false">
                        <span class="how-buy-step-number grid size-12 place-items-center rounded-2xl bg-brand text-lg font-black text-white">4</span>
                        <span class="mt-5 block text-lg font-extrabold text-brand">Recibe confirmación</span>
                        <span class="mt-2 block text-sm leading-relaxed text-brand/60">Ventas confirmará existencias, entrega y forma de pago antes de procesar la compra.</span>
                        <span class="mt-5 inline-flex items-center text-xs font-black text-accent">Acompañamiento real <span class="ml-1" aria-hidden="true">✓</span></span>
                    </button>
                </li>
            </ol>

            <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('catalog.index') }}" class="inline-flex h-12 items-center rounded-full bg-brand px-6 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-black">Comenzar mi compra</a>
                <a href="https://wa.me/50360331749?text=Hola%20Lumens%2C%20necesito%20ayuda%20para%20comprar%20en%20l%C3%ADnea."
                   target="_blank" rel="noopener noreferrer"
                   class="inline-flex h-12 items-center rounded-full border border-brand/20 bg-white px-6 text-sm font-bold text-brand transition hover:border-emerald-600 hover:text-emerald-700">
                    Necesito ayuda
                </a>
            </div>
        </div>
    </div>
</section>

{{-- Promociones --}}
@if ($homeSlides->isNotEmpty())
<section id="promociones" class="bg-white" data-news-carousel>
    <div class="mx-auto max-w-7xl px-4 py-16">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wider text-accent">Promociones y novedades</p>
                <h2 class="mt-2 text-3xl font-bold text-brand">Lo nuevo de Lumens</h2>
                <p class="mt-2 lumens-muted">Banners administrables para campañas, promociones y artículos nuevos.</p>
            </div>
            @if ($homeSlides->count() > 1)
                <div class="flex gap-2">
                    <button type="button" data-news-prev
                            class="grid size-11 place-items-center rounded-full border border-mist text-brand transition hover:border-accent hover:text-accent"
                            aria-label="Banner anterior">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <button type="button" data-news-next
                            class="grid size-11 place-items-center rounded-full bg-brand text-white transition hover:bg-black"
                            aria-label="Banner siguiente">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            @endif
        </div>

        <div data-news-track class="-mx-4 flex snap-x snap-mandatory gap-5 overflow-x-auto px-4 pb-4 scroll-smooth">
            @foreach ($homeSlides as $slide)
                <a href="{{ $slide->link_url ?: route('catalog.index') }}"
                   data-news-slide
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

        @if ($homeSlides->count() > 1)
            <div class="mt-3 flex justify-center gap-2" aria-label="Seleccionar novedad">
                @foreach ($homeSlides as $slide)
                    <button type="button" data-news-dot="{{ $loop->index }}"
                            @class(['h-2.5 rounded-full transition-all', 'w-8 bg-brand' => $loop->first, 'w-2.5 bg-mist' => ! $loop->first])
                            aria-label="Mostrar novedad {{ $loop->iteration }}" aria-current="{{ $loop->first ? 'true' : 'false' }}"></button>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endif

{{-- Ofertas activas --}}
@if ($saleProducts->isNotEmpty())
<section class="border-y border-accent/25 bg-accent/10">
    <div class="mx-auto max-w-7xl px-4 py-16">
        <div class="mb-8 flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-black uppercase tracking-wider text-accent">Precios por tiempo limitado</p>
                <h2 class="mt-2 text-3xl font-bold text-brand">Ofertas activas</h2>
                <p class="mt-2 text-brand/65">El descuento ya está aplicado y se conserva al agregar el producto al carrito.</p>
            </div>
            <a href="{{ route('offers.index') }}" class="hidden text-sm font-bold text-brand hover:text-accent sm:inline">Ver todas →</a>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($saleProducts as $product)
                @include('partials.product-card', ['product' => $product])
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
        const buyingGuide = document.querySelector('[data-how-to-buy]');

        if (buyingGuide) {
            const steps = [...buyingGuide.querySelectorAll('[data-how-step]')];
            const progress = buyingGuide.querySelector('[data-how-progress]');
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            let activeStep = 0;
            let guideTimer = null;

            const showStep = (index) => {
                activeStep = index;
                steps.forEach((step, stepIndex) => {
                    const isActive = stepIndex === activeStep;
                    step.classList.toggle('is-active', isActive);
                    step.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                if (progress) {
                    progress.style.width = `${((activeStep + 1) / steps.length) * 100}%`;
                }
            };

            const stopGuide = () => {
                if (guideTimer) window.clearInterval(guideTimer);
                guideTimer = null;
            };

            const startGuide = () => {
                stopGuide();
                if (!reducedMotion) {
                    guideTimer = window.setInterval(() => showStep((activeStep + 1) % steps.length), 2200);
                }
            };

            steps.forEach((step, index) => {
                step.addEventListener('click', () => {
                    showStep(index);
                    startGuide();
                });
                step.addEventListener('focus', () => showStep(index));
            });

            showStep(0);

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver(([entry]) => entry.isIntersecting ? startGuide() : stopGuide(), { threshold: 0.25 });
                observer.observe(buyingGuide);
            } else {
                startGuide();
            }
        }

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const heroCarousel = document.querySelector('[data-hero-carousel]');

        if (heroCarousel) {
            const slides = [...heroCarousel.querySelectorAll('[data-hero-slide]')];
            const dots = [...heroCarousel.querySelectorAll('[data-hero-dot]')];
            const prev = heroCarousel.querySelector('[data-hero-prev]');
            const next = heroCarousel.querySelector('[data-hero-next]');
            let activeIndex = 0;
            let timer = null;

            const showHeroSlide = (index) => {
                activeIndex = (index + slides.length) % slides.length;

                slides.forEach((slide, slideIndex) => {
                    const isActive = slideIndex === activeIndex;
                    slide.classList.toggle('z-10', isActive);
                    slide.classList.toggle('opacity-100', isActive);
                    slide.classList.toggle('pointer-events-none', !isActive);
                    slide.classList.toggle('opacity-0', !isActive);
                    slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                });

                dots.forEach((dot, dotIndex) => {
                    const isActive = dotIndex === activeIndex;
                    dot.classList.toggle('w-8', isActive);
                    dot.classList.toggle('bg-accent', isActive);
                    dot.classList.toggle('w-2.5', !isActive);
                    dot.classList.toggle('bg-white/60', !isActive);
                    dot.setAttribute('aria-current', isActive ? 'true' : 'false');
                });
            };

            const stopHero = () => {
                if (timer) window.clearInterval(timer);
                timer = null;
            };
            const startHero = () => {
                stopHero();
                if (!reducedMotion && slides.length > 1) {
                    timer = window.setInterval(() => showHeroSlide(activeIndex + 1), 6000);
                }
            };
            const selectHeroSlide = (index) => {
                showHeroSlide(index);
                startHero();
            };

            prev?.addEventListener('click', () => selectHeroSlide(activeIndex - 1));
            next?.addEventListener('click', () => selectHeroSlide(activeIndex + 1));
            dots.forEach((dot, index) => dot.addEventListener('click', () => selectHeroSlide(index)));
            heroCarousel.addEventListener('mouseenter', stopHero);
            heroCarousel.addEventListener('mouseleave', startHero);
            heroCarousel.addEventListener('focusin', stopHero);
            heroCarousel.addEventListener('focusout', startHero);
            startHero();
        }

        const newsCarousel = document.querySelector('[data-news-carousel]');

        if (newsCarousel) {
            const track = newsCarousel.querySelector('[data-news-track]');
            const slides = [...newsCarousel.querySelectorAll('[data-news-slide]')];
            const dots = [...newsCarousel.querySelectorAll('[data-news-dot]')];
            const prev = newsCarousel.querySelector('[data-news-prev]');
            const next = newsCarousel.querySelector('[data-news-next]');
            let activeIndex = 0;
            let timer = null;

            const updateNewsDots = () => {
                dots.forEach((dot, dotIndex) => {
                    const isActive = dotIndex === activeIndex;
                    dot.classList.toggle('w-8', isActive);
                    dot.classList.toggle('bg-brand', isActive);
                    dot.classList.toggle('w-2.5', !isActive);
                    dot.classList.toggle('bg-mist', !isActive);
                    dot.setAttribute('aria-current', isActive ? 'true' : 'false');
                });
            };

            const showNewsSlide = (index) => {
                if (!track || slides.length === 0) return;
                activeIndex = (index + slides.length) % slides.length;
                track.scrollTo({ left: slides[activeIndex].offsetLeft - track.offsetLeft, behavior: 'smooth' });
                updateNewsDots();
            };

            const stopNews = () => {
                if (timer) window.clearInterval(timer);
                timer = null;
            };
            const startNews = () => {
                stopNews();
                if (!reducedMotion && slides.length > 1) {
                    timer = window.setInterval(() => showNewsSlide(activeIndex + 1), 5000);
                }
            };
            const selectNewsSlide = (index) => {
                showNewsSlide(index);
                startNews();
            };

            prev?.addEventListener('click', () => selectNewsSlide(activeIndex - 1));
            next?.addEventListener('click', () => selectNewsSlide(activeIndex + 1));
            dots.forEach((dot, index) => dot.addEventListener('click', () => selectNewsSlide(index)));
            newsCarousel.addEventListener('mouseenter', stopNews);
            newsCarousel.addEventListener('mouseleave', startNews);
            newsCarousel.addEventListener('focusin', stopNews);
            newsCarousel.addEventListener('focusout', startNews);
            startNews();
        }
    });
</script>
@endsection
