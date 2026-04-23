<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('einvoice_status')->default('draft')->after('status');
            $table->string('einvoice_uuid')->nullable()->after('einvoice_status');
            $table->string('einvoice_submission_uid')->nullable()->after('einvoice_uuid');
            $table->string('einvoice_long_id')->nullable()->after('einvoice_submission_uid');
            $table->json('einvoice_errors')->nullable()->after('einvoice_long_id');
            $table->timestamp('einvoice_submitted_at')->nullable()->after('einvoice_errors');
            $table->timestamp('einvoice_validated_at')->nullable()->after('einvoice_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'einvoice_status',
                'einvoice_uuid',
                'einvoice_submission_uid',
                'einvoice_long_id',
                'einvoice_errors',
                'einvoice_submitted_at',
                'einvoice_validated_at',
            ]);
        });
    }
};