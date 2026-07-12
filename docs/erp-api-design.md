# Diseño de API ERP -> Lumens Ecommerce

Este documento define el contrato ideal para que el ERP alimente la pagina web de Lumens Ecommerce con productos, categorias, marcas, imagenes, variantes, especificaciones tecnicas, existencias y relaciones entre productos.

La idea es que el ERP pueda enviar lo que tenga disponible hoy, pero que el contrato deje claro todo lo que la web necesita para funcionar bien como catalogo comercial.

## Objetivo

Sincronizar desde el ERP hacia la web:

- Categorias de productos.
- Marcas.
- Productos principales.
- Variantes de productos.
- Imagenes.
- Especificaciones tecnicas.
- Certificaciones.
- Tags comerciales.
- Precios.
- Promociones.
- Existencias.
- Productos relacionados.
- Estado activo/inactivo.

## Base URL

```text
https://tudominio.com/api/sync
```

Para ambiente local:

```text
http://localhost:8090/api/sync
```

## Autenticacion

Todas las peticiones del ERP hacia la web deben enviar un token fijo o API key.

```http
Authorization: Bearer ERP_API_KEY
Content-Type: application/json
Accept: application/json
```

Tambien se puede usar:

```http
X-ERP-Token: ERP_API_KEY
```

## Reglas Generales

- El identificador principal del ERP debe enviarse como `erp_id`.
- El SKU debe ser unico.
- Si existe `erp_id`, la web actualiza el registro existente.
- Si no existe `erp_id`, la web puede buscar por `sku`.
- Si no existe ni `erp_id` ni `sku`, la web crea un producto nuevo solo si el payload es valido.
- Los campos que el ERP no tenga pueden enviarse como `null` o simplemente omitirse.
- La web no debe borrar productos ausentes en una sincronizacion parcial.
- Para desactivar productos se debe enviar `is_active: false`.
- Las cantidades deben venir como numeros enteros.
- Los precios deben venir como numeros decimales.
- Las fechas deben enviarse en formato ISO 8601.

Ejemplo:

```json
"updated_at": "2026-07-12T10:30:00-06:00"
```

## Endpoints Recomendados

### 1. Health Check

Verifica que la API este disponible.

```http
GET /api/sync/health
```

Respuesta:

```json
{
  "status": "ok",
  "service": "lumens-ecommerce",
  "timestamp": "2026-07-12T10:30:00-06:00"
}
```

### 2. Sincronizar Catalogo Completo

Endpoint recomendado para enviar varias entidades en una sola carga.

```http
POST /api/sync/catalog
```

Uso recomendado:

- Carga inicial.
- Sincronizacion nocturna.
- Recuperacion completa si hubo errores.

Payload:

```json
{
  "sync_id": "ERP-SYNC-20260712-001",
  "source": "erp",
  "mode": "upsert",
  "sent_at": "2026-07-12T10:30:00-06:00",
  "categories": [],
  "brands": [],
  "products": []
}
```

Respuesta:

```json
{
  "status": "success",
  "sync_id": "ERP-SYNC-20260712-001",
  "summary": {
    "categories_created": 2,
    "categories_updated": 4,
    "brands_created": 1,
    "brands_updated": 3,
    "products_created": 10,
    "products_updated": 120,
    "products_failed": 0
  },
  "errors": []
}
```

### 3. Sincronizar Productos

Endpoint para enviar solo productos.

```http
POST /api/sync/products
```

Payload:

```json
{
  "sync_id": "ERP-PRODUCTS-20260712-001",
  "mode": "upsert",
  "sent_at": "2026-07-12T10:30:00-06:00",
  "products": []
}
```

### 4. Sincronizar Existencias

Endpoint liviano para actualizar stock con mas frecuencia.

```http
POST /api/sync/inventory
```

Payload:

