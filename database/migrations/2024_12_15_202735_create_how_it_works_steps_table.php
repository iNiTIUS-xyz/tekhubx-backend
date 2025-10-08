<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('how_it_works_steps', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['client', 'provider']); // Type can be 'client' or 'provider'
            $table->integer('step_count')->unsigned(); // Positive integer for step ordering
            $table->string('step_title'); // Title for the step
            $table->text('step_description')->nullable(); // Optional description for the step
            $table->string('step_image')->nullable(); // Optional image for the step
            $table->timestamps(); // Created and updated timestamps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('how_it_works_steps');
    }
};
