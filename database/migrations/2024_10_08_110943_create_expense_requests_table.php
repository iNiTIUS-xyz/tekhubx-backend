<?php

use App\Models\ExpenseCategory;
use App\Models\User;
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
        Schema::create('expense_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable();
            $table->foreignIdFor(ExpenseCategory::class)->nullable();
            $table->string('work_order_unique_id')->nullable();
            $table->decimal('amount')->nullable();
            $table->text('description')->nullable();
            $table->string('file')->nullable();
            $table->enum('status', GlobalConstant::AD)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_requests');
    }
};
