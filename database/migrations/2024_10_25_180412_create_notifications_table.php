<?php

use App\Models\CounterOffer;
use App\Models\ExpenseRequest;
use App\Models\PayChange;
use App\Models\SendWorkRequest;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'sender_id')->nullable();
            $table->foreignIdFor(User::class, 'receiver_id')->nullable();
            $table->foreignIdFor(CounterOffer::class)->nullable();
            $table->foreignIdFor(SendWorkRequest::class)->nullable();
            $table->foreignIdFor(ExpenseRequest::class)->nullable();
            $table->foreignIdFor(PayChange::class)->nullable();
            $table->string('work_order_unique_id')->nullable();
            $table->integer('payment_id')->nullable();
            $table->string('type')->nullable();
            $table->text('notification_text')->nullable();
            $table->boolean('is_read')->default(false)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
