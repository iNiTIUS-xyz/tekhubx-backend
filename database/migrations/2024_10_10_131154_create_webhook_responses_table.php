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
        Schema::create('webhook_responses', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique(); // Stripe event ID
            $table->string('type'); // Event type (e.g., payment_intent.succeeded)
            $table->json('payload'); // Full webhook payload
            $table->string('status')->default('pending'); // Status of the event (pending, processed, failed)
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_responses');
    }
};
