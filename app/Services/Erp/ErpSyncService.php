<?php

namespace App\Services\Erp;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDocument;
use App\Models\ProductImage;
use App\Models\ProductRelated;
use App\Models\ProductTechnicalSpec;
use App\Models\ProductVariant;
use App\Models\ProductVariantImage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio central de sincronizacion ERP.
 *
 * Usado tanto por el endpoint push (webhook entrante) como por
 * el pull (cliente HTTP saliente). Contiene toda la logica de
 * upsert de categorias, marcas, productos, variantes, imagenes,
 * especificaciones tecnicas, documentos y productos relacionados.
 */
class ErpSyncService
{
    public function syncCategories(array $items): SyncResult
    {
        $result = new SyncResult();

        foreach ($items as $c) {
            try {
                $erpId = $c['erp_id'] ?? $c['id'] ?? null;
                if (!$erpId || empty($c['name'])) {
                    $result->addError('category', $erpId, null, 'missing_required_field', 'erp_id o name faltante');
                    continue;
                }

                $existing = Category::where('lumen_id', $erpId)->first();
                $parentErpId = $c['parent_erp_id'] ?? null;
                Category::updateOrCreate(
                    ['lumen_id' => $erpId],
                    [
                        'name'           => $c['name'],
                        'slug'           => $c['slug'] ?? Str::slug($c['name']) . '-' . $erpId,
                        'description'    => $c['description'] ?? $existing?->description,
                        'image_url'      => $c['image_url'] ?? $existing?->image_url,
                        'parent_id'      => $parentErpId
                            ? Category::where('lumen_id', $parentErpId)->value('id')
                            : null,
                        'sort_order'     => isset($c['sort_order']) ? (int) $c['sort_order'] : (int) ($existing?->sort_order ?? 0),
                        'is_active'      => $existing ? $existing->is_active : (bool) ($c['is_active'] ?? true),
                    ]
                );

                $existing ? $result->updated++ : $result->created++;
            } catch (\Throwable $e) {
                $result->addError('category', $erpId ?? null, null, 'server_error', $e->getMessage());
            }
        }

        $result->summary = [
            'categories_created' => $result->created,
            'categories_updated' => $result->updated,
        ];

        return $result;
    }

    public function syncBrands(array $items): SyncResult
    {
        $result = new SyncResult();

        foreach ($items as $b) {
            try {
                $erpId = $b['erp_id'] ?? $b['id'] ?? null;
                if (!$erpId || empty($b['name'])) {
                    $result->addError('brand', $erpId, null, 'missing_required_field', 'erp_id o name faltante');
                    continue;
                }

                $existing = Brand::where('lumen_id', $erpId)->first();
                Brand::updateOrCreate(
                    ['lumen_id' => $erpId],
                    [
                        'name'        => $b['name'],
                        'slug'        => $b['slug'] ?? Str::slug($b['name']) . '-' . $erpId,
                        'logo_url'    => $b['logo_url'] ?? null,
                        'description' => $b['description'] ?? null,
                        'is_active'   => (bool) ($b['is_active'] ?? true),
                    ]
                );

                $existing ? $result->updated++ : $result->created++;
            } catch (\Throwable $e) {
                $result->addError('brand', $erpId ?? null, null, 'server_error', $e->getMessage());
            }
        }

        $result->summary = [
            'brands_created' => $result->created,
            'brands_updated' => $result->updated,
        ];

        return $result;
    }

