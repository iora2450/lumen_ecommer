# Guia de Uso · API de Sincronizacion ERP

> Documento para el equipo de la web: explica como invocar y consumir los
> endpoints de sincronizacion entre el ERP (Sistema Lumen) y la tienda
> online (Lumens Ecommerce).

---

## 1. Resumen rapido

La web expone una API REST que soporta dos direcciones:

| Direccion | Descripcion | Quien llama |
|---|---|---|
| **Push (webhook entrante)** | El ERP envia datos al endpoint de la web. | ERP -> Web |
| **Pull (cliente HTTP saliente)** | La web consulta al ERP bajo demanda. | Web -> ERP |

Ambos comparten la misma logica de upsert (`ErpSyncService`) y el mismo
formato de payload. La unica diferencia es quien inicia la llamada.

El boton **Actualizar desde ERP** del admin de la web dispara un pull
(`php artisan erp:pull`) que jala catalogo, inventario o precios.

---

## 2. Base URL

```
Produccion:  https://<tu-dominio-web>/api/sync
Local:       http://localhost:8090/api/sync
```

---

## 3. Autenticacion

Todas las requests (push entrante y pull saliente) usan **Bearer token**.

### 3.1 Web acepta webhooks del ERP
La web valida con `hash_equals` contra la clave guardada en
`settings.sync_api_key`. El ERP debe enviar **una** de estas dos formas:

```http
Authorization: Bearer {sync_api_key}
```

```http
X-API-KEY: {sync_api_key}
```

Si la web responde `401`, la clave es incorrecta o no esta configurada.
El admin de la web puede regenerar la clave en `/admin/sync`.

### 3.2 Web consulta al ERP (pull)
La web lee `ERP_BASE_URL` y `ERP_API_KEY` del `.env`. El ERP debe validar
el `Authorization: Bearer {ERP_API_KEY}` que envia la web.

```env
ERP_BASE_URL=https://erp.example.com/api
ERP_API_KEY=<token-compartido>
ERP_TIMEOUT=30
ERP_RETRY_ATTEMPTS=3
```

---

## 4. Endpoints disponibles (push / webhook)

Todos los endpoints push viven bajo `/api/sync/*`. La web registra cada
`sync_id` recibido en la tabla `processed_syncs` para evitar reprocesar
el mismo lote (idempotencia).

### 4.1 Health check

```http
GET /api/sync/health
```

**Sin autenticacion.** Util para que el ERP verifique disponibilidad.

```json
{
  "status": "ok",
  "service": "lumens-ecommerce",
  "last_sync": "2026-07-12T10:30:00+00:00",
  "timestamp": "2026-07-12T14:00:00+00:00"
}
```

### 4.2 Estado del ERP -> Web

```http
GET /api/sync/status
```

Devuelve la ultima ejecucion registrada y los ultimos `sync_id` procesados.

```json
{
  "last_run": "2026-07-12T14:00:00+00:00",
  "log": { "id": 23, "status": "success", "products_synced": 120 },
  "processed_ids": ["ERP-SYNC-20260712-001", "..."]
}
```

### 4.3 Catalogo completo (categorias + marcas + productos)

```http
POST /api/sync/catalog
Authorization: Bearer {sync_api_key}
Content-Type: application/json
```

```json
{
  "sync_id": "ERP-SYNC-20260712-001",
  "source": "erp",
  "mode": "upsert",
  "sent_at": "2026-07-12T10:30:00-06:00",
  "categories": [ { "erp_id": "CAT-100", "name": "Paneles LED" } ],
  "brands":     [ { "erp_id": "BR-100", "name": "Lumens Pro" } ],
  "products":   [ { "erp_id": "10025", "sku": "HLBPH4069FS1EMWR", "name": "..." } ]
}
```

**Respuestas:**

