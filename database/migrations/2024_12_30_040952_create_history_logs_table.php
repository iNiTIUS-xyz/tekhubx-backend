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
        Schema::create('history_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('provider_id')->nullable();
            $table->unsignedBigInteger('work_order_unique_id')->nullable();
            $table->unsignedBigInteger('work_order_send_request_id')->nullable();
            $table->unsignedBigInteger('work_order_counter_offer_id')->nullable();
            $table->unsignedBigInteger('expense_request_id')->nullable();
            $table->unsignedBigInteger('paychange_id')->nullable();
            $table->unsignedBigInteger('work_order_report_id')->nullable();
            $table->unsignedBigInteger('not_interest_id')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->timestamp('date_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_logs');
    }
};
