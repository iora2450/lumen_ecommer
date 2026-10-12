# Recibos web con facturación fiscal manual

Implementación en `lumensistema` y `lumens-ecommerce`.

## Comportamiento

1. El checkout de ecommerce guarda la compra y un recibo comercial de salida en la misma transacción. Ese recibo no es una factura fiscal.
2. `web-orders:sync` entrega el recibo a lumensistema. Un fallo no lo pierde; los reintentos conservan el mismo identificador y contenido. Después de que el ERP confirma la recepción, ecommerce deja de consultar y no recibe datos fiscales.
3. En **Ventas → Recibos web**, el operador asocia cliente, bodega, vendedor y cuenta; revisa los datos del receptor y guarda la revisión.
4. Un operador autorizado confirma el **pago completo**, método y referencia del comprobante verificado. Esto NO crea venta, mueve inventario ni envía DTE.
5. Otro clic explícito en **Generar y enviar factura fiscal a Hacienda** crea el número de control y código de generación, registra venta y pago, descuenta inventario y transmite el DTE. El sello solo se guarda al recibir la aceptación fiscal. No existen tareas programadas de emisión de DTE.
6. La aceptación habilita descarga de PDF y JSON. Ecommerce consulta el resultado y lo muestra en la administración de la compra.

El cliente del sistema se selecciona explícitamente; si es nuevo se crea en Clientes. Los datos fiscales revisados se conservan como una instantánea de este pedido, sin sobrescribir el cliente global. Para consumidor final se completan los códigos de departamento/municipio en la revisión si la compra solo contiene una dirección de entrega.

## Instalación

Aplicar primero las migraciones y luego activar la integración. No ejecutar `migrate:fresh` ni recrear la base existente.

En **lumensistema**, con su base instalada:

```sh
php artisan migrate --path=database/migrations/2026_10_10_000001_create_web_orders_tables.php --force
php artisan config:clear
```

La migración requiere las tablas existentes de ventas, roles y permisos. Crea `web_orders` y `web_order_events`, además de tres permisos. Los asigna inicialmente a roles 1 y 2; se pueden administrar en la pantalla de permisos:

- `sales-index`: acceso a la bandeja y documentos.
- `web-orders-review`: revisar y cancelar pedidos que aún no tienen venta.
- `web-orders-confirm-payment`: confirmar pago completo.
- `web-orders-process`: transmitir y consultar el DTE.

En **lumens-ecommerce**:

```sh
php artisan migrate --path=database/migrations/2026_10_10_000001_create_erp_order_deliveries_table.php --force
php artisan migrate --path=database/migrations/2026_10_11_000001_add_purchase_tax_snapshots.php --force
php artisan config:clear
```

Configurar un token aleatorio independiente de al menos 32 caracteres, igual en ambos `.env`. No incluirlo en Git ni en el frontend:

```dotenv
WEB_ORDERS_TOKEN=<token-compartido>
WEB_ORDERS_SOURCE=lumens-ecommerce
```

Solo en ecommerce:

```dotenv
WEB_ORDERS_ENABLED=true
WEB_ORDERS_ERP_URL=https://servidor-del-sistema/sistema/api
```

La URL debe terminar en `/api` e incluir el subdirectorio de la instalación, cuando corresponda. Para contenedores locales separados puede utilizarse `http://host.docker.internal:8084/api`. Esa URL solo sirve si lumensistema tiene su base local inicializada.

Solo en lumensistema:

```dotenv
WEB_ORDERS_DTE_URL=<endpoint-del-firmador-y-receptor>
WEB_ORDERS_DTE_STATUS_URL=<endpoint-de-consulta-del-proveedor>
WEB_ORDERS_DTE_TOKEN=<token-del-proveedor-si-lo-requiere>
WEB_ORDERS_ESTABLISHMENT=M001P001
```

El emisor, ambiente y credencial privada se leen del `app/Http/Controllers/company.json` que ya utiliza el sistema. El documento guardado NO contiene la credencial privada. Configure explícitamente el endpoint del ambiente adecuado; este módulo no infiere producción a partir de APP_URL.

Mantener activo el scheduler existente de ecommerce (`schedule:work` en Docker o `schedule:run` cada minuto). La tarea nueva corre cada minuto, usa bloqueo por recibo y reintentos con espera progresiva hasta obtener el acuse de recepción del ERP. Después se detiene y no consulta la factura fiscal. No requiere un worker de colas. Para comprobar manualmente la entrega:

```sh
php artisan web-orders:sync --limit=10
```

Las compras nuevas generan mensajes incluso mientras la integración está desactivada, para enviarlos al activarla. No se importan automáticamente compras históricas anteriores a esta migración.

Antes de habilitar emisión, ejecutar en lumensistema:

```sh
php artisan web-orders:check
```

Este comando verifica las tablas, columnas y configuración indispensables sin transmitir documentos ni imprimir credenciales. Una URL configurada no garantiza que el proveedor implemente el contrato: completar también la prueba de integración en su ambiente de pruebas.

