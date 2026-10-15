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
        <div class="mt-4 space-y-2 border-t border-slate-200 pt-4">
            <div class="flex justify-between text-sm"><span>Subtotal</span><span>${{ number_format((float) $quote->subtotal, 2) }}</span></div>
            @if ((float) $quote->discount_amount > 0)
                <div class="flex justify-between text-sm font-semibold text-emerald-700">
                    <span>Cupón {{ $quote->coupon_code }}</span><span>−${{ number_format((float) $quote->discount_amount, 2) }}</span>
                </div>
            @endif
            @if ($quote->tax_amount !== null)
                <div class="flex justify-between text-sm"><span>IVA</span><span>${{ number_format((float) $quote->tax_amount, 2) }}</span></div>
            @endif
            <div class="flex justify-between text-lg font-bold"><span>Total</span><span>${{ number_format((float) $quote->total, 2) }}</span></div>
        </div>

        @if ($quote->notes)
            <div class="mt-6 p-3 rounded-lg bg-slate-50">
                <p class="text-xs font-semibold text-slate-500 uppercase mb-1">Notas del cliente</p>
                <p class="text-sm text-slate-700">{{ $quote->notes }}</p>
            </div>
        @endif
    </div>

    <div class="space-y-4">
        @php($delivery = $quote->erpDelivery)
        <div class="rounded-xl border-2 {{ $delivery?->delivered_at ? 'border-emerald-300 bg-emerald-50' : ($delivery?->last_error ? 'border-red-300 bg-red-50' : 'border-amber-300 bg-amber-50') }} p-6">
            <h3 class="font-semibold text-[#203749] mb-3">Estado del envío al ERP</h3>
            @if ($delivery?->delivered_at)
                <p class="font-bold text-emerald-700">✓ Enviado al ERP</p>
                <p class="mt-1 text-sm">Recibo {{ data_get($delivery->erp_status, 'receipt_number', $quote->quote_number) }}</p>
                <p class="text-xs text-slate-500">Confirmado: {{ $delivery->delivered_at->timezone('America/El_Salvador')->format('d/m/Y H:i:s') }}</p>
            @elseif ($delivery?->last_error)
                <p class="font-bold text-red-700">No enviado al ERP</p>
                <p class="mt-1 text-sm text-red-700">{{ $delivery->last_error }}</p>
                <p class="mt-1 text-xs text-slate-500">Intentos: {{ $delivery->attempts }}. También se reintentará automáticamente.</p>
            @else
                <p class="font-bold text-amber-700">Pendiente de envío al ERP</p>
                <p class="mt-1 text-sm text-slate-600">El ERP todavía no ha confirmado la recepción de este pedido.</p>
            @endif

            @if (! $delivery?->delivered_at)
                <form method="POST" action="{{ route('admin.quotes.erp-delivery', $quote) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full rounded-lg bg-[#203749] px-4 py-2 font-semibold text-white">Actualizar envío al ERP</button>
                </form>
            @endif

            <p class="mt-3 text-xs text-slate-500">Este envío entrega el recibo comercial. El DTE se procesa manualmente en lumensistema después de validar el pago.</p>
        </div>
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

        @if ($quote->requires_fiscal_credit)
            <div class="rounded-xl border-2 border-amber-300 bg-amber-50 p-6">
                <h3 class="font-semibold text-[#203749] mb-1">Crédito fiscal solicitado</h3>
                <p class="mb-4 text-xs text-slate-600">Datos del receptor para preparar el DTE.</p>
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-xs text-slate-500">Razón social</dt><dd class="font-semibold">{{ $quote->fiscal_legal_name }}</dd></div>
                    <div><dt class="text-xs text-slate-500">NIT</dt><dd>{{ $quote->fiscal_nit }}</dd></div>
                    <div><dt class="text-xs text-slate-500">NRC</dt><dd>{{ $quote->fiscal_nrc }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Actividad económica</dt><dd>{{ $quote->fiscal_activity_code }} — {{ $quote->fiscal_activity_description }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Departamento</dt><dd>{{ $quote->fiscal_department }} ({{ $quote->fiscal_department_code }})</dd></div>
                    <div><dt class="text-xs text-slate-500">Municipio</dt><dd>{{ $quote->fiscal_municipality }} ({{ $quote->fiscal_municipality_code }})</dd></div>
                    <div><dt class="text-xs text-slate-500">Distrito</dt><dd>{{ $quote->fiscal_district }} ({{ $quote->fiscal_district_code }})</dd></div>
                    <div><dt class="text-xs text-slate-500">Dirección fiscal</dt><dd>{{ $quote->fiscal_address }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Teléfono DTE</dt><dd>{{ $quote->fiscal_phone }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Correo DTE</dt><dd>{{ $quote->fiscal_email }}</dd></div>
                </dl>
            </div>
        @endif

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
