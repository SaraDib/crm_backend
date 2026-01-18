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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('sku', 100);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->string('unit', 50)->default('piece');
            $table->string('category', 100)->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->decimal('stock_quantity', 10, 2)->default(0.00);
            $table->decimal('low_stock_threshold', 10, 2)->default(0.00);
            $table->string('image', 500)->nullable();
            $table->timestamps();
            
            $table->unique(['company_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
