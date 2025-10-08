<?php

use App\Models\FrontendServiceCategory;
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
        Schema::create('frontend_services', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(FrontendServiceCategory::class)->nullable();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->text('short_description')->nullable();
            $table->string('banner_image')->nullable();
            $table->longText('description')->nullable();
            $table->enum('status', GlobalConstant::SWITCH)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('frontend_services');
    }
};
