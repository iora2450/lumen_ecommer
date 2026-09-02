<?php

use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\HomeSlideAdminController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\QuoteAdminController;
use App\Http\Controllers\Admin\SyncAdminController;
use App\Http\Controllers\Admin\ThemeAdminController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CatalogController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\QuoteController;
use Illuminate\Support\Facades\Route;

// ── Páginas públicas ───────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/producto/{slug}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

// ── Carrito ───────────────────────────────────────────────────────────
Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrito/agregar/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrito/{key}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/carrito/finalizar', [CartController::class, 'checkout'])->name('cart.checkout');

// ── Cotizaciones ───────────────────────────────────────────────────────
Route::get('/cotizar', fn () => redirect()->route('cart.index'))->name('quote.create');
Route::post('/cotizar',         [QuoteController::class, 'store'])->name('quote.store');
Route::get('/cotizar/{quoteNumber}', [QuoteController::class, 'success'])->name('quote.success');

// ── Admin ─────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login',  [AdminAuth::class, 'showLogin'])->name('login')->middleware('guest');
    Route::post('login', [AdminAuth::class, 'login'])->middleware('guest');
    Route::post('logout', [AdminAuth::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');

        Route::get('products', [ProductAdminController::class, 'index'])->name('products.index');
        Route::get('products/{product}/edit', [ProductAdminController::class, 'edit'])->name('products.edit');
        Route::patch('products/{product}', [ProductAdminController::class, 'update'])->name('products.update');

        Route::get('categories', [CategoryAdminController::class, 'index'])->name('categories.index');
        Route::get('categories/{category}/edit', [CategoryAdminController::class, 'edit'])->name('categories.edit');
        Route::patch('categories/{category}', [CategoryAdminController::class, 'update'])->name('categories.update');

        Route::get('home-slides', [HomeSlideAdminController::class, 'index'])->name('home-slides.index');
        Route::get('home-slides/create', [HomeSlideAdminController::class, 'create'])->name('home-slides.create');
        Route::post('home-slides', [HomeSlideAdminController::class, 'store'])->name('home-slides.store');
        Route::get('home-slides/{homeSlide}/edit', [HomeSlideAdminController::class, 'edit'])->name('home-slides.edit');
        Route::patch('home-slides/{homeSlide}', [HomeSlideAdminController::class, 'update'])->name('home-slides.update');
        Route::delete('home-slides/{homeSlide}', [HomeSlideAdminController::class, 'destroy'])->name('home-slides.destroy');

        Route::get('theme', [ThemeAdminController::class, 'edit'])->name('theme.edit');
        Route::patch('theme', [ThemeAdminController::class, 'update'])->name('theme.update');

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
