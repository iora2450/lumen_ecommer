@extends('layouts.app')
@section('title', 'Carrito | Lumens')
@section('description', 'Revisa tus productos y finaliza como compra o cotización.')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8">
    <nav class="text-sm text-[#203749]/60 mb-6">
        <a href="{{ route('home') }}" class="hover:text-[#FFAE00]">Inicio</a>
        <span class="mx-2">/</span>
        <span class="text-[#203749]">Carrito</span>
    </nav>

    <div class="grid gap-8 lg:grid-cols-[1fr_420px]">
        <section class="rounded-2xl border border-[#C8D3D7]/70 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-[#203749]">Carrito</h1>
                    <p class="mt-1 lumens-muted">Revisa cantidades antes de enviar la solicitud.</p>
                </div>
                <a href="{{ route('catalog.index') }}" class="text-sm font-semibold text-[#203749] hover:text-[#FFAE00]">Seguir viendo productos</a>
            </div>

            @if (count($items) > 0)
                <form method="POST" action="{{ route('cart.update') }}" class="mt-6">
                    @csrf
                    @method('PATCH')
                    <div class="divide-y divide-[#C8D3D7]/70">
                        @foreach ($items as $item)
                            <div class="grid gap-4 py-5 sm:grid-cols-[88px_1fr_auto]">
                                <div class="grid size-22 place-items-center rounded-xl border border-[#C8D3D7]/70 bg-white p-2">
                                    @if ($item['image_url'])
                                        <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="size-full object-contain">
                                    @else
                                        <span class="text-xs font-bold text-[#203749]/45">Lumens</span>
                                    @endif
                                </div>
                                <div>
                                    <h2 class="font-bold text-[#203749]">{{ $item['name'] }}</h2>
                                    <p class="mt-1 text-xs font-mono text-[#203749]/60">{{ $item['sku'] }}</p>
                                    <p class="mt-3 text-sm text-[#203749]/70">${{ number_format((float) $item['price'], 2) }} c/u</p>
                                    <div class="mt-3 flex items-center gap-3">
                                        <label class="text-sm font-semibold text-[#203749]/70">
                                            Cantidad
                                            <input type="number" min="0" max="10000" name="items[{{ $item['key'] }}][qty]" value="{{ $item['qty'] }}"
                                                   class="ml-2 w-24 rounded-lg border border-[#C8D3D7] px-3 py-2 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                                        </label>
                                        <button form="remove-{{ $loop->index }}" type="submit" class="text-sm font-semibold text-[#203749]/55 hover:text-black">Eliminar</button>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-[#203749]/60">Subtotal</p>
                                    <p class="text-xl font-black text-[#203749]">${{ number_format((float) $item['subtotal'], 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button class="rounded-full bg-[#203749] px-6 py-3 text-sm font-bold text-white transition hover:bg-black">Actualizar carrito</button>
                    </div>
                </form>

                @foreach ($items as $item)
                    <form id="remove-{{ $loop->index }}" method="POST" action="{{ route('cart.remove', $item['key']) }}">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach
            @else
                <div class="mt-8 rounded-xl border border-dashed border-[#C8D3D7] bg-[#C8D3D7]/10 p-10 text-center">
                    <p class="font-semibold text-[#203749]">Tu carrito está vacío.</p>
                    <a href="{{ route('catalog.index') }}" class="mt-4 inline-flex rounded-full bg-[#FFAE00] px-5 py-3 text-sm font-bold text-[#203749]">Ver catálogo</a>
                </div>
            @endif
        </section>

        <aside class="rounded-2xl border border-[#C8D3D7]/70 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#203749]">Finalizar solicitud</h2>
            <div class="mt-4 rounded-xl bg-[#C8D3D7]/20 p-4">
                <div class="flex justify-between text-sm text-[#203749]/70">
                    <span>Total estimado</span>
                    <span class="font-black text-[#203749]">${{ number_format((float) $subtotal, 2) }}</span>
                </div>
            </div>

            <form method="POST" action="{{ route('cart.checkout') }}" class="mt-6 space-y-4">
                @csrf
                <div class="grid gap-3">
                    <label class="flex cursor-pointer items-center justify-between rounded-xl border border-[#C8D3D7] px-4 py-3 text-sm font-semibold text-[#203749]">
                        Solicitar cotización
                        <input type="radio" name="request_type" value="quote" class="accent-[#FFAE00]" checked>
                    </label>
                    <label class="flex cursor-pointer items-center justify-between rounded-xl border border-[#C8D3D7] px-4 py-3 text-sm font-semibold text-[#203749]">
                        Realizar compra
                        <input type="radio" name="request_type" value="purchase" class="accent-[#FFAE00]">
                    </label>
                </div>

                <div class="grid gap-3">
                    <input name="customer_name" value="{{ old('customer_name') }}" placeholder="Nombre completo *" required
                           class="rounded-lg border border-[#C8D3D7] px-3 py-2 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                    <input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="Correo electrónico *" required
                           class="rounded-lg border border-[#C8D3D7] px-3 py-2 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                    <input name="customer_phone" value="{{ old('customer_phone') }}" placeholder="Teléfono"
                           class="rounded-lg border border-[#C8D3D7] px-3 py-2 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                    <input name="customer_company" value="{{ old('customer_company') }}" placeholder="Empresa"
                           class="rounded-lg border border-[#C8D3D7] px-3 py-2 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                    <textarea name="shipping_address" rows="2" placeholder="Dirección de entrega"
                              class="rounded-lg border border-[#C8D3D7] px-3 py-2 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none">{{ old('shipping_address') }}</textarea>
                    <textarea name="notes" rows="3" placeholder="Notas para el equipo de ventas"
                              class="rounded-lg border border-[#C8D3D7] px-3 py-2 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none">{{ old('notes') }}</textarea>
                </div>

                <button @disabled(count($items) === 0)
                        class="w-full rounded-full bg-[#FFAE00] px-5 py-3 text-sm font-black text-[#203749] transition hover:bg-[#FFAE00] disabled:cursor-not-allowed disabled:opacity-50">
                    Enviar a ventas
                </button>
            </form>
        </aside>
    </div>
</div>
@endsection
