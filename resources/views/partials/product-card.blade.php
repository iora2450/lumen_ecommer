@php
    $price = $product->effective_price ?? $product->price;
    $imageUrl = $product->display_image_url;
@endphp
<article class="group flex h-full flex-col overflow-hidden rounded-2xl border bg-white transition-all duration-300 hover:-translate-y-1 hover:border-[#FFAE00] hover:shadow-xl lumens-card">
    <a href="{{ route('catalog.show', $product->slug) }}" class="relative block aspect-square overflow-hidden bg-white p-6">
        <div class="flex size-full items-center justify-center">
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                     class="size-full object-contain transition-transform duration-500 group-hover:scale-110"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="hidden size-full items-center justify-center text-[#C8D3D7]">
                    <svg class="size-16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            @else
                <svg class="size-16 text-[#C8D3D7]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            @endif
        </div>

        <div class="absolute top-3 left-3 flex flex-col gap-2">
            @if ($product->isOnSale ?? false)
                <span class="rounded-lg bg-[#203749] px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white shadow-sm">Promo</span>
            @endif
            @if ($product->is_featured)
                <span class="rounded-lg bg-[#FFAE00] px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-[#203749] shadow-sm">Destacado</span>
            @endif
        </div>

        <div class="absolute bottom-3 right-3 z-10">
            @if (($product->qty ?? 0) <= 0)
                <span class="rounded-lg bg-black/90 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white backdrop-blur-sm">Sin stock</span>
            @elseif (($product->qty ?? 0) < 10)
                <span class="rounded-lg bg-[#FFAE00]/95 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-[#203749] backdrop-blur-sm">Pocas unidades</span>
            @endif
        </div>
    </a>

    <div class="flex flex-1 flex-col border-t border-[#C8D3D7]/55 bg-[#C8D3D7]/15 p-5">
        @if ($product->brand)
            <p class="mb-1 text-[11px] font-bold uppercase tracking-widest text-[#203749]/55">{{ $product->brand->name }}</p>
        @endif

        <a href="{{ route('catalog.show', $product->slug) }}" class="font-bold leading-snug text-[#203749] transition-colors duration-200 line-clamp-2 group-hover:text-[#FFAE00]">
            {{ $product->name }}
        </a>
        <p class="mt-1.5 self-start rounded border border-[#C8D3D7] bg-white px-2 py-0.5 font-mono text-xs text-[#203749]/65">{{ $product->sku }}</p>

        @if (!empty($product->specs))
            <div class="mt-4 flex flex-wrap gap-1.5">
                @foreach (array_slice($product->specs, 0, 3, true) as $key => $value)
                    <span class="rounded border border-[#C8D3D7] bg-white px-2 py-1 text-[11px] font-medium text-[#203749]/70 shadow-sm">
                        <span class="text-[#203749]/45">{{ ucfirst($key) }}:</span> {{ $value }}
                    </span>
                @endforeach
            </div>
        @endif

        <div class="mt-auto flex items-end justify-between gap-2 pt-5">
            <div class="flex flex-col">
                @if ($product->isOnSale ?? false)
                    <span class="mb-0.5 text-xs font-medium text-[#203749]/45 line-through">${{ number_format((float) $product->price, 2) }}</span>
                @endif
                <span class="text-xl font-black leading-none text-[#203749]">${{ number_format((float) $price, 2) }}</span>
            </div>
            <form method="POST" action="{{ route('cart.add', $product) }}">
                @csrf
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="rounded-full bg-[#FFAE00] px-4 py-2 text-xs font-black text-[#203749] transition hover:bg-[#FFAE00]">
                    Agregar
                </button>
            </form>
        </div>
    </div>
</article>
