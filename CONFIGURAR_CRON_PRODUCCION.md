# Configurar sincronización automática en producción

Esta guía configura la sincronización entre el ecommerce de Lumens y la base de datos de Sistema Lumen cada 6 horas.

## Datos del servidor

- Proyecto Laravel: `/home/u712987967/domains/lumens.skytechsv.com/public_html`
- Dominio: `lumens.skytechsv.com`
- PHP predeterminado de SSH: `/usr/bin/php` — actualmente PHP 7.4.33
- PHP requerido por el proyecto: PHP 8.4.1 o superior

> No se debe usar `/usr/bin/php` para ejecutar Artisan mientras continúe apuntando a PHP 7.4.

## 1. Configurar PHP 8.4 en hPanel

En Hostinger, ingresar a:

1. **Sitios web**
2. **lumens.skytechsv.com**
3. **PHP Configuration**
4. Seleccionar **PHP 8.4**
5. Presionar **Actualizar**

Después de actualizar, conectarse por SSH y comprobar si está disponible el ejecutable:

```bash
/opt/alt/php84/usr/bin/php -v
```

Debe mostrar PHP 8.4.1 o una versión superior.

Si esa ruta no existe, listar los ejecutables PHP disponibles:

```bash
ls -1 /opt/alt/php*/usr/bin/php 2>/dev/null
```

En los siguientes ejemplos se utiliza:

```text
/opt/alt/php84/usr/bin/php
```

Si Hostinger muestra otra ruta para PHP 8.4, reemplazarla en todos los comandos.

## 2. Revisar el `.env` de producción

Entrar al directorio del proyecto:

```bash
cd /home/u712987967/domains/lumens.skytechsv.com/public_html
```

El archivo `.env` debe contener la conexión hacia Sistema Lumen:

```env
LUMEN_DB_CONNECTION=mysql
LUMEN_DB_HOST=31.97.140.171
LUMEN_DB_PORT=3306
LUMEN_DB_DATABASE=sistemadbtest
LUMEN_DB_USERNAME=USUARIO_REAL
LUMEN_DB_PASSWORD=CONTRASENA_REAL
```

No publicar ni compartir el usuario y la contraseña reales.

Después de modificar el `.env`, limpiar y reconstruir la configuración:

```bash
/opt/alt/php84/usr/bin/php artisan config:clear
/opt/alt/php84/usr/bin/php artisan config:cache
```

## 3. Comprobar la tarea programada

Ejecutar:

```bash
/opt/alt/php84/usr/bin/php artisan schedule:list
```

Debe aparecer una tarea similar a:

```text
0 */6 * * *  php artisan lumen:sync
```

La aplicación ya define `lumen:sync` para ejecutarse cada 6 horas.

## 4. Probar la sincronización manualmente

Desde el directorio del proyecto:

```bash
/opt/alt/php84/usr/bin/php artisan lumen:sync
```

Una sincronización correcta debe indicar cantidades mayores que cero, por ejemplo:

```text
Categorías: 34
Marcas: 47
Productos: 169
```

Si aparece un mensaje como este:

```text
No se pudo conectar a la DB de Lumen
Operation timed out
```

el servidor web no puede conectarse a `31.97.140.171:3306`. En ese caso se debe autorizar la IP pública del servidor de Hostinger en:

- El firewall del servidor `31.97.140.171`.
- La configuración de MySQL.
- Los permisos del usuario MySQL utilizado por el ecommerce.

## 5. Crear el cron en hPanel

Ingresar a:

**Sitios web → lumens.skytechsv.com → Avanzado → Trabajos cron**

Configurar la ejecución cada minuto:

```text
Minuto:          *
Hora:            *
Día:             *
Mes:             *
Día de la semana: *
```

Comando completo:

```bash
cd /home/u712987967/domains/lumens.skytechsv.com/public_html && /opt/alt/php84/usr/bin/php artisan schedule:run >> /home/u712987967/domains/lumens.skytechsv.com/public_html/storage/logs/scheduler.log 2>&1
```

El cron se inicia cada minuto, pero Laravel solo ejecuta `lumen:sync` cuando corresponde: a las `00:00`, `06:00`, `12:00` y `18:00`, según la zona horaria configurada por la aplicación.

## 6. Probar el scheduler

Ejecutar manualmente:

```bash
cd /home/u712987967/domains/lumens.skytechsv.com/public_html
/opt/alt/php84/usr/bin/php artisan schedule:run
```

Revisar los registros:

```bash
tail -n 100 storage/logs/scheduler.log
tail -n 100 storage/logs/laravel.log
```

Para observar el registro del cron en tiempo real:

```bash
tail -f storage/logs/scheduler.log
```

## 7. Alternativa: ejecutar únicamente la sincronización

Si el plan de Hostinger no permite ejecutar un cron cada minuto, se puede configurar directamente cada 6 horas:

```cron
0 */6 * * * cd /home/u712987967/domains/lumens.skytechsv.com/public_html && /opt/alt/php84/usr/bin/php artisan lumen:sync >> /home/u712987967/domains/lumens.skytechsv.com/public_html/storage/logs/lumen-sync.log 2>&1
```

La primera opción, usando `artisan schedule:run`, es la recomendada porque también permite que Laravel controle otras tareas programadas.

## 8. Verificación final

Comprobar lo siguiente:

- [ ] El sitio utiliza PHP 8.4.
- [ ] `/opt/alt/php84/usr/bin/php -v` funciona.
- [ ] El `.env` contiene las credenciales `LUMEN_DB_*` correctas.
- [ ] `artisan schedule:list` muestra `lumen:sync` cada 6 horas.
- [ ] `artisan lumen:sync` puede leer productos, categorías y marcas.
- [ ] El cron está creado y habilitado en hPanel.
- [ ] `storage/logs/scheduler.log` recibe nuevas entradas.
- [ ] El servidor `31.97.140.171` permite conexiones MySQL desde Hostinger.

## Advertencia sobre el historial actual

Actualmente el sincronizador puede registrar una ejecución como exitosa aunque la conexión a Sistema Lumen falle y se hayan procesado cero registros. Por eso, además del estado `success`, se deben revisar las cantidades sincronizadas y los mensajes de error.
