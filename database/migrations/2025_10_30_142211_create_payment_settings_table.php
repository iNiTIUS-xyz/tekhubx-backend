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
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gateway_name')->default('stripe');
            $table->string('stripe_secret')->nullable();
            $table->string('stripe_public')->nullable();
            $table->string('stripe_webhook_secret')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->enum('stripe_mode', ['test', 'live'])->default('test');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
