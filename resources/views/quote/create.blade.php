@extends('layouts.app')
@section('title', 'Solicitar cotización | Lumens')
@section('description', 'Solicita una cotización personalizada para tus proyectos de iluminación.')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8">
    <nav class="text-sm text-[#203749]/60 mb-6">
        <a href="{{ route('home') }}" class="hover:text-[#FFAE00]">Inicio</a>
        <span class="mx-2">/</span>
        <span class="text-[#203749]">Cotizar</span>
    </nav>

    <div class="rounded-2xl border border-[#C8D3D7]/70 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-[#203749]">Solicitar cotización</h1>
        <p class="mt-2 lumens-muted">Completa el formulario y te enviaremos una propuesta personalizada en menos de 24 horas.</p>

        @if ($errors->any())
            <div class="mt-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-rose-800">
                <p class="font-semibold">Por favor corrige los siguientes errores:</p>
                <ul class="mt-2 list-disc pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('quote.store') }}" class="mt-8 space-y-6">
            @csrf
            <input type="hidden" name="request_type" value="quote">

            {{-- Datos del cliente --}}
            <fieldset>
                <legend class="text-sm font-semibold text-[#203749] mb-3">Datos de contacto</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm text-[#203749]/70 mb-1">Nombre completo *</label>
                        <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                               class="w-full rounded-lg border border-[#C8D3D7] px-3 py-2 text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm text-[#203749]/70 mb-1">Email *</label>
                        <input type="email" name="customer_email" value="{{ old('customer_email') }}" required
                               class="w-full rounded-lg border border-[#C8D3D7] px-3 py-2 text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm text-[#203749]/70 mb-1">Teléfono</label>
                        <input type="tel" name="customer_phone" value="{{ old('customer_phone') }}"
                               class="w-full rounded-lg border border-[#C8D3D7] px-3 py-2 text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm text-[#203749]/70 mb-1">Empresa</label>
                        <input type="text" name="customer_company" value="{{ old('customer_company') }}"
                               class="w-full rounded-lg border border-[#C8D3D7] px-3 py-2 text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm text-[#203749]/70 mb-1">Dirección de envío</label>
                        <textarea name="shipping_address" rows="2"
                                  class="w-full rounded-lg border border-[#C8D3D7] px-3 py-2 text-[#203749] focus:border-[#FFAE00] focus:outline-none">{{ old('shipping_address') }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm text-[#203749]/70 mb-1">Notas del proyecto</label>
                        <textarea name="notes" rows="3"
                                  placeholder="Cuéntanos sobre tu proyecto, plazos, cantidades estimadas..."
                                  class="w-full rounded-lg border border-[#C8D3D7] px-3 py-2 text-[#203749] focus:border-[#FFAE00] focus:outline-none">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </fieldset>

            {{-- Items --}}
            <fieldset>
                <legend class="text-sm font-semibold text-[#203749] mb-3">Productos a cotizar</legend>
                <div id="quote-items" class="space-y-3">
                    <div class="quote-item grid gap-3 sm:grid-cols-[1fr_120px_40px] items-start p-3 rounded-lg border border-[#C8D3D7]/70 bg-[#C8D3D7]/10">
                        <div>
                            <label class="block text-xs text-[#203749]/60 mb-1">Producto / SKU</label>
                            <input type="text" name="items[0][sku]" placeholder="SKU del producto o nombre"
                                   class="w-full rounded-lg border border-[#C8D3D7] px-3 py-2 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                            <input type="hidden" name="items[0][product_id]" value="">
                        </div>
                        <div>
                            <label class="block text-xs text-[#203749]/60 mb-1">Cantidad</label>
                            <input type="number" name="items[0][qty]" min="1" value="1"
                                   class="w-full rounded-lg border border-[#C8D3D7] px-3 py-2 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                        </div>
                        <button type="button" onclick="removeItem(this)" class="mt-6 text-[#203749]/45 hover:text-black">✕</button>
                    </div>
                </div>
                <button type="button" onclick="addItem()"
                        class="mt-3 text-sm font-semibold text-[#203749] hover:text-[#FFAE00]">
                    + Agregar otro producto
                </button>
            </fieldset>

            <div class="rounded-lg bg-[#FFAE00]/15 border border-[#FFAE00]/45 p-4 text-sm text-[#203749]">
                💡 <strong>Tip:</strong> Si ya conoces los SKUs, agrégalos arriba. Si no, déjanos una nota
                y te haremos una propuesta personalizada.
            </div>

            <button type="submit"
                    class="w-full rounded-full bg-[#FFAE00] py-3 text-sm font-bold text-[#203749] shadow-sm transition hover:bg-[#FFAE00]">
                Enviar cotización
            </button>
        </form>
    </div>
</div>

<script>
let itemIndex = 1;
function addItem() {
    const container = document.getElementById('quote-items');
    const html = `
        <div class="quote-item grid gap-3 sm:grid-cols-[1fr_120px_40px] items-start p-3 rounded-lg border border-[#C8D3D7]/70 bg-[#C8D3D7]/10">
            <div>
                <label class="block text-xs text-[#203749]/60 mb-1">Producto / SKU</label>
                <input type="text" name="items[${itemIndex}][sku]" placeholder="SKU del producto o nombre"
                       class="w-full rounded-lg border border-[#C8D3D7] px-3 py-2 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none">
                <input type="hidden" name="items[${itemIndex}][product_id]" value="">
            </div>
            <div>
                <label class="block text-xs text-[#203749]/60 mb-1">Cantidad</label>
                <input type="number" name="items[${itemIndex}][qty]" min="1" value="1"
                       class="w-full rounded-lg border border-[#C8D3D7] px-3 py-2 text-sm text-[#203749] focus:border-[#FFAE00] focus:outline-none">
            </div>
            <button type="button" onclick="removeItem(this)" class="mt-6 text-[#203749]/45 hover:text-black">✕</button>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
    itemIndex++;
}
function removeItem(btn) {
    const items = document.querySelectorAll('.quote-item');
    if (items.length > 1) {
        btn.closest('.quote-item').remove();
    } else {
        alert('Debe haber al menos un producto.');
    }
}
</script>
@endsection
