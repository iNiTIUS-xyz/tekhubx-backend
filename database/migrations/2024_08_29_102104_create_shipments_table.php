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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('work_order_unique_id')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('shipment_description')->nullable();
            $table->string('shipment_carrier')->nullable();
            $table->string('shipment_carrier_name')->nullable();
            $table->string('shipment_direction')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
