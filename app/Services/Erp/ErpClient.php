<?php

namespace App\Services\Erp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cliente HTTP para jalar (pull) datos desde el ERP.
 *
 * El ERP expone un REST API identificado por config('erp.base_url')
 * y config('erp.endpoints'). Todas las requests llevan el header
 * Authorization: Bearer {api_key}.
 */
class ErpClient
{
    public function __construct(
        protected ?string $baseUrl = null,
        protected ?string $apiKey = null,
        protected ?int $timeout = null,
        protected ?int $retries = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('erp.base_url'), '/');
        $this->apiKey  = $apiKey  ?? (string) config('erp.api_key');
        $this->timeout = $timeout ?? (int) config('erp.timeout', 30);
        $this->retries = $retries ?? (int) config('erp.retry_attempts', 3);
    }

    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->apiKey);
    }

    public function health(): ?array
    {
        try {
            $response = $this->request()->get($this->url('health'));
            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('erp.client.health_failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function pullCategories(): array
    {
        return $this->getList('categories');
    }

    public function pullBrands(): array
    {
        return $this->getList('brands');
    }

    public function pullProducts(int $page = 1, int $perPage = 100): array
    {
        return $this->getList('products', [
            'page'      => $page,
            'per_page'  => $perPage,
        ]);
    }

    public function pullInventory(): array
    {
        $rows = $this->getList('inventory');
        return ['inventory' => $rows];
    }

    public function pullPrices(): array
    {
        $rows = $this->getList('prices');
        return ['prices' => $rows];
    }

    public function pullFullCatalog(int $pageSize = 100, ?\Closure $onPage = null): array
    {
        $all = ['categories' => [], 'brands' => [], 'products' => []];

        $all['categories'] = $this->pullCategories();
        $all['brands']     = $this->pullBrands();

        $page = 1;
        do {
            $batch = $this->pullProducts($page, $pageSize);
            $items = $batch['items'] ?? $batch['data'] ?? (is_array($batch) ? $batch : []);
            if (empty($items)) {
                break;
            }
            $all['products'] = array_merge($all['products'], $items);
            if ($onPage) {
                $onPage($page, $items);
            }
            $page++;
            $totalPages = $batch['meta']['last_page'] ?? $batch['last_page'] ?? null;
            if ($totalPages !== null && $page > (int) $totalPages) {
                break;
            }
        } while (count($items) === $pageSize);

        return $all;
    }

    protected function getList(string $key, array $query = []): array
    {
        try {
            $response = $this->request()->get($this->url($key), $query);
            if (!$response->successful()) {
                Log::warning('erp.client.list_failed', [
                    'endpoint' => $key,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                return [];
            }
            $json = $response->json();
            return $json['data'] ?? $json['items'] ?? (is_array($json) ? $json : []);
        } catch (\Throwable $e) {
            Log::warning('erp.client.list_exception', [
                'endpoint' => $key,
                'error'    => $e->getMessage(),
            ]);
            return [];
        }
    }

    protected function url(string $key): string
    {
        $path = config("erp.endpoints.$key", "/$key");
        if (!str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        return $this->baseUrl . $path;
    }

    protected function request(): PendingRequest
    {
        return Http::withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->retry($this->retries, fn (int $attempt) => (int) config('erp.retry_delay', 2) * $attempt);
    }
}
