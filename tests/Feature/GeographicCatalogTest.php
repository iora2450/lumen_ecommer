<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Quote;
use App\Support\GeographicCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GeographicCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
    }

    public function test_official_geographic_catalogs_are_available_in_checkout(): void
    {
        $catalog = app(GeographicCatalog::class);
        $product = $this->product();

        $this->assertCount(15, $catalog->departments());
        $this->assertCount(45, $catalog->municipalities());
        $this->assertCount(263, $catalog->districts());
        $this->assertSame('San Salvador', $catalog->findDepartment('06')['nombre']);
        $this->assertSame('SAN SALVADOR CENTRO', $catalog->findMunicipality('06', '23')['nombre']);
        $this->assertSame('SAN SALVADOR', $catalog->findDistrict('06', '14')['nombre']);

        $this->withSession(['cart.items' => $this->cartFor($product)])
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('name="fiscal_department_code"', false)
            ->assertSee('name="fiscal_municipality_code"', false)
            ->assertSee('name="fiscal_district_code"', false)
            ->assertSee('San Salvador')
            ->assertSee('SAN SALVADOR CENTRO')
            ->assertSee('SAN SALVADOR');
    }

    public function test_checkout_rejects_municipality_and_district_from_another_department(): void
    {
        $product = $this->product();

        $this->withSession(['cart.items' => $this->cartFor($product)])
            ->from(route('cart.index'))
            ->post(route('cart.checkout'), array_merge($this->fiscalData(), [
                'fiscal_municipality_code' => '14',
                'fiscal_district_code' => '33',
            ]))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHasErrors(['fiscal_municipality_code', 'fiscal_district_code']);

        $this->assertDatabaseCount('quotes', 0);
    }

    private function fiscalData(): array
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
            'fiscal_activity_code' => '46900',
            'fiscal_activity_description' => 'Venta al por mayor de otros productos',
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
            'sku' => 'GEOGRAPHIC-CATALOG-SKU',
            'name' => 'Producto con dirección fiscal',
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
