@extends('layouts.app')
@section('title', 'Pedido recibido | Lumens')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:py-14">
    <div class="mb-7 flex flex-wrap items-center justify-center gap-2 text-xs font-bold uppercase tracking-wide text-brand/55" aria-label="Progreso completado">
        <span class="inline-flex size-7 items-center justify-center rounded-full bg-emerald-600 text-white">✓</span>
        <span>Carrito revisado</span>
        <span class="h-px w-8 bg-mist"></span>
        <span class="inline-flex size-7 items-center justify-center rounded-full bg-emerald-600 text-white">✓</span>
        <span>Datos recibidos</span>
        <span class="h-px w-8 bg-mist"></span>
        <span class="inline-flex size-7 items-center justify-center rounded-full bg-brand text-white">3</span>
        <span class="text-brand">Confirmación</span>
    </div>

    <section class="overflow-hidden rounded-3xl border border-mist bg-white shadow-sm">
        <div class="bg-brand px-6 py-9 text-center text-white sm:px-10">
            <div class="mx-auto grid size-16 place-items-center rounded-full bg-accent text-3xl font-black text-brand">✓</div>
            <p class="mt-5 text-xs font-bold uppercase tracking-[0.2em] text-accent">Pedido registrado</p>
            <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">Tu compra fue recibida</h1>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-white/75">
                No hemos realizado ningún cobro. Ventas confirmará existencias, entrega y forma de pago antes de procesar el pedido.
            </p>
            <div class="mx-auto mt-6 inline-flex items-center gap-3 rounded-full bg-white/10 px-5 py-2.5">
                <span class="text-xs text-white/60">Número de seguimiento</span>
                <strong class="font-mono text-sm text-accent">{{ $order->quote_number }}</strong>
            </div>
        </div>

        <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-[1fr_280px]">
            <div>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-extrabold text-brand">Productos solicitados</h2>
                    <span class="rounded-full bg-mist/25 px-3 py-1 text-xs font-bold text-brand">{{ $order->items->sum('qty') }} unidades</span>
                </div>
                <div class="mt-4 divide-y divide-mist/70 rounded-2xl border border-mist/70">
                    @foreach ($order->items as $item)
                        <div class="grid grid-cols-[minmax(0,1fr)_auto] gap-4 p-4 text-sm sm:grid-cols-[minmax(0,1fr)_auto_auto]">
                            <div class="min-w-0">
                                <p class="font-bold text-brand">{{ $item->name }}</p>
                                <p class="mt-0.5 truncate font-mono text-xs text-brand/55">{{ $item->sku }}</p>
                            </div>
                            <span class="text-brand/70">{{ $item->qty }} × ${{ number_format((float) $item->price, 2) }}</span>
                            <span class="col-span-2 text-right font-black text-brand sm:col-span-1">${{ number_format((float) $item->subtotal, 2) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 space-y-2 rounded-2xl bg-mist/20 px-5 py-4 text-brand">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold">Subtotal</span>
                        <span class="font-bold">${{ number_format((float) $order->subtotal, 2) }}</span>
                    </div>
                    @if ((float) $order->discount_amount > 0)
                        <div class="flex items-center justify-between text-sm text-emerald-700">
                            <span class="font-semibold">Cupón {{ $order->coupon_code }}</span>
                            <span class="font-bold">−${{ number_format((float) $order->discount_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between border-t border-mist pt-2">
                        <span class="text-sm font-semibold">Total estimado</span>
                        <span class="text-2xl font-black">${{ number_format((float) $order->total, 2) }}</span>
                    </div>
                </div>
                <p class="mt-2 text-xs leading-relaxed text-brand/55">El total queda sujeto a validación de existencias, entrega, impuestos y condiciones comerciales.</p>
                @if ($order->requires_fiscal_credit)
                    <div class="mt-4 rounded-2xl border border-accent/50 bg-accent/10 px-4 py-3 text-sm leading-relaxed text-brand">
                        <strong>Crédito fiscal solicitado.</strong> Recibimos los datos de {{ $order->fiscal_legal_name }} para preparar el DTE. Ventas los validará antes de emitirlo.
                    </div>
                @endif
            </div>

            <aside class="space-y-5">
                <div class="rounded-2xl border border-mist/70 p-5">
                    <h2 class="text-sm font-extrabold text-brand">Datos de seguimiento</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-xs text-brand/55">Cliente</dt>
                            <dd class="mt-0.5 font-semibold text-brand">{{ $order->customer_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-brand/55">Correo</dt>
                            <dd class="mt-0.5 break-all font-semibold text-brand">{{ $order->customer_email }}</dd>
                        </div>
                        @if ($order->delivery_method_label)
                            <div>
                                <dt class="text-xs text-brand/55">Entrega</dt>
                                <dd class="mt-0.5 font-semibold text-brand">{{ $order->delivery_method_label }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-xs text-brand/55">Pago</dt>
                            <dd class="mt-0.5 font-semibold text-brand">{{ $order->payment_status_label }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-accent/40 bg-accent/10 p-5 text-sm leading-relaxed text-brand">
                    <strong>¿Qué sigue?</strong>
                    <p class="mt-1">Te contactaremos a <span class="font-semibold">{{ $order->customer_email }}</span> para continuar.</p>
                </div>
            </aside>
        </div>
    </section>

    <div class="mt-7 flex flex-wrap justify-center gap-3">
        <a href="{{ route('home') }}" class="inline-flex h-11 items-center rounded-full border border-mist bg-white px-5 text-sm font-bold text-brand hover:bg-mist/25">Volver al inicio</a>
        <a href="{{ route('catalog.index') }}" class="inline-flex h-11 items-center rounded-full bg-brand px-5 text-sm font-bold text-white hover:bg-black">Seguir explorando</a>
    </div>
</div>
@endsection
