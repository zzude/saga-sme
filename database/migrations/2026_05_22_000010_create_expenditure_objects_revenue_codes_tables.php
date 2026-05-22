<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Objek Sebagai (Expenditure Objects) — JANM standard
        Schema::create('expenditure_objects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            // Hierarchy: Objek → Sub Objek
            $table->unsignedBigInteger('parent_id')->nullable(); // null = top level objek
            $table->string('code', 10);                          // e.g. 21000, 21100
            $table->string('name');                              // e.g. Emolumen, Gaji Pokok
            $table->enum('level', ['objek', 'sub_objek'])->default('objek');

            // JANM standard classification
            $table->enum('category', [
                'mengurus',      // 1x000 — Perbelanjaan Mengurus
                'pembangunan',   // 5x000 — Perbelanjaan Pembangunan
            ])->default('mengurus');

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('expenditure_objects')->nullOnDelete();
            $table->unique(['company_id', 'code'], 'unique_expenditure_object_code');
            $table->index(['company_id', 'parent_id']);
        });

        // Kod Hasil (Revenue Codes) — JANM standard
        Schema::create('revenue_codes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');

            $table->unsignedBigInteger('parent_id')->nullable(); // null = top level
            $table->string('code', 10);                          // e.g. 00000, 10000
            $table->string('name');                              // e.g. Hasil Cukai, Lesen
            $table->enum('level', ['head', 'sub'])->default('head');

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('revenue_codes')->nullOnDelete();
            $table->unique(['company_id', 'code'], 'unique_revenue_code');
            $table->index(['company_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revenue_codes');
        Schema::dropIfExists('expenditure_objects');
    }
};
