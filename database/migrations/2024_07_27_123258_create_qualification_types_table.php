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
        Schema::create('qualification_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', [GlobalConstant::SWITCH])->default(GlobalConstant::SWITCH[0]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qualification_types');
    }
};
