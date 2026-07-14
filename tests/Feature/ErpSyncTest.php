<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProcessedSync;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\Erp\ErpSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ErpSyncTest extends TestCase
{
    use RefreshDatabase;

    protected string $apiKey = 'TEST-API-KEY';

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('sync_api_key', $this->apiKey, 'sync');
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/sync/health');
        $response->assertOk();
        $response->assertJsonStructure(['status', 'service', 'timestamp']);
    }

    public function test_push_without_auth_returns_401(): void
    {
        $response = $this->postJson('/api/sync/catalog', [
            'sync_id'   => 'NOAUTH-001',
            'products'  => [],
        ]);
        $response->assertStatus(401);
    }

    public function test_push_catalog_creates_product_with_relations(): void
    {
        $payload = [
            'sync_id' => 'TEST-PUSH-001',
            'source'  => 'erp',
            'mode'    => 'upsert',
            'categories' => [
                ['erp_id' => 'CAT-1', 'name' => 'Iluminacion', 'sort_order' => 1, 'is_active' => true],
            ],
            'brands' => [
                ['erp_id' => 'BR-1', 'name' => 'Lumens Pro', 'is_active' => true],
            ],
            'products' => [
                [
                    'erp_id' => '100',
                    'sku'    => 'TEST-PUSH-SKU',
                    'name'   => 'Test Product',
                    'category_erp_id' => 'CAT-1',
                    'brand_erp_id'    => 'BR-1',
                    'price'  => 99.99,
                    'qty'    => 50,
                    'currency' => 'USD',
                    'is_active' => true,
                    'images' => [
                        ['url' => 'http://x/a.png', 'alt' => 'A', 'sort_order' => 0, 'is_primary' => true],
                    ],
                    'variants' => [
                        ['erp_id' => '100-V1', 'sku' => 'TEST-PUSH-SKU-V1', 'name' => 'V1', 'price' => 99.99, 'qty' => 10],
                    ],
                    'technical_specs' => [
                        ['group' => 'Electricas', 'key' => 'wattage', 'label' => 'Potencia', 'value' => '10', 'unit' => 'W'],
                    ],
                    'documents' => [
                        ['type' => 'datasheet', 'title' => 'PDF', 'url' => 'http://x/a.pdf'],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/sync/catalog', $payload, [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'success');

        $product = Product::where('sku', 'TEST-PUSH-SKU')->first();
        $this->assertNotNull($product);
        $this->assertEquals('100', (string) $product->lumen_id);
        $this->assertEquals('Iluminacion', $product->category->name);
        $this->assertEquals('Lumens Pro', $product->brand->name);
        $this->assertCount(1, $product->variants);
        $this->assertCount(1, $product->images);
        $this->assertCount(1, $product->technicalSpecs);
        $this->assertCount(1, $product->documents);

        $this->assertDatabaseHas('processed_syncs', [
            'sync_id' => 'TEST-PUSH-001',
            'status'  => 'success',
        ]);
    }

    public function test_inventory_endpoint_updates_stock(): void
    {
        Product::create([
            'lumen_id' => '500',
            'sku'      => 'INV-SKU',
            'name'     => 'Inv Test',
            'price'    => 10,
            'stock_status' => 'in_stock',
        ]);

        $response = $this->postJson('/api/sync/inventory', [
            'sync_id' => 'INV-TEST-001',
            'items'   => [
                ['erp_id' => '500', 'sku' => 'INV-SKU', 'qty' => 99, 'available_qty' => 95, 'stock_status' => 'low_stock'],
            ],
        ], ['Authorization' => 'Bearer ' . $this->apiKey]);

        $response->assertOk();
        $this->assertDatabaseHas('products', [
            'sku'          => 'INV-SKU',
            'qty'          => 99,
            'available_qty'=> 95,
            'stock_status' => 'low_stock',
        ]);
    }

    public function test_prices_endpoint_updates_pricing(): void
    {
        Product::create([
            'lumen_id' => '600',
            'sku'      => 'PRC-SKU',
            'name'     => 'Price Test',
            'price'    => 10,
            'is_promotion' => false,
        ]);

        $response = $this->postJson('/api/sync/prices', [
            'sync_id' => 'PRC-TEST-001',
            'items'   => [
                ['erp_id' => '600', 'sku' => 'PRC-SKU', 'price' => 19.99, 'compare_at_price' => 24.99, 'promotion_price' => 14.99, 'is_promotion' => true],
            ],
        ], ['Authorization' => 'Bearer ' . $this->apiKey]);

        $response->assertOk();
        $this->assertDatabaseHas('products', [
            'sku' => 'PRC-SKU',
            'price' => '19.99',
            'compare_at_price' => '24.99',
            'promotion_price' => '14.99',
            'is_promotion' => 1,
        ]);
    }

    public function test_idempotency_returns_previous_result(): void
    {
        $first = $this->postJson('/api/sync/inventory', [
            'sync_id' => 'IDEMP-001',
            'items'   => [['sku' => 'NO-SUCH-SKU', 'qty' => 5]],
        ], ['Authorization' => 'Bearer ' . $this->apiKey]);

        $first->assertSuccessful();

        $second = $this->postJson('/api/sync/inventory', [
            'sync_id' => 'IDEMP-001',
            'items'   => [['sku' => 'NO-SUCH-SKU', 'qty' => 999]],
        ], ['Authorization' => 'Bearer ' . $this->apiKey]);

        $second->assertSuccessful();
        $second->assertJsonPath('message', 'Sync ya procesado.');
    }

    public function test_sync_service_handles_variant_sku_uniqueness(): void
    {
        $svc = app(ErpSyncService::class);
        $svc->syncBrands([['erp_id' => 'B1', 'name' => 'B']]);
        $svc->syncCategories([['erp_id' => 'C1', 'name' => 'C']]);
        $svc->syncProducts([[
            'erp_id' => 'P1', 'sku' => 'P1', 'name' => 'P1',
            'price' => 1, 'qty' => 1, 'is_active' => true,
            'category_erp_id' => 'C1', 'brand_erp_id' => 'B1',
            'variants' => [
                ['erp_id' => 'V1', 'sku' => 'V1', 'name' => 'V1', 'price' => 1],
            ],
        ]]);

        // Re-sync with same SKU should update, not fail.
        $res = $svc->syncProducts([[
            'erp_id' => 'P1', 'sku' => 'P1', 'name' => 'P1 updated',
            'price' => 2, 'qty' => 5, 'is_active' => true,
            'category_erp_id' => 'C1', 'brand_erp_id' => 'B1',
        ]]);

        $this->assertEquals(0, $res->failed);
        $this->assertEquals('P1 updated', Product::where('sku', 'P1')->value('name'));
        $this->assertCount(1, ProductVariant::all(), 'Se mantiene la variante original');
    }

    public function test_erp_pull_products_imports_products_from_http_api(): void
    {
        config([
            'erp.base_url' => 'https://erp.test/api',
            'erp.api_key' => 'ERP-PULL-TOKEN',
            'erp.retry_attempts' => 1,
        ]);

        Http::fake([
            'erp.test/api/products?page=1&per_page=100' => Http::response([
                'data' => [
                    [
                        'erp_id' => 'ERP-900',
                        'sku' => 'ERP-PULL-SKU',
                        'name' => 'Producto desde ERP',
                        'description' => 'Producto importado por pull HTTP.',
                        'price' => 35.50,
                        'qty' => 12,
                        'is_active' => true,
                    ],
                ],
                'meta' => ['current_page' => 1, 'last_page' => 1],
            ]),
        ]);

        $exitCode = Artisan::call('erp:pull', ['--type' => 'products']);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('products', [
            'lumen_id' => 'ERP-900',
            'sku' => 'ERP-PULL-SKU',
            'name' => 'Producto desde ERP',
            'price' => '35.50',
            'qty' => 12,
            'is_active' => 1,
        ]);
    }
}
