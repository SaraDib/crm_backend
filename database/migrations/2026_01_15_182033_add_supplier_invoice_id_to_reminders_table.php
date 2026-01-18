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
        Schema::table('reminders', function (Blueprint $table) {
            $table->foreignId('supplier_invoice_id')->nullable()->after('invoice_id')->constrained('supplier_invoices')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropForeign(['supplier_invoice_id']);
            $table->dropColumn('supplier_invoice_id');
        });
    }
};
