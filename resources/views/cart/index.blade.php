@extends('layouts.app')
@section('title', 'Carrito | Lumens')
@section('description', 'Revisa tus productos y continúa como compra o cotización.')

@section('content')
@php
    $selectedType = old('request_type', 'quote');
    $selectedDelivery = old('delivery_method', 'delivery');
@endphp
<div class="mx-auto max-w-7xl px-4 py-8 sm:py-10">
    <nav class="mb-6 text-sm text-brand/60" aria-label="Migas de pan">
        <a href="{{ route('home') }}" class="hover:text-accent">Inicio</a>
        <span class="mx-2">/</span>
        <span class="text-brand">Carrito</span>
    </nav>

    <ol class="relative mb-7 grid grid-cols-3 text-center text-[10px] font-bold uppercase tracking-wide text-brand/55 before:absolute before:left-[16.66%] before:right-[16.66%] before:top-3.5 before:h-px before:bg-mist" aria-label="Progreso de la solicitud">
        <li class="relative z-10 flex flex-col items-center gap-2 text-brand">
            <span class="inline-flex size-7 items-center justify-center rounded-full bg-brand text-white">1</span>
            <span>Carrito</span>
        </li>
        <li class="relative z-10 flex flex-col items-center gap-2">
            <span class="inline-flex size-7 items-center justify-center rounded-full border border-mist bg-white">2</span>
            <span>Datos</span>
        </li>
        <li class="relative z-10 flex flex-col items-center gap-2">
            <span class="inline-flex size-7 items-center justify-center rounded-full border border-mist bg-white">3</span>
            <span>Confirmación</span>
        </li>
    </ol>

    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800" role="alert">
            <p class="font-bold">Revisa los datos antes de continuar:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_430px]">
        <section class="rounded-3xl border border-mist/70 bg-white p-5 shadow-sm sm:p-7">
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-accent">Tu selección</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-brand">Carrito de productos</h1>
                    <p class="mt-1 text-sm text-brand/65">Ajusta las cantidades; se usarán al cotizar o confirmar la compra.</p>
                </div>
                <a href="{{ route('catalog.index') }}" class="text-sm font-bold text-brand hover:text-accent">+ Agregar más productos</a>
            </div>

            @if (count($items) > 0)
                <form id="cart-update-form" method="POST" action="{{ route('cart.update') }}" class="mt-6">
                    @csrf
                    @method('PATCH')
                    <div class="divide-y divide-mist/70">
                        @foreach ($items as $item)
                            <article class="grid gap-4 py-5 sm:grid-cols-[96px_minmax(0,1fr)_auto]" data-cart-row data-price="{{ $item['price'] }}">
                                <div class="grid size-24 place-items-center rounded-2xl border border-mist/70 bg-white p-2">
                                    @if ($item['image_url'])
                                        <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="size-full object-contain"
                                             onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');">
                                        <span class="hidden text-xs font-bold text-brand/45">Lumens</span>
                                    @else
                                        <span class="text-xs font-bold text-brand/45">Lumens</span>
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <h2 class="font-bold leading-snug text-brand">{{ $item['name'] }}</h2>
                                    <p class="mt-1 truncate font-mono text-xs text-brand/60">{{ $item['sku'] }}</p>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                        <span class="rounded-full bg-mist/25 px-2.5 py-1 font-semibold text-brand">${{ number_format((float) $item['price'], 2) }} c/u</span>
                                        @if ($item['available_qty'] > 0)
                                            <span class="font-semibold text-emerald-700">{{ $item['available_qty'] }} disponibles</span>
                                        @else
                                            <span class="font-semibold text-amber-700">Disponibilidad por confirmar</span>
                                        @endif
                                    </div>

                                    <div class="mt-4 flex flex-wrap items-center gap-3">
                                        <div class="inline-flex items-center rounded-full border border-mist bg-white p-1" aria-label="Cantidad de {{ $item['name'] }}">
                                            <button type="button" class="grid size-8 place-items-center rounded-full text-lg font-bold text-brand hover:bg-mist/30" data-qty-change="-1" aria-label="Restar uno">−</button>
                                            <input type="number" min="0" max="10000" name="items[{{ $item['key'] }}][qty]" value="{{ $item['qty'] }}"
                                                   class="w-14 border-0 bg-transparent text-center text-sm font-bold text-brand focus:outline-none" data-cart-qty data-cart-key="{{ $item['key'] }}" aria-label="Cantidad">
                                            <button type="button" class="grid size-8 place-items-center rounded-full text-lg font-bold text-brand hover:bg-mist/30" data-qty-change="1" aria-label="Sumar uno">+</button>
                                        </div>
                                        <button form="remove-{{ $loop->index }}" type="submit" class="text-sm font-semibold text-brand/55 hover:text-rose-700">Eliminar</button>
                                    </div>
                                </div>

                                <div class="text-left sm:text-right">
                                    <p class="text-xs text-brand/60">Subtotal</p>
                                    <p class="text-xl font-black text-brand" data-row-subtotal>${{ number_format((float) $item['subtotal'], 2) }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-5 flex flex-col items-start justify-between gap-3 border-t border-mist/70 pt-5 sm:flex-row sm:items-center">
                        <p class="text-xs text-brand/60">Coloca 0 para retirar un producto.</p>
                        <button class="rounded-full border border-brand px-5 py-2.5 text-sm font-bold text-brand transition hover:bg-brand hover:text-white">Guardar cantidades</button>
                    </div>
                </form>

                @foreach ($items as $item)
                    <form id="remove-{{ $loop->index }}" method="POST" action="{{ route('cart.remove', $item['key']) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @else
                <div class="mt-8 rounded-2xl border border-dashed border-mist bg-mist/10 p-10 text-center">
                    <div class="mx-auto grid size-14 place-items-center rounded-full bg-white text-brand shadow-sm" aria-hidden="true">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l2.2 11.2a2 2 0 002 1.6h7.7a2 2 0 001.9-1.4L21 7H6M9 21h.01M18 21h.01"/></svg>
                    </div>
                    <p class="mt-4 font-bold text-brand">Tu carrito está vacío.</p>
                    <p class="mt-1 text-sm text-brand/60">Explora el catálogo y agrega los productos que necesitas.</p>
                    <a href="{{ route('catalog.index') }}" class="mt-5 inline-flex rounded-full bg-accent px-5 py-3 text-sm font-black text-brand">Ver catálogo</a>
                </div>
            @endif
        </section>

        <aside class="rounded-3xl border border-mist/70 bg-white p-5 shadow-sm lg:sticky lg:top-28 sm:p-7">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-accent">Paso 2</p>
                    <h2 class="mt-1 text-xl font-extrabold text-brand" id="checkout-title">Solicitar cotización</h2>
                </div>
                <div class="text-right">
                    <p class="text-xs text-brand/60">Total estimado</p>
                    <p class="text-2xl font-black text-brand" data-cart-total>${{ number_format((float) $subtotal, 2) }}</p>
                </div>
            </div>

            <form id="checkout-form" method="POST" action="{{ route('cart.checkout') }}" class="mt-6 space-y-5">
                @csrf
                @foreach ($items as $item)
                    <input type="hidden" name="items[{{ $item['key'] }}][qty]" value="{{ $item['qty'] }}" data-checkout-qty="{{ $item['key'] }}">
                @endforeach

                <fieldset>
                    <legend class="mb-2 text-sm font-bold text-brand">¿Qué deseas hacer?</legend>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <label class="cursor-pointer rounded-2xl border border-mist p-4 transition has-[:checked]:border-accent has-[:checked]:bg-accent/10 has-[:checked]:ring-2 has-[:checked]:ring-accent/20">
                            <span class="flex items-start gap-3">
                                <input type="radio" name="request_type" value="quote" class="mt-1 accent-accent" @checked($selectedType === 'quote')>
                                <span>
                                    <span class="block text-sm font-bold text-brand">Cotización</span>
                                    <span class="mt-1 block text-xs leading-relaxed text-brand/60">Recibe precio y condiciones formales.</span>
                                </span>
                            </span>
                        </label>
                        <label class="cursor-pointer rounded-2xl border border-mist p-4 transition has-[:checked]:border-accent has-[:checked]:bg-accent/10 has-[:checked]:ring-2 has-[:checked]:ring-accent/20">
                            <span class="flex items-start gap-3">
                                <input type="radio" name="request_type" value="purchase" class="mt-1 accent-accent" @checked($selectedType === 'purchase')>
                                <span>
                                    <span class="block text-sm font-bold text-brand">Comprar</span>
                                    <span class="mt-1 block text-xs leading-relaxed text-brand/60">Confirma tu intención de compra.</span>
                                </span>
                            </span>
                        </label>
                    </div>
                </fieldset>

                <div class="rounded-2xl border border-accent/40 bg-accent/10 p-4 text-sm leading-relaxed text-brand" id="checkout-explanation" aria-live="polite"></div>

                <fieldset class="grid gap-3">
                    <legend class="mb-1 text-sm font-bold text-brand">Tus datos</legend>
                    <label class="grid gap-1 text-xs font-semibold text-brand/70">
                        Nombre completo *
                        <input name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" required
                               class="rounded-xl border border-mist px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="Nombre y apellido">
                    </label>
                    <label class="grid gap-1 text-xs font-semibold text-brand/70">
                        Correo electrónico *
                        <input type="email" name="customer_email" value="{{ old('customer_email') }}" autocomplete="email" required
                               class="rounded-xl border border-mist px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="nombre@empresa.com">
                    </label>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <label class="grid gap-1 text-xs font-semibold text-brand/70">
                            <span id="phone-label">Teléfono</span>
                            <input name="customer_phone" value="{{ old('customer_phone') }}" autocomplete="tel" id="customer-phone"
                                   class="rounded-xl border border-mist px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="0000-0000">
                        </label>
                        <label class="grid gap-1 text-xs font-semibold text-brand/70">
                            Empresa <span class="font-normal">(opcional)</span>
                            <input name="customer_company" value="{{ old('customer_company') }}" autocomplete="organization"
                                   class="rounded-xl border border-mist px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="Nombre comercial">
                        </label>
                    </div>
                </fieldset>

                <fieldset id="purchase-fields" class="space-y-3 rounded-2xl border border-mist/70 bg-mist/10 p-4">
                    <legend class="px-1 text-sm font-bold text-brand">Entrega y pago</legend>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-mist bg-white px-3 py-2.5 text-xs font-bold text-brand has-[:checked]:border-accent">
                            <input type="radio" name="delivery_method" value="delivery" class="accent-accent" @checked($selectedDelivery === 'delivery')>
                            Entrega a domicilio
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-mist bg-white px-3 py-2.5 text-xs font-bold text-brand has-[:checked]:border-accent">
                            <input type="radio" name="delivery_method" value="pickup" class="accent-accent" @checked($selectedDelivery === 'pickup')>
                            Retiro en tienda
                        </label>
                    </div>
                    <label class="grid gap-1 text-xs font-semibold text-brand/70" id="shipping-address-wrap">
                        Dirección de entrega *
                        <textarea name="shipping_address" rows="2" autocomplete="street-address" id="shipping-address"
                                  class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="Dirección completa y referencias">{{ old('shipping_address') }}</textarea>
                    </label>
                    <div class="flex gap-3 rounded-xl bg-white p-3 text-xs leading-relaxed text-brand/70">
                        <svg class="mt-0.5 size-5 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M10.3 3.6L2.7 17a2 2 0 001.7 3h15.2a2 2 0 001.7-3L13.7 3.6a2 2 0 00-3.4 0z"/></svg>
                        <p><strong class="text-brand">Pago por coordinar.</strong> Todavía no se realizará ningún cobro. Ventas confirmará existencias, entrega y forma de pago.</p>
                    </div>
                </fieldset>

                <label class="grid gap-1 text-xs font-semibold text-brand/70">
                    Notas <span class="font-normal">(opcional)</span>
                    <textarea name="notes" rows="3" class="rounded-xl border border-mist px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="Detalles del proyecto, fecha requerida u otra indicación">{{ old('notes') }}</textarea>
                </label>

                <button type="submit" @disabled(count($items) === 0) id="checkout-button"
                        class="w-full rounded-full bg-accent px-5 py-3.5 text-sm font-black text-brand shadow-sm transition hover:-translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-50">
                    Enviar cotización
                </button>
                <p class="text-center text-xs leading-relaxed text-brand/55" id="checkout-footnote">Sin compromiso de compra. Te contactaremos para confirmar precios y disponibilidad.</p>
            </form>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
    const checkoutForm = document.getElementById('checkout-form');
    const typeInputs = document.querySelectorAll('input[name="request_type"]');
    const purchaseFields = document.getElementById('purchase-fields');
    const deliveryInputs = document.querySelectorAll('input[name="delivery_method"]');
    const phoneInput = document.getElementById('customer-phone');
    const phoneLabel = document.getElementById('phone-label');
    const addressWrap = document.getElementById('shipping-address-wrap');
    const addressInput = document.getElementById('shipping-address');
    const title = document.getElementById('checkout-title');
    const explanation = document.getElementById('checkout-explanation');
    const button = document.getElementById('checkout-button');
    const footnote = document.getElementById('checkout-footnote');

    function selectedType() {
        return document.querySelector('input[name="request_type"]:checked')?.value || 'quote';
    }

    function selectedDelivery() {
        return document.querySelector('input[name="delivery_method"]:checked')?.value || 'delivery';
    }

    function refreshDelivery() {
        const needsAddress = selectedType() === 'purchase' && selectedDelivery() === 'delivery';
        addressWrap?.classList.toggle('hidden', !needsAddress);
        if (addressInput) {
            addressInput.required = needsAddress;
            addressInput.disabled = selectedType() !== 'purchase';
        }
    }

    function refreshCheckoutMode() {
        const isPurchase = selectedType() === 'purchase';
        purchaseFields?.classList.toggle('hidden', !isPurchase);
        deliveryInputs.forEach((input) => input.disabled = !isPurchase);
        if (phoneInput) phoneInput.required = isPurchase;
        if (phoneLabel) phoneLabel.textContent = isPurchase ? 'Teléfono *' : 'Teléfono (opcional)';

        if (isPurchase) {
            title.textContent = 'Confirmar solicitud de compra';
            explanation.innerHTML = '<strong>Compra asistida:</strong> registra el pedido ahora y nuestro equipo confirma existencias, entrega y pago antes de procesarlo.';
            button.textContent = 'Confirmar solicitud de compra';
            footnote.textContent = 'No se realizará ningún cargo en línea. El pedido queda pendiente de confirmación por ventas.';
        } else {
            title.textContent = 'Solicitar cotización';
            explanation.innerHTML = '<strong>Cotización formal:</strong> envía tu lista y recibirás confirmación de precios, disponibilidad y condiciones comerciales.';
            button.textContent = 'Enviar cotización';
            footnote.textContent = 'Sin compromiso de compra. Te contactaremos para confirmar precios y disponibilidad.';
        }

        refreshDelivery();
    }

    function refreshTotals() {
        let total = 0;
        document.querySelectorAll('[data-cart-row]').forEach((row) => {
            const input = row.querySelector('[data-cart-qty]');
            const qty = Math.max(0, Number.parseInt(input.value || '0', 10));
            const subtotal = Number.parseFloat(row.dataset.price || '0') * qty;
            row.querySelector('[data-row-subtotal]').textContent = money.format(subtotal);
            total += subtotal;

            const checkoutQty = checkoutForm?.querySelector(`[data-checkout-qty="${input.dataset.cartKey}"]`);
            if (checkoutQty) checkoutQty.value = qty;
        });
        document.querySelectorAll('[data-cart-total]').forEach((element) => element.textContent = money.format(total));
    }

    document.querySelectorAll('[data-qty-change]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.parentElement.querySelector('[data-cart-qty]');
            const next = Math.max(0, Math.min(10000, Number.parseInt(input.value || '0', 10) + Number(button.dataset.qtyChange)));
            input.value = next;
            refreshTotals();
        });
    });
    document.querySelectorAll('[data-cart-qty]').forEach((input) => input.addEventListener('input', refreshTotals));
    typeInputs.forEach((input) => input.addEventListener('change', refreshCheckoutMode));
    deliveryInputs.forEach((input) => input.addEventListener('change', refreshDelivery));
    checkoutForm?.addEventListener('submit', () => {
        button.disabled = true;
        button.textContent = selectedType() === 'purchase' ? 'Registrando pedido…' : 'Enviando cotización…';
    });

    refreshCheckoutMode();
    refreshTotals();
});
</script>
@endsection
