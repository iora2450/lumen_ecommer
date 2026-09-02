<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') | Lumens</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-slate-50 text-slate-900 font-['Poppins',sans-serif]">
    <div class="flex min-h-screen">
        {{-- Sidebar --}}
        <aside class="w-60 shrink-0 bg-[#203749] text-white flex flex-col">
            <div class="p-5 border-b border-slate-700">
                <span class="text-xl font-bold">L<span class="text-[#FFAE00]">u</span>mens</span>
                <p class="text-xs text-slate-400 mt-1">Panel administrativo</p>
            </div>
            <nav class="flex-1 p-3 space-y-1 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.dashboard') ? 'bg-[#FFAE00] text-[#203749] font-semibold' : 'hover:bg-slate-700' }}">
                    Dashboard
                </a>
                <a href="{{ route('admin.products.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.products.*') ? 'bg-[#FFAE00] text-[#203749] font-semibold' : 'hover:bg-slate-700' }}">
                    Productos
                </a>
                <a href="{{ route('admin.categories.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.categories.*') ? 'bg-[#FFAE00] text-[#203749] font-semibold' : 'hover:bg-slate-700' }}">
                    Categorías
                </a>
                <a href="{{ route('admin.home-slides.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.home-slides.*') ? 'bg-[#FFAE00] text-[#203749] font-semibold' : 'hover:bg-slate-700' }}">
                    Carrusel inicio
                </a>
                <a href="{{ route('admin.quotes.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.quotes.*') ? 'bg-[#FFAE00] text-[#203749] font-semibold' : 'hover:bg-slate-700' }}">
                    Cotizaciones
                </a>
                <a href="{{ route('admin.theme.edit') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.theme.*') ? 'bg-[#FFAE00] text-[#203749] font-semibold' : 'hover:bg-slate-700' }}">
                    Tema y contacto
                </a>
                <a href="{{ route('admin.sync.index') }}" class="block rounded-lg px-3 py-2 {{ request()->routeIs('admin.sync.*') ? 'bg-[#FFAE00] text-[#203749] font-semibold' : 'hover:bg-slate-700' }}">
                    Sincronización
                </a>
                <a href="{{ route('home') }}" target="_blank" class="block rounded-lg px-3 py-2 hover:bg-slate-700">
                    Ver sitio
                </a>
            </nav>
            <div class="p-3 border-t border-slate-700 text-xs">
                <p class="text-slate-400">Sesión iniciada como:</p>
                <p class="font-semibold">{{ auth()->user()->name ?? 'Invitado' }}</p>
                <form method="POST" action="{{ route('admin.logout') }}" class="mt-2">
                    @csrf
                    <button type="submit" class="text-rose-300 hover:text-rose-200">Cerrar sesión</button>
                </form>
            </div>
        </aside>

        {{-- Main --}}
        <main class="flex-1 p-8">
            @if (session('success'))
                <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800">
                    {{ session('error') }}
                </div>
            @endif
            @if (isset($errors) && $errors->any())
                <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-rose-800">
                    Revisa los campos marcados. {{ $errors->first() }}
                </div>
            @endif

            <header class="mb-6">
                <h1 class="text-2xl font-bold text-[#203749]">@yield('title')</h1>
                @hasSection('subtitle')
                    <p class="text-slate-600 mt-1">@yield('subtitle')</p>
                @endif
            </header>

            @yield('content')
        </main>
    </div>
</body>
</html>