```json
{
  "sync_id": "ERP-STOCK-20260712-001",
  "warehouse": "principal",
  "sent_at": "2026-07-12T10:30:00-06:00",
  "items": [
    {
      "erp_id": "10025",
      "sku": "HLBPH4069FS1EMWR",
      "qty": 250,
      "available_qty": 240,
      "reserved_qty": 10,
      "backorder_qty": 0,
      "stock_status": "in_stock"
    }
  ]
}
```

Valores recomendados para `stock_status`:

```text
in_stock
low_stock
out_of_stock
backorder
discontinued
```

### 5. Sincronizar Precios

Endpoint separado para cambios de precio o promociones.

```http
POST /api/sync/prices
```

Payload:

```json
{
  "sync_id": "ERP-PRICES-20260712-001",
  "currency": "USD",
  "sent_at": "2026-07-12T10:30:00-06:00",
  "items": [
    {
      "erp_id": "10025",
      "sku": "HLBPH4069FS1EMWR",
      "price": 18.5,
      "compare_at_price": 22.0,
      "cost": 9.2,
      "is_promotion": true,
      "promotion_price": 16.99,
      "promotion_starts_at": "2026-07-12T00:00:00-06:00",
      "promotion_ends_at": "2026-07-31T23:59:59-06:00"
    }
  ]
}
```

## Estructura de Categoria

```json
{
  "erp_id": "CAT-001",
  "name": "Paneles LED",
  "slug": "paneles-led",
  "description": "Iluminacion uniforme para oficinas y comercios.",
  "image_url": "https://erp.example.com/images/categories/paneles-led.jpg",
  "parent_erp_id": null,
  "sort_order": 3,
  "is_active": true,
  "updated_at": "2026-07-12T10:30:00-06:00"
}
```

Campos importantes:

| Campo | Tipo | Requerido | Comentario |
| --- | --- | --- | --- |
| `erp_id` | string | Si | ID unico de categoria en ERP. |
| `name` | string | Si | Nombre comercial. |
| `slug` | string | No | Si no viene, la web lo genera. |
| `description` | string/null | No | Descripcion publica. |
| `image_url` | string/null | No | Imagen para categoria. |
| `parent_erp_id` | string/null | No | Para categorias jerarquicas. |
| `sort_order` | integer | No | Orden visual. |
| `is_active` | boolean | No | Si aparece en la web. |

## Estructura de Marca

```json
{
  "erp_id": "BRAND-001",
  "name": "Acuity Brands",
  "slug": "acuity-brands",
  "logo_url": "https://erp.example.com/images/brands/acuity.png",
  "description": "Soluciones de iluminacion comercial e industrial.",
  "is_active": true,
  "updated_at": "2026-07-12T10:30:00-06:00"
}
```

Campos importantes:

| Campo | Tipo | Requerido | Comentario |
| --- | --- | --- | --- |
| `erp_id` | string | Si | ID unico de marca en ERP. |
| `name` | string | Si | Nombre de la marca. |
| `slug` | string | No | Si no viene, la web lo genera. |
| `logo_url` | string/null | No | Logo publico. |
| `description` | string/null | No | Descripcion publica. |
| `is_active` | boolean | No | Si aparece en filtros y catalogo. |

## Estructura de Producto

```json
{
  "erp_id": "10025",
  "sku": "HLBPH4069FS1EMWR",
  "name": "Ojo de buey LED 4 pulgadas",
  "slug": "ojo-de-buey-led-4-pulgadas",
  "short_description": "Luminaria empotrable LED de alta eficiencia.",
  "description": "Luminaria empotrable tipo ojo de buey de 4 pulgadas con tecnologia LED de alta eficiencia para interiores comerciales y residenciales.",
  "category_erp_id": "CAT-003",
  "brand_erp_id": "BRAND-001",
  "price": 18.5,
  "compare_at_price": 22.0,
  "cost": 9.2,
  "currency": "USD",
  "qty": 250,
  "available_qty": 240,
  "reserved_qty": 10,
  "stock_status": "in_stock",
  "image_url": "https://erp.example.com/images/products/ojo-de-buey.png",
  "images": [],
  "variants": [],
  "specs": {},
  "technical_specs": [],
  "documents": [],
  "certifications": ["UL", "DLC"],
  "tags": ["indoor", "recessed", "dimmable"],
  "related_products": [],
  "is_featured": true,
  "is_promotion": false,
  "promotion_price": null,
  "weight": "0.45 kg",
  "dimensions": "12 cm x 12 cm x 6 cm",
  "upc": "123456789012",
  "mpn": "HLBPH4069FS1EMWR",
  "is_active": true,
  "updated_at": "2026-07-12T10:30:00-06:00"
}
```

