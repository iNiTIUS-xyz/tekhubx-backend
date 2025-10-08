<?php

use App\Utils\GlobalConstant;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('client_id')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('account_id')->nullable();
            $table->string('payment_unique_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->unsignedBigInteger('work_order_unique_id')->nullable();
            $table->decimal('total_labor')->nullable();
            $table->json('services_fee')->nullable();
            $table->decimal('tax')->nullable();
            $table->decimal('extra_fee')->nullable();
            $table->string('extra_fee_note')->nullable();
            $table->dateTime('payment_date_time')->nullable();
            $table->string('gateway')->nullable(); //paypal, authorize
            $table->decimal('expense_fee')->nullable();
            $table->decimal('pay_change_fee')->nullable();
            $table->decimal('debit')->nullable();
            $table->decimal('credit')->nullable();
            $table->decimal('balance')->nullable();
            $table->decimal('point_debit')->nullable();
            $table->decimal('point_credit')->nullable();
            $table->decimal('point_balance')->nullable();
            $table->string('ip_address')->nullable();
            $table->json('meta_data')->nullable();
            $table->string('status')->comment('Pending, Hold, Completed, Settlement, Reject, Deposited, Refunded, Canceled, Under Review')->nullable();
            $table->string('transaction_type')->comment('Payment, Withdraw, Add Money, Charge, Refund, Cancel, Subscription, Point')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
