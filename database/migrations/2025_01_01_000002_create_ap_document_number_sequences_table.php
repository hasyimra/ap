<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_document_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 20);
            $table->string('period', 6);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['document_type', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_document_number_sequences');
    }
};
