# Lumens E-commerce

Nueva plataforma de catálogo y cotizaciones B2B/B2C para Lumens, construida con Laravel, MySQL y Docker.

## Inicio rápido

1. Copiar `.env.example` a `.env`.
2. Ejecutar `docker compose up -d --build`.
3. Ejecutar `docker compose exec app php artisan key:generate`.
4. Ejecutar `docker compose exec app php artisan migrate`.
5. Abrir `http://localhost:8090`.

El servicio `assets` instala y compila automáticamente los estilos antes de iniciar la aplicación.

Mailpit queda disponible en `http://localhost:8026`.

La documentación funcional y técnica se encuentra en `documentacion/`.
