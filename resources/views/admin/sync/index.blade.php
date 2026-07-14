@extends('layouts.admin')
@section('title', 'Sincronización')
@section('subtitle', 'Sincronización con Sistema Lumen')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="font-semibold text-[#203749] mb-4">Actualizar desde ERP</h3>
        <p class="text-sm text-slate-600 mb-4">
            Consulta el API HTTP del ERP y actualiza la información de la web.
        </p>

        <div class="mb-4 rounded-lg border px-3 py-2 text-xs {{ $erpConfigured ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700' }}">
            @if ($erpConfigured)
                ERP configurado: {{ config('erp.base_url') }}
            @else
                Falta configurar ERP_BASE_URL y ERP_API_KEY en el .env.
            @endif
        </div>

        @if (!empty($erpHealth))
            <div class="mb-4 rounded-lg bg-slate-100 p-3 text-xs text-slate-600">
                Health ERP: <span class="font-semibold text-emerald-700">{{ $erpHealth['status'] ?? 'ok' }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.sync.pull') }}" class="space-y-3">
            @csrf
            <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500" for="sync-type">
                Tipo de actualización
            </label>
            <select id="sync-type" name="type" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:border-[#FFAE00] focus:outline-none focus:ring-2 focus:ring-amber-100">
                <option value="full">Catálogo completo</option>
                <option value="products">Solo productos</option>
                <option value="categories">Solo categorías</option>
                <option value="brands">Solo marcas</option>
                <option value="inventory">Solo existencias</option>
                <option value="prices">Solo precios</option>
            </select>
            <button type="submit" class="w-full rounded-lg bg-[#203749] py-2 font-bold text-white hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60" @disabled(!$erpConfigured)>
                🔄 Actualizar desde ERP
            </button>
        </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h3 class="font-semibold text-[#203749] mb-4">Sync directo legado</h3>
        <p class="text-sm text-slate-600 mb-4">
            Ejecuta la sincronización antigua por conexión directa a base de datos.
            Úsala solo si el ERP HTTP todavía no está disponible.
        </p>
        <form method="POST" action="{{ route('admin.sync.run') }}">
            @csrf
            <button type="submit" class="w-full rounded-lg bg-[#FFAE00] text-[#203749] py-2 font-bold hover:bg-amber-400">
                🔁 Ejecutar sync legado
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
