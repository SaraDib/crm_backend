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
        Schema::create('device_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->enum('report_type', ['maintenance', 'inspection', 'repair', 'diagnostic']);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('findings')->nullable();
            $table->text('recommendations')->nullable();
            $table->json('parts_used')->nullable();
            $table->decimal('labor_hours', 5, 2)->nullable();
            $table->decimal('labor_cost', 10, 2)->nullable();
            $table->decimal('parts_cost', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('report_date')->index();
            $table->date('next_service_date')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->string('report_file', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_reports');
    }
};
