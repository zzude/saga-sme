<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_individual')->default(true)->after('email');
            $table->string('tin')->nullable()->after('is_individual');
            $table->string('id_type')->nullable()->after('tin');
            // NRIC / BRN / PASSPORT / ARMY
            $table->string('id_value')->nullable()->after('id_type');
            $table->string('sst_registration_no')->nullable()->after('id_value');
            $table->string('msic_code')->nullable()->after('sst_registration_no');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'is_individual',
                'tin',
                'id_type',
                'id_value',
                'sst_registration_no',
                'msic_code',
            ]);
        });
    }
};