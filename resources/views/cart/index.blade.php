@extends('layouts.app')
@section('title', 'Carrito | Lumens')
@section('description', 'Revisa los productos de tu carrito y completa tu compra.')

@section('content')
@php
    $selectedActivityCode = old('fiscal_activity_code');
    $selectedActivityData = collect($economicActivities)->firstWhere('codigo', $selectedActivityCode);
    $selectedActivityDescription = $selectedActivityData['actividad_economica'] ?? null;
    $selectedActivity = $selectedActivityCode && $selectedActivityDescription
        ? $selectedActivityCode.' — '.$selectedActivityDescription
        : '';
    $selectedDepartmentCode = old('fiscal_department_code');
    $selectedMunicipalityCode = old('fiscal_municipality_code');
    $selectedDistrictCode = old('fiscal_district_code');
    $availableMunicipalities = collect($municipalities)->where('departamento', $selectedDepartmentCode);
    $availableDistricts = collect($districts)->where('departamento', $selectedDepartmentCode);
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
                    <p class="mt-1 text-sm text-brand/65">Ajusta las cantidades antes de confirmar la compra.</p>
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
                                            <span class="rounded-lg border border-rose-300 bg-rose-50 px-2.5 py-1 font-black uppercase tracking-wide text-rose-800">Sin existencias · Solicitar compra por importación</span>
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
                    <h2 class="mt-1 text-xl font-extrabold text-brand">Finalizar compra</h2>
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

                <div class="rounded-2xl border border-accent/40 bg-accent/10 p-4 text-sm leading-relaxed text-brand">
                    <strong>Compra asistida:</strong> registra tu pedido y nuestro equipo confirmará existencias, entrega y pago antes de procesarlo.
                </div>

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
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1 text-xs font-semibold text-brand/70">
                            <span>Teléfono *</span>
                            <input name="customer_phone" value="{{ old('customer_phone') }}" autocomplete="tel" required
                                   class="rounded-xl border border-mist px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="0000-0000">
                        </label>
                        <label class="grid gap-1 text-xs font-semibold text-brand/70">
                            <span>Empresa <span class="font-normal">(opcional)</span></span>
                            <input name="customer_company" value="{{ old('customer_company') }}" autocomplete="organization"
                                   class="rounded-xl border border-mist px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="Nombre comercial">
                        </label>
                    </div>
                </fieldset>

                <section class="rounded-2xl border border-mist/70 bg-mist/10 p-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="requires_fiscal_credit" value="1" id="fiscal-credit-toggle"
                               class="mt-1 size-4 shrink-0 accent-accent" aria-controls="fiscal-credit-fields"
                               @checked(old('requires_fiscal_credit'))>
                        <span>
                            <span class="block text-sm font-bold text-brand">Deseo crédito fiscal</span>
                            <span class="mt-1 block text-xs leading-relaxed text-brand/60">Completa los datos del receptor para preparar el Comprobante de Crédito Fiscal electrónico (DTE).</span>
                        </span>
                    </label>

                    <fieldset id="fiscal-credit-fields" class="mt-4 grid gap-3 border-t border-mist/70 pt-4" aria-live="polite">
                        <legend class="sr-only">Datos para crédito fiscal y DTE</legend>
                        <div class="rounded-xl border border-accent/40 bg-accent/10 p-3 text-xs leading-relaxed text-brand/75">
                            Todos los campos son necesarios para preparar el DTE. Ingresa los datos tal como aparecen en tu registro de IVA.
                        </div>
                        <label class="grid gap-1 text-xs font-semibold text-brand/70">
                            Nombre, denominación o razón social *
                            <input name="fiscal_legal_name" value="{{ old('fiscal_legal_name') }}" data-fiscal-required
                                   autocomplete="organization" class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="Razón social registrada">
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-1 text-xs font-semibold text-brand/70">
                                <span>NIT *</span>
                                <input name="fiscal_nit" value="{{ old('fiscal_nit') }}" data-fiscal-required
                                       class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="0000-000000-000-0">
                            </label>
                            <label class="grid gap-1 text-xs font-semibold text-brand/70">
                                <span>NRC *</span>
                                <input name="fiscal_nrc" value="{{ old('fiscal_nrc') }}" data-fiscal-required
                                       class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="000000-0">
                            </label>
                        </div>
                        <label class="grid gap-1 text-xs font-semibold text-brand/70">
                            Actividad económica / giro *
                            <input type="search" id="fiscal-activity-search" value="{{ $selectedActivity }}"
                                   list="economic-activity-options" data-fiscal-required autocomplete="off"
                                   class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none"
                                   placeholder="Escribe el código o una palabra de la actividad">
                            <span class="font-normal text-brand/55">Busca y selecciona una opción del catálogo CAT-019.</span>
                        </label>
                        <input type="hidden" name="fiscal_activity_code" id="fiscal-activity-code" value="{{ $selectedActivityCode }}">
                        <input type="hidden" name="fiscal_activity_description" id="fiscal-activity-description" value="{{ $selectedActivityDescription }}">
                        <datalist id="economic-activity-options">
                            @foreach ($economicActivities as $activity)
                                <option value="{{ $activity['codigo'] }} — {{ $activity['actividad_economica'] }}"
                                        data-code="{{ $activity['codigo'] }}"
                                        data-description="{{ $activity['actividad_economica'] }}"></option>
                            @endforeach
                        </datalist>
                        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                            <label class="grid gap-1 text-xs font-semibold text-brand/70">
                                Departamento *
                                <select name="fiscal_department_code" id="fiscal-department" data-fiscal-required autocomplete="address-level1"
                                        class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none">
                                    <option value="">Selecciona</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department['codigo'] }}" @selected($selectedDepartmentCode === $department['codigo'])>
                                            {{ $department['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-1 text-xs font-semibold text-brand/70">
                                Municipio *
                                <select name="fiscal_municipality_code" id="fiscal-municipality" data-fiscal-required autocomplete="address-level2"
                                        class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none">
                                    <option value="">Selecciona</option>
                                    @foreach ($availableMunicipalities as $municipality)
                                        <option value="{{ $municipality['codigo'] }}" @selected($selectedMunicipalityCode === $municipality['codigo'])>
                                            {{ $municipality['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-1 text-xs font-semibold text-brand/70">
                                Distrito *
                                <select name="fiscal_district_code" id="fiscal-district" data-fiscal-required
                                        class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none">
                                    <option value="">Selecciona</option>
                                    @foreach ($availableDistricts as $district)
                                        <option value="{{ $district['codigo'] }}" @selected($selectedDistrictCode === $district['codigo'])>
                                            {{ $district['nombre'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <p class="-mt-1 text-xs leading-relaxed text-brand/55">Los municipios y distritos se muestran según el departamento seleccionado, usando los catálogos oficiales para DTE.</p>
                        <label class="grid gap-1 text-xs font-semibold text-brand/70">
                            Dirección fiscal completa *
                            <textarea name="fiscal_address" rows="2" data-fiscal-required autocomplete="street-address"
                                      class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="Dirección registrada para el DTE">{{ old('fiscal_address') }}</textarea>
                        </label>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="grid gap-1 text-xs font-semibold text-brand/70">
                                <span>Teléfono de facturación *</span>
                                <input name="fiscal_phone" value="{{ old('fiscal_phone') }}" data-fiscal-required autocomplete="tel"
                                       class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="0000-0000">
                            </label>
                            <label class="grid gap-1 text-xs font-semibold text-brand/70">
                                <span>Correo para recibir el DTE *</span>
                                <input type="email" name="fiscal_email" value="{{ old('fiscal_email') }}" data-fiscal-required autocomplete="email"
                                       class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="facturacion@empresa.com">
                            </label>
                        </div>
                    </fieldset>
                </section>

                <fieldset class="space-y-3 rounded-2xl border border-mist/70 bg-mist/10 p-4">
                    <legend class="px-1 text-sm font-bold text-brand">Entrega y pago</legend>
                    <div class="flex items-center gap-3 rounded-xl border border-accent/40 bg-white px-3 py-3 text-xs font-bold text-brand">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-accent/15 text-accent" aria-hidden="true">✓</span>
                        <span>Entrega a domicilio</span>
                    </div>
                    <input type="hidden" name="delivery_method" value="delivery">
                    <label class="grid gap-1 text-xs font-semibold text-brand/70">
                        Dirección de entrega *
                        <textarea name="shipping_address" rows="2" autocomplete="street-address" required
                                  class="rounded-xl border border-mist bg-white px-3 py-2.5 text-sm font-normal text-brand focus:border-accent focus:outline-none" placeholder="Dirección completa y referencias">{{ old('shipping_address') }}</textarea>
                    </label>
                    <label class="grid gap-1 text-xs font-semibold text-brand/70">
                        Cupón de descuento <span class="font-normal">(opcional)</span>
                        <input name="coupon_code" value="{{ old('coupon_code') }}" maxlength="50" autocomplete="off"
                               class="rounded-xl border border-mist bg-white px-3 py-2.5 font-mono text-sm font-bold uppercase text-brand focus:border-accent focus:outline-none"
                               placeholder="Ejemplo: VERANO20">
                        <span class="font-normal text-brand/55">Si cumple las condiciones, el descuento aparecerá en la confirmación del pedido.</span>
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
                    Confirmar compra
                </button>
                <p class="text-center text-xs leading-relaxed text-brand/55">No se realizará ningún cargo en línea. El pedido queda pendiente de confirmación por ventas.</p>

                <section class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4" aria-labelledby="purchase-help-title">
                    <div class="flex items-start gap-3">
                        <div class="grid size-10 shrink-0 place-items-center rounded-full bg-emerald-600 text-white" aria-hidden="true">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12a9 9 0 01-13.2 8L3 21l1.2-4.1A9 9 0 1121 12z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 id="purchase-help-title" class="text-sm font-extrabold text-brand">¿Necesitas ayuda con tu compra?</h3>
                            <p class="mt-1 text-xs leading-relaxed text-brand/65">Nuestro equipo está disponible para acompañarte y resolver tus dudas antes de confirmar el pedido.</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                        <a href="https://wa.me/50360331749?text=Hola%20Lumens%2C%20necesito%20ayuda%20con%20mi%20compra%20en%20l%C3%ADnea."
                           target="_blank" rel="noopener noreferrer"
                           class="inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-emerald-600 px-4 py-2.5 text-center text-xs font-black text-white transition hover:bg-emerald-700"
                           aria-label="Chatear con Lumens por WhatsApp al +503 6033 1749">
                            Chatea con nosotros
                        </a>
                        <a href="mailto:marketing@lumens.com.sv?subject=Ayuda%20con%20mi%20compra%20en%20l%C3%ADnea"
                           class="inline-flex min-h-11 items-center justify-center rounded-full border border-brand/25 bg-white px-4 py-2.5 text-center text-xs font-bold text-brand transition hover:border-brand hover:bg-brand hover:text-white">
                            Escríbenos por correo
                        </a>
                    </div>

                    <div class="mt-3 space-y-1 text-center text-xs text-brand/70">
                        <p><strong>WhatsApp:</strong> <a href="https://wa.me/50360331749" target="_blank" rel="noopener noreferrer" class="font-bold underline decoration-emerald-500 underline-offset-2">+503 6033 1749</a></p>
                        <p><strong>Correo:</strong> <a href="mailto:marketing@lumens.com.sv" class="font-bold underline decoration-accent underline-offset-2">marketing@lumens.com.sv</a></p>
                    </div>
                </section>
            </form>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
    const checkoutForm = document.getElementById('checkout-form');
    const button = document.getElementById('checkout-button');
    const fiscalCreditToggle = document.getElementById('fiscal-credit-toggle');
    const fiscalCreditFields = document.getElementById('fiscal-credit-fields');
    const fiscalRequiredInputs = fiscalCreditFields?.querySelectorAll('[data-fiscal-required]') || [];
    const activitySearch = document.getElementById('fiscal-activity-search');
    const activityCode = document.getElementById('fiscal-activity-code');
    const activityDescription = document.getElementById('fiscal-activity-description');
    const activityOptions = Array.from(document.querySelectorAll('#economic-activity-options option'));
    const fiscalDepartment = document.getElementById('fiscal-department');
    const fiscalMunicipality = document.getElementById('fiscal-municipality');
    const fiscalDistrict = document.getElementById('fiscal-district');
    const municipalities = {{ Illuminate\Support\Js::from($municipalities) }};
    const districts = {{ Illuminate\Support\Js::from($districts) }};

    function refreshFiscalCredit() {
        const isEnabled = fiscalCreditToggle?.checked === true;
        fiscalCreditFields?.classList.toggle('hidden', !isEnabled);
        fiscalCreditToggle?.setAttribute('aria-expanded', isEnabled ? 'true' : 'false');
        fiscalRequiredInputs.forEach((input) => {
            input.disabled = !isEnabled;
            input.required = isEnabled;
        });
    }

    function syncEconomicActivity() {
        if (!activitySearch || !activityCode || !activityDescription) return false;

        const value = activitySearch.value.trim();
        const selectedOption = activityOptions.find((option) =>
            option.value === value || option.dataset.code === value
        );

        activityCode.value = selectedOption?.dataset.code || '';
        activityDescription.value = selectedOption?.dataset.description || '';

        if (selectedOption && activitySearch.value !== selectedOption.value) {
            activitySearch.value = selectedOption.value;
        }

        activitySearch.setCustomValidity(
            value !== '' && !selectedOption ? 'Selecciona una actividad económica válida del catálogo.' : ''
        );

        return Boolean(selectedOption);
    }

    function fillGeographicSelect(select, items, selectedCode) {
        if (!select) return;

        select.replaceChildren(new Option('Selecciona', ''));
        items.forEach((item) => select.add(new Option(item.nombre, item.codigo)));
        select.value = items.some((item) => item.codigo === selectedCode) ? selectedCode : '';
    }

    function refreshGeographicSelections(preserveSelection = true) {
        const departmentCode = fiscalDepartment?.value || '';
        const municipalityCode = preserveSelection ? fiscalMunicipality?.value || '' : '';
        const districtCode = preserveSelection ? fiscalDistrict?.value || '' : '';

        fillGeographicSelect(
            fiscalMunicipality,
            municipalities.filter((item) => item.departamento === departmentCode),
            municipalityCode,
        );
        fillGeographicSelect(
            fiscalDistrict,
            districts.filter((item) => item.departamento === departmentCode),
            districtCode,
        );
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
    fiscalCreditToggle?.addEventListener('change', refreshFiscalCredit);
    fiscalDepartment?.addEventListener('change', () => refreshGeographicSelections(false));
    activitySearch?.addEventListener('input', syncEconomicActivity);
    activitySearch?.addEventListener('change', syncEconomicActivity);
    checkoutForm?.addEventListener('submit', (event) => {
        if (fiscalCreditToggle?.checked && !syncEconomicActivity()) {
            event.preventDefault();
            activitySearch?.reportValidity();
            return;
        }

        button.disabled = true;
        button.textContent = 'Registrando compra…';
    });

    refreshGeographicSelections();
    refreshFiscalCredit();
    refreshTotals();
});
</script>
@endsection
