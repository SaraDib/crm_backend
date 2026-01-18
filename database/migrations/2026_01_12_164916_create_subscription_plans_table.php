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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->integer('duration_months');
            $table->decimal('price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0.00);
            $table->decimal('final_price', 10, 2);
            $table->integer('customer_limit')->default(0);
            $table->integer('user_limit');
            $table->integer('email_credits')->default(0);
            $table->integer('sms_credits')->default(0);
            $table->integer('whatsapp_credits')->default(0);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_trial')->default(false);
            $table->integer('trial_days')->default(14);
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