| Codigo | Cuando |
|---|---|
| `200` | Todo aplicado sin errores. |
| `207` | Aplicado con errores parciales (ver `errors[]`). |
| `401` | API key invalida. |
| `422` | Validacion fallo (`sync_id` o `products.*.sku` faltante). |
| `200` (idempotente) | Mismo `sync_id` ya procesado: retorna resumen previo. |

Ejemplo de respuesta exitosa:

```json
{
  "status": "success",
  "sync_id": "ERP-SYNC-20260712-001",
  "summary": {
    "categories_created": 2, "categories_updated": 4,
    "brands_created": 1,     "brands_updated": 3,
    "products_created": 10,  "products_updated": 120
  },
  "errors": []
}
```

Ejemplo con errores:

```json
{
  "status": "partial_success",
  "sync_id": "ERP-SYNC-20260712-001",
  "summary": { "products_created": 9, "products_updated": 118, "products_failed": 2 },
  "errors": [
    { "entity": "product", "erp_id": "10099", "sku": "DUP-SKU",
      "code": "duplicate_sku", "message": "El SKU ya existe en otro producto." },
    { "entity": "variant", "erp_id": "10025-50W", "sku": null,
      "code": "missing_sku", "message": "La variante no trae SKU." }
  ]
}
```

### 4.4 Solo productos

```http
POST /api/sync/products
```

Mismo payload que `catalog` pero sin `categories` ni `brands`.

```json
{
  "sync_id": "ERP-PROD-20260712-001",
  "mode": "upsert",
  "sent_at": "2026-07-12T10:30:00-06:00",
  "products": [ { "sku": "...", "name": "...", "price": 18.5 } ]
}
```

### 4.5 Solo inventario (uso intensivo)

```http
POST /api/sync/inventory
```

```json
{
  "sync_id": "ERP-STOCK-20260712-001",
  "warehouse": "principal",
  "sent_at": "2026-07-12T10:30:00-06:00",
  "items": [
    { "erp_id": "10025", "sku": "HLBPH4069FS1EMWR",
      "qty": 250, "available_qty": 240, "reserved_qty": 10,
      "stock_status": "in_stock" }
  ]
}
```

`stock_status` sugerido:

```text
in_stock | low_stock | out_of_stock | backorder | discontinued
```

### 4.6 Solo precios / promociones

```http
POST /api/sync/prices
```

```json
{
  "sync_id": "ERP-PRICE-20260712-001",
  "currency": "USD",
  "sent_at": "2026-07-12T10:30:00-06:00",
  "items": [
    { "erp_id": "10025", "sku": "HLBPH4069FS1EMWR",
      "price": 18.5, "compare_at_price": 22.0,
      "is_promotion": true, "promotion_price": 16.99,
      "promotion_starts_at": "2026-07-12T00:00:00-06:00",
      "promotion_ends_at":   "2026-07-31T23:59:59-06:00" }
  ]
}
```

### 4.7 Legacy / compatibilidad

```http
POST /api/sync/lumen
```

Alias exacto de `/api/sync/catalog`. Conservar si el ERP actual ya envia
alli; los nuevos integradores deben usar `/api/sync/catalog`.

---

## 5. Pull del ERP (web -> ERP)

La web puede jalar datos del ERP usando los endpoints configurados en
`config/erp.php`. La web asume que el ERP expone:

```
GET /health
GET /categories
GET /brands
GET /products
GET /inventory
GET /prices
```

Todos con respuesta JSON, idealmente:

```json
{ "data": [ /* ... */ ], "meta": { "current_page": 1, "last_page": 4 } }
```

Si el ERP responde sin `data`, la web acepta la lista directa.

### 5.1 Disparar pull desde el admin de la web

En el panel `/admin/sync` hay (o se agregara) un boton **Actualizar desde
ERP** con selector de tipo:

```
[ Catalogo completo ]
[ Solo productos    ]
[ Solo categorias   ]
[ Solo marcas       ]
[ Solo inventario   ]
[ Solo precios      ]
```

Internamente llama al comando:

