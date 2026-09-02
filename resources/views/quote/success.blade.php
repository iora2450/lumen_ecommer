@extends('layouts.app')
@section('title', 'Cotización recibida | Lumens')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12">
    <div class="rounded-2xl border border-[#C8D3D7] bg-white p-8 text-center shadow-sm">
        <div class="mx-auto size-16 grid place-items-center rounded-full bg-[#FFAE00] text-[#203749] text-3xl">✓</div>
        <h1 class="mt-4 text-2xl font-bold text-[#203749]">¡{{ $quote->request_type_label }} recibida!</h1>
        <p class="mt-2 text-[#203749]/80">
            Tu número de solicitud es:
            <span class="font-mono font-bold">{{ $quote->quote_number }}</span>
        </p>
        <p class="mt-2 text-sm text-[#203749]/65">
            Nos pondremos en contacto contigo a <strong>{{ $quote->customer_email }}</strong> en menos de 24 horas.
        </p>
    </div>

    <div class="mt-8 rounded-2xl border border-[#C8D3D7]/70 bg-white p-8">
        <h2 class="text-lg font-semibold text-[#203749]">Resumen</h2>
        <div class="mt-4 grid gap-3 text-sm">
            <div class="grid grid-cols-3">
                <span class="text-[#203749]/60">Cliente:</span>
                <span class="col-span-2 font-medium">{{ $quote->customer_name }}</span>
            </div>
            @if ($quote->customer_company)
                <div class="grid grid-cols-3">
                    <span class="text-[#203749]/60">Empresa:</span>
                    <span class="col-span-2 font-medium">{{ $quote->customer_company }}</span>
                </div>
            @endif
            <div class="grid grid-cols-3">
                <span class="text-[#203749]/60">Estado:</span>
                <span class="col-span-2"><span class="rounded-full bg-[#FFAE00]/20 text-[#203749] px-2 py-0.5 text-xs font-semibold">Pendiente</span></span>
            </div>
            <div class="grid grid-cols-3">
                <span class="text-[#203749]/60">Tipo:</span>
                <span class="col-span-2 font-medium">{{ $quote->request_type_label }}</span>
            </div>
        </div>

        <div class="mt-6">
            <h3 class="text-sm font-semibold text-[#203749] mb-3">Productos cotizados</h3>
            <div class="rounded-lg border border-[#C8D3D7]/70 divide-y divide-[#C8D3D7]/70">
                @foreach ($quote->items as $item)
                    <div class="grid grid-cols-[1fr_auto_auto] gap-3 p-3 text-sm">
                        <div>
                            <p class="font-medium text-[#203749]">{{ $item->name }}</p>
                            <p class="text-xs text-[#203749]/60 font-mono">{{ $item->sku }}</p>
                        </div>
                        <span class="text-[#203749]/70">{{ $item->qty }} × ${{ number_format((float) $item->price, 2) }}</span>
                        <span class="font-semibold text-[#203749]">${{ number_format((float) $item->subtotal, 2) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex justify-between text-lg font-bold text-[#203749]">
                <span>Total estimado:</span>
                <span>${{ number_format((float) $quote->subtotal, 2) }}</span>
            </div>
        </div>

        <div class="mt-6 flex gap-3 justify-center">
            <a href="{{ route('home') }}" class="inline-flex h-10 items-center rounded-full border border-[#C8D3D7] px-5 text-sm font-semibold text-[#203749] hover:bg-[#C8D3D7]/25">
                Volver al inicio
            </a>
            <a href="{{ route('catalog.index') }}" class="inline-flex h-10 items-center rounded-full bg-[#203749] px-5 text-sm font-semibold text-white hover:bg-black">
                Seguir explorando
            </a>
        </div>
    </div>
</div>
@endsection
