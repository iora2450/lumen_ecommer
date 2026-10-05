@extends('layouts.admin')
@section('title', $coupon->exists ? 'Editar cupón' : 'Nuevo cupón')
@section('subtitle', 'Define el descuento y las condiciones necesarias para utilizarlo.')

@section('content')
<form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="grid gap-6 lg:grid-cols-[1fr_340px]">
    @csrf
    @if ($coupon->exists)
        @method('PATCH')
    @endif

    <div class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Cupón</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Código que escribirá el cliente *
                    <input name="code" value="{{ old('code', $coupon->code) }}" required maxlength="50" placeholder="VERANO20"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-mono font-bold uppercase focus:border-[#FFAE00] focus:outline-none">
                    <span class="text-xs font-normal text-slate-500">Letras, números, guiones y guiones bajos.</span>
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Nombre interno *
                    <input name="name" value="{{ old('name', $coupon->name) }}" required maxlength="255" placeholder="Promoción de verano"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Tipo de descuento *
                    <select name="discount_type" required class="rounded-lg border border-slate-300 bg-white px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                        <option value="percentage" @selected(old('discount_type', $coupon->discount_type) === 'percentage')>Porcentaje</option>
                        <option value="fixed" @selected(old('discount_type', $coupon->discount_type) === 'fixed')>Monto fijo</option>
                    </select>
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Valor del descuento *
                    <input type="number" name="discount_value" value="{{ old('discount_value', $coupon->discount_value) }}" required min="0.01" step="0.01" placeholder="10.00"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                    <span class="text-xs font-normal text-slate-500">Ejemplo: 10 para 10 % o $10 según el tipo.</span>
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-1 text-sm font-bold uppercase tracking-wide text-slate-500">Condiciones</h2>
            <p class="mb-4 text-xs text-slate-500">Los campos vacíos no impondrán esa condición.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Compra mínima
                    <input type="number" name="minimum_subtotal" value="{{ old('minimum_subtotal', $coupon->minimum_subtotal) }}" min="0" step="0.01" placeholder="100.00"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Límite total de usos
                    <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" min="1" step="1" placeholder="Sin límite"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Vigente desde
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d\TH:i')) }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
                <label class="grid gap-1 text-sm font-semibold text-slate-700">
                    Vigente hasta
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $coupon->ends_at?->format('Y-m-d\TH:i')) }}"
                           class="rounded-lg border border-slate-300 px-3 py-2 font-normal focus:border-[#FFAE00] focus:outline-none">
                </label>
            </div>
        </section>
    </div>

    <aside class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-500">Disponibilidad</h2>
            <label class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-3 text-sm font-semibold text-slate-700">
                Cupón activo
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active ?? true)) class="size-4 accent-[#FFAE00]">
            </label>
            @if ($coupon->exists)
                <dl class="mt-4 space-y-2 text-sm">
                    <div><dt class="text-xs text-slate-500">Estado actual</dt><dd class="font-semibold">{{ $coupon->status_label }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Veces utilizado</dt><dd class="font-semibold">{{ $coupon->times_used }}</dd></div>
                </dl>
            @endif
        </section>

        <div class="flex gap-3">
            <button class="rounded-lg bg-[#203749] px-5 py-3 text-sm font-bold text-white">Guardar</button>
            <a href="{{ route('admin.coupons.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700">Volver</a>
        </div>
    </aside>
</form>

@if ($coupon->exists)
    <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="mt-5" onsubmit="return confirm('¿Eliminar este cupón? Los pedidos conservarán el código y descuento aplicado.')">
        @csrf
        @method('DELETE')
        <button class="text-sm font-semibold text-rose-600 hover:text-rose-700">Eliminar cupón</button>
    </form>
@endif
@endsection
