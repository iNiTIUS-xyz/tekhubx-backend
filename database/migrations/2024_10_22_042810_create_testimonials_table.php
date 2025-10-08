<?php

use App\Utils\GlobalConstant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name')->nullable();
            $table->string('designation')->nullable();
            $table->text('quote')->nullable();
            $table->enum('review_star', GlobalConstant::REVIEW_STAR)->default(GlobalConstant::REVIEW_STAR[4]);
            $table->string('image')->nullable();
            $table->enum('status', [GlobalConstant::SWITCH])->default(GlobalConstant::SWITCH[0]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
