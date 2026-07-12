<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\SyncLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    /**
     * POST /api/sync/lumen
     * Recibe datos desde Sistema Lumen y los upsert en este e-commerce.
     */
    public function lumen(Request $request): JsonResponse
    {
        // Validar API key
        $expectedKey = Setting::get('sync_api_key');
        $providedKey = $request->header('X-API-KEY') ?? $request->input('api_key');

        if (!$expectedKey || !$providedKey || !hash_equals((string) $expectedKey, (string) $providedKey)) {
            return response()->json(['success' => false, 'message' => 'Invalid API key'], 401);
        }

        $log = SyncLog::create([
            'source' => 'lumen',
            'status' => 'started',
        ]);

        try {
            $data = $request->validate([
                'categories'         => 'array',
                'categories.*.id'    => 'required|integer',
                'categories.*.name'  => 'required|string',
                'brands'             => 'array',
                'brands.*.id'        => 'required|integer',
                'brands.*.name'      => 'required|string',
                'products'           => 'array',
                'products.*.id'      => 'required|integer',
                'products.*.code'    => 'required|string',
                'products.*.name'    => 'required|string',
            ]);

            $categoriesSynced = $this->syncCategories($data['categories'] ?? []);
            $brandsSynced     = $this->syncBrands($data['brands'] ?? []);
            $productsSynced   = $this->syncProducts($data['products'] ?? []);

            Setting::set('sync_last_run', now()->toIso8601String(), 'sync');

            $log->update([
                'status'           => 'success',
                'products_synced'  => $productsSynced,
                'categories_synced'=> $categoriesSynced,
                'brands_synced'    => $brandsSynced,
                'finished_at'      => now(),
                'message'          => "Synced $productsSynced products, $categoriesSynced categories, $brandsSynced brands",
            ]);

            return response()->json([
                'success'          => true,
                'products_synced'  => $productsSynced,
                'categories_synced'=> $categoriesSynced,
                'brands_synced'    => $brandsSynced,
            ]);
        } catch (\Throwable $e) {
            $log->update([
                'status'     => 'failed',
                'finished_at'=> now(),
                'message'    => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Sync failed',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function status(): JsonResponse
    {
        $last = SyncLog::where('source', 'lumen')
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'last_run' => Setting::get('sync_last_run'),
            'log'      => $last,
        ]);
    }

    protected function syncCategories(array $items): int
    {
        return DB::transaction(function () use ($items) {
            $count = 0;
            foreach ($items as $c) {
                Category::updateOrCreate(
                    ['lumen_id' => $c['id']],
                    [
                        'name'        => $c['name'],
                        'slug'        => Str::slug($c['name']) . '-' . $c['id'],
                        'description' => $c['description'] ?? null,
                        'is_active'   => $c['is_active'] ?? true,
                    ]
                );
                $count++;
            }
            return $count;
        });
    }

    protected function syncBrands(array $items): int
    {
        return DB::transaction(function () use ($items) {
            $count = 0;
            foreach ($items as $b) {
                Brand::updateOrCreate(
                    ['lumen_id' => $b['id']],
                    [
                        'name'      => $b['name'],
                        'slug'      => Str::slug($b['name']) . '-' . $b['id'],
                        'is_active' => $b['is_active'] ?? true,
                    ]
                );
                $count++;
            }
            return $count;
        });
    }

    protected function syncProducts(array $items): int
    {
        return DB::transaction(function () use ($items) {
            $count = 0;
            foreach ($items as $p) {
                // Resolver category/brand por lumen_id
                $categoryId = !empty($p['category_id'])
                    ? Category::where('lumen_id', $p['category_id'])->value('id')
                    : null;
                $brandId = !empty($p['brand_id'])
                    ? Brand::where('lumen_id', $p['brand_id'])->value('id')
                    : null;

                Product::updateOrCreate(
                    ['lumen_id' => $p['id']],
                    [
                        'sku'         => $p['code'],
                        'name'        => $p['name'],
                        'slug'        => Str::slug($p['name']) . '-' . $p['id'],
                        'description' => $p['product_details'] ?? null,
                        'price'       => (float) ($p['price'] ?? 0),
                        'cost'        => isset($p['cost']) ? (float) $p['cost'] : null,
                        'qty'         => (int) ($p['qty'] ?? 0),
                        'image_url'   => $p['image'] ?? null,
                        'category_id' => $categoryId,
                        'brand_id'    => $brandId,
                        'is_featured' => (bool) ($p['featured'] ?? false),
                        'is_promotion' => (bool) ($p['promotion'] ?? false),
                        'promotion_price' => isset($p['promotion_price']) ? (float) $p['promotion_price'] : null,
                        'is_active'   => (bool) ($p['is_active'] ?? true),
                        'synced_at'   => now(),
                    ]
                );
                $count++;
            }
            return $count;
        });
    }
}