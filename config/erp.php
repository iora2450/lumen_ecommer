<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ERP base URL (push + pull)
    |--------------------------------------------------------------------------
    |
    | URL base del ERP (Sistema Lumen u otro sistema corporativo) desde donde
    | esta web sincroniza catalogos, productos, inventario y precios.
    |
    */
    'base_url' => env('ERP_BASE_URL', 'https://erp.example.com/api'),

    /*
    |--------------------------------------------------------------------------
    | URL publica del Sistema Lumen legacy
    |--------------------------------------------------------------------------
    |
    | La DB legacy guarda algunas imagenes solo como nombre de archivo. Esta
    | URL permite resolverlas desde el sitio viejo mientras se migran a CDN.
    |
    */
    'legacy_public_url' => env('LUMEN_PUBLIC_URL', 'http://localhost:8084'),
    'legacy_product_image_path' => env('LUMEN_PRODUCT_IMAGE_PATH', 'public/images/product'),

    /*
    |--------------------------------------------------------------------------
    | API key compartida
    |--------------------------------------------------------------------------
    |
    | Token estatico que esta web envia al ERP (pull) y que el ERP envia
    | en cada webhook (push). Se valida con hash_equals().
    |
    */
    'api_key' => env('ERP_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Timeouts y reintentos
    |--------------------------------------------------------------------------
    */
    'timeout' => env('ERP_TIMEOUT', 30),
    'retry_attempts' => env('ERP_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('ERP_RETRY_DELAY', 2),

    /*
    |--------------------------------------------------------------------------
    | Endpoints del ERP (pull)
    |--------------------------------------------------------------------------
    |
    | Rutas que la web llama cuando hace pull desde el ERP.
    |
    */
    'endpoints' => [
        'health'         => '/health',
        'categories'     => '/categories',
        'brands'         => '/brands',
        'products'       => '/products',
        'product_show'   => '/products/{id}',
        'inventory'      => '/inventory',
        'prices'         => '/prices',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autenticacion del ERP -> web (push)
    |--------------------------------------------------------------------------
    |
    | Claves que la web acepta en headers Authorization o X-ERP-Token.
    | Por defecto se valida contra Setting::get('sync_api_key'); aqui
    | podemos definir fallbacks.
    |
    */
    'inbound_headers' => [
        'bearer'  => 'Authorization',
        'custom'  => 'X-ERP-Token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Origen de los webhooks entrantes
    |--------------------------------------------------------------------------
    */
    'inbound_source' => env('ERP_INBOUND_SOURCE', 'erp'),

    /*
    |--------------------------------------------------------------------------
    | Limites por lote
    |--------------------------------------------------------------------------
    */
    'max_items_per_batch' => env('ERP_MAX_ITEMS_PER_BATCH', 1000),

    /*
    |--------------------------------------------------------------------------
    | Idempotencia
    |--------------------------------------------------------------------------
    |
    | Si es true, los webhooks con sync_id ya procesado se rechazan con 200
    | y resumen del resultado previo (no se vuelven a aplicar).
    |
    */
    'enforce_idempotency' => env('ERP_ENFORCE_IDEMPOTENCY', true),

];
