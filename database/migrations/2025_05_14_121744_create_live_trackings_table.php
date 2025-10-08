<?php

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
        Schema::create('live_trackings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->string('work_order_unique_id');
            $table->decimal('latitude', 20, 7);
            $table->decimal('longitude', 20, 7);
            $table->decimal('speed', 10, 2)->default(0);
            $table->decimal('heading', 10, 2)->default(0);
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->string('status')->default('on_my_way');
            $table->dateTime('tracked_at');
            $table->timestamps();

            $table->index(['work_order_unique_id', 'tracked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_trackings');
    }
};