## Contrato entre aplicaciones

- `POST /api/web-orders`: recibe un recibo comercial versión 2 (precios sin IVA), origen, ID externo, `document_type=receipt`, número de recibo, cliente, productos, moneda USD y totales.
- `GET /api/web-orders/{external_id}?source=lumens-ecommerce`: solo vuelve a confirmar la recepción del recibo para compatibilidad e idempotencia.
- Ambos endpoints requieren `Authorization: Bearer <WEB_ORDERS_TOKEN>`.
- Una repetición idéntica devuelve el pedido existente. Reutilizar origen/ID con otro contenido devuelve HTTP 409 y nunca sobrescribe el pedido.
- El acuse contiene únicamente origen, ID externo, tipo, número y estado del recibo. Nunca devuelve número de control, código de generación, sello, pago, venta ni estado fiscal.
- No hay endpoint de integración que confirme pagos, emita documentos o consulte una factura fiscal.

Se transmiten IDs de productos del ERP (`lumen_id`) y SKU, nunca los IDs locales de ecommerce. Las variantes se resuelven por producto/SKU. Los precios, cantidades y descuentos se congelan al comprar. La tasa, el descuento, el IVA y el total por línea se congelan también. Al registrar la venta se verifica que la tasa del catálogo ERP coincida con la instantánea; una diferencia bloquea la emisión sin alterar el pago.

Versión 2: `prices_include_tax=false`; `subtotal` es la suma sin IVA; `discount` se distribuye proporcionalmente por línea, en centavos; cada línea lleva `tax_rate`, `discount`, `tax` y `total`. El IVA se redondea por línea sobre la base después del descuento. El `tax` del pedido suma ese IVA y `total = subtotal - discount + tax`. El receptor recalcula y valida esos importes. Se conserva la recepción de versión 1 con IVA incluido para mensajes históricos inmutables; no se recalculan pedidos anteriores. Desplegar primero el receptor ERP compatible con ambas versiones.

## Contrato con el proveedor DTE

El endpoint de envío recibe `POST application/x-www-form-urlencoded`, con un campo `json_variable`. Su contenido JSON mantiene el contrato del sistema anterior: `nit`, `activo`, `passwordPri` y `dteJson`.

Se espera una respuesta JSON con `estado`, `codigoGeneracion`, `selloRecibido`, `descripcionMsg` y, opcionalmente, `codigoMsg`, `observaciones` y `fhProcesamiento`. Solo `PROCESADO`/`RECIBIDO` con sello válido se consideran aceptación. Una respuesta explícita `RECHAZADO` permite corregir al receptor y volver a procesar la misma venta y código. Una respuesta de documento duplicado exige consulta.

El endpoint de consulta debe aceptar `POST application/x-www-form-urlencoded` con `ambiente`, `codigoGeneracion` y `tipoDte`; devolver el resultado fiscal de **ese código**, con la misma estructura y `codigoGeneracion` obligatorio. Si el servicio existente no ofrece este contrato, hay que implementar/configurar su adaptador antes de habilitar recuperación por consulta. No basta con colocar la URL de recepción en la variable de consulta.

No se realizó ninguna transmisión real a Hacienda durante el desarrollo. Falta comprobar la compatibilidad del proveedor real y la aceptación en su ambiente de pruebas antes de activar emisión operativa.

## Consistencia y límites

- **IVA:** los precios publicados no incluyen IVA. El carrito muestra subtotal, descuento, IVA y total antes de finalizar. Ejemplo: $100 sin descuento + $13 IVA = $113; solo un pago de $113 habilita emisión. Cupones se aplican antes del IVA; productos exentos conservan tasa cero.
- La sincronización de productos importa `tax_rate` (0 o 13); el comando directo `lumen:sync` resuelve `products.tax_id` contra `taxes.rate`. El API de sincronización permite `tax_rate`; si se omite conserva la tasa existente. La migración asigna 13 a los productos existentes: **sincronizar y revisar el catálogo antes de aceptar nuevas compras**, especialmente productos exentos. Las variantes heredan la tasa del producto.
- Los pedidos nuevos guardan el impuesto por línea; los históricos mantienen sus importes y `tax_amount` nulo. Compras con total cero se rechazan porque este flujo requiere pago completo positivo.
- Se soportan DTE 01 y 03, USD, IVA 13 % o exento, ventas al contado pagadas completas y descuentos distribuidos proporcionalmente en centavos. Los descuentos quedan incluidos en los precios finales de las líneas, con referencia al pedido original.
- El checkout actual no cobra un costo de envío separado; el módulo no agrega un cargo posterior. Si se añade ese cargo en ecommerce, deberá ampliarse el contrato y su tratamiento en el DTE antes de facturarlo.
- La recepción no reserva ni descuenta existencias. El procesamiento valida disponibilidad en la bodega seleccionada y bloquea las filas de inventario. No permite inventario negativo para pedidos web.
- Venta, detalle, pago, movimiento de inventario, asociación al pedido y estado `processing` se confirman en una sola transacción local antes de la llamada externa.
- Doble clic u operadores simultáneos no crean otra venta. Un rechazo o reintento no descuenta otra vez el inventario.
- Timeout, respuesta inválida o caída del proceso dejan el pedido `unknown` o `processing`. Nunca se reenvía automáticamente. Tras dos minutos se permite consultar un envío que sigue en `processing`; un resultado desconocido sigue bloqueado.
- El PDF/JSON del pedido usa la instantánea emitida, no los datos actuales de cliente/producto. No se envía correo de factura automáticamente desde este módulo; se mantienen los correos de recepción de compra existentes.
- Las rutas antiguas de edición, borrado, cobro y retransmisión no pueden modificar/reemitir estas ventas. Los accesos antiguos a sus documentos llevan a Pedidos web.
- Cancelar un pedido pagado sin venta deja constancia de devolución pendiente, pero no realiza reembolsos. Después de registrar la venta se requiere el procedimiento fiscal/de inventario correspondiente.
- Para desactivar el enlace, cambiar `WEB_ORDERS_ENABLED=false` en ecommerce y limpiar la caché de configuración. Conservar tablas e historial; no deshacer migraciones con pedidos ya recibidos.

