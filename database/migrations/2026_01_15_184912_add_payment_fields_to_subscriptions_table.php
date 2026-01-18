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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->enum('payment_method', ['bank_transfer', 'cash', 'check', 'mobile_money', 'other'])->nullable()->after('status');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('payment_reference');
            $table->foreignId('validated_by')->nullable()->after('paid_at')->constrained('users')->onDelete('set null');
            $table->text('payment_notes')->nullable()->after('validated_by');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_reference', 'paid_at', 'validated_by', 'payment_notes']);
        });
    }
};
