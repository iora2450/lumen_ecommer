# Arquitectura inicial

## Aplicación

- Laravel 13
- PHP 8.4
- Blade + Tailwind CSS
- MySQL 8.4
- Redis para caché, sesiones y colas
- Nginx
- Mailpit para pruebas de correo

## Contenedores

| Servicio | Uso | Puerto local |
|---|---|---:|
| nginx | Sitio web | 8090 |
| app | PHP-FPM | interno |
| db | MySQL | 3312 |
| redis | Caché y colas | interno |
| queue | Trabajos en segundo plano | interno |
| scheduler | Sincronización programada | interno |
| mailpit | Correos de desarrollo | 8026 |

## Integración con Sistema Lumens

La tienda tendrá su propia base de datos. El ERP seguirá siendo la fuente maestra para productos, categorías, marcas, precios, promociones, existencias, imágenes y variantes.

La sincronización usará registros de auditoría, upserts por identificador del ERP y credenciales independientes. Las cotizaciones y contenidos del sitio pertenecerán exclusivamente al e-commerce.

La existencia publicable se calculará desde `product_warehouse`, no únicamente desde `products.qty`. Se excluirán servicios, anticipos, registros de prueba y productos inactivos mediante reglas configurables.
