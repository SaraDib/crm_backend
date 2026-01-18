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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('device_categories')->onDelete('set null');
            $table->string('device_type', 100);
            $table->string('brand', 100)->nullable();
            $table->string('model', 100);
            $table->string('serial_number', 100);
            $table->string('imei', 20)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2)->nullable();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable()->index();
            $table->string('warranty_type', 50)->nullable();
            $table->text('warranty_notes')->nullable();
            $table->enum('status', ['active', 'inactive', 'under_maintenance', 'retired', 'lost', 'sold'])->default('active')->index();
            $table->string('location', 255)->nullable();
            $table->string('assigned_to', 255)->nullable();
            $table->text('notes')->nullable();
            $table->date('last_maintenance')->nullable();
            $table->date('next_maintenance')->nullable()->index();
            $table->timestamps();

            $table->unique(['company_id', 'serial_number']);
            $table->index(['company_id', 'customer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