    public function syncProducts(array $items, bool $includeRelations = true): SyncResult
    {
        $result = new SyncResult();

        foreach ($items as $p) {
            try {
                $erpId = $p['erp_id'] ?? $p['id'] ?? null;
                $sku   = $p['sku'] ?? $p['code'] ?? null;

                if (!$sku || empty($p['name'])) {
                    $result->addError('product', $erpId, $sku, 'missing_required_field', 'sku o name faltante');
                    continue;
                }

                $existingSku = Product::where('sku', $sku)
                    ->when($erpId, fn ($q) => $q->where('lumen_id', '!=', $erpId))
                    ->first();
                if ($existingSku) {
                    $result->addError('product', $erpId, $sku, 'duplicate_sku', 'El SKU ya existe en otro producto.');
                    continue;
                }

                $categoryId = $this->resolveCategoryId($p['category_erp_id'] ?? $p['category_id'] ?? null);
                $brandId    = $this->resolveBrandId($p['brand_erp_id'] ?? $p['brand_id'] ?? null);

                DB::transaction(function () use ($p, $erpId, $sku, $categoryId, $brandId, $result, $includeRelations) {
                    $existing = Product::when($erpId, fn ($q) => $q->where('lumen_id', $erpId))
                        ->orWhere('sku', $sku)
                        ->first();

                    $product = Product::updateOrCreate(
                        $erpId ? ['lumen_id' => $erpId] : ['sku' => $sku],
                        [
                            'lumen_id'      => $erpId,
                            'sku'           => $sku,
                            'name'          => $p['name'],
                            'slug'          => $existing?->slug ?? $p['slug'] ?? Str::slug($p['name']) . ($erpId ? '-' . $erpId : ''),
                            'short_description' => $existing?->short_description ?? $this->cleanRichText($p['short_description'] ?? null),
                            'description'   => $existing?->description ?? $this->cleanRichText($p['description'] ?? $p['product_details'] ?? null),
                            'price'         => (float) ($p['price'] ?? 0),
                            'compare_at_price' => isset($p['compare_at_price']) ? (float) $p['compare_at_price'] : null,
                            'cost'          => isset($p['cost']) ? (float) $p['cost'] : null,
                            'currency'      => $p['currency'] ?? 'USD',
                            'qty'           => (int) ($p['qty'] ?? 0),
                            'available_qty' => (int) ($p['available_qty'] ?? $p['qty'] ?? 0),
                            'reserved_qty'  => (int) ($p['reserved_qty'] ?? 0),
                            'backorder_qty' => (int) ($p['backorder_qty'] ?? 0),
                            'stock_status'  => $p['stock_status'] ?? $this->inferStockStatus((int) ($p['qty'] ?? 0)),
                            'image_url'     => $existing?->image_url ?? $this->firstImage($p['image_url'] ?? $p['image'] ?? null),
                            'category_id'   => $categoryId,
                            'brand_id'      => $brandId,
                            'is_featured'   => $existing ? $existing->is_featured : (bool) ($p['is_featured'] ?? $p['featured'] ?? false),
                            'is_promotion'  => $existing ? $existing->is_promotion : (bool) ($p['is_promotion'] ?? $p['promotion'] ?? false),
                            'promotion_price' => $existing ? $existing->promotion_price : (isset($p['promotion_price']) ? (float) $p['promotion_price'] : null),
                            'promotion_starts_at' => $existing ? $existing->promotion_starts_at : $this->parseDate($p['promotion_starts_at'] ?? null),
                            'promotion_ends_at'   => $existing ? $existing->promotion_ends_at : $this->parseDate($p['promotion_ends_at'] ?? null),
                            'specs'         => $p['specs'] ?? null,
                            'tags'          => $p['tags'] ?? null,
                            'certifications'=> $p['certifications'] ?? null,
                            'weight'        => $p['weight'] ?? null,
                            'dimensions'    => $p['dimensions'] ?? null,
                            'upc'           => $p['upc'] ?? null,
                            'mpn'           => $p['mpn'] ?? $p['code'] ?? null,
                            'is_active'     => $existing ? $existing->is_active : (bool) ($p['is_active'] ?? true),
                            'synced_at'     => now(),
                            'erp_last_sync_at' => now(),
                        ]
                    );

                    if ($includeRelations) {
                        if (!empty($p['images']) || !empty($p['image_url'])) {
                            $this->syncProductImages($product, $p['images'] ?? [], $p['image_url'] ?? null);
                        }
                        if (!empty($p['variants'])) {
                            $this->syncVariants($product, $p['variants']);
                        }
                        if (!empty($p['technical_specs'])) {
                            $this->syncTechnicalSpecs($product, $p['technical_specs']);
                        }
                        if (!empty($p['documents'])) {
                            $this->syncDocuments($product, $p['documents']);
                        }
                        if (!empty($p['related_products'])) {
                            $this->syncRelated($product, $p['related_products']);
                        }
                    }

                    $existing ? $result->updated++ : $result->created++;
                });
            } catch (\Throwable $e) {
                $result->addError('product', $erpId, $sku, 'server_error', $e->getMessage());
            }
        }

        $result->summary = [
            'products_created' => $result->created,
            'products_updated' => $result->updated,
        ];

        return $result;
    }

    public function syncInventory(array $items): SyncResult
    {
        $result = new SyncResult();

        foreach ($items as $i) {
            try {
                $erpId = $i['erp_id'] ?? null;
                $sku   = $i['sku'] ?? null;

                $product = null;
                if ($erpId) {
                    $product = Product::where('lumen_id', $erpId)->first();
                }
                if (!$product && $sku) {
                    $product = Product::where('sku', $sku)->first();
                }

                if (!$product) {
                    $result->addError('inventory', $erpId, $sku, 'product_not_found', 'Producto no encontrado.');
                    continue;
                }

                $qty = (int) ($i['qty'] ?? 0);
                $product->update([
                    'qty'           => $qty,
                    'available_qty' => (int) ($i['available_qty'] ?? max(0, $qty - ($i['reserved_qty'] ?? 0))),
                    'reserved_qty'  => (int) ($i['reserved_qty'] ?? 0),
                    'backorder_qty' => (int) ($i['backorder_qty'] ?? 0),
                    'stock_status'  => $i['stock_status'] ?? $this->inferStockStatus($qty),
                    'erp_last_sync_at' => now(),
                ]);

                $result->updated++;
            } catch (\Throwable $e) {
                $result->addError('inventory', $i['erp_id'] ?? null, $i['sku'] ?? null, 'server_error', $e->getMessage());
            }
        }

        $result->summary = ['inventory_updated' => $result->updated];
        return $result;
    }

