<?php

use App\Models\EmployeeProvider;
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
        Schema::create('work_summeries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(EmployeeProvider::class)->nullable();
            $table->integer('user_id')->nullable();
            $table->string('uuid')->nullable();
            $table->unsignedBigInteger('work_category_id')->nullable();
            $table->string('work_category_name')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_summeries');
    }
};
