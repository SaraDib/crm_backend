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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('email', 255)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('secondary_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 100)->nullable()->index();
            $table->string('postal_code', 20)->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->string('vat_number', 100)->nullable();
            $table->string('business_registration_number', 100)->nullable();
            $table->string('logo', 500)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->string('currency', 3)->default('MAD');
            $table->string('language', 10)->default('en');
            $table->string('date_format', 20)->default('Y-m-d');
            $table->string('time_format', 10)->default('24h');
            $table->string('smtp_host', 255)->nullable();
            $table->integer('smtp_port')->nullable();
            $table->string('smtp_username', 255)->nullable();
            $table->text('smtp_password')->nullable();
            $table->enum('smtp_encryption', ['none', 'ssl', 'tls'])->default('tls');
            $table->text('whatsapp_api_key')->nullable();
            $table->string('whatsapp_phone_number_id', 100)->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending_verification'])->default('pending_verification')->index();
            $table->string('verification_token', 100)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