```bash
php artisan erp:pull --type={full|products|categories|brands|inventory|prices}
```

Que se conecta a `ERP_BASE_URL` con `Authorization: Bearer {ERP_API_KEY}`,
trae la data pagina por pagina (100 items por pagina por default) y la
procesa con la misma logica de upsert del push.

Dry-run:

```bash
php artisan erp:pull --type=full --dry-run
```

Esto lista cuantos registros se procesarian sin tocar la base.

### 5.2 Cron recomendado

Para mantener el catalogo actualizado sin intervencion manual:

```php
// routes/console.php o app/Console/Kernel.php
$schedule->command('erp:pull --type=full')->dailyAt('03:00');
$schedule->command('erp:pull --type=inventory')->everyFifteenMinutes();
$schedule->command('erp:pull --type=prices')->hourly();
```

---

## 6. Estructuras de dato

Aplica identico para push y pull. Campos marcados con **`?`** son
opcionales; si faltan, la web usa defaults razonables.

### 6.1 Categoria

```json
{
  "erp_id": "CAT-001",
  "name": "Paneles LED",
  "slug": "paneles-led",
  "description": "Iluminacion uniforme para oficinas.",
  "image_url": "https://...",
  "parent_erp_id": null,
  "sort_order": 3,
  "is_active": true
}
```

`parent_erp_id` permite jerarquia (la web resuelve el `parent_id`
internamente).

### 6.2 Marca

```json
{
  "erp_id": "BRAND-001",
  "name": "Acuity Brands",
  "slug": "acuity-brands",
  "logo_url": "https://...",
  "description": "Soluciones de iluminacion comercial.",
  "is_active": true
}
```

### 6.3 Producto

```json
{
  "erp_id": "10025",
  "sku": "HLBPH4069FS1EMWR",
  "name": "Ojo de buey LED 4 pulgadas",
  "slug": "ojo-de-buey-led-4-pulgadas",
  "short_description": "Luminaria empotrable LED.",
  "description": "Luminaria empotrable tipo ojo de buey...",
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
  "stock_status": "in_stock",

  "image_url": "https://.../principal.png",
  "images": [
    { "url": "https://.../principal.png", "alt": "Vista frontal",
      "sort_order": 0, "is_primary": true },
    { "url": "https://.../lateral.png",   "alt": "Vista lateral",
      "sort_order": 1, "is_primary": false }
  ],

  "variants": [ { "erp_id": "10025-30W", "sku": "...", "name": "30W" } ],
  "specs": { "wattage": "10W", "voltage": "120V" },
  "technical_specs": [
    { "group": "Electricas", "key": "wattage",
      "label": "Potencia", "value": "10", "unit": "W", "sort_order": 1 }
  ],
  "documents": [
    { "type": "datasheet", "title": "Ficha tecnica",
      "url": "https://.../ficha.pdf", "language": "es", "sort_order": 1 }
  ],

  "certifications": ["UL", "DLC"],
  "tags": ["indoor", "recessed"],
  "related_products": [
    { "erp_id": "10026", "sku": "...", "relation_type": "similar",
      "sort_order": 1 }
  ],

  "is_featured": true,
  "is_promotion": false,
  "promotion_price": 16.99,
  "promotion_starts_at": "2026-07-12T00:00:00-06:00",
  "promotion_ends_at":   "2026-07-31T23:59:59-06:00",

  "weight": "0.45 kg",
  "dimensions": "12 cm x 12 cm x 6 cm",
  "upc": "123456789012",
  "mpn": "HLBPH4069FS1EMWR",

  "is_active": true,
  "updated_at": "2026-07-12T10:30:00-06:00"
}
```

### 6.4 Variante

```json
{
  "erp_id": "10025-30W",
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
    "wattage": "30W", "cct": "4000K",
    "voltage": "120-277V", "finish": "Blanco"
  },
  "image_url": "https://.../troffer-30w.jpg",
  "is_active": true,
  "updated_at": "2026-07-12T10:30:00-06:00"
}
```

