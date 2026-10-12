<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErpOrderDelivery extends Model
{
    protected $guarded = [];
    protected $hidden = ['payload'];
    protected $casts = [
        'payload' => 'array', 'erp_status' => 'array',
        'delivered_at' => 'datetime', 'next_attempt_at' => 'datetime', 'checked_at' => 'datetime',
    ];
}