Campos minimos para crear producto:

| Campo | Tipo | Requerido | Comentario |
| --- | --- | --- | --- |
| `erp_id` | string | Recomendado | ID unico del ERP. |
| `sku` | string | Si | Codigo unico. |
| `name` | string | Si | Nombre comercial. |
| `description` | string/null | No | Descripcion larga. |
| `price` | decimal | No | Precio de venta. Si no viene, usar `0`. |
| `qty` | integer | No | Existencia total. Si no viene, usar `0`. |
| `is_active` | boolean | No | Si no viene, usar `true`. |

Campos comerciales recomendados:

| Campo | Tipo | Comentario |
| --- | --- | --- |
| `short_description` | string/null | Resumen para cards o listados. |
| `compare_at_price` | decimal/null | Precio anterior o precio de referencia. |
| `is_promotion` | boolean | Indica si esta en promocion. |
| `promotion_price` | decimal/null | Precio promocional. |
| `is_featured` | boolean | Producto destacado en home. |
| `tags` | array | Etiquetas para busqueda y agrupaciones. |
| `certifications` | array | Certificaciones como UL, DLC, FCC. |

Campos tecnicos recomendados:

| Campo | Tipo | Comentario |
| --- | --- | --- |
| `specs` | object | Objeto simple clave/valor. |
| `technical_specs` | array | Lista ordenada con grupo, etiqueta, valor y unidad. |
| `documents` | array | Fichas tecnicas, manuales, certificados. |
| `weight` | string/null | Peso del producto. |
| `dimensions` | string/null | Dimensiones fisicas. |
| `upc` | string/null | Codigo UPC. |
| `mpn` | string/null | Codigo del fabricante. |

## Especificaciones Tecnicas

La web puede aceptar especificaciones en dos formatos.

### Formato simple

Este formato es facil de enviar desde un ERP:

```json
{
  "specs": {
    "wattage": "10W",
    "lumens": "600 lm",
    "cct": "2700K-5000K",
    "voltage": "120V",
    "ip_rating": "IP44",
    "dimmable": "Si",
    "installation": "Empotrable"
  }
}
```

### Formato detallado

Este formato es mejor para mostrar fichas tecnicas ordenadas:

```json
{
  "technical_specs": [
    {
      "group": "Electricas",
      "key": "wattage",
      "label": "Potencia",
      "value": "10",
      "unit": "W",
      "sort_order": 1
    },
    {
      "group": "Fotometricas",
      "key": "lumens",
      "label": "Flujo luminoso",
      "value": "600",
      "unit": "lm",
      "sort_order": 2
    },
    {
      "group": "Construccion",
      "key": "ip_rating",
      "label": "Proteccion IP",
      "value": "IP44",
      "unit": null,
      "sort_order": 3
    }
  ]
}
```

Campos de especificacion recomendados:

| Campo | Tipo | Comentario |
| --- | --- | --- |
| `group` | string/null | Grupo visual: Electricas, Fotometricas, Construccion, Instalacion. |
| `key` | string | Clave tecnica estable. |
| `label` | string | Nombre visible para cliente. |
| `value` | string/number/boolean | Valor. |
| `unit` | string/null | Unidad: W, lm, K, V, mm, kg. |
| `sort_order` | integer | Orden de aparicion. |

## Variantes

Las variantes representan presentaciones del mismo producto: potencia, color de luz, acabado, voltaje, tamano, etc.

