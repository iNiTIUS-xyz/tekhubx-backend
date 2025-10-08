<?php

use App\Models\Documentation;
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
        Schema::create('documentations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Documentation::class, 'parent_id')->nullable();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('icon')->nullable();
            $table->string('image')->nullable();
            $table->longText('description')->nullable();
            $table->enum('status', [GlobalConstant::SWITCH])->default(GlobalConstant::SWITCH[0]);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentations');
    }
};