    public function syncPrices(array $items): SyncResult
    {
        $result = new SyncResult();

        foreach ($items as $i) {
            try {
                $erpId = $i['erp_id'] ?? null;
                $sku   = $i['sku'] ?? null;

                $product = $erpId ? Product::where('lumen_id', $erpId)->first() : null;
                if (!$product && $sku) {
                    $product = Product::where('sku', $sku)->first();
                }
                if (!$product) {
                    $result->addError('price', $erpId, $sku, 'product_not_found', 'Producto no encontrado.');
                    continue;
                }

                $promotionPrice = isset($i['promotion_price']) ? (float) $i['promotion_price'] : null;
                $hasPromotion = (bool) ($i['is_promotion'] ?? ($promotionPrice !== null));

                $product->update([
                    'price'         => (float) ($i['price'] ?? $product->price),
                    'compare_at_price' => isset($i['compare_at_price']) ? (float) $i['compare_at_price'] : null,
                    'cost'          => isset($i['cost']) ? (float) $i['cost'] : null,
                    'is_promotion'  => $hasPromotion,
                    'promotion_price' => $promotionPrice,
                    'promotion_starts_at' => $this->parseDate($i['promotion_starts_at'] ?? null),
                    'promotion_ends_at'   => $this->parseDate($i['promotion_ends_at'] ?? null),
                    'erp_last_sync_at' => now(),
                ]);

                $result->updated++;
            } catch (\Throwable $e) {
                $result->addError('price', $i['erp_id'] ?? null, $i['sku'] ?? null, 'server_error', $e->getMessage());
            }
        }

        $result->summary = ['prices_updated' => $result->updated];
        return $result;
    }

