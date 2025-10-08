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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->integer('plan_id')->nullable();
            $table->string('uuid')->nullable();
            $table->timestamp('start_date_time')->nullable();
            $table->timestamp('end_date_time')->nullable();
            $table->enum('status', GlobalConstant::SUBSCRIPTION_SWITCH)->default(GlobalConstant::SUBSCRIPTION_SWITCH[1]);
            $table->decimal('amount')->nullable();
            $table->decimal('point')->nullable();
            $table->decimal('work_order_fee')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
