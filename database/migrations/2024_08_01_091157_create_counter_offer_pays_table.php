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
        Schema::create('counter_offer_pays', function (Blueprint $table) {
            $table->id();
            $table->enum('type', GlobalConstant::PAY_TYPE)->nullable();
            $table->decimal('max_hour')->nullable();
            $table->decimal('amount_per_hour', 12, 2)->nullable();
            $table->decimal('total_pay_amount', 12, 2)->nullable();
            $table->decimal('amount_per_device', 12, 2)->nullable();
            $table->integer('max_device')->nullable();
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->decimal('fixed_amount_max_hours')->nullable();
            $table->decimal('hourly_amount_after', 12, 2)->nullable();
            $table->decimal('hourly_amount_max_hours')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counter_offer_pays');
    }
};
