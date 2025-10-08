<?php

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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->nullable();
            $table->string('uuid')->nullable();
            $table->string('client_title')->nullable();
            $table->integer('client_manager_id')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->string('notes')->nullable();
            $table->string('default_policies')->nullable();
            $table->string('default_standard_instruction')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('zip_code')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->string('location_type')->nullable();
            $table->boolean('company_name_with_logo')->nullable();
            $table->boolean('client_name_with_logo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
