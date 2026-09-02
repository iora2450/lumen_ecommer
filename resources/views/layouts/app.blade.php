<!DOCTYPE html>
<html lang="es">
<head>
    @php
        $siteName = App\Models\Setting::get('site_name', 'Lumens');
        $primaryColor = App\Models\Setting::get('primary_color', '#203749');
        $primaryDarkColor = App\Models\Setting::get('primary_dark_color', '#203749');
        $accentColor = App\Models\Setting::get('accent_color', '#FFAE00');
        $mistColor = '#C8D3D7';
        $cartCount = collect(session('cart.items', []))->sum(fn ($item) => (int) ($item['qty'] ?? 0));
        $footerEmail = App\Models\Setting::get('footer_email', 'ventas@lumens.local');
        $footerPhone = App\Models\Setting::get('footer_phone', '+503 2222 3333');
        $footerLocation = App\Models\Setting::get('footer_location', 'San Salvador, El Salvador');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'Catálogo de iluminación comercial e industrial Lumens.')">
    <title>@yield('title', 'Lumens | Soluciones de iluminación')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --lumens-primary: {{ $primaryColor }};
            --lumens-primary-dark: {{ $primaryDarkColor }};
            --lumens-accent: {{ $accentColor }};
            --lumens-mist: {{ $mistColor }};
        }
        .text-\[\#203749\] { color: var(--lumens-primary) !important; }
        .bg-\[\#203749\] { background-color: var(--lumens-primary) !important; }
        .border-\[\#203749\] { border-color: var(--lumens-primary) !important; }
        .text-\[\#FFAE00\] { color: var(--lumens-accent) !important; }
        .bg-\[\#FFAE00\] { background-color: var(--lumens-accent) !important; }
        .border-\[\#FFAE00\] { border-color: var(--lumens-accent) !important; }
        .text-\[\#C8D3D7\] { color: var(--lumens-mist) !important; }
        .bg-\[\#C8D3D7\] { background-color: var(--lumens-mist) !important; }
        .border-\[\#C8D3D7\] { border-color: var(--lumens-mist) !important; }
        .from-\[\#203749\] { --tw-gradient-from: var(--lumens-primary) var(--tw-gradient-from-position) !important; --tw-gradient-to: rgb(32 55 73 / 0) var(--tw-gradient-to-position) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to) !important; }
        .to-\[\#203749\] { --tw-gradient-to: var(--lumens-primary-dark) var(--tw-gradient-to-position) !important; }
        @media (hover: hover) {
            .hover\:border-\[\#FFAE00\]:hover { border-color: var(--lumens-accent) !important; }
            .hover\:text-\[\#FFAE00\]:hover { color: var(--lumens-accent) !important; }
            .hover\:bg-\[\#FFAE00\]:hover { background-color: var(--lumens-accent) !important; }
            .hover\:bg-amber-400:hover { background-color: color-mix(in srgb, var(--lumens-accent) 88%, white) !important; }
            .hover\:text-amber-600:hover { color: color-mix(in srgb, var(--lumens-accent) 75%, black) !important; }
        }
    </style>
</head>
<body class="antialiased bg-[#C8D3D7]/25 text-black">
    {{-- Top bar --}}
    <div class="bg-[#203749] text-white">
        <div class="mx-auto max-w-7xl px-4 py-2 flex items-center justify-between text-xs sm:text-sm">
            <p class="font-medium">Iluminación comercial e industrial</p>
            <div class="hidden md:flex items-center gap-5">
                <span>Atención personalizada</span>
                <span class="text-[#FFAE00]">Arma tu pedido en el carrito</span>
            </div>
        </div>
    </div>

    {{-- Header / Nav --}}
    <header class="sticky top-0 z-50 border-b border-[#C8D3D7]/70 bg-white/95 backdrop-blur">
        <div class="mx-auto max-w-7xl px-4 flex items-center gap-5 min-h-20">
            <a href="{{ route('home') }}" class="shrink-0" aria-label="Ir al inicio">
                <span class="text-2xl font-bold text-[#203749]">{{ $siteName }}</span>
            </a>

            <nav class="hidden flex-1 items-center justify-center gap-7 text-sm font-semibold text-[#203749] lg:flex">
                <a class="transition hover:text-[#FFAE00] {{ request()->routeIs('home') ? 'text-[#FFAE00]' : '' }}" href="{{ route('home') }}">Inicio</a>
                <a class="transition hover:text-[#FFAE00] {{ request()->routeIs('catalog.*') ? 'text-[#FFAE00]' : '' }}" href="{{ route('catalog.index') }}">Catálogo</a>
                <a class="transition hover:text-[#FFAE00] {{ request()->routeIs('cart.*') ? 'text-[#FFAE00]' : '' }}" href="{{ route('cart.index') }}">Carrito</a>
            </nav>

            <div class="ml-auto flex items-center gap-2">
                <form action="{{ route('catalog.index') }}" method="GET" class="hidden sm:flex">
                    <input type="search" name="q" placeholder="Buscar productos..." value="{{ request('q') }}"
                        class="h-11 rounded-full border border-[#C8D3D7] px-4 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none w-48 lg:w-64">
                </form>
                <a href="{{ route('cart.index') }}"
                    class="inline-flex h-11 items-center rounded-full bg-[#FFAE00] px-4 text-sm font-bold text-[#203749] shadow-sm transition hover:-translate-y-0.5 hover:bg-[#FFAE00]">
                    Carrito
                </a>
                <a href="{{ route('cart.index') }}"
                   class="relative grid size-11 place-items-center rounded-full border border-[#C8D3D7] text-[#203749] transition hover:border-[#FFAE00]"
                   aria-label="Ver carrito">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l2.2 11.2a2 2 0 002 1.6h7.7a2 2 0 001.9-1.4L21 7H6M9 21h.01M18 21h.01"/></svg>
                    @if ($cartCount > 0)
                        <span class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-[#FFAE00] px-1 text-[10px] font-black text-[#203749]">{{ $cartCount }}</span>
                    @endif
                </a>
                <details class="relative lg:hidden">
                    <summary class="grid size-11 cursor-pointer list-none place-items-center rounded-full border border-[#C8D3D7] text-[#203749]">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </summary>
                    <nav class="absolute right-0 top-14 grid w-56 gap-1 rounded-2xl border border-[#C8D3D7] bg-white p-3 text-sm font-semibold shadow-xl">
                        <a class="rounded-xl px-3 py-2 hover:bg-[#C8D3D7]/30" href="{{ route('home') }}">Inicio</a>
                        <a class="rounded-xl px-3 py-2 hover:bg-[#C8D3D7]/30" href="{{ route('catalog.index') }}">Catálogo</a>
                        <a class="rounded-xl px-3 py-2 hover:bg-[#C8D3D7]/30" href="{{ route('cart.index') }}">Carrito ({{ $cartCount }})</a>
                    </nav>
                </details>
            </div>
        </div>
    </header>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mx-auto max-w-7xl px-4 mt-4">
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800">
                ✅ {{ session('success') }}
            </div>
        </div>
    @endif
    @if (session('error'))
        <div class="mx-auto max-w-7xl px-4 mt-4">
            <div class="rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800">
                ⚠️ {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- Main content --}}
    <main>
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="mt-16 bg-[#203749] text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 grid gap-8 md:grid-cols-4">
            <div>
                <span class="text-xl font-bold">{{ $siteName }}</span>
                <p class="mt-3 text-sm text-[#C8D3D7]">Iluminación comercial e industrial con tecnología de punta.</p>
            </div>
            <div>
                <h4 class="font-semibold mb-3">Empresa</h4>
                <ul class="space-y-2 text-sm text-[#C8D3D7]">
                    <li><a href="{{ route('home') }}">Inicio</a></li>
                    <li><a href="{{ route('catalog.index') }}">Catálogo</a></li>
                    <li><a href="{{ route('cart.index') }}">Carrito</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-3">Contacto</h4>
                <ul class="space-y-2 text-sm text-[#C8D3D7]">
                    <li>{{ $footerEmail }}</li>
                    <li>{{ $footerPhone }}</li>
                    <li>{{ $footerLocation }}</li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-3">Horarios</h4>
                <ul class="space-y-2 text-sm text-[#C8D3D7]">
                    <li>Lun-Vie: 8:00am - 5:00pm</li>
                    <li>Sáb: 9:00am - 1:00pm</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-[#C8D3D7]/25">
            <div class="mx-auto max-w-7xl px-4 py-4 text-xs text-[#C8D3D7]">
                © {{ date('Y') }} {{ $siteName }}. Todos los derechos reservados.
            </div>
        </div>
    </footer>
</body>
</html>
