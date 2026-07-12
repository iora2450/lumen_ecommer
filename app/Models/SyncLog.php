<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'source', 'status', 'products_synced',
        'categories_synced', 'brands_synced', 'message', 'finished_at',
    ];

    protected $casts = [
        'finished_at' => 'datetime',
    ];
}