    protected function syncProductImages(Product $product, array $images, ?string $primaryUrl): void
    {
        $product->images()->delete();
        $rows = [];
        $primaryUrl = $this->firstImage($primaryUrl);

        if ($images) {
            foreach ($images as $idx => $img) {
                $rows[] = [
                    'product_id' => $product->id,
                    'image_url'  => $this->firstImage(is_array($img) ? ($img['url'] ?? '') : $img) ?? '',
                    'alt'        => is_array($img) ? ($img['alt'] ?? null) : null,
                    'sort_order' => is_array($img) ? (int) ($img['sort_order'] ?? $idx) : $idx,
                    'is_primary' => is_array($img) ? (bool) ($img['is_primary'] ?? false) : ($idx === 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        } elseif ($primaryUrl) {
            $rows[] = [
                'product_id' => $product->id,
                'image_url'  => $primaryUrl,
                'alt'        => $product->name,
                'sort_order' => 0,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            $hasPrimary = collect($rows)->contains('is_primary', true);
            if (!$hasPrimary) {
                $rows[0]['is_primary'] = true;
            }
            ProductImage::insert($rows);
        }
    }

    protected function syncVariants(Product $product, array $variants, bool $replace = true): void
    {
        $incomingIds = [];

        foreach ($variants as $v) {
            $vErpId = $v['erp_id'] ?? $v['id'] ?? null;
            $sku    = $v['sku'] ?? null;
            $name   = $v['name'] ?? null;

            if (!$sku || !$name) {
                continue;
            }

            $payload = [
                'product_id'      => $product->id,
                'lumen_variant_id'=> $vErpId,
                'sku'             => $sku,
                'name'            => $name,
                'price'           => (float) ($v['price'] ?? $product->price),
                'compare_at_price'=> isset($v['compare_at_price']) ? (float) $v['compare_at_price'] : null,
                'cost'            => isset($v['cost']) ? (float) $v['cost'] : null,
                'qty'             => (int) ($v['qty'] ?? 0),
                'available_qty'   => (int) ($v['available_qty'] ?? $v['qty'] ?? 0),
                'reserved_qty'    => (int) ($v['reserved_qty'] ?? 0),
                'stock_status'    => $v['stock_status'] ?? $this->inferStockStatus((int) ($v['qty'] ?? 0)),
                'attributes'      => $v['attributes'] ?? null,
                'is_active'       => (bool) ($v['is_active'] ?? true),
                'erp_last_sync_at'=> now(),
            ];

            $match = $vErpId
                ? ['lumen_variant_id' => $vErpId]
                : ['sku' => $sku];

            $variant = ProductVariant::updateOrCreate($match, $payload);
            $incomingIds[] = $variant->id;

            if (!empty($v['images']) || !empty($v['image_url'])) {
                $this->syncVariantImages($variant, $v['images'] ?? [], $v['image_url'] ?? null);
            }
        }

        // Solo reemplazamos si llegan variantes en el payload (incluso [] vacio)
        if ($replace && $incomingIds) {
            $product->variants()->whereNotIn('id', $incomingIds)->delete();
        }
    }

    protected function syncVariantImages(ProductVariant $variant, array $images, ?string $primaryUrl): void
    {
        $variant->images()->delete();
        $rows = [];
        $primaryUrl = $this->firstImage($primaryUrl);

        if ($images) {
            foreach ($images as $idx => $img) {
                $rows[] = [
                    'product_variant_id' => $variant->id,
                    'image_url' => $this->firstImage(is_array($img) ? ($img['url'] ?? '') : $img) ?? '',
                    'alt'       => is_array($img) ? ($img['alt'] ?? null) : null,
                    'sort_order'=> is_array($img) ? (int) ($img['sort_order'] ?? $idx) : $idx,
                    'is_primary'=> is_array($img) ? (bool) ($img['is_primary'] ?? false) : ($idx === 0),
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ];
            }
        } elseif ($primaryUrl) {
            $rows[] = [
                'product_variant_id' => $variant->id,
                'image_url' => $primaryUrl,
                'alt'       => $variant->name,
                'sort_order'=> 0,
                'is_primary'=> true,
                'created_at'=> now(),
                'updated_at'=> now(),
            ];
        }

        if ($rows) {
            ProductVariantImage::insert($rows);
        }
    }

    protected function syncTechnicalSpecs(Product $product, array $specs): void
    {
        $product->technicalSpecs()->delete();
        $rows = [];
        foreach ($specs as $idx => $s) {
            $rows[] = [
                'product_id' => $product->id,
                'group'      => $s['group'] ?? null,
                'key'        => $s['key'] ?? null,
                'label'      => $s['label'] ?? null,
                'value'      => $s['value'] ?? null,
                'unit'       => $s['unit'] ?? null,
                'sort_order' => (int) ($s['sort_order'] ?? $idx),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if ($rows) {
            ProductTechnicalSpec::insert($rows);
        }
    }

    protected function syncDocuments(Product $product, array $documents): void
    {
        $product->documents()->delete();
        $rows = [];
        foreach ($documents as $idx => $d) {
            $rows[] = [
                'product_id' => $product->id,
                'type'       => $d['type'] ?? 'datasheet',
                'title'      => $d['title'] ?? 'Documento',
                'url'        => $d['url'] ?? null,
                'language'   => $d['language'] ?? 'es',
                'sort_order' => (int) ($d['sort_order'] ?? $idx),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        if ($rows) {
            ProductDocument::insert($rows);
        }
    }

    protected function syncRelated(Product $product, array $related): void
    {
        $product->relatedProducts()->delete();
        $rows = [];
        foreach ($related as $idx => $r) {
            $erpId = $r['erp_id'] ?? null;
            $sku   = $r['sku'] ?? null;

            $resolved = false;
            if ($erpId) {
                $resolved = Product::where('lumen_id', $erpId)->exists();
            } elseif ($sku) {
                $resolved = Product::where('sku', $sku)->exists();
            }

            $rows[] = [
                'product_id'    => $product->id,
                'related_erp_id'=> $erpId,
                'related_sku'   => $sku,
                'relation_type' => $r['relation_type'] ?? 'similar',
                'sort_order'    => (int) ($r['sort_order'] ?? $idx),
                'resolved_at'   => $resolved ? now() : null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }
        if ($rows) {
            ProductRelated::insert($rows);
        }
    }

    protected function resolveCategoryId(?string $erpId): ?int
    {
        if (!$erpId) {
            return null;
        }
        return Category::where('lumen_id', $erpId)->value('id');
    }

    protected function resolveBrandId(?string $erpId): ?int
    {
        if (!$erpId) {
            return null;
        }
        return Brand::where('lumen_id', $erpId)->value('id');
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function cleanRichText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/<\s*br\s*\/?>/i', ' ', $text);
        $text = preg_replace('/<\s*\/?(p|div|span|li|ul|ol)[^>]*>/i', ' ', $text);
        $text = strip_tags($text);
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text) ?: null;
    }

    protected function firstImage(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $image = trim(explode(',', $value)[0]);

        return $image !== '' ? $image : null;
    }

    protected function inferStockStatus(int $qty): string
    {
        return match (true) {
            $qty <= 0 => 'out_of_stock',
            $qty < 10 => 'low_stock',
            default => 'in_stock',
        };
    }
}
