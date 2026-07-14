<?php

namespace App\Console\Commands;

use App\Models\ProcessedSync;
use App\Models\Setting;
use App\Models\SyncLog;
use App\Services\Erp\ErpSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LumenSync extends Command
{
    protected $signature = 'lumen:sync {--dry-run : Solo mostrar lo que se sincronizaría}';
    protected $description = 'Sincroniza productos, categorías y marcas desde Sistema Lumen (DB directa)';

    public function handle(ErpSyncService $sync): int
    {
        $syncId = 'LUMEN-' . now()->format('Ymd-His') . '-' . strtoupper(substr(md5((string) microtime(true)), 0, 6));
        $log = SyncLog::create([
            'source'  => 'lumen',
            'status'  => 'started',
            'message' => "lumen.db-direct sync_id=$syncId",
        ]);

        ProcessedSync::create([
            'sync_id'     => $syncId,
            'source'      => 'lumen',
            'mode'        => 'upsert',
            'status'      => 'processing',
            'received_at' => now(),
        ]);

        $this->info('🔄 Iniciando sincronización directa desde Sistema Lumen...');

        try {
            $lumenData = $this->fetchLumenData();

            $categoriesCount = count($lumenData['categories'] ?? []);
            $brandsCount     = count($lumenData['brands'] ?? []);
            $productsCount   = count($lumenData['products'] ?? []);

            $this->line("   Categorías: $categoriesCount");
            $this->line("   Marcas: $brandsCount");
            $this->line("   Productos: $productsCount");

            if ($this->option('dry-run')) {
                $this->warn('⚠️  Modo dry-run: no se hicieron cambios.');
                $log->update(['status' => 'success', 'finished_at' => now(), 'message' => 'Dry run']);
                return self::SUCCESS;
            }

            $catRes = $sync->syncCategories($lumenData['categories'] ?? []);
            $brdRes = $sync->syncBrands($lumenData['brands'] ?? []);
            $prdRes = $sync->syncProducts($lumenData['products'] ?? []);

            Setting::set('sync_last_run', now()->toIso8601String(), 'sync');

            $summary = array_merge($catRes->summary, $brdRes->summary, $prdRes->summary);
            $errors  = array_merge($catRes->errors, $brdRes->errors, $prdRes->errors);

            ProcessedSync::where('sync_id', $syncId)->update([
                'status'         => empty($errors) ? 'success' : 'partial_success',
                'summary'        => $summary,
                'items_processed'=> ($prdRes->created + $prdRes->updated) + ($catRes->created + $catRes->updated) + ($brdRes->created + $brdRes->updated),
                'items_failed'   => count($errors),
                'finished_at'    => now(),
            ]);

            $log->update([
                'status'           => empty($errors) ? 'success' : 'partial_success',
                'products_synced'  => $prdRes->created + $prdRes->updated,
                'categories_synced'=> $catRes->created + $catRes->updated,
                'brands_synced'    => $brdRes->created + $brdRes->updated,
                'finished_at'      => now(),
                'message'          => "Synced {$prdRes->created} new + {$prdRes->updated} updated products, " . count($errors) . " errors",
            ]);

            $this->info("✅ Sincronización completa: {$prdRes->created}+{$prdRes->updated} productos, " . count($errors) . " errores.");
            return $errors ? self::FAILURE : self::SUCCESS;
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
            config(['database.connections.lumen_runtime' => $lumenConfig]);
            DB::purge('lumen_runtime');

            $categories = DB::connection('lumen_runtime')
                ->table('categories')
                ->select('id', 'name', 'parent_id', 'is_active')
                ->get()
                ->map(fn ($r) => [
                    'erp_id'   => (string) $r->id,
                    'name'     => $r->name,
                    'parent_erp_id' => $r->parent_id ? (string) $r->parent_id : null,
                    'is_active' => (bool) $r->is_active,
                ])
                ->all();

            $brands = DB::connection('lumen_runtime')
                ->table('brands')
                ->select('id', 'title', 'image')
                ->get()
                ->map(fn ($r) => [
                    'erp_id'   => (string) $r->id,
                    'name'     => $r->title,
                    'logo_url' => $r->image,
                    'is_active' => true,
                ])
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
                ->map(fn ($r) => [
                    'erp_id'      => (string) $r->id,
                    'sku'         => $r->code,
                    'name'        => $r->name,
                    'description' => $r->product_details,
                    'price'       => (float) $r->price,
                    'cost'        => (float) $r->cost,
                    'qty'         => (int) $r->qty,
                    'image_url'   => $r->image,
                    'category_erp_id' => $r->category_id ? (string) $r->category_id : null,
                    'brand_erp_id'    => $r->brand_id ? (string) $r->brand_id : null,
                    'is_featured' => (bool) $r->featured,
                    'is_promotion' => (bool) $r->promotion,
                    'promotion_price' => $r->promotion_price ? (float) $r->promotion_price : null,
                    'is_active'   => true,
                ])
                ->all();

            return compact('categories', 'brands', 'products');
        } catch (\Throwable $e) {
            $this->warn('⚠️  No se pudo conectar a la DB de Lumen: ' . $e->getMessage());
            $this->warn('   Usando datos de muestra vacíos.');
            return ['categories' => [], 'brands' => [], 'products' => []];
        }
    }
}
