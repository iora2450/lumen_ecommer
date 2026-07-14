<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedSync extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'sync_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sync_id',
        'source',
        'mode',
        'status',
        'items_processed',
        'items_failed',
        'summary',
        'received_at',
        'finished_at',
    ];

    protected $casts = [
        'items_processed' => 'integer',
        'items_failed' => 'integer',
        'summary' => 'array',
        'received_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function markFinished(string $status, array $summary = []): void
    {
        $this->update([
            'status' => $status,
            'summary' => $summary,
            'items_processed' => $summary['processed'] ?? $this->items_processed,
            'items_failed' => $summary['failed'] ?? $this->items_failed,
            'finished_at' => now(),
        ]);
    }
}
