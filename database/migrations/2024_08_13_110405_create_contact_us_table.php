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
        Schema::create('contact_us', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 20)->nullable();
            $table->string('last_name', 20)->nullable();
            $table->string('company_name', 40)->nullable();
            $table->string('email', 40)->nullable();
            $table->string('phone', 20)->nullable();
            $table->enum('annual_revenue', GlobalConstant::REVENUE)->nullable();
            $table->enum('need_technicians', GlobalConstant::NEED)->nullable();
            $table->text('why_chose_us')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_us');
    }
};
