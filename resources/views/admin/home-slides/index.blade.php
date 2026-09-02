@extends('layouts.admin')
@section('title', 'Carrusel inicio')
@section('subtitle', 'Banners de promociones, productos nuevos o campañas visibles en la página principal.')

@section('content')
<div class="mb-6 flex justify-end">
    <a href="{{ route('admin.home-slides.create') }}" class="rounded-lg bg-[#203749] px-4 py-2 text-sm font-bold text-white">
        Nuevo banner
    </a>
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">Vista</th>
                <th class="px-4 py-3">Contenido</th>
                <th class="px-4 py-3">Orden</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse ($slides as $slide)
                <tr class="align-middle hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="h-20 w-32 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                            <img src="{{ $slide->display_image_url }}" alt="{{ $slide->title }}" class="size-full object-cover">
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @if ($slide->badge)
                            <p class="text-xs font-bold uppercase tracking-wide text-[#FFAE00]">{{ $slide->badge }}</p>
                        @endif
                        <p class="font-semibold text-[#203749]">{{ $slide->title }}</p>
                        @if ($slide->subtitle)
                            <p class="mt-1 max-w-xl text-xs text-slate-500">{{ $slide->subtitle }}</p>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $slide->sort_order }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $slide->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                            {{ $slide->is_active ? 'Activo' : 'Oculto' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.home-slides.edit', $slide) }}" class="text-sm font-semibold text-[#203749] hover:text-[#FFAE00]">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                        Aún no hay banners. Crea uno con la imagen de una promoción o producto nuevo.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $slides->links() }}</div>
@endsection
