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
        Schema::create('default_client_lists', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('client_title')->nullable();
            $table->integer('client_manager_id')->nullable();
            $table->string('logo')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('company_name_logo')->nullable();
            $table->boolean('client_name_logo')->nullable();
            $table->text('policies')->nullable();
            $table->text('instructions')->nullable();
            $table->string('address_line_one')->nullable();
            $table->string('address_line_two')->nullable();
            $table->string('city')->nullable();
            $table->integer('state_id')->nullable();
            $table->integer('zip_code')->nullable();
            $table->integer('country_id')->nullable();
            $table->enum('location_type', GlobalConstant::LOCATION_TYPE)->nullable();
            $table->string('projects')->nullable();
            $table->string('work_orders')->nullable();
            $table->enum('status', GlobalConstant::STATUS)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_client_lists');
    }
};
