<?php

use App\Utils\GlobalConstant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('send_work_requests', function (Blueprint $table) {
            $table->id();
            $table->string('work_order_unique_id')->nullable();
            $table->string('uuid')->nullable();
            $table->string('user_id')->nullable();
            $table->dateTime('request_date_time')->nullable();
            $table->enum('after_withdraw', GlobalConstant::WITHDRAW)->nullable();
            $table->dateTime('expired_request_time')->nullable();
            $table->enum('status', GlobalConstant::SWITCH)->default(GlobalConstant::SWITCH[0]);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('send_work_requests');
    }
};
