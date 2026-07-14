<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProcessedSync;
use App\Models\Setting;
use App\Models\SyncLog;
use App\Services\Erp\ErpSyncService;
use App\Services\Erp\SyncResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SyncController extends Controller
{
    public function __construct(protected ErpSyncService $sync)
    {
    }

    /**
     * GET /api/sync/health
     */
    public function health(): JsonResponse
    {
        $last = Setting::get('sync_last_run');
        return response()->json([
            'status'    => 'ok',
            'service'   => 'lumens-ecommerce',
            'last_sync' => $last,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/sync/status
     */
    public function status(): JsonResponse
    {
        $last = SyncLog::orderByDesc('id')->first();
        $processed = ProcessedSync::orderByDesc('received_at')->limit(20)->get();

        return response()->json([
            'last_run'      => Setting::get('sync_last_run'),
            'log'           => $last,
            'processed_ids' => $processed->pluck('sync_id'),
        ]);
    }

    /**
     * POST /api/sync/catalog (push)
     * Recibe categorias + marcas + productos en una sola carga.
     */
    public function catalog(Request $request): JsonResponse
    {
        $auth = $this->authenticate($request);
        if ($auth !== null) {
            return $auth;
        }

        $data = $request->validate([
            'sync_id'             => 'required|string|max:128',
            'source'              => 'nullable|string|max:32',
            'mode'                => 'nullable|in:upsert,replace',
            'sent_at'             => 'nullable|date',
            'categories'          => 'array',
            'brands'              => 'array',
            'products'            => 'array',
            'products.*.sku'      => 'required|string',
            'products.*.name'     => 'required|string',
        ]);

        if ($idempotency = $this->checkIdempotency($data['sync_id'])) {
            return $idempotency;
        }

        $record = $this->startSync($data, 'catalog');

        $products = $request->input('products', []);
        $categories = $request->input('categories', []);
        $brands = $request->input('brands', []);

        $catRes = $this->sync->syncCategories($categories);
        $brdRes = $this->sync->syncBrands($brands);
        $prdRes = $this->sync->syncProducts($products, includeRelations: true);

        $summary = array_merge(
            $catRes->summary,
            $brdRes->summary,
            $prdRes->summary,
        );

        $errors = array_merge($catRes->errors, $brdRes->errors, $prdRes->errors);

        return $this->finishSync($record, $summary, $errors, $data['sync_id']);
    }

    /**
     * POST /api/sync/products (push)
     */
    public function products(Request $request): JsonResponse
    {
        $auth = $this->authenticate($request);
        if ($auth !== null) {
            return $auth;
        }

        $data = $request->validate([
            'sync_id'             => 'required|string|max:128',
            'mode'                => 'nullable|in:upsert,replace',
            'sent_at'             => 'nullable|date',
            'products'            => 'array|required',
            'products.*.sku'      => 'required|string',
            'products.*.name'     => 'required|string',
        ]);

        if ($idempotency = $this->checkIdempotency($data['sync_id'])) {
            return $idempotency;
        }

        $record = $this->startSync($data, 'products');
        $prdRes = $this->sync->syncProducts($request->input('products', []), true);
        return $this->finishSync($record, $prdRes->summary, $prdRes->errors, $data['sync_id']);
    }

    /**
     * POST /api/sync/inventory (push)
     */
    public function inventory(Request $request): JsonResponse
    {
        $auth = $this->authenticate($request);
        if ($auth !== null) {
            return $auth;
        }

        $data = $request->validate([
            'sync_id'             => 'required|string|max:128',
            'warehouse'           => 'nullable|string',
            'sent_at'             => 'nullable|date',
            'items'               => 'array|required',
            'items.*.sku'         => 'required_without:items.*.erp_id',
            'items.*.erp_id'      => 'required_without:items.*.sku',
            'items.*.qty'         => 'required|integer',
        ]);

        if ($idempotency = $this->checkIdempotency($data['sync_id'])) {
            return $idempotency;
        }

        $record = $this->startSync($data, 'inventory');
        $res = $this->sync->syncInventory($request->input('items', []));
        return $this->finishSync($record, $res->summary, $res->errors, $data['sync_id']);
    }

    /**
     * POST /api/sync/prices (push)
     */
    public function prices(Request $request): JsonResponse
    {
        $auth = $this->authenticate($request);
        if ($auth !== null) {
            return $auth;
        }

        $data = $request->validate([
            'sync_id'             => 'required|string|max:128',
            'currency'            => 'nullable|string|size:3',
            'sent_at'             => 'nullable|date',
            'items'               => 'array|required',
            'items.*.sku'         => 'required_without:items.*.erp_id',
            'items.*.erp_id'      => 'required_without:items.*.sku',
        ]);

        if ($idempotency = $this->checkIdempotency($data['sync_id'])) {
            return $idempotency;
        }

        $record = $this->startSync($data, 'prices');
        $res = $this->sync->syncPrices($request->input('items', []));
        return $this->finishSync($record, $res->summary, $res->errors, $data['sync_id']);
    }

    /**
     * POST /api/sync/lumen (legacy, backward compatibility)
     */
    public function lumen(Request $request): JsonResponse
    {
        return $this->catalog($request);
    }

    protected function authenticate(Request $request): ?JsonResponse
    {
        $expectedKey = (string) Setting::get('sync_api_key');
        if (!$expectedKey) {
            return response()->json(['success' => false, 'code' => 'server_error', 'message' => 'sync_api_key no configurado en la web.'], 500);
        }

        $bearer = $request->bearerToken();
        $headerToken = $request->header('X-API-KEY') ?? $request->header('X-ERP-Token');
        $providedKey = $bearer ?: $headerToken ?: $request->input('api_key');

        if (!$providedKey || !hash_equals($expectedKey, (string) $providedKey)) {
            return response()->json(['success' => false, 'code' => 'unauthorized', 'message' => 'Invalid API key'], 401);
        }

        return null;
    }

    protected function checkIdempotency(string $syncId): ?JsonResponse
    {
        if (!config('erp.enforce_idempotency', true)) {
            return null;
        }

        $existing = ProcessedSync::find($syncId);
        if (!$existing) {
            return null;
        }

        return response()->json([
            'status'  => $existing->status === 'success' ? 'success' : 'partial_success',
            'sync_id' => $syncId,
            'message' => 'Sync ya procesado.',
            'summary' => $existing->summary,
            'errors'  => [],
        ], 200);
    }

    protected function startSync(array $data, string $endpoint): ProcessedSync
    {
        Setting::set('sync_last_run', now()->toIso8601String(), 'sync');
        SyncLog::create([
            'source'    => $data['source'] ?? config('erp.inbound_source', 'erp'),
            'status'    => 'started',
            'message'   => "push.$endpoint sync_id={$data['sync_id']}",
        ]);

        return ProcessedSync::create([
            'sync_id'    => $data['sync_id'],
            'source'     => $data['source'] ?? config('erp.inbound_source', 'erp'),
            'mode'       => $data['mode'] ?? 'upsert',
            'status'     => 'processing',
            'received_at'=> isset($data['sent_at']) ? Carbon::parse($data['sent_at']) : now(),
        ]);
    }

    protected function finishSync(ProcessedSync $record, array $summary, array $errors, string $syncId): JsonResponse
    {
        $allOk = empty($errors);
        $record->update([
            'status'         => $allOk ? 'success' : 'partial_success',
            'summary'        => $summary,
            'items_processed'=> array_sum(array_map(fn ($v) => (int) $v, array_filter($summary, 'is_int'))),
            'items_failed'   => count($errors),
            'finished_at'    => now(),
        ]);

        SyncLog::orderByDesc('id')->first()?->update([
            'status'      => $allOk ? 'success' : 'partial_success',
            'finished_at' => now(),
            'products_synced' => ($summary['products_created'] ?? 0) + ($summary['products_updated'] ?? 0),
            'categories_synced' => ($summary['categories_created'] ?? 0) + ($summary['categories_updated'] ?? 0),
            'brands_synced' => ($summary['brands_created'] ?? 0) + ($summary['brands_updated'] ?? 0),
            'message'     => "push completed sync_id=$syncId",
        ]);

        return response()->json([
            'status'  => $allOk ? 'success' : 'partial_success',
            'sync_id' => $syncId,
            'summary' => $summary,
            'errors'  => $errors,
        ], $allOk ? 200 : 207);
    }
}
