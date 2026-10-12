<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('erp_order_deliveries')) {
            return;
        }

        DB::table('erp_order_deliveries')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                $payload = json_decode($row->payload, true) ?: [];
                $acknowledgement = $row->delivered_at ? [
                    'source' => $payload['source'] ?? null,
                    'external_id' => $payload['external_id'] ?? null,
                    'document_type' => 'receipt',
                    'receipt_number' => $payload['order_number'] ?? null,
                    'receipt_status' => 'received',
                    'received_at' => $row->delivered_at,
                ] : null;

                DB::table('erp_order_deliveries')->where('id', $row->id)->update([
                    'erp_status' => $acknowledgement ? json_encode($acknowledgement) : null,
                    'next_attempt_at' => $row->delivered_at ? null : $row->next_attempt_at,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Fiscal identifiers intentionally cannot be restored to ecommerce.
    }
};
