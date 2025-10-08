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
        Schema::create('pool_details', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('talent_id')->nullable();
            $table->integer('provider_id')->nullable();
            $table->enum('status', GlobalConstant::STATUS)->default(GlobalConstant::STATUS[1]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_details');
    }
};
