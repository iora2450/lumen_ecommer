<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_out_of_stock_product_invites_customer_to_request_an_import_purchase(): void
    {
        $product = Product::create([
            'sku' => 'IMPORT-TEST-SKU',
            'name' => 'Luminaria para importar',
            'price' => 100,
            'qty' => 0,
            'is_active' => true,
        ]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee('Sin existencias')
            ->assertSee('Solicita una compra por importación.')
            ->assertSee('Solicitar importación');

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Producto sin existencias')
            ->assertSee('Solicita una compra por importación')
            ->assertSee('Solicitar compra por importación');
    }

    public function test_in_stock_product_keeps_the_standard_add_to_cart_action(): void
    {
        $product = Product::create([
            'sku' => 'STOCK-TEST-SKU',
            'name' => 'Luminaria disponible',
            'price' => 100,
            'qty' => 12,
            'is_active' => true,
        ]);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('EN STOCK (12)')
            ->assertSee('Agregar al carrito')
            ->assertDontSee('Producto sin existencias');
    }
}
