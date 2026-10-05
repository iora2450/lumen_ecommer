<?php

use App\Http\Controllers\Admin\AuthController as AdminAuth;
use App\Http\Controllers\Admin\CategoryAdminController;
use App\Http\Controllers\Admin\CouponAdminController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\FooterAdminController;
use App\Http\Controllers\Admin\HeroSlideAdminController;
use App\Http\Controllers\Admin\HomeSlideAdminController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\QuoteAdminController;
use App\Http\Controllers\Admin\SyncAdminController;
use App\Http\Controllers\Admin\ThemeAdminController;
use App\Http\Controllers\Web\CartController;
use App\Http\Controllers\Web\CatalogController;
use App\Http\Controllers\Web\HomeController;
use Illuminate\Support\Facades\Route;

// ── Páginas públicas ───────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/ofertas', [CatalogController::class, 'offers'])->name('offers.index');
Route::get('/producto/{slug}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

// ── Carrito ───────────────────────────────────────────────────────────
Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
Route::post('/carrito/agregar/{product}', [CartController::class, 'add'])->name('cart.add');
Route::patch('/carrito', [CartController::class, 'update'])->name('cart.update');
Route::delete('/carrito/{key}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/carrito/finalizar', [CartController::class, 'checkout'])->name('cart.checkout');

// Confirmación de compra
Route::get('/carrito/confirmacion/{orderNumber}', [CartController::class, 'success'])->name('cart.success');

// ── Admin ─────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuth::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuth::class, 'login'])->middleware('guest');
    Route::post('logout', [AdminAuth::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');

        Route::get('products', [ProductAdminController::class, 'index'])->name('products.index');
        Route::get('products/{product}/edit', [ProductAdminController::class, 'edit'])->name('products.edit');
        Route::patch('products/{product}', [ProductAdminController::class, 'update'])->name('products.update');

        Route::get('categories', [CategoryAdminController::class, 'index'])->name('categories.index');
        Route::get('categories/{category}/edit', [CategoryAdminController::class, 'edit'])->name('categories.edit');
        Route::patch('categories/{category}/visibility', [CategoryAdminController::class, 'updateVisibility'])->name('categories.visibility');
        Route::patch('categories/{category}', [CategoryAdminController::class, 'update'])->name('categories.update');

        Route::get('hero-slides', [HeroSlideAdminController::class, 'index'])->name('hero-slides.index');
        Route::get('hero-slides/create', [HeroSlideAdminController::class, 'create'])->name('hero-slides.create');
        Route::post('hero-slides', [HeroSlideAdminController::class, 'store'])->name('hero-slides.store');
        Route::get('hero-slides/{heroSlide}/edit', [HeroSlideAdminController::class, 'edit'])->name('hero-slides.edit');
        Route::patch('hero-slides/{heroSlide}', [HeroSlideAdminController::class, 'update'])->name('hero-slides.update');
        Route::delete('hero-slides/{heroSlide}', [HeroSlideAdminController::class, 'destroy'])->name('hero-slides.destroy');

        Route::get('home-slides', [HomeSlideAdminController::class, 'index'])->name('home-slides.index');
        Route::get('home-slides/create', [HomeSlideAdminController::class, 'create'])->name('home-slides.create');
        Route::post('home-slides', [HomeSlideAdminController::class, 'store'])->name('home-slides.store');
        Route::get('home-slides/{homeSlide}/edit', [HomeSlideAdminController::class, 'edit'])->name('home-slides.edit');
        Route::patch('home-slides/{homeSlide}', [HomeSlideAdminController::class, 'update'])->name('home-slides.update');
        Route::delete('home-slides/{homeSlide}', [HomeSlideAdminController::class, 'destroy'])->name('home-slides.destroy');

        Route::get('coupons', [CouponAdminController::class, 'index'])->name('coupons.index');
        Route::get('coupons/create', [CouponAdminController::class, 'create'])->name('coupons.create');
        Route::post('coupons', [CouponAdminController::class, 'store'])->name('coupons.store');
        Route::get('coupons/{coupon}/edit', [CouponAdminController::class, 'edit'])->name('coupons.edit');
        Route::patch('coupons/{coupon}', [CouponAdminController::class, 'update'])->name('coupons.update');
        Route::delete('coupons/{coupon}', [CouponAdminController::class, 'destroy'])->name('coupons.destroy');

        Route::get('theme', [ThemeAdminController::class, 'edit'])->name('theme.edit');
        Route::patch('theme', [ThemeAdminController::class, 'update'])->name('theme.update');

        Route::get('footer', [FooterAdminController::class, 'edit'])->name('footer.edit');
        Route::patch('footer', [FooterAdminController::class, 'update'])->name('footer.update');

        Route::get('quotes', [QuoteAdminController::class, 'index'])->name('quotes.index');
        Route::get('quotes/{quote}', [QuoteAdminController::class, 'show'])->name('quotes.show');
        Route::patch('quotes/{quote}/status', [QuoteAdminController::class, 'updateStatus'])->name('quotes.status');

        Route::get('sync', [SyncAdminController::class, 'index'])->name('sync.index');
        Route::post('sync/run', [SyncAdminController::class, 'runNow'])->name('sync.run');
        Route::post('sync/pull', [SyncAdminController::class, 'pull'])->name('sync.pull');
        Route::post('sync/regenerate', [SyncAdminController::class, 'regenerateKey'])->name('sync.regenerate');
    });
});

// ── Health check ───────────────────────────────────────────────────────
Route::get('/up', fn () => response()->json(['status' => 'ok', 'service' => 'lumens-ecommerce']));
