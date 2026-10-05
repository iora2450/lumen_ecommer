@extends('layouts.admin')
@section('title', 'Cupones de descuento')
@section('subtitle', 'Crea códigos promocionales con vigencia, compra mínima y límite de usos.')

@section('content')
<div class="mb-6 flex justify-end">
    <a href="{{ route('admin.coupons.create') }}" class="rounded-lg bg-[#203749] px-4 py-2 text-sm font-bold text-white">
        Nuevo cupón
    </a>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">Código</th>
                <th class="px-4 py-3">Descuento</th>
                <th class="px-4 py-3">Condiciones</th>
                <th class="px-4 py-3">Usos</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse ($coupons as $coupon)
                @php
                    $statusClasses = match ($coupon->status_label) {
                        'Activo' => 'bg-emerald-100 text-emerald-800',
                        'Programado' => 'bg-blue-100 text-blue-800',
                        'Vencido', 'Agotado' => 'bg-amber-100 text-amber-800',
                        default => 'bg-slate-100 text-slate-700',
                    };
                @endphp
                <tr class="align-top hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <p class="font-mono font-black text-[#203749]">{{ $coupon->code }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $coupon->name }}</p>
                    </td>
                    <td class="px-4 py-3 font-bold text-[#203749]">{{ $coupon->discount_label }}</td>
                    <td class="px-4 py-3 text-xs text-slate-600">
                        <div class="space-y-1">
                            <p>Compra mínima: {{ $coupon->minimum_subtotal !== null ? '$'.number_format((float) $coupon->minimum_subtotal, 2) : 'Sin mínimo' }}</p>
                            <p>Desde: {{ $coupon->starts_at?->format('d/m/Y H:i') ?? 'Inmediato' }}</p>
                            <p>Hasta: {{ $coupon->ends_at?->format('d/m/Y H:i') ?? 'Sin vencimiento' }}</p>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        {{ $coupon->times_used }} / {{ $coupon->usage_limit ?? '∞' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusClasses }}">{{ $coupon->status_label }}</span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="font-semibold text-[#203749] hover:text-[#FFAE00]">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                        Aún no hay cupones. Crea el primero para iniciar una promoción.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $coupons->links() }}</div>
@endsection
