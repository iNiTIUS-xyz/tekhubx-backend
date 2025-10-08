<?php

use App\Models\User;
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
        Schema::create('counter_offers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->foreignIdFor(User::class)->nullable();
            $table->unsignedBigInteger('work_order_unique_id')->nullable();
            $table->unsignedBigInteger('employed_providers_id')->nullable();
            $table->unsignedBigInteger('pay_id')->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->json('expense_id')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', GlobalConstant::SWITCH)->default(GlobalConstant::SWITCH[0]);
            $table->enum('withdraw', GlobalConstant::WITHDRAW)->nullable();
            $table->dateTime('expired_request_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counter_offers');
    }
};
