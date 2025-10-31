
<?php

use App\Models\User;
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
        Schema::create('work_order_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('work_order_unique_id')->nullable();
            $table->foreignIdFor(User::class, 'sender_id')->nullable();
            $table->foreignIdFor(User::class, 'receiver_id')->nullable();
            $table->longText('message')->nullable();
            $table->dateTime('request_date_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_chat_messages');
    }
};
