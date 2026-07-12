@extends('layouts.app')
@section('title', 'Catálogo de productos | Lumens')
@section('description', 'Explora nuestro catálogo completo de iluminación LED comercial e industrial.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    {{-- Breadcrumb --}}
    <nav class="text-sm text-slate-500 mb-6">
        <a href="{{ route('home') }}" class="hover:text-amber-600">Inicio</a>
        <span class="mx-2">/</span>
        <span class="text-slate-700">Catálogo</span>
    </nav>

    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
        {{-- Botón para mostrar filtros en móvil --}}
        <button id="toggle-filters" class="mb-4 flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 font-semibold text-[#203749] shadow-sm lg:hidden transition hover:border-[#FFAE00]">
            <span class="flex items-center gap-2">
                <svg class="w-5 h-5 text-[#FFAE00]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filtros y Categorías
            </span>
            <svg id="toggle-icon" class="w-5 h-5 text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        {{-- Sidebar de filtros --}}
        <aside id="filters-sidebar" class="hidden space-y-6 lg:block bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
            <form method="GET" action="{{ route('catalog.index') }}" id="filters-form">
                {{-- Búsqueda --}}
                <div>
                    <label class="text-sm font-semibold text-[#203749]">Buscar productos</label>
                    <div class="relative mt-2">
                        <input type="search" name="q" value="{{ request('q') }}"
                               placeholder="Nombre, SKU..."
                               class="w-full rounded-xl border border-slate-200 pl-10 pr-4 py-2.5 text-sm focus:border-[#FFAE00] focus:ring-1 focus:ring-[#FFAE00] focus:outline-none transition">
                        <svg class="absolute left-3 top-2.5 h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                {{-- Categorías --}}
                <div class="mt-6">
                    <h3 class="text-sm font-bold text-[#203749] uppercase tracking-wider mb-3">Categorías</h3>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('catalog.index', array_merge(request()->except('category'), ['category' => null])) }}"
                               class="flex justify-between items-center rounded-lg px-3 py-2 text-sm transition {{ !request('category') ? 'bg-[#FFAE00]/10 font-bold text-[#203749] border-l-2 border-[#FFAE00]' : 'text-slate-600 hover:bg-slate-50 hover:text-[#203749]' }}">
                                <span>Todas</span>
                                <span class="bg-slate-100 text-slate-500 text-xs px-2 py-0.5 rounded-full">{{ $categories->sum('products_count') }}</span>
                            </a>
                        </li>
                        @foreach ($categories as $cat)
                            <li>
                                <a href="{{ route('catalog.index', array_merge(request()->except('category'), ['category' => $cat->slug])) }}"
                                   class="flex justify-between items-center rounded-lg px-3 py-2 text-sm transition {{ request('category') === $cat->slug ? 'bg-[#FFAE00]/10 font-bold text-[#203749] border-l-2 border-[#FFAE00]' : 'text-slate-600 hover:bg-slate-50 hover:text-[#203749]' }}">
                                    <span>{{ $cat->name }}</span>
                                    <span class="{{ request('category') === $cat->slug ? 'bg-white text-[#203749]' : 'bg-slate-100 text-slate-500' }} text-xs px-2 py-0.5 rounded-full shadow-sm">{{ $cat->products_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Marcas --}}
                <div class="mt-6">
                    <h3 class="text-sm font-bold text-[#203749] uppercase tracking-wider mb-3">Marcas</h3>
                    <ul class="space-y-1">
                        <li>
                            <a href="{{ route('catalog.index', array_merge(request()->except('brand'), ['brand' => null])) }}"
                               class="flex justify-between items-center rounded-lg px-3 py-2 text-sm transition {{ !request('brand') ? 'bg-[#FFAE00]/10 font-bold text-[#203749] border-l-2 border-[#FFAE00]' : 'text-slate-600 hover:bg-slate-50 hover:text-[#203749]' }}">
                                <span>Todas</span>
                            </a>
                        </li>
                        @foreach ($brands as $brand)
                            <li>
                                <a href="{{ route('catalog.index', array_merge(request()->except('brand'), ['brand' => $brand->slug])) }}"
                                   class="flex justify-between items-center rounded-lg px-3 py-2 text-sm transition {{ request('brand') === $brand->slug ? 'bg-[#FFAE00]/10 font-bold text-[#203749] border-l-2 border-[#FFAE00]' : 'text-slate-600 hover:bg-slate-50 hover:text-[#203749]' }}">
                                    <span>{{ $brand->name }}</span>
                                    <span class="{{ request('brand') === $brand->slug ? 'bg-white text-[#203749]' : 'bg-slate-100 text-slate-500' }} text-xs px-2 py-0.5 rounded-full shadow-sm">{{ $brand->products_count }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Filtros adicionales --}}
                <div class="mt-6 space-y-3 border-t border-slate-100 pt-5">
                    <label class="flex items-center gap-3 text-sm text-slate-700 cursor-pointer group">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="on_sale" value="1" {{ request('on_sale') ? 'checked' : '' }}
                                   onchange="this.form.submit()" class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-slate-300 checked:border-[#FFAE00] checked:bg-[#FFAE00] transition-all">
                            <span class="absolute text-[#203749] opacity-0 peer-checked:opacity-100 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" stroke="currentColor" stroke-width="1">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </span>
                        </div>
                        <span class="group-hover:text-[#203749] transition">En promoción</span>
                    </label>
                    <label class="flex items-center gap-3 text-sm text-slate-700 cursor-pointer group">
                        <div class="relative flex items-center">
                            <input type="checkbox" name="in_stock" value="1" {{ request('in_stock') ? 'checked' : '' }}
                                   onchange="this.form.submit()" class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-slate-300 checked:border-[#FFAE00] checked:bg-[#FFAE00] transition-all">
                            <span class="absolute text-[#203749] opacity-0 peer-checked:opacity-100 top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 pointer-events-none">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" stroke="currentColor" stroke-width="1">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                            </span>
                        </div>
                        <span class="group-hover:text-[#203749] transition">Solo con stock</span>
                    </label>
                </div>

                <button type="submit" class="mt-6 w-full rounded-xl bg-[#203749] text-white py-3 text-sm font-bold shadow-md transition hover:bg-[#1a2c3a] hover:shadow-lg hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-[#FFAE00] focus:ring-offset-2">
                    Aplicar filtros
                </button>
            </form>
        </aside>

        {{-- Listado --}}
        <div>
            <div class="flex items-center justify-between mb-5">
                <p class="text-sm text-slate-600">
                    {{ $products->total() }} productos
                </p>
                <select name="sort" onchange="window.location.href = '{{ route('catalog.index') }}?' + new URLSearchParams({...Object.fromEntries(new FormData(document.getElementById('filters-form'))), sort: this.value}).toString()"
                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm">
                    <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Nombre (A-Z)</option>
                    <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Precio: menor a mayor</option>
                    <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Precio: mayor a menor</option>
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>Más recientes</option>
                </select>
            </div>

            @if ($products->count() > 0)
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($products as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @else
                <div class="rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center">
                    <p class="text-slate-500">No se encontraron productos con los filtros seleccionados.</p>
                    <a href="{{ route('catalog.index') }}" class="mt-3 inline-block text-sm font-semibold text-[#203749] hover:text-amber-600">
                        Limpiar filtros →
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggle-filters');
        const sidebar = document.getElementById('filters-sidebar');
        const toggleIcon = document.getElementById('toggle-icon');

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
                if (sidebar.classList.contains('hidden')) {
                    toggleIcon.classList.remove('rotate-180');
                } else {
                    toggleIcon.classList.add('rotate-180');
                }
            });
        }
    });
</script>
@endsection