@extends('layouts.admin')
@section('title', 'Dashboard')
@section('subtitle', 'Vista general del e-commerce')

@section('content')
{{-- Stats --}}
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-xs text-slate-500 uppercase tracking-wide">Productos activos</p>
        <p class="mt-2 text-3xl font-bold text-[#203749]">{{ $stats['active_products'] }}</p>
        <p class="text-xs text-slate-500 mt-1">de {{ $stats['total_products'] }} totales</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-xs text-slate-500 uppercase tracking-wide">Pedidos</p>
        <p class="mt-2 text-3xl font-bold text-[#203749]">{{ $stats['total_quotes'] }}</p>
        <p class="text-xs text-amber-600 mt-1">{{ $stats['pending_quotes'] }} pendientes</p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-xs text-slate-500 uppercase tracking-wide">Stock bajo</p>
        <p class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['low_stock_products'] }}</p>
        <p class="text-xs text-rose-600 mt-1">{{ $stats['out_of_stock'] }} sin stock</p>
    </div>
</div>

<div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
    <a href="{{ route('admin.products.index', ['status' => 'promotion']) }}" class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-[#FFAE00] hover:shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Productos en oferta</p>
        <p class="mt-2 text-2xl font-bold text-[#203749]">{{ $stats['promotion_products'] }}</p>
    </a>
    <a href="{{ route('admin.categories.index', ['status' => 'promotion']) }}" class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-[#FFAE00] hover:shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Categorías en oferta</p>
        <p class="mt-2 text-2xl font-bold text-[#203749]">{{ $stats['promotion_categories'] }}</p>
    </a>
    <a href="{{ route('admin.home-slides.index') }}" class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-[#FFAE00] hover:shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Portada</p>
        <p class="mt-2 text-sm font-semibold text-[#203749]">Banners promocionales</p>
        <p class="mt-1 text-xs text-slate-500">Imágenes, mensajes y enlaces</p>
    </a>
    <a href="{{ route('admin.theme.edit') }}" class="rounded-xl border border-slate-200 bg-white p-5 transition hover:border-[#FFAE00] hover:shadow-sm">
        <p class="text-xs uppercase tracking-wide text-slate-500">Tema visual</p>
        <p class="mt-2 text-sm font-semibold text-[#203749]">Colores, portada y contacto</p>
    </a>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    {{-- Pedidos recientes --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="font-semibold text-[#203749] mb-4">Pedidos recientes</h3>
        @if ($recentQuotes->count() > 0)
            <div class="space-y-3">
                @foreach ($recentQuotes as $q)
                    <a href="{{ route('admin.quotes.show', $q) }}" class="flex items-center justify-between p-3 rounded-lg border border-slate-200 hover:bg-slate-50">
                        <div>
                            <p class="font-mono text-xs text-slate-500">{{ $q->quote_number }}</p>
                            <p class="font-medium text-slate-800">{{ $q->customer_name }}</p>
                            <p class="text-xs text-slate-500">{{ $q->customer_email }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-[#203749]">${{ number_format((float) $q->total, 2) }}</p>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $q->status === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                {{ $q->status }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
            <a href="{{ route('admin.quotes.index') }}" class="mt-4 block text-center text-sm font-semibold text-[#203749] hover:text-amber-600">
                Ver todas
            </a>
        @else
            <p class="text-sm text-slate-500">No hay pedidos aún.</p>
        @endif
    </div>

    {{-- Estado de sincronización --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="font-semibold text-[#203749] mb-4">Sincronización con Lumen</h3>
        @if ($lastSync)
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Último sync:</span>
                    <span class="font-medium">{{ $lastSync->created_at->diffForHumans() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Estado:</span>
                    <span class="font-medium">
                        @if ($lastSync->status === 'success') Exitoso
                        @elseif ($lastSync->status === 'failed') Falló
                        @else En progreso
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Productos:</span>
                    <span class="font-medium">{{ $lastSync->products_synced }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Categorías:</span>
                    <span class="font-medium">{{ $lastSync->categories_synced }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Marcas:</span>
                    <span class="font-medium">{{ $lastSync->brands_synced }}</span>
                </div>
            </div>
        @else
            <p class="text-sm text-slate-500">No se ha ejecutado sincronización aún.</p>
        @endif
        <a href="{{ route('admin.sync.index') }}" class="mt-4 block text-center text-sm font-semibold text-[#203749] hover:text-amber-600">
            Ir a sync
        </a>
    </div>
</div>
@endsection
