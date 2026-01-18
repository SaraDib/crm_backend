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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('subscription_plans')->onDelete('restrict');
            $table->string('stripe_subscription_id', 255)->nullable()->unique();
            $table->string('stripe_customer_id', 255)->nullable();
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('ends_at')->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->enum('status', ['active', 'canceled', 'expired', 'pending', 'past_due'])->default('pending')->index();
            $table->boolean('auto_renew')->default(true);
            $table->integer('email_credits_used')->default(0);
            $table->integer('sms_credits_used')->default(0);
            $table->integer('whatsapp_credits_used')->default(0);
            $table->integer('customer_count')->default(0);
            $table->integer('user_count')->default(1);
            $table->timestamp('canceled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
