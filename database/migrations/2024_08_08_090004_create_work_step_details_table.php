<?php

use App\Utils\GlobalConstant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('work_step_details', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('short_description')->nullable();
            $table->string('step_one_image')->nullable();
            $table->text('step_one_details')->nullable();
            $table->string('step_two_image')->nullable();
            $table->text('step_two_details')->nullable();
            $table->string('step_three_image')->nullable();
            $table->text('step_three_details')->nullable();
            $table->string('step_image')->nullable();
            $table->json('step_list')->nullable();
            $table->enum('status', [GlobalConstant::SWITCH])->default(GlobalConstant::SWITCH[0]);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_step_details');
    }
};
