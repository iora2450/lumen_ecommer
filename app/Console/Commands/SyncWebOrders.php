<?php

namespace App\Console\Commands;

use App\Models\ErpOrderDelivery;
use App\Services\Erp\WebOrderDelivery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncWebOrders extends Command
{
    protected $signature = 'web-orders:sync {--limit=50}';
    protected $description = 'Entrega recibos de ventas web al ERP; nunca emite ni consulta DTE.';

    public function handle(WebOrderDelivery $service): int
    {
        if (! config('web_orders.enabled')) {
            $this->info('Integración de pedidos desactivada.');
            return self::SUCCESS;
        }
        if (! config('web_orders.base_url') || ! config('web_orders.token')) {
            $this->error('Falta configurar URL o token de pedidos web.');
            return self::FAILURE;
        }
        $rows = ErpOrderDelivery::whereNull('delivered_at')->where(function ($q) {
            $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now());
        })->orderBy('next_attempt_at')->limit(max(1, min(500, (int) $this->option('limit'))))->get();
        foreach ($rows as $row) {
            // Separate per-order lock also protects simultaneous manual command runs.
            $lock = Cache::lock('web-order-delivery-'.$row->id, 60);
            if (! $lock->get()) {
                continue;
            }
            try {
                $row->refresh();
                $service->sync($row);
            } finally {
                $lock->release();
            }
        }
        $this->info('Pedidos revisados: '.$rows->count());
        return self::SUCCESS;
    }
}
