<?php

namespace App\Services\Erp;

class SyncResult
{
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $failed = 0,
        public array $errors = [],
        public array $summary = [],
    ) {}

    public function addError(string $entity, ?string $erpId, ?string $sku, string $code, string $message): void
    {
        $this->failed++;
        $this->errors[] = [
            'entity' => $entity,
            'erp_id' => $erpId,
            'sku'    => $sku,
            'code'   => $code,
            'message'=> $message,
        ];
    }

    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'failed'  => $this->failed,
            'summary' => $this->summary,
            'errors'  => $this->errors,
        ];
    }
}
