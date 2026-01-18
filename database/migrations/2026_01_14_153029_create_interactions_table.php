<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('user_id'); // L'employé qui a fait l'interaction
            $table->string('type'); // call, email, meeting, note, task
            $table->string('subject')->nullable();
            $table->text('content');
            $table->datetime('interaction_date');
            $table->string('status')->nullable(); // completed, pending, cancelled
            $table->json('meta_data')->nullable(); // Durée appel, lien email, etc.
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
