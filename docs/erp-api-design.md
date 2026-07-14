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
- Inventario por bodega.
- SEO y datos para compartir en redes.
- Datos B2B.
- Datos tecnicos especificos de iluminacion.
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

- Todo payload debe incluir `api_version`.
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
- Los pesos y dimensiones deben enviarse en campos estructurados, no solo como texto libre.
- Las sincronizaciones grandes deben enviarse por paginas o lotes.
- El mismo `sync_id` debe ser idempotente: si el ERP reenvia exactamente el mismo lote, la web no debe duplicar datos.
- Si se reenvia el mismo `sync_id` con contenido diferente, la web debe responder error `sync_id_conflict`.

Ejemplo:

```json
"updated_at": "2026-07-12T10:30:00-06:00"
```

## Versionado del API

Cada payload debe declarar la version del contrato.

```json
{
  "api_version": "2026-07-12",
  "sync_id": "ERP-SYNC-20260712-001"
}
```

Reglas:

- Cambios compatibles mantienen la misma version.
- Cambios que renombren campos, cambien tipos o eliminen campos deben crear una nueva version.
- La web debe aceptar al menos la version actual y una version anterior durante una ventana de migracion.

Tambien puede enviarse por header:

```http
X-API-Version: 2026-07-12
```

## Idempotencia

`sync_id` identifica una ejecucion del ERP. Debe ser unico por lote.

Comportamiento esperado:

- Primer envio de `sync_id`: procesa el lote.
- Reintento con mismo `sync_id` y mismo contenido: devuelve el resultado anterior.
- Reintento con mismo `sync_id` y contenido diferente: devuelve error `sync_id_conflict`.
- Si un lote falla parcialmente, el ERP puede reenviar el mismo lote completo.

Respuesta recomendada para reintento idempotente:

```json
{
  "status": "already_processed",
  "sync_id": "ERP-SYNC-20260712-001",
  "message": "Este lote ya fue procesado previamente.",
  "summary": {
    "products_created": 10,
    "products_updated": 120,
    "products_failed": 0
  }
}
```

## Paginacion de Sincronizacion

Para catalogos grandes no se recomienda enviar 50,000 productos en un solo POST.

Campos recomendados:

```json
{
  "api_version": "2026-07-12",
  "sync_id": "ERP-SYNC-20260712-001",
  "batch_id": "ERP-SYNC-20260712-001-PAGE-0001",
  "page": 1,
  "per_page": 500,
  "total_pages": 12,
  "total_records": 5784,
  "is_last_page": false
}
```

Reglas:

