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
        Schema::create('counter_offer_expenses', function (Blueprint $table) {
            $table->id();
            $table->enum('category', GlobalConstant::EXPENSES_CATEGORY)->nullable();
            $table->text('description')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counter_offer_expenses');
    }
};
