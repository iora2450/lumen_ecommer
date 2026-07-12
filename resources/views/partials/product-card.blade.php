@php
    $price = $product->effective_price ?? $product->price;
@endphp
<a href="{{ route('catalog.show', $product->slug) }}"
   class="group flex flex-col h-full rounded-2xl border border-slate-200 bg-white overflow-hidden transition-all duration-300 hover:border-[#FFAE00] hover:shadow-xl hover:-translate-y-1">
    {{-- Image / placeholder --}}
    <div class="relative aspect-square bg-white flex items-center justify-center overflow-hidden p-6">
        @if ($product->image_url)
            <img src="{{ asset($product->image_url) }}" alt="{{ $product->name }}"
                 class="size-full object-contain transition-transform duration-500 group-hover:scale-110"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="hidden size-full items-center justify-center text-slate-400">
                <svg class="size-16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        @else
            <svg class="size-16 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        @endif

        {{-- Hover Action Overlay --}}
        <div class="absolute inset-0 bg-[#203749]/5 opacity-0 transition-opacity duration-300 group-hover:opacity-100 flex items-center justify-center">
            <span class="translate-y-8 rounded-full bg-[#FFAE00] px-6 py-2.5 text-sm font-bold text-[#203749] opacity-0 shadow-lg transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                Ver detalles
            </span>
        </div>

        {{-- Badges --}}
        <div class="absolute top-3 left-3 flex flex-col gap-2">
            @if ($product->isOnSale ?? false)
                <span class="rounded-lg bg-rose-500 text-white text-[10px] font-black uppercase tracking-wider px-2.5 py-1 shadow-sm">Promo</span>
            @endif
            @if ($product->is_featured)
                <span class="rounded-lg bg-[#FFAE00] text-[#203749] text-[10px] font-black uppercase tracking-wider px-2.5 py-1 shadow-sm">Destacado</span>
            @endif
        </div>

        {{-- Stock badge --}}
        <div class="absolute bottom-3 right-3 z-10">
            @if (($product->qty ?? 0) <= 0)
                <span class="rounded-lg bg-slate-800/90 backdrop-blur-sm text-white text-[10px] font-bold uppercase tracking-wider px-2.5 py-1">Sin stock</span>
            @elseif (($product->qty ?? 0) < 10)
                <span class="rounded-lg bg-amber-500/90 backdrop-blur-sm text-white text-[10px] font-bold uppercase tracking-wider px-2.5 py-1">Pocas unidades</span>
            @endif
        </div>
    </div>

    {{-- Content --}}
    <div class="flex flex-col flex-1 p-5 border-t border-slate-100 bg-slate-50/50">
        @if ($product->brand)
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ $product->brand->name }}</p>
        @endif
        <h3 class="font-bold text-[#203749] leading-snug line-clamp-2 transition-colors duration-200 group-hover:text-[#FFAE00]">
            {{ $product->name }}
        </h3>
        <p class="mt-1.5 text-xs text-slate-500 font-mono bg-white inline-block px-2 py-0.5 rounded border border-slate-200 self-start">{{ $product->sku }}</p>

        {{-- Specs --}}
        @if (!empty($product->specs))
            <div class="mt-4 flex flex-wrap gap-1.5">
                @foreach (array_slice($product->specs, 0, 3, true) as $key => $value)
                    <span class="rounded bg-white border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-600 shadow-sm">
                        <span class="text-slate-400">{{ ucfirst($key) }}:</span> {{ $value }}
                    </span>
                @endforeach
            </div>
        @endif

        <div class="mt-auto pt-5 flex items-end justify-between gap-2">
            <div class="flex flex-col">
                @if ($product->isOnSale ?? false)
                    <span class="text-xs text-slate-400 line-through font-medium mb-0.5">${{ number_format((float) $product->price, 2) }}</span>
                @endif
                <span class="text-xl font-black text-[#203749] leading-none">${{ number_format((float) $price, 2) }}</span>
            </div>
        </div>
    </div>
</a>