<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->morphs('documentable'); // Pour lier à Customer, Invoice, Ticket, etc.
            $table->string('name');
            $table->string('file_path');
            $table->string('file_type'); // pdf, image, docx
            $table->unsignedInteger('file_size');
            $table->unsignedBigInteger('user_id'); // Qui a uploadé
            $table->string('category')->nullable(); // KYC, Contrat, Facture
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