```json
{
  "variants": [
    {
      "erp_id": "10025-30W-4000K",
      "sku": "TROFFER-2X4-30W-4000K",
      "name": "30W / 4000K",
      "price": 72.0,
      "compare_at_price": null,
      "cost": 38.0,
      "qty": 45,
      "available_qty": 42,
      "reserved_qty": 3,
      "stock_status": "in_stock",
      "attributes": {
        "wattage": "30W",
        "cct": "4000K",
        "voltage": "120-277V",
        "finish": "Blanco"
      },
      "image_url": "https://erp.example.com/images/products/troffer-30w.jpg",
      "is_active": true,
      "updated_at": "2026-07-12T10:30:00-06:00"
    }
  ]
}
```

Campos importantes:

| Campo | Tipo | Requerido | Comentario |
| --- | --- | --- | --- |
| `erp_id` | string | Recomendado | ID unico de variante en ERP. |
| `sku` | string | Si | SKU unico de variante. |
| `name` | string | Si | Nombre visible. |
| `price` | decimal/null | No | Si no viene, hereda precio del producto. |
| `qty` | integer | No | Existencia de la variante. |
| `attributes` | object | Si | Atributos que diferencian la variante. |
| `is_active` | boolean | No | Si se puede cotizar/mostrar. |

## Imagenes

```json
{
  "images": [
    {
      "url": "https://erp.example.com/images/products/ojo-de-buey.png",
      "alt": "Ojo de buey LED 4 pulgadas",
      "sort_order": 0,
      "is_primary": true
    },
    {
      "url": "https://erp.example.com/images/products/ojo-de-buey-side.png",
      "alt": "Vista lateral del ojo de buey LED",
      "sort_order": 1,
      "is_primary": false
    }
  ]
}
```

Reglas:

- Debe existir maximo una imagen con `is_primary: true`.
- Si no viene imagen primaria, la web puede usar la primera imagen.
- Las URLs deben ser publicas o accesibles por la web.
- Si el ERP no aloja imagenes publicas, debe enviarse una URL de CDN o un proceso separado de carga de archivos.

## Documentos Tecnicos

```json
{
  "documents": [
    {
      "type": "datasheet",
      "title": "Ficha tecnica",
      "url": "https://erp.example.com/docs/HLBPH4069FS1EMWR.pdf",
      "language": "es",
      "sort_order": 1
    },
    {
      "type": "certificate",
      "title": "Certificado UL",
      "url": "https://erp.example.com/docs/HLBPH4069FS1EMWR-ul.pdf",
      "language": "en",
      "sort_order": 2
    }
  ]
}
```

Tipos recomendados:

```text
datasheet
manual
certificate
ies
warranty
installation_guide
```

## Productos Relacionados

```json
{
  "related_products": [
    {
      "erp_id": "10080",
      "sku": "PANEL-LED-2X2",
      "relation_type": "similar",
      "sort_order": 1
    },
    {
      "erp_id": "10081",
      "sku": "DRIVER-LED-40W",
      "relation_type": "accessory",
      "sort_order": 2
    }
  ]
}
```

Tipos recomendados:

```text
similar
accessory
replacement
upsell
cross_sell
same_family
required_component
```

Reglas:

- La relacion puede resolverse por `erp_id` o por `sku`.
- Si el producto relacionado no existe todavia, la web puede guardar la relacion pendiente y resolverla en la siguiente sincronizacion.

## Payload Completo de Ejemplo

