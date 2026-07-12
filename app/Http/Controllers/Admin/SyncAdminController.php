<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SyncLog;
use Illuminate\Http\Request;

class SyncAdminController extends Controller
{
    public function index()
    {
        $lastSync = SyncLog::orderByDesc('id')->first();
        $history  = SyncLog::orderByDesc('id')->limit(20)->get();

        return view('admin.sync.index', compact('lastSync', 'history'));
    }

    public function runNow(Request $request)
    {
        \Illuminate\Support\Facades\Artisan::call('lumen:sync');

        return back()->with('success', 'Sincronización ejecutada.');
    }

    public function regenerateKey(Request $request)
    {
        $key = \Illuminate\Support\Str::random(40);
        Setting::set('sync_api_key', $key, 'sync');

        return back()->with('success', 'Nueva API key generada: ' . $key);
    }
}