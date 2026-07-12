<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\SyncLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LumenSync extends Command
{
    protected $signature = 'lumen:sync {--dry-run : Solo mostrar lo que se sincronizaría}';
    protected $description = 'Sincroniza productos, categorías y marcas desde Sistema Lumen';

    public function handle(): int
    {
        $log = SyncLog::create(['source' => 'lumen', 'status' => 'started']);
        $this->info('🔄 Iniciando sincronización con Sistema Lumen...');

        try {
            // Conectar a la base de datos de Lumen directamente
            $lumenProducts   = $this->fetchLumenData();
            $categoriesCount = count($lumenProducts['categories'] ?? []);
            $brandsCount     = count($lumenProducts['brands'] ?? []);
            $productsCount   = count($lumenProducts['products'] ?? []);

            $this->line("   Categorías: $categoriesCount");
            $this->line("   Marcas: $brandsCount");
            $this->line("   Productos: $productsCount");

            if ($this->option('dry-run')) {
                $this->warn('⚠️  Modo dry-run: no se hicieron cambios.');
                $log->update(['status' => 'success', 'finished_at' => now(), 'message' => 'Dry run']);
                return self::SUCCESS;
            }

            // Sincronizar
            $syncCategories = $this->syncCategories($lumenProducts['categories'] ?? []);
            $syncBrands     = $this->syncBrands($lumenProducts['brands'] ?? []);
            $syncProducts   = $this->syncProducts($lumenProducts['products'] ?? []);

            Setting::set('sync_last_run', now()->toIso8601String(), 'sync');

            $log->update([
                'status'           => 'success',
                'products_synced'  => $syncProducts,
                'categories_synced'=> $syncCategories,
                'brands_synced'    => $syncBrands,
                'finished_at'      => now(),
                'message'          => "Synced $syncProducts products, $syncCategories categories, $syncBrands brands",
            ]);

            $this->info("✅ Sincronización completa: $syncProducts productos, $syncCategories categorías, $syncBrands marcas.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'finished_at' => now(), 'message' => $e->getMessage()]);
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function fetchLumenData(): array
    {
        $lumenConfig = config('database.connections.lumen');

        if (!$lumenConfig) {
            throw new \Exception('Conexión "lumen" no configurada en config/database.php');
        }

        try {
            // Conectar a la DB de Lumen
            config(['database.connections.lumen_runtime' => $lumenConfig]);
            DB::purge('lumen_runtime');

            $categories = DB::connection('lumen_runtime')
                ->table('categories')
                ->select('id', 'name', 'parent_id', 'is_active')
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();

            $brands = DB::connection('lumen_runtime')
                ->table('brands')
                ->select('id', 'title', 'image')
                ->get()
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->title, 'image' => $r->image, 'is_active' => true])
                ->all();

            $products = DB::connection('lumen_runtime')
                ->table('products')
                ->where('is_active', 1)
                ->select(
                    'id', 'name', 'code', 'price', 'cost', 'qty',
                    'image', 'product_details', 'category_id', 'brand_id',
                    'featured', 'promotion', 'promotion_price', 'is_variant'
                )
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();

            return compact('categories', 'brands', 'products');
        } catch (\Throwable $e) {
            $this->warn('⚠️  No se pudo conectar a la DB de Lumen: ' . $e->getMessage());
            $this->warn('   Usando datos de muestra vacíos.');
            return ['categories' => [], 'brands' => [], 'products' => []];
        }
    }

    protected function syncCategories(array $items): int
    {
        $count = 0;
        foreach ($items as $c) {
            \App\Models\Category::updateOrCreate(
                ['lumen_id' => $c['id']],
                [
                    'name'      => $c['name'],
                    'slug'      => Str::slug($c['name']) . '-' . $c['id'],
                    'is_active' => (bool) ($c['is_active'] ?? 1),
                ]
            );
            $count++;
        }
        return $count;
    }

    protected function syncBrands(array $items): int
    {
        $count = 0;
        foreach ($items as $b) {
            \App\Models\Brand::updateOrCreate(
                ['lumen_id' => $b['id']],
                [
                    'name'      => $b['name'],
                    'slug'      => Str::slug($b['name']) . '-' . $b['id'],
                    'logo_url'  => $b['image'] ?? null,
                    'is_active' => (bool) ($b['is_active'] ?? 1),
                ]
            );
            $count++;
        }
        return $count;
    }

    protected function syncProducts(array $items): int
    {
        $count = 0;
        foreach ($items as $p) {
            $categoryId = !empty($p['category_id'])
                ? \App\Models\Category::where('lumen_id', $p['category_id'])->value('id')
                : null;
            $brandId = !empty($p['brand_id'])
                ? \App\Models\Brand::where('lumen_id', $p['brand_id'])->value('id')
                : null;

            \App\Models\Product::updateOrCreate(
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
                    'is_promotion'=> (bool) ($p['promotion'] ?? false),
                    'promotion_price' => isset($p['promotion_price']) ? (float) $p['promotion_price'] : null,
                    'is_active'   => true,
                    'synced_at'   => now(),
                ]
            );
            $count++;
        }
        return $count;
    }
}