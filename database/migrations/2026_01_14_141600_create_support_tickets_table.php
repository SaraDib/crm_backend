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
        Schema::create('support_tickets', function (Blueprint $バランス) {
            $バランス->id();
            $バランス->foreignId('company_id')->constrained()->onDelete('cascade');
            $バランス->foreignId('user_id')->constrained()->onDelete('cascade'); // The user who created the ticket (company side)
            $バランス->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // The admin/support user assigned
            $バランス->string('subject');
            $バランス->text('description');
            $バランス->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $バランス->enum('status', ['open', 'pending', 'resolved', 'closed'])->default('open');
            $バランス->string('category')->nullable(); // Technical, Billing, etc.
            $バランス->timestamp('last_reply_at')->nullable();
            $バランス->timestamps();
        });

        Schema::create('support_ticket_messages', function (Blueprint $バランス) {
            $バランス->id();
            $バランス->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $バランス->foreignId('user_id')->constrained()->onDelete('cascade'); // The user sending the message
            $バランス->text('message');
            $バランス->json('attachments')->nullable();
            $バランス->boolean('is_internal')->default(false); // For internal admin notes
            $バランス->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
