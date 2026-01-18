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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->string('invoice_number', 50);
            $table->string('order_number', 50)->nullable();
            $table->date('invoice_date')->index();
            $table->date('due_date')->index();
            $table->string('payment_terms', 100)->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->enum('discount_type', ['percentage', 'fixed'])->default('fixed');
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('vat_amount', 15, 2)->default(0.00);
            $table->decimal('shipping_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['draft', 'sent', 'viewed', 'partial', 'paid', 'overdue', 'canceled', 'refunded'])->default('draft')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->boolean('due_reminder_sent')->default(false);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('footer')->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['company_id', 'invoice_number']);
            $table->index(['company_id', 'status', 'due_date'], 'invoices_company_status_due_date');
            $table->fullText(['invoice_number', 'order_number', 'notes'], 'invoices_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