Si envias `variants` en el payload de producto, la web reemplaza la lista
completa (no agrega). Si **no** envias `variants`, conserva las
existentes.

### 6.5 Tipos de relacion

```text
similar | accessory | replacement | upsell | cross_sell |
same_family | required_component
```

---

## 7. Reglas de upsert (comportamiento esperado)

| Regla | Comportamiento de la web |
|---|---|
| Match por `erp_id` | Si existe, actualiza; si no, crea. |
| Match por `sku` | Si no hay `erp_id` en payload, usa `sku`. |
| SKU duplicado | `errors[]` con `code: duplicate_sku`. No se aplica. |
| Categoria / marca faltante | `errors[]` con `code: category_not_found` o `brand_not_found`. |
| `parent_erp_id` sin match | El padre quedara null hasta la siguiente sincronizacion. |
| Variante sin `sku` o `name` | Se ignora esa variante (no aparece en `errors[]`). |
| `stock_status` ausente | Se infiere de `qty` (`<10` low, `0` out, otro in). |
| Promocion sin vigencia | Se acepta; la web valida fechas al mostrar el precio. |
| Imagen sin `is_primary` | La primera pasa a ser primaria automaticamente. |
| `is_active: false` | El producto permanece en la web pero no aparece en catalogos. |

---

## 8. Codigos de error

```text
unauthorized            API key invalida o faltante
invalid_payload         JSON malformado
missing_required_field  Falta erp_id / sku / name
duplicate_sku           SKU ya existe en otro producto
invalid_price           price < 0
invalid_qty             qty no es entero o es negativo
category_not_found      category_erp_id no existe (todavia)
brand_not_found         brand_erp_id no existe (todavia)
product_not_found       inventory / prices sin match
server_error            Excepcion no controlada (ver log)
```

---

## 9. Idempotencia

- La web guarda cada `sync_id` recibido en `processed_syncs`.
- Reenviar el mismo `sync_id` devuelve `200` con el resumen previo y
  mensaje `Sync ya procesado`.
- El ERP puede reintentar con confianza tras timeouts.

Para evitar colisiones, se recomienda generar `sync_id` asi:

```text
ERP-<TIPO>-YYYYMMDD-HHMMSS-<rand 6 chars>
Ejemplo: ERP-CATALOG-20260712-103000-A7F2K1
```

---

## 10. Frecuencias recomendadas

| Carga | Frecuencia | Endpoint |
|---|---|---|
| Catalogo completo | 1 vez al dia (off-hours) | `POST /api/sync/catalog` |
| Productos nuevos / editados | Cuando ocurran | `POST /api/sync/products` |
| Inventario | Cada 5-15 min | `POST /api/sync/inventory` |
| Precios / promociones | Al cambiar | `POST /api/sync/prices` |

Desde el lado web, los pulls complementarios pueden correr:

```text
pull --type=full         diario        03:00
pull --type=inventory    cada 15 min
pull --type=prices       cada 1 h
```

---

## 11. Ejemplos curl (push)

### Carga completa

```bash
curl -X POST https://web.example.com/api/sync/catalog \
  -H "Authorization: Bearer $SYNC_API_KEY" \
  -H "Content-Type: application/json" \
  -d @catalog.json
```

### Solo inventario

```bash
curl -X POST https://web.example.com/api/sync/inventory \
  -H "Authorization: Bearer $SYNC_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "sync_id": "ERP-STOCK-20260712-001",
    "items": [
      { "erp_id": "10025", "sku": "HLBPH4069FS1EMWR",
        "qty": 250, "available_qty": 240, "stock_status": "in_stock" }
    ]
  }'
```

### Solo precios

