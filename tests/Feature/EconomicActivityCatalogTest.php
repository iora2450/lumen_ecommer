<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Quote;
use App\Support\EconomicActivityCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EconomicActivityCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_official_cat_019_catalog_is_available_in_the_checkout_form(): void
    {
        $catalog = app(EconomicActivityCatalog::class);
        $product = $this->product();

        $this->assertCount(774, $catalog->all());
        $this->assertSame(
            'Venta al por mayor de otros productos',
            $catalog->find('46900')['actividad_economica']
        );

        $this->withSession(['cart.items' => $this->cartFor($product)])
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Actividad económica / giro')
            ->assertSee('economic-activity-options', false)
            ->assertSee('46900 — Venta al por mayor de otros productos');
    }

    public function test_checkout_rejects_an_activity_code_that_is_not_in_the_catalog(): void
    {
        $product = $this->product();

        $this->withSession(['cart.items' => $this->cartFor($product)])
            ->from(route('cart.index'))
            ->post(route('cart.checkout'), $this->fiscalData('99999'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors(['fiscal_activity_code']);

        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_checkout_saves_the_official_description_for_the_selected_code(): void
    {
        $product = $this->product();

        $this->withSession(['cart.items' => $this->cartFor($product)])
            ->post(route('cart.checkout'), array_merge($this->fiscalData('46900'), [
                'fiscal_activity_description' => 'Descripción alterada',
            ]))
            ->assertRedirect();

        $quote = Quote::sole();

        $this->assertSame('46900', $quote->fiscal_activity_code);
        $this->assertSame('Venta al por mayor de otros productos', $quote->fiscal_activity_description);
    }

    private function fiscalData(string $activityCode): array
    {
        return [
            'customer_name' => 'Cliente Fiscal',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '2222-3333',
            'delivery_method' => Quote::DELIVERY_ADDRESS,
            'shipping_address' => 'Colonia de prueba, calle principal #1',
            'requires_fiscal_credit' => '1',
            'fiscal_legal_name' => 'Cliente Fiscal, S.A. de C.V.',
            'fiscal_nit' => '0614-010199-101-2',
            'fiscal_nrc' => '123456-7',
            'fiscal_activity_code' => $activityCode,
            'fiscal_activity_description' => 'Descripción enviada por el formulario',
            'fiscal_department_code' => '06',
            'fiscal_municipality_code' => '23',
            'fiscal_district_code' => '14',
            'fiscal_address' => 'Colonia de prueba, calle principal #1',
            'fiscal_phone' => '2222-3333',
            'fiscal_email' => 'facturacion@example.com',
        ];
    }

    private function product(): Product
    {
        return Product::create([
            'sku' => 'ACTIVITY-CATALOG-SKU',
            'name' => 'Producto con crédito fiscal',
            'price' => 50,
            'qty' => 10,
            'is_active' => true,
        ]);
    }

    private function cartFor(Product $product): array
    {
        return [
            'product-'.$product->id => [
                'type' => 'product',
                'id' => $product->id,
                'qty' => 1,
            ],
        ];
    }
}
