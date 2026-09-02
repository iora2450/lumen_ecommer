<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('delivery_method', 24)->nullable()->after('shipping_address');
            $table->string('payment_method', 40)->nullable()->after('delivery_method');
            $table->string('payment_status', 24)->default('not_applicable')->after('payment_method');
        });

        DB::table('quotes')
            ->where('request_type', 'purchase')
            ->update([
                'payment_method' => 'pending_coordination',
                'payment_status' => 'pending_coordination',
            ]);
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['delivery_method', 'payment_method', 'payment_status']);
        });
    }
};
