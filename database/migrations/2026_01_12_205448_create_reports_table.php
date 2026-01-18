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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name', 255);
            $table->enum('type', ['customers', 'invoices', 'payments', 'communications', 'devices', 'financial', 'custom'])->index();
            $table->text('description')->nullable();
            $table->json('filters')->nullable();
            $table->json('columns')->nullable();
            $table->string('sort_by', 100)->nullable();
            $table->enum('sort_order', ['asc', 'desc'])->default('asc');
            $table->enum('format', ['pdf', 'excel', 'csv', 'html'])->default('pdf');
            $table->boolean('is_scheduled')->default(false)->index();
            $table->enum('schedule_frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
            $table->time('schedule_time')->nullable();
            $table->integer('schedule_day')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