- `sync_id` agrupa toda la corrida.
- `batch_id` identifica una pagina/lote especifico.
- Cada `batch_id` tambien debe ser idempotente.
- `per_page` recomendado: entre 250 y 1000 registros.
- La web debe guardar estado de cada lote para saber si la corrida quedo completa.

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
  "api_version": "2026-07-12",
  "sync_id": "ERP-SYNC-20260712-001",
  "batch_id": "ERP-SYNC-20260712-001-PAGE-0001",
  "source": "erp",
  "mode": "upsert",
  "page": 1,
  "per_page": 500,
  "total_pages": 1,
  "is_last_page": true,
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
  "api_version": "2026-07-12",
  "sync_id": "ERP-PRODUCTS-20260712-001",
  "batch_id": "ERP-PRODUCTS-20260712-001-PAGE-0001",
  "mode": "upsert",
  "page": 1,
  "per_page": 500,
  "total_pages": 1,
  "is_last_page": true,
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
  "api_version": "2026-07-12",
  "sync_id": "ERP-STOCK-20260712-001",
  "sent_at": "2026-07-12T10:30:00-06:00",
  "items": [
    {
      "erp_id": "10025",
      "sku": "HLBPH4069FS1EMWR",
      "qty": 250,
      "available_qty": 240,
      "reserved_qty": 10,
      "backorder_qty": 0,
      "stock_status": "in_stock",
      "warehouses": [
        {
          "warehouse_id": "SS-01",
          "warehouse_name": "San Salvador",
          "qty": 150,
          "available_qty": 145,
          "reserved_qty": 5,
          "backorder_qty": 0,
          "ships_from_warehouse": true
        },
        {
          "warehouse_id": "SM-01",
          "warehouse_name": "San Miguel",
          "qty": 100,
          "available_qty": 95,
          "reserved_qty": 5,
          "backorder_qty": 0,
          "ships_from_warehouse": false
        }
      ]
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
  "api_version": "2026-07-12",
  "sync_id": "ERP-PRICES-20260712-001",
  "currency": "USD",
  "price_list_id": "PUBLIC-USD",
  "country": "SV",
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
      "promotion_ends_at": "2026-07-31T23:59:59-06:00",
      "price_tiers": [
        {
          "min_qty": 10,
          "max_qty": 49,
          "price": 17.5
        },
        {
          "min_qty": 50,
          "max_qty": null,
          "price": 16.75
        }
      ],
      "customer_group_pricing": [
        {
          "customer_group": "contratistas",
          "price": 16.25,
          "currency": "USD"
        }
      ]
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
  "localized_slugs": {
    "es": "ojo-de-buey-led-4-pulgadas",
    "en": "4-inch-led-downlight"
  },
  "short_description": "Luminaria empotrable LED de alta eficiencia.",
  "description": "Luminaria empotrable tipo ojo de buey de 4 pulgadas con tecnologia LED de alta eficiencia para interiores comerciales y residenciales.",
  "seo": {
    "meta_title": "Ojo de buey LED 4 pulgadas | Lumens",
    "meta_description": "Ojo de buey LED empotrable de 4 pulgadas, eficiente y certificado para proyectos comerciales.",
    "canonical_url": "https://tudominio.com/producto/ojo-de-buey-led-4-pulgadas",
    "og_image": "https://erp.example.com/images/products/ojo-de-buey-og.jpg"
  },
  "category_erp_id": "CAT-003",
  "brand_erp_id": "BRAND-001",
  "price": 18.5,
  "compare_at_price": 22.0,
  "cost": 9.2,
  "currency": "USD",
  "qty": 250,
  "available_qty": 240,
  "reserved_qty": 10,
  "backorder_qty": 0,
  "low_stock_threshold": 10,
  "backorder_policy": "deny",
  "stock_status": "in_stock",
  "warehouses": [],
  "image_url": "https://erp.example.com/images/products/ojo-de-buey.png",
  "images": [],
  "videos": [],
  "variants": [],
  "specs": {},
  "technical_specs": [],
  "lighting_specs": {
    "lifespan_hours": 50000,
    "warranty_years": 5,
    "warranty_terms_url": "https://erp.example.com/docs/warranty.pdf",
    "dimmable": true,
    "dimmable_protocol": "TRIAC",
    "beam_angle": "90",
    "cri": 90,
    "power_factor": 0.9,
    "thd": "<20%",
    "sdcm": 3,
    "driver_required": false,
    "driver_included": true
  },
  "documents": [],
  "certifications": ["UL", "DLC"],
  "tags": ["indoor", "recessed", "dimmable"],
  "related_products": [],
  "compatibility": [],
  "required_components": [],
  "is_featured": true,
  "is_promotion": false,
  "promotion_price": null,
  "weight": "0.45 kg",
  "weight_value": 0.45,
  "weight_unit": "kg",
  "dimensions": "12 cm x 12 cm x 6 cm",
  "dimensions_structured": {
    "length_value": 12,
    "width_value": 12,
    "height_value": 6,
    "unit": "cm"
  },
  "logistics": {
    "lead_time_days": 3,
    "freight_class": null,
    "ships_from_warehouse": "SS-01",
    "hazmat": false,
    "oversized": false,
    "ltl_only": false
  },
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
| `seo` | object/null | Campos SEO y Open Graph. |
| `localized_slugs` | object/null | Slugs por idioma. |
| `compare_at_price` | decimal/null | Precio anterior o precio de referencia. |
| `is_promotion` | boolean | Indica si esta en promocion. |
| `promotion_price` | decimal/null | Precio promocional. |
| `price_tiers` | array | Precios por cantidad para B2B. |
| `customer_group_pricing` | array | Precios por grupo de cliente. |
| `is_featured` | boolean | Producto destacado en home. |
| `tags` | array | Etiquetas para busqueda y agrupaciones. |
| `certifications` | array | Certificaciones como UL, DLC, FCC. |

Campos tecnicos recomendados:

| Campo | Tipo | Comentario |
| --- | --- | --- |
| `specs` | object | Objeto simple clave/valor. |
| `technical_specs` | array | Lista ordenada con grupo, etiqueta, valor y unidad. |
| `lighting_specs` | object | Campos criticos del nicho de iluminacion. |
| `documents` | array | Fichas tecnicas, manuales, certificados. |
| `weight_value` | decimal/null | Peso numerico. |
| `weight_unit` | string/null | Unidad de peso: `kg`, `lb`, `g`. |
| `dimensions_structured` | object/null | Dimensiones estructuradas. |
| `upc` | string/null | Codigo UPC. |
| `mpn` | string/null | Codigo del fabricante. |

Campos logisticos recomendados:

| Campo | Tipo | Comentario |
| --- | --- | --- |
| `warehouses` | array | Existencia por bodega. |
| `low_stock_threshold` | integer/null | Umbral para mostrar bajo inventario. |
| `backorder_policy` | string | `deny`, `allow`, `preorder`, `notify_only`. |
| `logistics.lead_time_days` | integer/null | Dias estimados para despacho. |
| `logistics.freight_class` | string/null | Clase de flete, si aplica. |
| `logistics.ships_from_warehouse` | string/null | Bodega principal de despacho. |
| `logistics.hazmat` | boolean | Material peligroso. |
| `logistics.oversized` | boolean | Producto sobredimensionado. |
| `logistics.ltl_only` | boolean | Requiere transporte LTL/carga. |

## SEO

```json
{
  "seo": {
    "meta_title": "Troffer LED 2x4 | Lumens",
    "meta_description": "Troffer LED 2x4 para oficinas, disponible en varias potencias y temperaturas de color.",
    "canonical_url": "https://tudominio.com/producto/troffer-led-2x4",
    "og_image": "https://erp.example.com/images/products/troffer-og.jpg"
  },
  "localized_slugs": {
    "es": "troffer-led-2x4",
    "en": "2x4-led-troffer"
  }
}
```

Reglas:

- Si `meta_title` no viene, la web puede usar `name`.
- Si `meta_description` no viene, la web puede usar `short_description` o recortar `description`.
- `canonical_url` es opcional; si no viene, la web lo genera con `APP_URL` + `slug`.
- `og_image` debe apuntar a una imagen publica.

## Multimedia

```json
{
  "videos": [
    {
      "type": "demo",
      "title": "Instalacion del Troffer LED 2x4",
      "url": "https://www.youtube.com/watch?v=example",
      "thumbnail_url": "https://erp.example.com/videos/troffer-thumb.jpg",
      "sort_order": 1
    },
    {
      "type": "360",
      "title": "Vista 360",
      "url": "https://erp.example.com/360/troffer-led-2x4",
      "thumbnail_url": null,
      "sort_order": 2
    }
  ]
}
```

Tipos recomendados:

```text
demo
installation
360
review
technical
```

## Campos Especificos De Iluminacion

```json
{
  "lighting_specs": {
    "lifespan_hours": 50000,
    "l70_hours": 50000,
    "warranty_years": 5,
    "warranty_terms_url": "https://erp.example.com/docs/warranty.pdf",
    "dimmable": true,
    "dimmable_protocol": "0-10V",
    "beam_angle": "110",
    "cri": 90,
    "power_factor": 0.9,
    "thd": "<20%",
    "sdcm": 3,
    "driver_required": true,
    "driver_included": false,
    "driver_sku": "DRIVER-LED-40W",
    "ballast_required": false
  }
}
```

Campos recomendados:

| Campo | Tipo | Comentario |
| --- | --- | --- |
| `lifespan_hours` | integer/null | Vida util estimada. |
| `l70_hours` | integer/null | Vida L70, por ejemplo 50000. |
| `warranty_years` | integer/null | Anos de garantia. |
| `warranty_terms_url` | string/null | URL de terminos de garantia. |
| `dimmable` | boolean/null | Si permite atenuacion. |
| `dimmable_protocol` | string/null | `0-10V`, `DALI`, `TRIAC`, `ELV`, `PWM`, etc. |
| `beam_angle` | string/null | Angulo de apertura. |
| `cri` | integer/null | Indice de reproduccion cromatica. |
| `power_factor` | decimal/null | Factor de potencia. |
| `thd` | string/null | Distorsion armonica total. |
| `sdcm` | integer/null | Consistencia de color. |
| `driver_required` | boolean/null | Si requiere driver externo. |
| `driver_included` | boolean/null | Si el driver viene incluido. |
| `driver_sku` | string/null | SKU del driver recomendado/requerido. |
| `ballast_required` | boolean/null | Si requiere balasto. |

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
      "images": [
        {
          "url": "https://erp.example.com/images/products/troffer-30w.jpg",
          "alt": "Troffer LED 2x4 30W 4000K",
          "sort_order": 0,
          "is_primary": true
        }
      ],
      "lighting_specs": {
        "wattage": "30W",
        "cct": "4000K",
        "lumens": "3750 lm",
        "dimmable_protocol": "0-10V"
      },
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
| `images` | array | No | Imagenes especificas de la variante. |
| `lighting_specs` | object | No | Datos tecnicos que cambian por variante. |
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

## Compatibilidad Y Componentes Requeridos

Para iluminacion es importante mapear drivers, balastos, sensores, kits de emergencia, accesorios o componentes obligatorios.

```json
{
  "compatibility": [
    {
      "erp_id": "20045",
      "sku": "DRIVER-LED-40W",
      "compatibility_type": "compatible_driver",
      "required": false,
      "notes": "Compatible para configuraciones de 30W y 40W.",
      "sort_order": 1
    }
  ],
  "required_components": [
    {
      "erp_id": "20046",
      "sku": "MOUNTING-KIT-2X4",
      "component_type": "mounting_kit",
      "qty_required": 1,
      "notes": "Requerido para instalacion suspendida.",
      "sort_order": 1
    }
  ]
}
```

Tipos recomendados para `compatibility_type`:

```text
compatible_driver
compatible_ballast
compatible_sensor
compatible_emergency_kit
compatible_mounting_kit
compatible_accessory
replacement_part
```

Tipos recomendados para `component_type`:

```text
driver
ballast
sensor
emergency_kit
mounting_kit
accessory
lamp
housing
```

## Inventario Multi-Bodega

```json
{
  "warehouses": [
    {
      "warehouse_id": "SS-01",
      "warehouse_name": "San Salvador",
      "qty": 150,
      "available_qty": 145,
      "reserved_qty": 5,
      "backorder_qty": 0,
      "lead_time_days": 1,
      "ships_from_warehouse": true
    },
    {
      "warehouse_id": "SM-01",
      "warehouse_name": "San Miguel",
      "qty": 100,
      "available_qty": 95,
      "reserved_qty": 5,
      "backorder_qty": 0,
      "lead_time_days": 3,
      "ships_from_warehouse": false
    }
  ]
}
```

Reglas:

- `qty` global puede ser la suma de bodegas.
- `available_qty` global puede ser la suma de disponibles.
- Si el ERP solo tiene una bodega, enviar un arreglo con una sola bodega.
- La web puede mostrar stock global al cliente y usar multi-bodega internamente para despacho.

## Precios B2B

```json
{
  "price_tiers": [
    {
      "min_qty": 1,
      "max_qty": 9,
      "price": 78.0,
      "currency": "USD",
      "price_list_id": "PUBLIC-USD"
    },
    {
      "min_qty": 10,
      "max_qty": 49,
      "price": 74.0,
      "currency": "USD",
      "price_list_id": "PUBLIC-USD"
    }
  ],
  "customer_group_pricing": [
    {
      "customer_group": "contratistas",
      "price": 72.0,
      "currency": "USD",
      "price_list_id": "CONTRACTORS-USD"
    }
  ]
}
```

Reglas:

- Si no hay login B2B, la web debe usar `price`.
- Si hay grupo de cliente, gana `customer_group_pricing`.
- Si hay precio por volumen, gana el rango de `price_tiers` aplicable.
- Si hay promocion activa, se debe definir en negocio si gana promocion o precio B2B.

## Moneda Y Listas De Precio

Para multi-moneda o precios por pais:

```json
{
  "currency": "USD",
  "country": "SV",
  "price_list_id": "PUBLIC-SV-USD",
  "exchange_rate": {
    "from": "USD",
    "to": "USD",
    "rate": 1,
    "rate_date": "2026-07-12"
  }
}
```

Reglas:

- `currency` debe venir en ISO 4217.
- `country` debe venir en ISO 3166-1 alpha-2 cuando aplique.
- `price_list_id` permite manejar listas por pais, canal o tipo de cliente.
- Si la web solo vende en USD al inicio, mantener `currency: "USD"` y omitir `exchange_rate`.

## Payload Completo de Ejemplo

```json
{
  "api_version": "2026-07-12",
  "sync_id": "ERP-SYNC-20260712-001",
  "batch_id": "ERP-SYNC-20260712-001-PAGE-0001",
  "source": "erp",
  "mode": "upsert",
  "page": 1,
  "per_page": 500,
  "total_pages": 1,
  "is_last_page": true,
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
      "localized_slugs": {
        "es": "troffer-led-2x4",
        "en": "2x4-led-troffer"
      },
      "short_description": "Luminaria troffer LED para cielos rasos.",
      "description": "Luminaria troffer LED para cielos rasos de oficinas y comercios.",
      "seo": {
        "meta_title": "Troffer LED 2x4 | Lumens",
        "meta_description": "Troffer LED 2x4 para oficinas y comercios, con opciones de potencia y temperatura de color.",
        "canonical_url": "https://tudominio.com/producto/troffer-led-2x4",
        "og_image": "https://erp.example.com/images/products/troffer-og.jpg"
      },
      "category_erp_id": "CAT-003",
      "brand_erp_id": "BRAND-001",
      "price": 78.0,
      "compare_at_price": null,
      "cost": 38.0,
      "currency": "USD",
      "qty": 100,
      "available_qty": 95,
      "reserved_qty": 5,
      "backorder_qty": 0,
      "low_stock_threshold": 10,
      "backorder_policy": "deny",
      "stock_status": "in_stock",
      "warehouses": [
        {
          "warehouse_id": "SS-01",
          "warehouse_name": "San Salvador",
          "qty": 100,
          "available_qty": 95,
          "reserved_qty": 5,
          "backorder_qty": 0,
          "lead_time_days": 1,
          "ships_from_warehouse": true
        }
      ],
      "image_url": "https://erp.example.com/images/products/troffer-2x4.png",
      "images": [
        {
          "url": "https://erp.example.com/images/products/troffer-2x4.png",
          "alt": "Troffer LED 2x4",
          "sort_order": 0,
          "is_primary": true
        }
      ],
      "videos": [
        {
          "type": "installation",
          "title": "Instalacion del Troffer LED 2x4",
          "url": "https://www.youtube.com/watch?v=example",
          "thumbnail_url": "https://erp.example.com/videos/troffer-thumb.jpg",
          "sort_order": 1
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
          "images": [],
          "lighting_specs": {
            "wattage": "30W",
            "cct": "4000K",
            "lumens": "3750 lm",
            "dimmable_protocol": "0-10V"
          },
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
      "lighting_specs": {
        "lifespan_hours": 50000,
        "l70_hours": 50000,
        "warranty_years": 5,
        "warranty_terms_url": "https://erp.example.com/docs/warranty.pdf",
        "dimmable": true,
        "dimmable_protocol": "0-10V",
        "beam_angle": "110",
        "cri": 90,
        "power_factor": 0.9,
        "thd": "<20%",
        "sdcm": 3,
        "driver_required": true,
        "driver_included": false,
        "driver_sku": "DRIVER-LED-40W",
        "ballast_required": false
      },
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
      "compatibility": [
        {
          "erp_id": "20045",
          "sku": "DRIVER-LED-40W",
          "compatibility_type": "compatible_driver",
          "required": false,
          "notes": "Compatible para configuraciones de 30W y 40W.",
          "sort_order": 1
        }
      ],
      "required_components": [],
      "is_featured": false,
      "is_promotion": false,
      "promotion_price": null,
      "price_tiers": [
        {
          "min_qty": 10,
          "max_qty": 49,
          "price": 74.0,
          "currency": "USD",
          "price_list_id": "PUBLIC-USD"
        }
      ],
      "customer_group_pricing": [
        {
          "customer_group": "contratistas",
          "price": 72.0,
          "currency": "USD",
          "price_list_id": "CONTRACTORS-USD"
        }
      ],
      "weight": "2.1 kg",
      "weight_value": 2.1,
      "weight_unit": "kg",
      "dimensions": "2 ft x 4 ft",
      "dimensions_structured": {
        "length_value": 4,
        "width_value": 2,
        "height_value": 0.2,
        "unit": "ft"
      },
      "logistics": {
        "lead_time_days": 3,
        "freight_class": null,
        "ships_from_warehouse": "SS-01",
        "hazmat": false,
        "oversized": true,
        "ltl_only": false
      },
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

- `api_version` requerido en el payload raiz.
- `sync_id` requerido en endpoints de sincronizacion.
- `sku` requerido y unico.
- `name` requerido.
- `price` debe ser mayor o igual a `0`.
- `qty` debe ser entero mayor o igual a `0`.
- `promotion_price` debe ser menor que `price` cuando `is_promotion` sea `true`.
- `category_erp_id` debe existir o venir en el mismo payload.
- `brand_erp_id` debe existir o venir en el mismo payload.
- `weight_value` debe ser numerico cuando venga.
- `weight_unit` debe venir si viene `weight_value`.
- `dimensions_structured.unit` debe venir si se envian dimensiones estructuradas.
- `low_stock_threshold` debe ser entero mayor o igual a `0`.
- `seo.meta_title` no deberia pasar de 70 caracteres.
- `seo.meta_description` no deberia pasar de 160 caracteres.
- Solo debe haber un `canonical_url` por producto e idioma.

### Variante

- `sku` requerido y unico.
- `name` requerido.
- `attributes` recomendado.
- `qty` debe ser entero mayor o igual a `0`.
- Si la variante tiene precio propio, `price` debe ser mayor o igual a `0`.
- Si la variante trae `images`, debe aplicar las mismas reglas de imagen del producto.
- Si la variante cambia especificaciones criticas, debe enviarlas en `lighting_specs` o `attributes`.

### Imagen

- `url` requerido.
- `sort_order` entero.
- Solo una imagen primaria por producto.

### Inventario

- `warehouses[].warehouse_id` requerido cuando se envie inventario multi-bodega.
- `warehouses[].qty` debe ser entero mayor o igual a `0`.
- `warehouses[].available_qty` no debe ser mayor que `warehouses[].qty`.
- `available_qty` global no debe ser mayor que `qty` global.
- `reserved_qty` no debe ser negativo.
- `backorder_policy` debe ser uno de: `deny`, `allow`, `preorder`, `notify_only`.

### Precios B2B

- `price_tiers[].min_qty` requerido.
- `price_tiers[].max_qty` puede ser `null` para rango abierto.
- Los rangos de `price_tiers` no deben traslaparse dentro de la misma lista.
- `customer_group_pricing[].customer_group` requerido.
- `price_list_id` recomendado cuando existan multiples listas de precio.

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
sync_id_conflict
batch_already_processed
invalid_api_version
invalid_price_tier
invalid_warehouse
invalid_seo
invalid_dimensions
invalid_weight
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
- SEO.
- Multimedia.
- Datos de iluminacion.
- Datos B2B.

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
- Inventario por bodega.
- Backorder.
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
- Listas de precio.
- Precios por volumen.
- Precios por grupo de cliente.

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

- `seo`, `localized_slugs`, `canonical_url`, `og_image`.
- `videos`.
- `technical_specs` como tabla tecnica ordenada.
- `lighting_specs`.
- `documents` para fichas tecnicas/certificados.
- `related_products`.
- `compatibility`, `required_components`.
- `warehouses`.
- `available_qty`, `reserved_qty`, `backorder_qty`, `low_stock_threshold`, `backorder_policy`.
- Vigencia de promociones: `promotion_starts_at`, `promotion_ends_at`.
- `price_tiers`, `customer_group_pricing`, `price_list_id`.
- Campos estructurados: `weight_value`, `weight_unit`, `dimensions_structured`.
- `logistics`: tiempo de entrega, flete, sobredimensionado, LTL.

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

### Fase 1: MVP reforzado

Implementar primero:

- `api_version`.
- `sync_id` idempotente.
- Paginacion por `batch_id`.
- Productos, categorias y marcas.
- Precio base.
- Stock global.
- Inventario multi-bodega basico.
- SEO basico: `meta_title`, `meta_description`, `canonical_url`, `og_image`.
- Imagen principal y galeria simple.
- Variantes basicas.

### Fase 2: Iluminacion y B2B

Agregar cuando el ERP o el equipo comercial tenga esos datos:

- `lighting_specs`.
- Garantia: `warranty_years`, `warranty_terms_url`.
- Vida util: `lifespan_hours`, `l70_hours`.
- Atenuacion: `dimmable`, `dimmable_protocol`.
- Fotometria: `beam_angle`, `cri`, `power_factor`, `thd`, `sdcm`.
- Logistica B2B: `lead_time_days`, `freight_class`, `ships_from_warehouse`.
- Reglas de despacho: `hazmat`, `oversized`, `ltl_only`.
- `low_stock_threshold` y `backorder_policy`.

### Fase 3: Catalogo avanzado

Agregar cuando ya exista volumen de catalogo y clientes B2B:

- `price_tiers`.
- `customer_group_pricing`.
- Multiples listas de precio por pais/canal.
- `videos`.
- `compatibility`.
- `required_components`.
- Productos relacionados avanzados.
- Variantes con galeria propia.
- Slugs localizados por idioma.
