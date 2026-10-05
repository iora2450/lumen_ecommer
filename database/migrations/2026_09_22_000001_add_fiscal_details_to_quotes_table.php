<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->boolean('requires_fiscal_credit')->default(false)->after('customer_company');
            $table->string('fiscal_legal_name')->nullable()->after('requires_fiscal_credit');
            $table->string('fiscal_nit', 25)->nullable()->after('fiscal_legal_name');
            $table->string('fiscal_nrc', 25)->nullable()->after('fiscal_nit');
            $table->string('fiscal_activity_code', 10)->nullable()->after('fiscal_nrc');
            $table->string('fiscal_activity_description')->nullable()->after('fiscal_activity_code');
            $table->string('fiscal_department', 100)->nullable()->after('fiscal_activity_description');
            $table->string('fiscal_municipality', 100)->nullable()->after('fiscal_department');
            $table->text('fiscal_address')->nullable()->after('fiscal_municipality');
            $table->string('fiscal_phone', 30)->nullable()->after('fiscal_address');
            $table->string('fiscal_email')->nullable()->after('fiscal_phone');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'requires_fiscal_credit',
                'fiscal_legal_name',
                'fiscal_nit',
                'fiscal_nrc',
                'fiscal_activity_code',
                'fiscal_activity_description',
                'fiscal_department',
                'fiscal_municipality',
                'fiscal_address',
                'fiscal_phone',
                'fiscal_email',
            ]);
        });
    }
};
