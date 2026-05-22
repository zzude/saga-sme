<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('asset_no')->unique();            // FA-2026-00001
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id');

            // ── Acquisition ──────────────────────────────────────────────────
            $table->date('purchase_date');
            $table->decimal('purchase_amount', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->integer('useful_life_years');
            $table->enum('depreciation_method', [
                'straight_line',
                'reducing_balance',
            ])->default('straight_line');

            // Vendor info
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('vendor_invoice_no')->nullable();

            // Linked journal when purchased
            $table->unsignedBigInteger('purchase_journal_id')->nullable();

            // ── Location ─────────────────────────────────────────────────────
            $table->string('location')->nullable();          // e.g. "HQ - Bilik Server"
            $table->string('assigned_to')->nullable();       // e.g. "Ahmad bin Ali"

            // ── Current Value ─────────────────────────────────────────────────
            $table->decimal('current_book_value', 15, 2);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);

            // ── Status ────────────────────────────────────────────────────────
            $table->enum('status', [
                'active',
                'disposed',
                'written_off',
            ])->default('active');

            // Disposal info
            $table->date('disposed_at')->nullable();
            $table->decimal('disposal_proceeds', 15, 2)->nullable();
            $table->unsignedBigInteger('disposal_journal_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('asset_categories')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            $table->foreign('purchase_journal_id')->references('id')->on('journal_headers')->nullOnDelete();
            $table->foreign('disposal_journal_id')->references('id')->on('journal_headers')->nullOnDelete();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
