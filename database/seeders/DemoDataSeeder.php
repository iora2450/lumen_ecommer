<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // ── Settings ───────────────────────────────────────────────
        $settings = [
            ['key' => 'site_name',           'value' => 'Lumens',                 'group' => 'general'],
            ['key' => 'site_email',          'value' => 'ventas@lumens.local',    'group' => 'general'],
            ['key' => 'site_phone',          'value' => '+503 2222 3333',         'group' => 'general'],
            ['key' => 'site_address',        'value' => 'San Salvador, El Salvador', 'group' => 'general'],
            ['key' => 'primary_color',       'value' => '#203749',              'group' => 'theme'],
            ['key' => 'primary_dark_color',  'value' => '#203749',              'group' => 'theme'],
            ['key' => 'accent_color',        'value' => '#FFAE00',              'group' => 'theme'],
            ['key' => 'quote_min_days',      'value' => '3',                      'group' => 'quotes'],
            ['key' => 'sync_api_key',        'value' => Str::random(40),          'group' => 'sync'],
            ['key' => 'sync_last_run',       'value' => now()->toIso8601String(), 'group' => 'sync'],
        ];
        foreach ($settings as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }

        // ── Categorías ────────────────────────────────────────────
        $categories = [
            ['name' => 'Iluminación exterior', 'description' => 'Área, calles, estacionamientos y fachadas.', 'sort_order' => 1],
            ['name' => 'High Bay',             'description' => 'Soluciones eficientes para bodegas e industria.', 'sort_order' => 2],
            ['name' => 'Paneles LED',          'description' => 'Iluminación uniforme para oficinas y comercios.', 'sort_order' => 3],
            ['name' => 'Emergencia',           'description' => 'Respaldo, señalización y rutas de evacuación.', 'sort_order' => 4],
            ['name' => 'Reflectores',          'description' => 'Reflectores LED de alta potencia para exteriores.', 'sort_order' => 5],
            ['name' => 'Luminarias lineales',  'description' => 'Luminarias LED lineales para aplicaciones comerciales.', 'sort_order' => 6],
        ];
        $catModels = [];
        foreach ($categories as $c) {
            $catModels[] = Category::updateOrCreate(
                ['slug' => Str::slug($c['name'])],
                array_merge($c, ['slug' => Str::slug($c['name']), 'is_active' => true])
            );
        }

        // ── Marcas ────────────────────────────────────────────────
        $brands = [
            ['name' => 'Lumens Pro',    'description' => 'Nuestra marca premium para proyectos comerciales.'],
            ['name' => 'Acuity Brands', 'description' => 'Distribuidor autorizado en El Salvador.'],
            ['name' => 'Eaton',         'description' => 'Soluciones de iluminación industrial.'],
        ];
        $brandModels = [];
        foreach ($brands as $b) {
            $brandModels[] = Brand::updateOrCreate(
                ['slug' => Str::slug($b['name'])],
                array_merge($b, ['slug' => Str::slug($b['name']), 'is_active' => true])
            );
        }

        // ── Productos (basados en el home actual) ──────────────────
        $products = [
            [
                'sku'   => 'HLBPH4069FS1EMWR',
                'name'  => 'Ojo de buey LED 4"',
                'description' => 'Luminaria empotrable tipo ojo de buey de 4 pulgadas con tecnología LED de alta eficiencia.',
                'price' => 18.50,
                'cost'  => 9.20,
                'qty'   => 250,
                'category_id' => $catModels[2]->id,
                'brand_id'    => $brandModels[0]->id,
                'is_featured' => true,
                'image_url'   => '/images/products/ojo-de-buey.png',
                'specs' => [
                    'wattage'    => '10W',
                    'lumens'     => '600 lm',
                    'cct'        => '2700K-5000K',
                    'voltage'    => '120V',
                    'ip_rating'  => 'IP44',
                ],
                'tags' => ['indoor', 'recessed', 'dimmable'],
                'certifications' => ['UL', 'DLC'],
            ],
            [
                'sku'   => '22CGTS-L3C3',
                'name'  => 'Panel LED 2x2 seleccionable',
                'description' => 'Panel LED 2x2 con temperatura de color y potencia seleccionables. Ideal para oficinas.',
                'price' => 65.00,
                'cost'  => 32.00,
                'qty'   => 180,
                'category_id' => $catModels[2]->id,
                'brand_id'    => $brandModels[1]->id,
                'is_featured' => true,
                'image_url'   => '/images/products/panel-2x2.png',
                'specs' => [
                    'wattage'   => '21/30/40W',
                    'voltage'   => '120-277V',
                    'cct'       => '3500K-5000K',
                    'efficacy'  => '125 lm/W',
                ],
                'tags' => ['indoor', 'office', 'selectable'],
                'certifications' => ['UL', 'DLC', 'FCC'],
            ],
            [
                'sku'   => 'UHBS-2436-MV-L84050-U',
                'name'  => 'High Bay UHBS 2436',
                'description' => 'Luminaria High Bay de alto rendimiento para bodegas e industria.',
                'price' => 245.00,
                'cost'  => 145.00,
                'qty'   => 85,
                'category_id' => $catModels[1]->id,
                'brand_id'    => $brandModels[1]->id,
                'is_featured' => true,
                'image_url'   => '/images/products/high-bay.png',
                'specs' => [
                    'wattage'  => '152-245W',
                    'lumens'   => '24,825-37,167 lm',
                    'cct'      => '4000K-5000K',
                    'voltage'  => '120-277V',
                ],
                'tags' => ['industrial', 'high-bay', 'warehouse'],
                'certifications' => ['UL', 'DLC Premium'],
            ],
            [
                'sku'   => 'ESXF1-ALO-SWW2-KY-DDB',
                'name'  => 'Reflector LED seleccionable',
                'description' => 'Reflector LED con potencia y CCT seleccionables. Resistente al agua IP66.',
                'price' => 89.50,
                'cost'  => 42.00,
                'qty'   => 140,
                'category_id' => $catModels[4]->id,
                'brand_id'    => $brandModels[1]->id,
                'is_featured' => true,
                'image_url'   => '/images/products/reflector.jpg',
                'specs' => [
                    'wattage'  => '9/19/34W',
                    'ip_rating'=> 'IP66',
                    'cct'      => '3000K-5000K',
                    'voltage'  => '120-277V',
                ],
                'tags' => ['outdoor', 'flood', 'selectable'],
                'certifications' => ['UL', 'DLC'],
            ],
            [
                'sku'   => '4SLSTPSLC-UNV',
                'name'  => 'Luminaria lineal de 4 pies',
                'description' => 'Luminaria lineal LED de 4 pies con múltiples opciones de potencia.',
                'price' => 72.00,
                'cost'  => 38.00,
                'qty'   => 200,
                'category_id' => $catModels[5]->id,
                'brand_id'    => $brandModels[2]->id,
                'is_featured' => false,
                'image_url'   => '/images/products/lineal.png',
                'specs' => [
                    'wattage' => '18/30/48W',
                    'voltage' => '120-277V',
                    'cct'     => '3500K-5000K',
                ],
                'tags' => ['indoor', 'linear', 'commercial'],
                'certifications' => ['UL'],
            ],
            [
                'sku'   => 'APEL',
                'name'  => 'Luminaria de emergencia APEL',
                'description' => 'Luminaria de emergencia con batería integrada. Hasta 90 minutos de autonomía.',
                'price' => 45.00,
                'cost'  => 22.00,
                'qty'   => 320,
                'category_id' => $catModels[3]->id,
                'brand_id'    => $brandModels[2]->id,
                'is_featured' => false,
                'image_url'   => '/images/products/emergencia.png',
                'specs' => [
                    'wattage'   => '1.5W',
                    'voltage'   => '120V',
                    'battery'   => 'Integrada',
                    'runtime'   => '90 min',
                ],
                'tags' => ['emergency', 'safety'],
                'certifications' => ['UL 924'],
            ],
            // ── Producto con promoción ──
            [
                'sku'   => 'AREA-LIGHT-150W',
                'name'  => 'Area Light 150W',
                'description' => 'Luminaria para estacionamientos y áreas exteriores.',
                'price' => 195.00,
                'cost'  => 110.00,
                'qty'   => 60,
                'category_id' => $catModels[0]->id,
                'brand_id'    => $brandModels[0]->id,
                'is_featured' => true,
                'is_promotion' => true,
                'promotion_price' => 159.00,
                'image_url'   => '/images/products/area-light.png',
                'specs' => [
                    'wattage'  => '150W',
                    'lumens'   => '21,000 lm',
                    'cct'      => '5000K',
                    'voltage'  => '120-277V',
                    'ip_rating'=> 'IP65',
                ],
                'tags' => ['outdoor', 'area-light', 'parking'],
                'certifications' => ['UL', 'DLC Premium'],
            ],
            // ── Producto con variantes ──
            [
                'sku'   => 'TROFFER-2X4',
                'name'  => 'Troffer LED 2x4',
                'description' => 'Luminaria troffer LED para cielos rasos de oficinas y comercios.',
                'price' => 78.00,
                'cost'  => 38.00,
                'qty'   => 0,
                'category_id' => $catModels[2]->id,
                'brand_id'    => $brandModels[1]->id,
                'is_featured' => false,
                'image_url'   => '/images/products/troffer-2x4.png',
                'specs' => [
                    'wattage' => '30/40/50W',
                    'voltage' => '120-277V',
                    'cct'     => '3500K-5000K',
                ],
                'tags' => ['indoor', 'troffer', 'office'],
                'certifications' => ['UL', 'DLC'],
            ],
        ];

        foreach ($products as $data) {
            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                array_merge($data, ['slug' => Str::slug($data['name'])])
            );

            // Imagen principal
            if (!empty($data['image_url'])) {
                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'is_primary' => true],
                    ['image_url' => $data['image_url'], 'sort_order' => 0]
                );
            }

            // Variantes para el troffer
            if ($product->sku === 'TROFFER-2X4') {
                $variants = [
                    ['sku' => 'TROFFER-2X4-30W', 'name' => '30W / 4000K', 'price' => 72.00, 'qty' => 45, 'attributes' => ['wattage' => '30W', 'cct' => '4000K']],
                    ['sku' => 'TROFFER-2X4-40W', 'name' => '40W / 4000K', 'price' => 78.00, 'qty' => 35, 'attributes' => ['wattage' => '40W', 'cct' => '4000K']],
                    ['sku' => 'TROFFER-2X4-50W', 'name' => '50W / 5000K', 'price' => 86.00, 'qty' => 20, 'attributes' => ['wattage' => '50W', 'cct' => '5000K']],
                ];
                foreach ($variants as $v) {
                    ProductVariant::updateOrCreate(
                        ['product_id' => $product->id, 'sku' => $v['sku']],
                        array_merge($v, ['product_id' => $product->id, 'is_active' => true])
                    );
                }
            }
        }

        $this->command->info('✅ Demo data seeded: ' . Category::count() . ' categories, ' . Brand::count() . ' brands, ' . Product::count() . ' products, ' . ProductVariant::count() . ' variants');
    }
}
