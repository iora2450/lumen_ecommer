<?php

namespace App\Console\Commands;

use App\Models\ProcessedSync;
use App\Models\SyncLog;
use App\Services\Erp\ErpClient;
use App\Services\Erp\ErpSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ErpPull extends Command
{
    protected $signature = 'erp:pull
        {--type=full : Tipo de pull: full | products | categories | brands | inventory | prices}
        {--sync-id= : sync_id para trazabilidad}
        {--dry-run : No escribir en la base}';

    protected $description = 'Jala (pull) datos desde el ERP via HTTP y los upsert en este ecommerce';

    public function handle(ErpClient $client, ErpSyncService $sync): int
    {
        if (!$client->isConfigured()) {
            $this->error('ERP no configurado. Define ERP_BASE_URL y ERP_API_KEY en .env');
            return self::FAILURE;
        }

        $type = $this->option('type');
        $syncId = $this->option('sync-id') ?: 'PULL-' . now()->format('Ymd-His') . '-' . strtoupper(substr(md5((string) microtime(true)), 0, 6));
        $dryRun = (bool) $this->option('dry-run');

        $log = SyncLog::create([
            'source'  => 'pull',
            'status'  => 'started',
            'message' => "pull.$type sync_id=$syncId",
        ]);

        $record = ProcessedSync::create([
            'sync_id'    => $syncId,
            'source'     => 'pull',
            'mode'       => 'upsert',
            'status'     => 'processing',
            'received_at'=> now(),
        ]);

        $this->info("🔄 Pull $type desde ERP (sync_id=$syncId)");

        try {
            $summary = match ($type) {
                'full'        => $this->pullFull($client, $sync, $dryRun),
                'categories'  => $this->pullCategories($client, $sync, $dryRun),
                'brands'      => $this->pullBrands($client, $sync, $dryRun),
                'products'    => $this->pullProducts($client, $sync, $dryRun),
                'inventory'   => $this->pullInventory($client, $sync, $dryRun),
                'prices'      => $this->pullPrices($client, $sync, $dryRun),
                default => throw new \InvalidArgumentException("Tipo $type no soportado"),
            };

            $record->update([
                'status'         => 'success',
                'summary'        => $summary,
                'items_processed'=> array_sum(array_map(fn ($v) => (int) $v, array_filter($summary, 'is_int'))),
                'finished_at'    => now(),
            ]);

            $log->update([
                'status'           => 'success',
                'finished_at'      => now(),
                'products_synced'  => array_sum(array_intersect_key($summary, array_flip(['products_created','products_updated','inventory_updated','prices_updated']))),
                'categories_synced'=> $summary['categories_created'] ?? 0,
                'brands_synced'    => $summary['brands_created']   ?? 0,
                'message'          => "pull.$type ok",
            ]);

            $this->info('✅ Pull completado: ' . json_encode($summary));
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $record->update(['status' => 'failed', 'finished_at' => now(), 'summary' => ['error' => $e->getMessage()]]);
            $log->update(['status' => 'failed', 'finished_at' => now(), 'message' => $e->getMessage()]);
            $this->error('❌ Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    protected function pullFull(ErpClient $client, ErpSyncService $sync, bool $dryRun): array
    {
        $catalog = $client->pullFullCatalog(pageSize: 100);
        if ($dryRun) {
            return ['dry_run' => true, 'received' => array_map('count', $catalog)];
        }
        $a = $sync->syncCategories($catalog['categories'] ?? []);
        $b = $sync->syncBrands($catalog['brands'] ?? []);
        $c = $sync->syncProducts($catalog['products'] ?? []);

        return array_merge($a->summary, $b->summary, $c->summary);
    }

    protected function pullCategories(ErpClient $client, ErpSyncService $sync, bool $dryRun): array
    {
        $rows = $client->pullCategories();
        if ($dryRun) return ['dry_run' => true, 'received' => count($rows)];
        return $sync->syncCategories($rows)->summary;
    }

    protected function pullBrands(ErpClient $client, ErpSyncService $sync, bool $dryRun): array
    {
        $rows = $client->pullBrands();
        if ($dryRun) return ['dry_run' => true, 'received' => count($rows)];
        return $sync->syncBrands($rows)->summary;
    }

    protected function pullProducts(ErpClient $client, ErpSyncService $sync, bool $dryRun): array
    {
        $summary = ['products_created' => 0, 'products_updated' => 0];
        $page = 1;
        do {
            $batch = $client->pullProducts($page, 100);
            $items = $batch['items'] ?? $batch['data'] ?? (is_array($batch) ? $batch : []);
            if (empty($items)) break;
            if ($dryRun) {
                $summary['would_process'] = ($summary['would_process'] ?? 0) + count($items);
            } else {
                $r = $sync->syncProducts($items);
                $summary['products_created'] += $r->summary['products_created'] ?? 0;
                $summary['products_updated'] += $r->summary['products_updated'] ?? 0;
            }
            $page++;
        } while (count($items) === 100);
        return $summary;
    }

    protected function pullInventory(ErpClient $client, ErpSyncService $sync, bool $dryRun): array
    {
        $rows = $client->pullInventory()['inventory'] ?? [];
        if ($dryRun) return ['dry_run' => true, 'received' => count($rows)];
        return $sync->syncInventory($rows)->summary;
    }

    protected function pullPrices(ErpClient $client, ErpSyncService $sync, bool $dryRun): array
    {
        $rows = $client->pullPrices()['prices'] ?? [];
        if ($dryRun) return ['dry_run' => true, 'received' => count($rows)];
        return $sync->syncPrices($rows)->summary;
    }
}