## Demostración local sin Hacienda

El repositorio incluye un simulador exclusivo para el ambiente local. Devuelve una aceptación sintética, conserva el mismo flujo manual y nunca transmite el documento a Hacienda. La aplicación marca la pantalla, el estado público y el PDF como **SIMULACIÓN LOCAL — Sin validez fiscal**.

Desde `lumensistema`:

```sh
docker exec lumensistema-app php docker/setup-web-orders-local.php
docker compose -f docker-compose.yml -f docker-compose.web-orders-local.yml up -d --no-deps web-orders-simulator
docker exec lumensistema-app php artisan web-orders:check
```

El instalador solo acepta el host MySQL `db`, la base `lumensistema` y el ambiente `local`. Inicializa el esquema base únicamente cuando la base está vacía; después aplica migraciones y datos mínimos repetibles para la demostración. La credencial aleatoria de acceso queda con permisos privados en `storage/app/web-orders-demo-access.json` y no se imprime en consola. Los datos del emisor de demostración viven en `storage/app/web-orders-demo-company.json`.

Desde `lumens-ecommerce`, después de configurar su `.env` local con el mismo `WEB_ORDERS_TOKEN` y `WEB_ORDERS_ERP_URL=http://host.docker.internal:8084/api`:

```sh
docker exec lumens-web-app php artisan migrate --force
docker exec lumens-web-app php artisan lumen:sync
docker exec lumens-web-app php artisan web-orders:sync --limit=10
```

Para este modo deben permanecer `WEB_ORDERS_SIMULATED=true`, `APP_ENV=local` y las dos URL DTE apuntando al host interno `web-orders-simulator`. El gateway rechaza la simulación si alguna de esas condiciones no se cumple. Antes de preparar el ambiente local se guardó una copia privada del `.env` en `storage/app/web-orders-env-before-local.backup`.

## Verificación

```sh
# ERP, usando su runtime PHP 7.4
php vendor/bin/phpunit tests/Feature/WebOrdersTest.php
php vendor/bin/phpunit tests/Unit/SaleMultiWarehouseInventoryTest.php

# Ecommerce
php artisan test --compact
```

Pruebas con SQLite aislado y proveedor DTE simulado: autenticación, mensajes duplicados/modificados, totales con IVA agregado, exentos, redondeo por línea, tasas modificadas, vista previa de cupones, permisos, pago parcial, emisión explícita, inventario, cupones, DTE 01/03, PDF/JSON, rechazo y corrección, timeout, consulta, cancelación, bloqueo del reenvío antiguo y renderizado de la pantalla. No necesitan ni alteran datos productivos.

Estado local verificado: se completó una compra real entre los dos contenedores por $100.00 más $13.00 de IVA. Ecommerce entregó únicamente el recibo y dejó de consultar después del acuse. El ERP exigió revisión y pago completo, mantuvo venta, pago e inventario sin cambios al confirmar el pago y solo creó los identificadores fiscales y registró la venta tras el segundo clic manual. El simulador devolvió aceptación; se creó una venta y un pago por $113.00, el inventario bajó de 20 a 19 y se registró un movimiento de kardex. Los identificadores fiscales permanecieron exclusivamente en lumensistema.

La suite completa antigua de ERP no corre con su PHP 7.4 actual: `tests/Unit/KardexRecordTest.php` contiene sintaxis de argumentos nombrados de PHP 8. Se ejecutaron por separado las pruebas relevantes de este cambio y de inventario, sin modificar ese archivo.
