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
        Schema::create('payment_intent_saves', function (Blueprint $table) {
            $table->id();
            $table->string('provider_id')->nullable();
            $table->string('payment_intent_id')->nullable();
            $table->string('work_order_unique_id')->nullable();
            $table->decimal('amount', 10, 2)->nullable(); // Assuming amount is a decimal with 10 digits total and 2 decimal places
            $table->string('client_secret')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('capture_id')->nullable();
            $table->string('transfer_id')->nullable();
            $table->string('intent_status')->nullable();
            $table->string('capture_status')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_intent_saves');
    }
};
