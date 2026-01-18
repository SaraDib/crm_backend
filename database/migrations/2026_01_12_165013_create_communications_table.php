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
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('communication_templates')->onDelete('set null');
            $table->enum('type', ['email', 'sms', 'whatsapp'])->index();
            $table->string('to', 255);
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('content');
            $table->json('attachments')->nullable();
            $table->enum('status', ['draft', 'queued', 'sent', 'delivered', 'failed', 'opened', 'clicked'])->default('draft')->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('message_id', 255)->nullable();
            $table->decimal('cost', 8, 2)->default(0.00);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['company_id', 'type', 'status', 'sent_at'], 'communications_company_type_status');
            $table->fullText(['to', 'subject', 'content'], 'communications_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communications');
    }
};
