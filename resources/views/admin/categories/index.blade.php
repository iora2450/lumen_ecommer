@extends('layouts.admin')
@section('title', 'Categorías')
@section('subtitle', 'Elige qué familias y productos pueden ver los clientes en la tienda.')

@section('content')
<form method="GET" class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-[1fr_180px_auto]">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar categoría"
           class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#FFAE00] focus:outline-none">
    <select name="status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-[#FFAE00] focus:outline-none">
        <option value="">Todas</option>
        <option value="active" @selected(request('status') === 'active')>Visibles en web</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Ocultas de web</option>
        <option value="featured" @selected(request('status') === 'featured')>Destacadas</option>
        <option value="promotion" @selected(request('status') === 'promotion')>En oferta</option>
    </select>
    <button class="rounded-lg bg-[#203749] px-4 py-2 text-sm font-semibold text-white">Filtrar</button>
</form>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-4 py-3">Categoría</th>
                <th class="px-4 py-3 text-right">Productos</th>
                <th class="px-4 py-3 text-right">Orden</th>
                <th class="px-4 py-3">Estado web</th>
                <th class="px-4 py-3 text-right">Acción</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($categories as $category)
                <tr>
                    <td class="px-4 py-3">
                        <p class="font-semibold text-[#203749]">{{ $category->name }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $category->slug }}</p>
                    </td>
                    <td class="px-4 py-3 text-right font-semibold">{{ $category->products_count }}</td>
                    <td class="px-4 py-3 text-right">{{ $category->sort_order }}</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-1">
                            <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $category->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $category->is_active ? 'Visible' : 'Oculta' }}
                            </span>
                            @if ($category->is_featured)
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">Destacada</span>
                            @endif
                            @if ($category->is_promotion)
                                <span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-800">Oferta</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <form method="POST" action="{{ route('admin.categories.visibility', $category) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $category->is_active ? 0 : 1 }}">
                                <button class="rounded-lg border px-3 py-2 text-xs font-semibold {{ $category->is_active ? 'border-slate-300 text-slate-700 hover:bg-slate-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                                    {{ $category->is_active ? 'Ocultar de web' : 'Mostrar en web' }}
                                </button>
                            </form>
                            <a href="{{ route('admin.categories.edit', $category) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Editar</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">No hay categorías con esos filtros.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">
    {{ $categories->links() }}
</div>
@endsection
