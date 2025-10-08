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
        Schema::create('provider_checkouts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('work_order_unique_id')->nullable();
            $table->date('start_date')->nullable();
            $table->time('start_time')->nullable();
            $table->integer('duration')->nullable();
            $table->text('message')->nullable();
            $table->string('confirmed')->default('no');
            $table->string('at_risk')->default('no');
            $table->string('on_my_way')->default('no');
            $table->timestamp('on_my_way_at')->nullable();
            $table->string('is_check_in')->default('no');
            $table->string('is_check_out')->default('no');
            $table->date('check_in_time')->nullable();
            $table->integer('timeliness_rate')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_checkouts');
    }
};
