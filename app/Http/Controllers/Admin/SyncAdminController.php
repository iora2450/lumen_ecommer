<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Services\Erp\ErpClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SyncAdminController extends Controller
{
    public function index(ErpClient $erp)
    {
        $lastSync = SyncLog::orderByDesc('id')->first();
        $history  = SyncLog::orderByDesc('id')->limit(20)->get();
        $erpHealth = $erp->isConfigured() ? $erp->health() : null;
        $erpConfigured = $erp->isConfigured();

        return view('admin.sync.index', compact('lastSync', 'history', 'erpHealth', 'erpConfigured'));
    }

    public function runNow(Request $request)
    {
        \Illuminate\Support\Facades\Artisan::call('lumen:sync');

        return back()->with('success', 'Sincronización ejecutada.');
    }

    public function pull(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => 'nullable|in:full,products,categories,brands,inventory,prices',
        ]);

        $type = $request->input('type', 'full');
        $exitCode = \Illuminate\Support\Facades\Artisan::call('erp:pull', ['--type' => $type]);

        $output = \Illuminate\Support\Facades\Artisan::output();

        if ($exitCode !== 0) {
            return back()->with('error', trim($output) ?: 'No se pudo actualizar desde el ERP.');
        }

        return back()->with('success', "Actualización desde ERP ($type) ejecutada. " . trim($output));
    }

    public function regenerateKey(Request $request)
    {
        $key = \Illuminate\Support\Str::random(40);
        \App\Models\Setting::set('sync_api_key', $key, 'sync');

        return back()->with('success', 'Nueva API key generada: ' . $key);
    }
}
