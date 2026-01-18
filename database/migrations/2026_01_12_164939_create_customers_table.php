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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('customer_number', 50);
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->string('salutation', 10)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('company_name', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 20)->index();
            $table->string('secondary_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable()->index();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable()->index();
            $table->string('postal_code', 20)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('tax_status', ['exempt', 'vat_registered', 'non_vat', 'unknown'])->default('unknown');
            $table->string('vat_number', 100)->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('customer_categories')->onDelete('set null');
            $table->string('source', 100)->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0.00);
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            $table->integer('payment_terms')->default(30);
            $table->decimal('discount_rate', 5, 2)->default(0.00);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['lead', 'prospect', 'customer', 'inactive', 'blacklisted'])->default('lead')->index();
            $table->integer('lead_score')->default(0);
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('last_purchase_at')->nullable()->index();
            $table->decimal('total_purchases', 15, 2)->default(0.00);
            $table->decimal('total_paid', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'customer_number']);
            $table->unique(['company_id', 'email']);
            $table->index('created_at');
            $table->index(['company_id', 'status', 'created_at'], 'customers_company_status_created');
            $table->fullText(['first_name', 'last_name', 'company_name', 'email', 'phone'], 'customers_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
