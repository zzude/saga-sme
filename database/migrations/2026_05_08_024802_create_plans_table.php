<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // Free, Pro, Enterprise
            $table->string('slug')->unique();                // free, pro, enterprise
            $table->integer('max_users')->default(2);        // -1 = unlimited
            $table->integer('max_invoices_per_month')->default(20);
            $table->integer('max_customers')->default(50);
            $table->boolean('has_einvoice')->default(false);
            $table->boolean('has_multicurrency')->default(false);
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default plans
        DB::table('plans')->insert([
            [
                'name'                   => 'Free',
                'slug'                   => 'free',
                'max_users'              => 2,
                'max_invoices_per_month' => 20,
                'max_customers'          => 50,
                'has_einvoice'           => false,
                'has_multicurrency'      => false,
                'price_monthly'          => 0,
                'is_active'              => true,
                'sort_order'             => 1,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'name'                   => 'Pro',
                'slug'                   => 'pro',
                'max_users'              => 10,
                'max_invoices_per_month' => 500,
                'max_customers'          => 1000,
                'has_einvoice'           => true,
                'has_multicurrency'      => true,
                'price_monthly'          => 99,
                'is_active'              => true,
                'sort_order'             => 2,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
            [
                'name'                   => 'Enterprise',
                'slug'                   => 'enterprise',
                'max_users'              => -1,
                'max_invoices_per_month' => -1,
                'max_customers'          => -1,
                'has_einvoice'           => true,
                'has_multicurrency'      => true,
                'price_monthly'          => 299,
                'is_active'              => true,
                'sort_order'             => 3,
                'created_at'             => now(),
                'updated_at'             => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
