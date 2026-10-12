<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductImageMirror
{
    /**
     * Descarga una imagen del ERP y devuelve su ruta publica en este sitio.
     */
    public function mirror(?string $source): ?string
    {
        $source = $this->firstImage($source);

        if ($source === null) {
            return null;
        }

        if ($localPath = $this->localPath($source)) {
            return $localPath;
        }

        try {
            $url = $this->sourceUrl($source);

            if ($url === null || ! $this->isAllowedSource($url)) {
                throw new RuntimeException('El origen de la imagen no esta autorizado.');
            }

            $response = Http::connectTimeout((int) config('erp.product_images.connect_timeout', 5))
                ->timeout((int) config('erp.product_images.timeout', 20))
                ->retry((int) config('erp.product_images.retry_attempts', 2), 250)
                ->withOptions(['allow_redirects' => false])
                ->get($url);

            if (! $response->successful()) {
                throw new RuntimeException("El servidor de imagenes respondio HTTP {$response->status()}.");
            }

            $contents = $response->body();
            $maxBytes = (int) config('erp.product_images.max_bytes', 8 * 1024 * 1024);

            if ($contents === '' || strlen($contents) > $maxBytes) {
                throw new RuntimeException('La imagen esta vacia o excede el limite permitido.');
            }

            $imageInfo = @getimagesizefromstring($contents);
            $extension = $imageInfo ? image_type_to_extension($imageInfo[2], false) : false;
            $extension = $extension === 'jpeg' ? 'jpg' : $extension;

            if (! in_array($extension, ['jpg', 'png', 'gif', 'webp', 'avif'], true)) {
                throw new RuntimeException('El archivo recibido no es una imagen compatible.');
            }

            $filename = hash('sha256', $contents).'.'.$extension;
            $disk = (string) config('erp.product_images.disk', 'product_images');

            if (! Storage::disk($disk)->exists($filename) && ! Storage::disk($disk)->put($filename, $contents)) {
                throw new RuntimeException('No se pudo guardar la imagen en el hosting.');
            }

            return $this->publicPrefix().'/'.$filename;
        } catch (\Throwable $e) {
            Log::warning('No se pudo copiar una imagen del ERP al hosting.', [
                'source' => $source,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function sourceUrl(string $source): ?string
    {
        if (preg_match('#^https?://#i', $source)) {
            return $source;
        }

        $filename = basename(str_replace('\\', '/', urldecode($source)));

        if ($filename === '' || $filename === '.' || $filename === '..') {
            return null;
        }

        $baseUrl = rtrim((string) config('erp.legacy_public_url'), '/');
        $imagePath = trim((string) config('erp.legacy_product_image_path'), '/');

        if ($baseUrl === '') {
            return null;
        }

        return $baseUrl.'/'.($imagePath !== '' ? $imagePath.'/' : '').rawurlencode($filename);
    }

    protected function isAllowedSource(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        $configuredHosts = (array) config('erp.product_images.allowed_hosts', []);
        $serviceHosts = [
            parse_url((string) config('erp.legacy_public_url'), PHP_URL_HOST),
            parse_url((string) config('erp.base_url'), PHP_URL_HOST),
        ];

        $allowedHosts = array_values(array_unique(array_filter(array_map(
            fn ($allowedHost) => strtolower(trim((string) $allowedHost)),
            [...$configuredHosts, ...$serviceHosts]
        ))));

        return in_array($host, $allowedHosts, true);
    }

    protected function localPath(string $source): ?string
    {
        $prefix = $this->publicPrefix();
        $path = parse_url($source, PHP_URL_PATH) ?: $source;

        if (preg_match('#^https?://#i', $source)) {
            $sourceHost = strtolower((string) parse_url($source, PHP_URL_HOST));
            $appHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

            if ($sourceHost === '' || $sourceHost !== $appHost) {
                return null;
            }
        }

        if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
            return $path;
        }

        return null;
    }

    protected function publicPrefix(): string
    {
        return '/'.trim((string) config('erp.product_images.public_path', '/images/products/synced'), '/');
    }

    protected function firstImage(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $image = trim(explode(',', $value)[0]);

        return $image !== '' ? $image : null;
    }
}