```bash
curl -X POST https://web.example.com/api/sync/prices \
  -H "Authorization: Bearer $SYNC_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "sync_id": "ERP-PRICE-20260712-001",
    "items": [
      { "erp_id": "10025", "sku": "HLBPH4069FS1EMWR",
        "price": 19.5, "compare_at_price": 22.0,
        "is_promotion": true, "promotion_price": 14.99,
        "promotion_starts_at": "2026-07-12T00:00:00-06:00",
        "promotion_ends_at":   "2026-07-31T23:59:59-06:00" }
    ]
  }'
```

---

## 12. Modelo de datos en la web

```
products
  id, lumen_id, sku, name, slug, short_description, description,
  price, compare_at_price, cost, currency,
  qty, available_qty, reserved_qty, backorder_qty, stock_status,
  image_url, category_id, brand_id,
  is_featured, is_promotion, promotion_price,
  promotion_starts_at, promotion_ends_at,
  specs (json), tags (json), certifications (json),
  weight, dimensions, upc, mpn,
  is_active, synced_at, erp_last_sync_at, timestamps

product_variants
  id, product_id, lumen_variant_id, sku, name,
  price, compare_at_price, cost,
  qty, available_qty, reserved_qty, stock_status,
  attributes (json), image_url, is_active, erp_last_sync_at

product_images                     product_variant_images
  id, product_id, image_url,         id, product_variant_id, image_url,
  alt, sort_order, is_primary        alt, sort_order, is_primary

product_technical_specs            product_documents
  id, product_id, group, key,        id, product_id, type, title,
  label, value, unit, sort_order     url, language, sort_order

product_related
  id, product_id, related_erp_id, related_sku,
  relation_type, sort_order, resolved_at

processed_syncs                    sync_logs (legacy)
  sync_id (PK), source, mode,        id, source, status, products_synced,
  status, items_processed,           categories_synced, brands_synced,
  items_failed, summary (json),      message, finished_at, timestamps
  received_at, finished_at
```

---

## 13. Validacion de entrada (reglas que se aplican)

| Endpoint | Reglas |
|---|---|
| catalog | `sync_id` requerido, `products[].sku/name` requeridos. |
| products | Mismas reglas. |
| inventory | `items[].(sku xor erp_id)` requerido, `items[].qty` requerido. |
| prices | `items[].(sku xor erp_id)` requerido. |

Si una validacion falla, la web responde `422` con detalle por campo y
**no aplica nada** del lote.

---

## 14. Pre-requisitos del ERP (pull)

Si la web hara pull contra el ERP, el ERP debe:

1. Exponer los endpoints listados en la seccion 5.
2. Validar `Authorization: Bearer {ERP_API_KEY}`.
3. Responder JSON con la estructura `{ "data": [...] }` o lista directa.
4. Responder 4xx/5xx coherentes (la web loguea y omite la lista).

---

## 15. Operacion desde el admin de la web

Pagina: `/admin/sync`

Bloques disponibles:

- **Ejecutar sync directa** -> `lumen:sync` (coneccion DB directa al
  ERP MySQL). Para entornos donde el ERP no expone REST.
- **Actualizar desde ERP** (pull HTTP) -> `erp:pull --type=...`.
  Selector: full / products / categories / brands / inventory / prices.
- **Regenerar API key** -> Cambia la clave Bearer que el ERP usa para
  push.
- **Historial** -> Ultimas 20 ejecuciones con conteos.

---

## 16. Versionado

- La API actual es `v1` (implícita).
- Cambios compatibles (campo opcional nuevo): la web acepta y rellena
  defaults.
- Cambios incompatibles: se introducira `/api/v2/sync/*` y se mantendra
  la v1 un ciclo de deprecation.

---

## 17. Soporte

Cualquier problema:

- Backend (ecommerce): revisar logs en `storage/logs/laravel.log` y tabla
  `sync_logs` + `processed_syncs`.
- Si el ERP ve `401`: regenerar `sync_api_key` y actualizar en el ERP.
- Si la web ve `partial_success`: revisar `errors[]` y aplicar
  correcciones en el ERP antes del siguiente intento.
