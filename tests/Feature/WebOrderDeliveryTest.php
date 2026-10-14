<?php

namespace Tests\Feature;

use App\Models\{ErpOrderDelivery,Product,Quote};
use App\Services\Erp\WebOrderDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Http,Mail,DB};
use Tests\TestCase;

class WebOrderDeliveryTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Mail::fake();
        config(['web_orders.enabled' => true,'web_orders.base_url' => 'https://erp.test/api',
            'web_orders.token' => str_repeat('t', 40),'web_orders.source' => 'lumens-ecommerce']);
    }
    private function checkout(): ErpOrderDelivery
    {
        $product = Product::create(['lumen_id' => '17','sku' => 'SKU-WEB','name' => 'Lámpara','price' => 11.30,'qty' => 10,'is_active' => true]);
        $this->withSession(['cart.items' => ['product-'.$product->id => ['type' => 'product','id' => $product->id,'qty' => 2]]])
            ->post(route('cart.checkout'), ['customer_name' => 'Cliente','customer_email' => 'buyer@example.com',
                'customer_phone' => '22223333','shipping_address' => 'Dirección','delivery_method' => 'delivery'])
            ->assertSessionHasNoErrors()->assertRedirect();
        return ErpOrderDelivery::sole();
    }
    private function erpResponse(ErpOrderDelivery $delivery): array
    {
        return ['data' => ['source' => 'lumens-ecommerce','external_id' => $delivery->payload['external_id'],
            'document_type' => 'receipt','receipt_number' => $delivery->payload['order_number'],
            'receipt_status' => 'received','received_at' => now()->toIso8601String()]];
    }
    public function test_checkout_commits_immutable_delivery_without_contacting_erp(): void
    {
        $delivery = $this->checkout();
        Http::assertNothingSent();
        $this->assertNull($delivery->delivered_at);
        $this->assertSame(17, $delivery->payload['items'][0]['erp_product_id']);
        $this->assertSame('25.54', $delivery->payload['total']);
        $this->assertSame(2, $delivery->payload['version']);
        $this->assertSame('receipt', $delivery->payload['document_type']);
        $this->assertFalse($delivery->payload['prices_include_tax']);
        $this->assertSame('2.94', $delivery->payload['tax']);
        $this->assertSame('13.00', $delivery->payload['items'][0]['tax_rate']);
        $this->assertFalse($delivery->payload['customer']['requires_fiscal_credit']);
        $this->assertSame('consumer_final', $delivery->payload['customer']['requested_document_type']);
        Product::query()->update(['price' => 99,'lumen_id' => '999']);
        app(WebOrderDelivery::class)->enqueue(Quote::sole());
        $this->assertDatabaseCount('erp_order_deliveries', 1);
        $this->assertSame(17, $delivery->fresh()->payload['items'][0]['erp_product_id']);
        $this->assertSame('11.30', $delivery->fresh()->payload['items'][0]['price']);
    }
    public function test_failed_receipt_delivery_retries_same_payload_and_stops_after_acknowledgement(): void
    {
        $delivery = $this->checkout();
        $payload = $delivery->payload;
        Http::fakeSequence()->push([], 503)->push($this->erpResponse($delivery), 200);
        $service = app(WebOrderDelivery::class);
        $service->sync($delivery);
        $this->assertNull($delivery->fresh()->delivered_at);
        $this->assertNotNull($delivery->next_attempt_at);
        $this->assertNotNull($delivery->last_error);
        $service->sync($delivery);
        $this->assertNotNull($delivery->fresh()->delivered_at);
        $service->sync($delivery);
        $this->assertSame('received', $delivery->fresh()->erp_status['receipt_status']);
        $this->assertArrayNotHasKey('control_number', $delivery->fresh()->erp_status);
        $this->assertArrayNotHasKey('generation_code', $delivery->fresh()->erp_status);
        $this->assertArrayNotHasKey('receipt', $delivery->fresh()->erp_status);
        $sent = Http::recorded();
        $this->assertSame('POST', $sent[0][0]->method());
        $this->assertSame($payload, $sent[0][0]->data());
        $this->assertSame($sent[0][0]->data(), $sent[1][0]->data());
        $this->assertCount(2, $sent);
    }
    public function test_missing_configuration_does_not_lose_checkout_and_disabled_sync_never_sends(): void
    {
        config(['web_orders.enabled' => false,'web_orders.token' => null]);
        $delivery = $this->checkout();
        $this->artisan('web-orders:sync')->assertSuccessful();
        $this->assertNull($delivery->fresh()->delivered_at);
        Http::assertNothingSent();
    }
    public function test_transaction_failure_rolls_back_order_and_outbox_together(): void
    {
        $delivery = $this->checkout();
        try {
            DB::transaction(function () {
                $q = Quote::sole()->replicate();
                $q->quote_number = 'P-ROLLBACK';
                $q->save();
                app(WebOrderDelivery::class)->enqueue($q);
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException $e) {
        }
        $this->assertDatabaseCount('quotes', 1);
        $this->assertDatabaseCount('erp_order_deliveries', 1);
    }
    public function test_invalid_success_response_keeps_order_pending_for_retry(): void
    {
        $delivery = $this->checkout();
        Http::fake(['*' => Http::response(['data' => ['status' => 'pending','external_id' => 'wrong']], 200)]);
        app(WebOrderDelivery::class)->sync($delivery);
        $this->assertNull($delivery->fresh()->delivered_at);
        $this->assertNotNull($delivery->last_error);
    }
    public function test_scheduler_does_not_retry_before_backoff(): void
    {
        $delivery = $this->checkout();
        Http::fake(['*' => Http::response([], 503)]);
        $this->artisan('web-orders:sync')->assertSuccessful();
        $this->artisan('web-orders:sync')->assertSuccessful();
        Http::assertSentCount(1);
        $this->travel(2)->minutes();
        $this->artisan('web-orders:sync')->assertSuccessful();
        Http::assertSentCount(2);
    }
}
