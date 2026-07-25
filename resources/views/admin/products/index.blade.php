@extends('layouts.admin')
@section('title', 'Productos')
@section('subtitle', 'Control comercial del catálogo sincronizado desde el ERP.')

@section('content')
<form method="GET" class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_180px_180px_auto]">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por producto, SKU o descripción"
           class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#FFAE00] focus:outline-none">
    <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#FFAE00] focus:outline-none">
        <option value="">Todos</option>
        <option value="active" @selected(request('status') === 'active')>Activos</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Inactivos</option>
        <option value="featured" @selected(request('status') === 'featured')>Destacados</option>
        <option value="promotion" @selected(request('status') === 'promotion')>En oferta</option>
    </select>
    <select name="category_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#FFAE00] focus:outline-none">
        <option value="">Categorías</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <button class="rounded-lg bg-[#203749] px-4 py-2 text-sm font-semibold text-white">Filtrar</button>
</form>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3">Producto</th>
                <th class="px-4 py-3">Categoría</th>
                <th class="px-4 py-3 text-right">Precio</th>
                <th class="px-4 py-3">Estado web</th>
                <th class="px-4 py-3 text-right">Acción</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($products as $product)
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-semibold text-[#203749]">{{ $product->name }}</p>
                        <p class="mt-1 font-mono text-xs text-slate-500">{{ $product->sku }}</p>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $product->category->name ?? 'Sin categoría' }}</td>
                    <td class="px-4 py-3 text-right">
                        @if ($product->isOnSale)
                            <p class="font-semibold text-emerald-700">${{ number_format((float) $product->effective_price, 2) }}</p>
                            <p class="text-xs text-slate-400 line-through">${{ number_format((float) $product->price, 2) }}</p>
                        @else
                            <p class="font-semibold">${{ number_format((float) $product->price, 2) }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $product->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $product->is_active ? 'Activo' : 'Oculto' }}
                            </span>
                            @if ($product->is_featured)
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Destacado</span>
                            @endif
                            @if ($product->is_promotion)
                                <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-800">Oferta</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.products.edit', $product) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">No hay productos con esos filtros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">
    {{ $products->links() }}
</div>
@endsection
