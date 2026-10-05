<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('fiscal_department_code', 2)->nullable()->after('fiscal_activity_description');
            $table->string('fiscal_municipality_code', 2)->nullable()->after('fiscal_department');
            $table->string('fiscal_district_code', 2)->nullable()->after('fiscal_municipality');
            $table->string('fiscal_district', 100)->nullable()->after('fiscal_district_code');
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn([
                'fiscal_department_code',
                'fiscal_municipality_code',
                'fiscal_district_code',
                'fiscal_district',
            ]);
        });
    }
};
