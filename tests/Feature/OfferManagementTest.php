<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_must_enter_a_lower_price_when_enabling_an_offer(): void
    {
        $product = $this->product('Oferta administrable', 100);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->patch(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => 100,
                'is_active' => '1',
                'is_promotion' => '1',
            ])
            ->assertSessionHasErrors(['promotion_price']);

        $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->patch(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => 100,
                'promotion_price' => 100,
                'is_active' => '1',
                'is_promotion' => '1',
            ])
            ->assertSessionHasErrors(['promotion_price']);

        $this->actingAs($admin)
            ->patch(route('admin.products.update', $product), [
                'name' => $product->name,
                'price' => 100,
                'promotion_price' => 75,
                'promotion_starts_at' => now()->subHour()->format('Y-m-d\TH:i'),
                'promotion_ends_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'is_active' => '1',
                'is_promotion' => '1',
            ])
            ->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();

        $this->assertTrue($product->is_promotion);
        $this->assertTrue($product->is_on_sale);
        $this->assertSame('75.00', $product->promotion_price);
        $this->assertSame(25, $product->discount_percentage);
    }

    public function test_public_offer_pages_only_show_current_valid_promotions(): void
    {
        $active = $this->product('Oferta activa', 100, [
            'is_promotion' => true,
            'promotion_price' => 75,
            'promotion_starts_at' => now()->subHour(),
            'promotion_ends_at' => now()->addHour(),
        ]);
        $scheduled = $this->product('Oferta programada', 100, [
            'is_promotion' => true,
            'promotion_price' => 70,
            'promotion_starts_at' => now()->addHour(),
        ]);
        $expired = $this->product('Oferta vencida', 100, [
            'is_promotion' => true,
            'promotion_price' => 65,
            'promotion_ends_at' => now()->subHour(),
        ]);
        $invalid = $this->product('Oferta sin rebaja', 100, [
            'is_promotion' => true,
            'promotion_price' => 100,
        ]);

        $this->get(route('offers.index'))
            ->assertOk()
            ->assertSee($active->name)
            ->assertSee('25%')
            ->assertSee('Descuento')
            ->assertSee('Ahorras $25.00')
            ->assertDontSee($scheduled->name)
            ->assertDontSee($expired->name)
            ->assertDontSee($invalid->name);

        $this->get(route('catalog.index', ['on_sale' => 1]))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($scheduled->name)
            ->assertDontSee($expired->name)
            ->assertDontSee($invalid->name);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Ofertas activas')
            ->assertSee($active->name)
            ->assertDontSee($scheduled->name);
    }

    public function test_promotional_price_is_used_in_the_cart(): void
    {
        $product = $this->product('Producto rebajado', 100, [
            'is_promotion' => true,
            'promotion_price' => 75,
        ]);

        $this->post(route('cart.add', $product), ['qty' => 2])
            ->assertSessionHas('success');

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('$75.00')
            ->assertSee('$150.00');
    }

    private function product(string $name, float $price, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'sku' => 'OFFER-'.str($name)->slug()->upper(),
            'name' => $name,
            'slug' => str($name)->slug(),
            'price' => $price,
            'qty' => 10,
            'is_active' => true,
        ], $attributes));
    }
}
