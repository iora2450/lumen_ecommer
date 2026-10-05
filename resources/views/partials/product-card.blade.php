@php
    $price = $product->effective_price ?? $product->price;
    $imageUrl = $product->display_image_url;
    $isOutOfStock = ($product->qty ?? 0) <= 0;
    $isOnSale = (bool) ($product->isOnSale ?? false);
    $savings = $isOnSale ? max(0, (float) $product->price - (float) $price) : 0;
@endphp
<article @class([
    'group flex h-full flex-col overflow-hidden rounded-2xl border bg-white transition-all duration-300 hover:-translate-y-1 hover:border-accent hover:shadow-xl lumens-card',
    'border-accent/70 shadow-lg ring-2 ring-accent/20' => $isOnSale,
])>
    <a href="{{ route('catalog.show', $product->slug) }}"
       @class([
           'relative block aspect-square overflow-hidden p-6',
           'bg-white' => ! $isOnSale,
           'bg-gradient-to-br from-accent/15 via-white to-white' => $isOnSale,
       ])>
        <div class="flex size-full items-center justify-center">
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $product->name }}"
                     class="size-full object-contain transition-transform duration-500 group-hover:scale-110"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="hidden size-full items-center justify-center text-mist">
                    <svg class="size-16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            @else
                <svg class="size-16 text-mist" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            @endif
        </div>

        <div class="absolute top-3 left-3 flex flex-col gap-2">
            @if ($isOnSale)
                <span class="relative grid size-28 place-items-center transition duration-300 group-hover:rotate-6 group-hover:scale-110" aria-label="Ahorra {{ $product->discount_percentage }} por ciento">
                    <svg class="absolute inset-0 size-full overflow-visible drop-shadow-[0_6px_5px_rgba(32,55,73,0.28)]" viewBox="0 0 100 100" aria-hidden="true">
                        <polygon
                            points="50,2 56,17 67,5 69,22 84,12 79,29 96,25 84,38 99,43 83,50 98,59 80,62 92,77 75,73 80,94 65,80 60,99 51,82 41,98 38,80 21,92 27,73 7,78 20,62 1,57 17,49 1,42 19,37 6,23 24,28 20,9 35,21 40,3 48,18"
                            fill="var(--lumens-accent)"
                            stroke="var(--lumens-primary)"
                            stroke-width="2.5"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span class="relative z-10 -mt-1 text-center text-brand">
                        <span class="block text-2xl font-black leading-none">{{ $product->discount_percentage }}%</span>
                        <span class="mt-1 block text-[9px] font-black uppercase leading-none tracking-wider">Descuento</span>
                    </span>
                </span>
            @endif
            @if ($product->is_featured)
                <span class="rounded-lg bg-accent px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-brand shadow-sm">Destacado</span>
            @endif
        </div>

        <div class="absolute bottom-3 right-3 z-10">
            @if ($isOutOfStock)
                <span class="rounded-lg bg-rose-700 px-3 py-1.5 text-xs font-black uppercase tracking-wider text-white shadow-lg">Sin existencias</span>
            @elseif (($product->qty ?? 0) < 10)
                <span class="rounded-lg bg-accent/95 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-brand backdrop-blur-sm">Pocas unidades</span>
            @endif
        </div>
    </a>

    <div class="flex flex-1 flex-col border-t border-mist/55 bg-mist/15 p-5">
        @if ($product->brand)
            <p class="mb-1 text-[11px] font-bold uppercase tracking-widest text-brand/55">{{ $product->brand->name }}</p>
        @endif

        <a href="{{ route('catalog.show', $product->slug) }}" class="font-bold leading-snug text-brand transition-colors duration-200 line-clamp-2 group-hover:text-accent">
            {{ $product->name }}
        </a>
        <p class="mt-1.5 self-start rounded border border-mist bg-white px-2 py-0.5 font-mono text-xs text-brand/65">{{ $product->sku }}</p>

        @if (!empty($product->specs))
            <div class="mt-4 flex flex-wrap gap-1.5">
                @foreach (array_slice($product->specs, 0, 3, true) as $key => $value)
                    <span class="rounded border border-mist bg-white px-2 py-1 text-[11px] font-medium text-brand/70 shadow-sm">
                        <span class="text-brand/45">{{ ucfirst($key) }}:</span> {{ $value }}
                    </span>
                @endforeach
            </div>
        @endif

        @if ($isOutOfStock)
            <div class="mt-4 rounded-xl border border-rose-300 bg-rose-50 px-3 py-2.5 text-rose-900" role="status">
                <p class="text-xs font-black uppercase tracking-wide">No hay existencia inmediata</p>
                <p class="mt-0.5 text-xs font-medium leading-relaxed">Solicita una compra por importación.</p>
            </div>
        @endif

        <div class="mt-auto flex items-end justify-between gap-2 pt-5">
            <div class="flex flex-col">
                @if ($isOnSale)
                    <span class="mb-0.5 text-xs font-medium text-brand/45 line-through">${{ number_format((float) $product->price, 2) }}</span>
                @endif
                <span @class(['font-black leading-none text-brand', 'text-2xl' => $isOnSale, 'text-xl' => ! $isOnSale])>${{ number_format((float) $price, 2) }}</span>
                @if ($isOnSale)
                    <span class="mt-1 text-[11px] font-black uppercase tracking-wide text-emerald-700">Ahorras ${{ number_format($savings, 2) }}</span>
                @endif
            </div>
            <form method="POST" action="{{ route('cart.add', $product) }}">
                @csrf
                <input type="hidden" name="qty" value="1">
                <button type="submit" class="rounded-full px-4 py-2 text-xs font-black transition {{ $isOutOfStock ? 'bg-rose-700 text-white hover:bg-rose-800' : 'bg-accent text-brand hover:bg-accent' }}">
                    {{ $isOutOfStock ? 'Solicitar importación' : 'Agregar' }}
                </button>
            </form>
        </div>
    </div>
</article>
