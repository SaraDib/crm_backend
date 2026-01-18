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
        Schema::create('whatsapp_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('phone_number')->nullable(); // Le numéro WhatsApp connecté
            $table->enum('status', ['disconnected', 'connecting', 'connected', 'error'])->default('disconnected');
            $table->integer('instance_port')->nullable(); // Port de l'instance (5101, 5102, etc.)
            $table->text('qr_code')->nullable(); // QR code en base64
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamp('last_disconnected_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('session_data')->nullable(); // Métadonnées de session
            $table->boolean('auto_restart')->default(true); // Redémarrage automatique
            $table->timestamps();

            // Un seul WhatsApp par entreprise
            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_connections');
    }
};