```json
{
  "sync_id": "ERP-SYNC-20260712-001",
  "source": "erp",
  "mode": "upsert",
  "sent_at": "2026-07-12T10:30:00-06:00",
  "categories": [
    {
      "erp_id": "CAT-003",
      "name": "Paneles LED",
      "slug": "paneles-led",
      "description": "Iluminacion uniforme para oficinas y comercios.",
      "image_url": null,
      "parent_erp_id": null,
      "sort_order": 3,
      "is_active": true,
      "updated_at": "2026-07-12T10:30:00-06:00"
    }
  ],
  "brands": [
    {
      "erp_id": "BRAND-001",
      "name": "Lumens Pro",
      "slug": "lumens-pro",
      "logo_url": null,
      "description": "Marca premium para proyectos comerciales.",
      "is_active": true,
      "updated_at": "2026-07-12T10:30:00-06:00"
    }
  ],
  "products": [
    {
      "erp_id": "10025",
      "sku": "TROFFER-2X4",
      "name": "Troffer LED 2x4",
      "slug": "troffer-led-2x4",
      "short_description": "Luminaria troffer LED para cielos rasos.",
      "description": "Luminaria troffer LED para cielos rasos de oficinas y comercios.",
      "category_erp_id": "CAT-003",
      "brand_erp_id": "BRAND-001",
      "price": 78.0,
      "compare_at_price": null,
      "cost": 38.0,
      "currency": "USD",
      "qty": 100,
      "available_qty": 95,
      "reserved_qty": 5,
      "stock_status": "in_stock",
      "image_url": "https://erp.example.com/images/products/troffer-2x4.png",
      "images": [
        {
          "url": "https://erp.example.com/images/products/troffer-2x4.png",
          "alt": "Troffer LED 2x4",
          "sort_order": 0,
          "is_primary": true
        }
      ],
      "variants": [
        {
          "erp_id": "10025-30W",
          "sku": "TROFFER-2X4-30W",
          "name": "30W / 4000K",
          "price": 72.0,
          "compare_at_price": null,
          "cost": 36.0,
          "qty": 45,
          "available_qty": 42,
          "reserved_qty": 3,
          "stock_status": "in_stock",
          "attributes": {
            "wattage": "30W",
            "cct": "4000K"
          },
          "image_url": null,
          "is_active": true,
          "updated_at": "2026-07-12T10:30:00-06:00"
        }
      ],
      "specs": {
        "wattage": "30/40/50W",
        "voltage": "120-277V",
        "cct": "3500K-5000K",
        "installation": "Cielo falso"
      },
      "technical_specs": [
        {
          "group": "Electricas",
          "key": "voltage",
          "label": "Voltaje",
          "value": "120-277",
          "unit": "V",
          "sort_order": 1
        }
      ],
      "documents": [
        {
          "type": "datasheet",
          "title": "Ficha tecnica",
          "url": "https://erp.example.com/docs/troffer-2x4.pdf",
          "language": "es",
          "sort_order": 1
        }
      ],
      "certifications": ["UL", "DLC"],
      "tags": ["indoor", "troffer", "office"],
      "related_products": [
        {
          "erp_id": "10026",
          "sku": "PANEL-LED-2X2",
          "relation_type": "similar",
          "sort_order": 1
        }
      ],
      "is_featured": false,
      "is_promotion": false,
      "promotion_price": null,
      "weight": "2.1 kg",
      "dimensions": "2 ft x 4 ft",
      "upc": null,
      "mpn": "TROFFER-2X4",
      "is_active": true,
      "updated_at": "2026-07-12T10:30:00-06:00"
    }
  ]
}
```

## Validaciones Recomendadas

### Producto

- `sku` requerido y unico.
- `name` requerido.
- `price` debe ser mayor o igual a `0`.
- `qty` debe ser entero mayor o igual a `0`.
- `promotion_price` debe ser menor que `price` cuando `is_promotion` sea `true`.
- `category_erp_id` debe existir o venir en el mismo payload.
- `brand_erp_id` debe existir o venir en el mismo payload.

### Variante

- `sku` requerido y unico.
- `name` requerido.
- `attributes` recomendado.
- `qty` debe ser entero mayor o igual a `0`.
- Si la variante tiene precio propio, `price` debe ser mayor o igual a `0`.

### Imagen

- `url` requerido.
- `sort_order` entero.
- Solo una imagen primaria por producto.

## Manejo de Errores

Respuesta cuando algunos productos fallan:

