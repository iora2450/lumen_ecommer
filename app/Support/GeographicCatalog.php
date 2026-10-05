<?php

namespace App\Support;

use JsonException;
use RuntimeException;

class GeographicCatalog
{
    private ?array $catalogs = null;

    public function departments(): array
    {
        return $this->catalog('CAT-012_departamentos');
    }

    public function municipalities(): array
    {
        return $this->catalog('CAT-013_municipios');
    }

    public function districts(): array
    {
        return $this->catalog('CAT-008_distritos');
    }

    public function departmentCodes(): array
    {
        return array_column($this->departments(), 'codigo');
    }

    public function findDepartment(string $code): ?array
    {
        return $this->find($this->departments(), $code);
    }

    public function findMunicipality(string $departmentCode, string $code): ?array
    {
        return $this->find($this->municipalities(), $code, $departmentCode);
    }

    public function findDistrict(string $departmentCode, string $code): ?array
    {
        return $this->find($this->districts(), $code, $departmentCode);
    }

    private function find(array $items, string $code, ?string $departmentCode = null): ?array
    {
        foreach ($items as $item) {
            if ($item['codigo'] === trim($code)
                && ($departmentCode === null || $item['departamento'] === trim($departmentCode))) {
                return $item;
            }
        }

        return null;
    }

    private function catalog(string $key): array
    {
        return $this->load()[$key];
    }

    private function load(): array
    {
        if ($this->catalogs !== null) {
            return $this->catalogs;
        }

        $path = resource_path('data/catalogos_geograficos_dte_sv.json');
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el catálogo geográfico para DTE.');
        }

        try {
            $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('El catálogo geográfico para DTE no contiene JSON válido.', previous: $exception);
        }

        $catalogs = $data['catalogos'] ?? null;

        if (! is_array($catalogs)) {
            throw new RuntimeException('El catálogo geográfico para DTE no tiene la estructura esperada.');
        }

        $this->catalogs = [
            'CAT-012_departamentos' => $this->validItems($catalogs['CAT-012_departamentos'] ?? [], false),
            'CAT-013_municipios' => $this->validItems($catalogs['CAT-013_municipios'] ?? [], true),
            'CAT-008_distritos' => $this->validItems($catalogs['CAT-008_distritos'] ?? [], true),
        ];

        return $this->catalogs;
    }

    private function validItems(mixed $items, bool $requiresDepartment): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter($items, function ($item) use ($requiresDepartment) {
            return is_array($item)
                && isset($item['codigo'], $item['nombre'])
                && is_string($item['codigo'])
                && is_string($item['nombre'])
                && (! $requiresDepartment || (isset($item['departamento']) && is_string($item['departamento'])));
        }));
    }
}
