<?php

use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\QuoteAdminController;
use App\Http\Controllers\Admin\SyncAdminController;
use App\Http\Controllers\Web\CatalogController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\QuoteController;
use Illuminate\Support\Facades\Route;

// ── Páginas públicas ───────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/producto/{slug}', [CatalogController::class, 'show'])->name('catalog.show');

// ── Cotizaciones ───────────────────────────────────────────────────────
Route::get('/cotizar',          [QuoteController::class, 'create'])->name('quote.create');
Route::post('/cotizar',         [QuoteController::class, 'store'])->name('quote.store');
Route::get('/cotizar/{quoteNumber}', [QuoteController::class, 'success'])->name('quote.success');

// ── Admin ─────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login',  [AdminAuth::class, 'showLogin'])->name('login')->middleware('guest');
    Route::post('login', [AdminAuth::class, 'login'])->middleware('guest');
    Route::post('logout', [AdminAuth::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');

        Route::get('quotes',                [QuoteAdminController::class, 'index'])->name('quotes.index');
        Route::get('quotes/{quote}',        [QuoteAdminController::class, 'show'])->name('quotes.show');
        Route::patch('quotes/{quote}/status', [QuoteAdminController::class, 'updateStatus'])->name('quotes.status');

        Route::get('sync',          [SyncAdminController::class, 'index'])->name('sync.index');
        Route::post('sync/run',     [SyncAdminController::class, 'runNow'])->name('sync.run');
        Route::post('sync/pull',    [SyncAdminController::class, 'pull'])->name('sync.pull');
        Route::post('sync/regenerate', [SyncAdminController::class, 'regenerateKey'])->name('sync.regenerate');
    });
});

// ── Health check ───────────────────────────────────────────────────────
Route::get('/up', fn () => response()->json(['status' => 'ok', 'service' => 'lumens-ecommerce']));