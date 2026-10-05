@extends('layouts.admin')
@section('title', 'Pedidos')
@section('subtitle', 'Gestión de compras realizadas desde el carrito')

@section('content')
{{-- Filtros --}}
<form method="GET" class="mb-6 flex gap-3 flex-wrap">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar por número, nombre o email..."
           class="flex-1 min-w-64 rounded-lg border border-slate-200 px-3 py-2 focus:border-[#FFAE00] focus:outline-none">
    <select name="status" class="rounded-lg border border-slate-200 px-3 py-2">
        <option value="">Todos los estados</option>
        @foreach (['pending' => 'Pendiente', 'reviewed' => 'Revisada', 'responded' => 'Respondida', 'closed' => 'Cerrada'] as $k => $v)
            <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $v }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-lg bg-[#203749] text-white px-4 py-2 font-semibold">Filtrar</button>
</form>

<div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">#</th>
                <th class="px-4 py-3">Tipo</th>
                <th class="px-4 py-3">Cliente</th>
                <th class="px-4 py-3">Fecha</th>
                <th class="px-4 py-3">Items</th>
                <th class="px-4 py-3 text-right">Total</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse ($quotes as $q)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-mono text-xs">{{ $q->quote_number }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full {{ $q->request_type === 'purchase' ? 'bg-[#203749] text-white' : 'bg-[#FFAE00]/20 text-[#203749]' }} px-2 py-0.5 text-xs font-bold">
                            {{ $q->request_type_label }}
                        </span>
                        @if ($q->requires_fiscal_credit)
                            <span class="mt-1 inline-block rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800">CCF / DTE</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium">{{ $q->customer_name }}</p>
                        <p class="text-xs text-slate-500">{{ $q->customer_email }}</p>
                    </td>
                    <td class="px-4 py-3 text-xs">{{ $q->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-center">{{ $q->items->count() }}</td>
                    <td class="px-4 py-3 text-right font-semibold">${{ number_format((float) $q->total, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full
                            {{ $q->status === 'pending' ? 'bg-amber-100 text-amber-800' : '' }}
                            {{ $q->status === 'reviewed' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $q->status === 'responded' ? 'bg-emerald-100 text-emerald-800' : '' }}
                            {{ $q->status === 'closed' ? 'bg-slate-100 text-slate-700' : '' }}">
                            {{ $q->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.quotes.show', $q) }}" class="text-sm font-semibold text-[#203749] hover:text-amber-600">Ver →</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">No hay pedidos.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $quotes->links() }}</div>
@endsection
