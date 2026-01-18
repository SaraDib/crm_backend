<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('template_name', 100);
            $table->enum('category', ['UTILITY', 'MARKETING', 'AUTHENTICATION'])->default('UTILITY');
            $table->string('language', 10)->default('en');
            $table->enum('header_type', ['TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT'])->nullable();
            $table->text('header_content')->nullable();
            $table->text('body');
            $table->text('footer')->nullable();
            $table->json('buttons')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'disabled'])->default('pending')->index();
            $table->string('whatsapp_template_id', 100)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'template_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
