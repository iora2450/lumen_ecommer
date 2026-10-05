<?php

namespace App\Support;

use JsonException;
use RuntimeException;

class EconomicActivityCatalog
{
    private ?array $activities = null;

    private ?array $activitiesByCode = null;

    public function all(): array
    {
        return $this->activities ??= $this->load();
    }

    public function codes(): array
    {
        return array_keys($this->byCode());
    }

    public function find(string $code): ?array
    {
        return $this->byCode()[trim($code)] ?? null;
    }

    private function byCode(): array
    {
        if ($this->activitiesByCode !== null) {
            return $this->activitiesByCode;
        }

        $this->activitiesByCode = [];

        foreach ($this->all() as $activity) {
            $this->activitiesByCode[$activity['codigo']] = $activity;
        }

        return $this->activitiesByCode;
    }

    private function load(): array
    {
        $path = resource_path('data/CAT-019_actividades_economicas.json');
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el catálogo de actividades económicas.');
        }

        try {
            $activities = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('El catálogo de actividades económicas no contiene JSON válido.', previous: $exception);
        }

        return array_values(array_filter($activities, fn ($activity) => is_array($activity)
            && isset($activity['codigo'], $activity['actividad_economica'])
            && is_string($activity['codigo'])
            && is_string($activity['actividad_economica'])
        ));
    }
}
