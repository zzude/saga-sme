<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE journal_headers MODIFY COLUMN source_type ENUM('manual','opening_balance','adjustment','reversal','pos') NOT NULL DEFAULT 'manual'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE journal_headers MODIFY COLUMN source_type ENUM('manual','opening_balance','adjustment','reversal') NOT NULL DEFAULT 'manual'");
    }
};