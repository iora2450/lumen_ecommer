# Produccion · Configuracion de Cron, Queue y Sync ERP

Esta guia deja la web lista para sincronizar productos del ERP de forma automatica en produccion.

## 1) Variables .env de produccion

Ajusta estas variables en tu `.env` del servidor:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Cola real (no usar sync en produccion)
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

# ERP HTTP pull
ERP_BASE_URL=https://erp.tu-dominio.com/api
ERP_API_KEY=tu_token_secreto
ERP_TIMEOUT=30
ERP_RETRY_ATTEMPTS=3
ERP_RETRY_DELAY=2

# Scheduler ERP (habilitado)
ERP_PULL_SCHEDULE_ENABLED=true
ERP_PULL_FULL_AT=03:00
ERP_PULL_INVENTORY_CRON=*/15 * * * *
ERP_PULL_PRICES_CRON=0 * * * *
```

Notas:
- `QUEUE_CONNECTION=sync` ejecuta todo en la misma peticion y no escala.
- En produccion usa `redis` + worker activo.

## 2) Build y optimizaciones Laravel

Ejecuta por SSH dentro del proyecto:

```bash
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si usas migraciones pendientes:

```bash
php artisan migrate --force
```

## 3) Cron del scheduler (obligatorio)

En el servidor, abre crontab:

```bash
crontab -e
```

Agrega una sola linea:

```cron
* * * * * cd /var/www/lumens-ecommerce && php artisan schedule:run >> /var/log/lumens-schedule.log 2>&1
```

Cambia la ruta por la ruta real de tu proyecto.

## 4) Worker de colas (obligatorio)

Se recomienda `supervisor` para mantener `queue:work` vivo.

Ejemplo de archivo `/etc/supervisor/conf.d/lumens-worker.conf`:

```ini
[program:lumens-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/lumens-ecommerce/artisan queue:work redis --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/lumens-worker.log
stopwaitsecs=3600
```

Aplicar cambios:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

## 5) Si despliegas con Docker Compose

Este proyecto ya trae servicios `scheduler` y `queue` en `compose.yaml`.

Levanta todo:

```bash
docker compose up -d --build
```

Verifica que esten arriba:

```bash
docker compose ps
```

Debes ver activos al menos:
- `lumens-web-app`
- `lumens-web-nginx`
- `lumens-web-redis`
- `lumens-web-queue`
- `lumens-web-scheduler`

## 6) Verificacion funcional

### 6.1 Ver tareas programadas

```bash
php artisan schedule:list
```

Debes ver:
- `lumen:sync`
- `erp:pull --type=full`
- `erp:pull --type=inventory`
- `erp:pull --type=prices`

### 6.2 Prueba manual de pull

```bash
php artisan erp:pull --type=products --dry-run
php artisan erp:pull --type=inventory --dry-run
php artisan erp:pull --type=prices --dry-run
```

### 6.3 Revisar estado API sync

```bash
curl -s https://tu-dominio.com/api/sync/health
curl -s https://tu-dominio.com/api/sync/status
```

## 7) Troubleshooting rapido

### Caso: no se sincroniza nada

Revisar:
- `ERP_PULL_SCHEDULE_ENABLED=true`
- cron instalado y ejecutando cada minuto
- worker de cola activo
- conectividad a Redis
- `ERP_BASE_URL` y `ERP_API_KEY` correctos

Logs utiles:

```bash
tail -f storage/logs/laravel.log
tail -f /var/log/lumens-schedule.log
tail -f /var/log/lumens-worker.log
```

### Caso: se queda en cola

Revisar:
- `QUEUE_CONNECTION=redis`
- proceso `queue:work` corriendo
- Redis accesible desde app

## 8) Comandos de mantenimiento

Reiniciar workers tras deploy:

```bash
php artisan queue:restart
```

Forzar una corrida ahora mismo:

```bash
php artisan schedule:run
```

## 9) Recomendacion de frecuencia

- Catalogo completo: diario en madrugada (`03:00`).
- Inventario: cada 5 a 15 minutos.
- Precios: cada 1 hora (o mas frecuente si el negocio lo necesita).

---

Checklist final:
- [ ] `.env` de produccion actualizado.
- [ ] cron de `schedule:run` activo.
- [ ] worker de colas activo (supervisor o servicio queue en Docker).
- [ ] `php artisan schedule:list` muestra tareas.
- [ ] pruebas `--dry-run` sin errores.
