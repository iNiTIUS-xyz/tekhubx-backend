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
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->integer('country_id')->nullable();
            $table->string('name')->nullable();
            $table->enum('status', [GlobalConstant::SWITCH])->default(GlobalConstant::SWITCH[0]);
            $table->decimal('tax')->default(0)->nullable();
            $table->string('short_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
