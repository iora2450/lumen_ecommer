<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryWebVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_hide_and_show_a_category_from_the_category_list(): void
    {
        $category = $this->category('Honorarios profesionales');
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Visible')
            ->assertSee('Ocultar de web');

        $this->actingAs($admin)
            ->from(route('admin.categories.index'))
            ->patch(route('admin.categories.visibility', $category), [
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success', 'La categoría Honorarios profesionales y sus productos se ocultaron de la web.');

        $this->assertFalse($category->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.categories.visibility', $category), [
                'is_active' => '1',
            ]);

        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_products_in_a_hidden_category_are_not_available_anywhere_on_the_storefront(): void
    {
        $hiddenCategory = $this->category('Honorarios', false);
        $visibleCategory = $this->category('Iluminación', true);
        $hiddenProduct = $this->product('Servicio reservado', $hiddenCategory, true);
        $visibleProduct = $this->product('Panel visible', $visibleCategory, true);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee($visibleProduct->name)
            ->assertSee($visibleCategory->name)
            ->assertDontSee($hiddenProduct->name)
            ->assertDontSee($hiddenCategory->name);

        $this->get(route('catalog.show', $hiddenProduct->slug))->assertNotFound();
        $this->post(route('cart.add', $hiddenProduct))->assertNotFound();

        $this->getJson(route('api.products.index'))
            ->assertOk()
            ->assertJsonFragment(['name' => $visibleProduct->name])
            ->assertJsonMissing(['name' => $hiddenProduct->name]);

        $this->getJson(route('api.products.show', $hiddenProduct->slug))->assertNotFound();
        $this->getJson(route('api.categories.index'))
            ->assertOk()
            ->assertJsonMissing(['name' => $hiddenCategory->name]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($visibleProduct->name)
            ->assertDontSee($hiddenProduct->name);
    }

    private function category(string $name, bool $isActive = true): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => str($name)->slug(),
            'is_active' => $isActive,
        ]);
    }

    private function product(string $name, Category $category, bool $featured = false): Product
    {
        return Product::create([
            'sku' => 'TEST-'.str($name)->slug()->upper(),
            'name' => $name,
            'slug' => str($name)->slug(),
            'price' => 100,
            'qty' => 10,
            'category_id' => $category->id,
            'is_featured' => $featured,
            'is_active' => true,
        ]);
    }
}
