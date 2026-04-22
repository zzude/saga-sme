<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("myinvois_submissions", function (Blueprint $table) {
            $table->id();
            $table->foreignId("company_id")->constrained()->cascadeOnDelete();
            $table->foreignId("invoice_id")->constrained()->cascadeOnDelete();
            $table->string("submission_uid")->nullable()->comment("UID dari LHDN");
            $table->string("document_uid")->nullable()->comment("Document UID selepas validated");
            $table->string("long_id")->nullable()->comment("Long ID untuk QR");
            $table->enum("status", ["draft","submitted","processing","validated","rejected","cancelled"])->default("draft");
            $table->string("invoice_no");
            $table->json("ubl_payload")->nullable()->comment("UBL JSON payload yang disubmit");
            $table->json("response_payload")->nullable()->comment("Raw response dari LHDN");
            $table->text("error_message")->nullable();
            $table->integer("retry_count")->default(0);
            $table->timestamp("submitted_at")->nullable();
            $table->timestamp("validated_at")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("myinvois_submissions");
    }
};