```json
{
  "status": "partial_success",
  "sync_id": "ERP-SYNC-20260712-001",
  "summary": {
    "products_created": 10,
    "products_updated": 118,
    "products_failed": 2
  },
  "errors": [
    {
      "entity": "product",
      "erp_id": "10099",
      "sku": "DUPLICATED-SKU",
      "code": "duplicate_sku",
      "message": "El SKU ya existe en otro producto."
    },
    {
      "entity": "variant",
      "erp_id": "10025-50W",
      "sku": null,
      "code": "missing_sku",
      "message": "La variante no trae SKU."
    }
  ]
}
```

Codigos recomendados:

```text
invalid_payload
missing_required_field
duplicate_sku
invalid_price
invalid_qty
category_not_found
brand_not_found
image_not_accessible
related_product_not_found
unauthorized
server_error
```

## Estrategia de Sincronizacion Recomendada

### Sincronizacion completa

Frecuencia recomendada:

```text
1 vez al dia
```

Incluye:

- Categorias.
- Marcas.
- Productos.
- Variantes.
- Imagenes.
- Especificaciones.
- Documentos.
- Relaciones.

### Sincronizacion parcial de inventario

Frecuencia recomendada:

```text
cada 5 a 15 minutos
```

Incluye:

- SKU.
- Existencia total.
- Existencia disponible.
- Existencia reservada.
- Estado de stock.

### Sincronizacion parcial de precios

Frecuencia recomendada:

```text
cuando cambien precios o promociones
```

Incluye:

- SKU.
- Precio.
- Precio anterior.
- Precio promocional.
- Vigencia de promocion.

## Mapeo Contra La Web Actual

La web actual ya maneja estos campos:

| Web | API propuesta |
| --- | --- |
| `products.lumen_id` | `product.erp_id` |
| `products.sku` | `product.sku` |
| `products.name` | `product.name` |
| `products.slug` | `product.slug` |
| `products.description` | `product.description` |
| `products.price` | `product.price` |
| `products.compare_at_price` | `product.compare_at_price` |
| `products.cost` | `product.cost` |
| `products.qty` | `product.qty` |
| `products.image_url` | `product.image_url` |
| `products.specs` | `product.specs` |
| `products.tags` | `product.tags` |
| `products.certifications` | `product.certifications` |
| `products.weight` | `product.weight` |
| `products.dimensions` | `product.dimensions` |
| `products.upc` | `product.upc` |
| `products.mpn` | `product.mpn` |
| `products.is_active` | `product.is_active` |
| `product_variants.lumen_variant_id` | `variant.erp_id` |
| `product_variants.sku` | `variant.sku` |
| `product_variants.name` | `variant.name` |
| `product_variants.price` | `variant.price` |
| `product_variants.qty` | `variant.qty` |
| `product_variants.attributes` | `variant.attributes` |
| `product_images.image_url` | `image.url` |

Campos que probablemente requieren desarrollo adicional en la web:

- `technical_specs` como tabla tecnica ordenada.
- `documents` para fichas tecnicas/certificados.
- `related_products`.
- `available_qty`, `reserved_qty`, `backorder_qty`.
- Vigencia de promociones: `promotion_starts_at`, `promotion_ends_at`.
- Inventario por bodega.

## Recomendacion Practica Para El ERP

Si el ERP no tiene todos los campos, empezar con este minimo:

```json
{
  "erp_id": "10025",
  "sku": "HLBPH4069FS1EMWR",
  "name": "Ojo de buey LED 4 pulgadas",
  "description": "Luminaria empotrable LED.",
  "category_erp_id": "CAT-003",
  "brand_erp_id": "BRAND-001",
  "price": 18.5,
  "qty": 250,
  "image_url": "https://erp.example.com/images/products/ojo-de-buey.png",
  "specs": {
    "wattage": "10W",
    "lumens": "600 lm",
    "voltage": "120V"
  },
  "is_active": true,
  "updated_at": "2026-07-12T10:30:00-06:00"
}
```

Luego agregar por fases:

1. Variantes.
2. Multiples imagenes.
3. Especificaciones tecnicas detalladas.
4. Documentos PDF.
5. Productos relacionados.
6. Promociones con vigencia.
7. Inventario por bodega.

