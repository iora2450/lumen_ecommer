<?php

use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

// ── Productos ──────────────────────────────────────────────────────────
Route::get('products',        [ProductController::class, 'index'])->name('api.products.index');
Route::get('products/featured', [ProductController::class, 'featured'])->name('api.products.featured');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('api.products.show');

// ── Catálogo ────────────────────────────────────────────────────────────
Route::get('categories',         [CategoryController::class, 'index'])->name('api.categories.index');
Route::get('categories/{slug}',  [CategoryController::class, 'show'])->name('api.categories.show');
Route::get('brands',             [BrandController::class, 'index'])->name('api.brands.index');

// ── Cotizaciones ────────────────────────────────────────────────────────
Route::post('quotes', [QuoteController::class, 'store'])->name('api.quotes.store');
Route::get('quotes/{quoteNumber}', [QuoteController::class, 'show'])->name('api.quotes.show');

// ── Sync (recibe datos desde Sistema Lumen) ─────────────────────────────
Route::post('sync/lumen',   [SyncController::class, 'lumen'])->name('api.sync.lumen');
Route::get('sync/status',   [SyncController::class, 'status'])->name('api.sync.status');