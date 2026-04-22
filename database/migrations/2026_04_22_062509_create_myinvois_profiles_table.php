<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("myinvois_profiles", function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained()->cascadeOnDelete();
            $table->enum("mode", ["taxpayer", "intermediary"])->default("taxpayer");
            $table->enum("environment", ["sandbox", "production"])->default("sandbox");
            $table->string("client_id")->nullable();
            $table->string("client_secret")->nullable();
            $table->string("tin")->nullable()->comment("Tax Identification Number");
            $table->string("branch_code")->nullable();
            $table->text("access_token")->nullable();
            $table->timestamp("token_expires_at")->nullable();
            $table->boolean("is_active")->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("myinvois_profiles");
    }
};
