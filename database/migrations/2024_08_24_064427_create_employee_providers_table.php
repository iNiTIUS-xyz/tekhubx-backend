<?php

use App\Models\Country;
use App\Models\State;
use App\Models\WorkCategory;
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
        Schema::create('employee_providers', function (Blueprint $table) {
            $table->id();
            $table->integer('provider_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->string('uuid')->nullable();
            $table->string('first_name', 30)->nullable();
            $table->string('last_name', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 15)->nullable();
            $table->text('address_line_1')->nullable();
            $table->text('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->foreignIdFor(State::class)->nullable();
            $table->integer('zip_code')->nullable();
            $table->foreignIdFor(Country::class)->nullable();
            $table->foreignIdFor(WorkCategory::class)->nullable();
            $table->text('bio')->nullable();
            $table->enum('status', GlobalConstant::SWITCH)->nullable();
            $table->string('token')->nullable();
            $table->string('role')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_providers');
    }
};
