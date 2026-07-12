@extends('layouts.admin')
@section('title', 'Sincronización')
@section('subtitle', 'Sincronización con Sistema Lumen')

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="font-semibold text-[#203749] mb-4">Ejecutar sincronización</h3>
        <p class="text-sm text-slate-600 mb-4">
            La sincronización trae productos, categorías y marcas desde Sistema Lumen.
            Se ejecuta automáticamente cada 6 horas, pero puedes forzarla manualmente.
        </p>
        <form method="POST" action="{{ route('admin.sync.run') }}">
            @csrf
            <button type="submit" class="w-full rounded-lg bg-[#FFAE00] text-[#203749] py-2 font-bold hover:bg-amber-400">
                🔄 Ejecutar sync ahora
            </button>
        </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="font-semibold text-[#203749] mb-4">API Key</h3>
        <p class="text-sm text-slate-600 mb-3">
            Esta clave la usa Sistema Lumen para autenticarse al hacer POST a <code>/api/sync/lumen</code>.
        </p>
        <div class="rounded-lg bg-slate-100 p-3 font-mono text-xs break-all">
            {{ \App\Models\Setting::get('sync_api_key') }}
        </div>
        <form method="POST" action="{{ route('admin.sync.regenerate') }}" class="mt-3">
            @csrf
            <button type="submit" class="text-sm font-semibold text-rose-600 hover:text-rose-700">
                🔑 Regenerar API key
            </button>
        </form>
    </div>
</div>

<div class="mt-8 rounded-xl border border-slate-200 bg-white overflow-hidden">
    <div class="p-6 border-b border-slate-200">
        <h3 class="font-semibold text-[#203749]">Historial de sincronización</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">Fecha</th>
                <th class="px-4 py-3">Estado</th>
                <th class="px-4 py-3 text-right">Productos</th>
                <th class="px-4 py-3 text-right">Categorías</th>
                <th class="px-4 py-3 text-right">Marcas</th>
                <th class="px-4 py-3">Mensaje</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse ($history as $h)
                <tr>
                    <td class="px-4 py-3 text-xs">{{ $h->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="px-4 py-3">
                        @if ($h->status === 'success') <span class="text-emerald-600">✅ Exitoso</span>
                        @elseif ($h->status === 'failed') <span class="text-rose-600">❌ Falló</span>
                        @else <span class="text-slate-500">⏳ En progreso</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">{{ $h->products_synced }}</td>
                    <td class="px-4 py-3 text-right">{{ $h->categories_synced }}</td>
                    <td class="px-4 py-3 text-right">{{ $h->brands_synced }}</td>
                    <td class="px-4 py-3 text-xs text-slate-600">{{ $h->message }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No hay historial.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection