@extends('layouts.admin')
@section('title', $quote->request_type_label . ' ' . $quote->quote_number)
@section('subtitle', $quote->customer_name . ' — ' . $quote->created_at->format('d/m/Y H:i'))

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="font-semibold text-[#203749] mb-4">Productos solicitados</h3>
        <div class="space-y-2">
            @foreach ($quote->items as $item)
                <div class="flex items-center justify-between p-3 rounded-lg border border-slate-200">
                    <div>
                        <p class="font-medium">{{ $item->name }}</p>
                        <p class="text-xs text-slate-500 font-mono">{{ $item->sku }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm">{{ $item->qty }} × ${{ number_format((float) $item->price, 2) }}</p>
                        <p class="font-bold">${{ number_format((float) $item->subtotal, 2) }}</p>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4 pt-4 border-t border-slate-200 flex justify-between text-lg font-bold">
            <span>Total</span>
            <span>${{ number_format((float) $quote->subtotal, 2) }}</span>
        </div>

        @if ($quote->notes)
            <div class="mt-6 p-3 rounded-lg bg-slate-50">
                <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Notas del cliente</p>
                <p class="text-sm text-slate-700">{{ $quote->notes }}</p>
            </div>
        @endif
    </div>

    <div class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-[#203749] mb-4">Cliente</h3>
            <dl class="text-sm space-y-2">
                <div><dt class="text-xs text-slate-500">Tipo de solicitud</dt><dd>{{ $quote->request_type_label }}</dd></div>
                <div><dt class="text-xs text-slate-500">Nombre</dt><dd>{{ $quote->customer_name }}</dd></div>
                <div><dt class="text-xs text-slate-500">Email</dt><dd>{{ $quote->customer_email }}</dd></div>
                @if ($quote->customer_phone)<div><dt class="text-xs text-slate-500">Teléfono</dt><dd>{{ $quote->customer_phone }}</dd></div>@endif
                @if ($quote->customer_company)<div><dt class="text-xs text-slate-500">Empresa</dt><dd>{{ $quote->customer_company }}</dd></div>@endif
                @if ($quote->shipping_address)<div><dt class="text-xs text-slate-500">Dirección</dt><dd>{{ $quote->shipping_address }}</dd></div>@endif
                @if ($quote->delivery_method_label)<div><dt class="text-xs text-slate-500">Entrega</dt><dd>{{ $quote->delivery_method_label }}</dd></div>@endif
                @if ($quote->payment_status_label)<div><dt class="text-xs text-slate-500">Pago</dt><dd>{{ $quote->payment_status_label }}</dd></div>@endif
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-6">
            <h3 class="font-semibold text-[#203749] mb-4">Estado</h3>
            <form method="POST" action="{{ route('admin.quotes.status', $quote) }}">
                @csrf
                @method('PATCH')
                <select name="status" class="w-full rounded-lg border border-slate-200 px-3 py-2 mb-3">
                    @foreach (['pending' => 'Pendiente', 'reviewed' => 'Revisada', 'responded' => 'Respondida', 'closed' => 'Cerrada'] as $k => $v)
                        <option value="{{ $k }}" {{ $quote->status === $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>
                <button type="submit" class="w-full rounded-lg bg-[#203749] text-white py-2 font-semibold">Actualizar estado</button>
            </form>
        </div>
    </div>
</div>
@endsection
