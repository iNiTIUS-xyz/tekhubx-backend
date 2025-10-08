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
        Schema::create('page_paragraphs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            
            // Paragraph 1
            $table->string('paragraph_one_image')->nullable();
            $table->string('paragraph_one_title')->nullable();
            $table->text('paragraph_one_description')->nullable();
        
            // Paragraph 2
            $table->string('paragraph_two_image')->nullable();
            $table->string('paragraph_two_title')->nullable();
            $table->text('paragraph_two_description')->nullable();
        
            // Paragraph 3
            $table->string('paragraph_three_image')->nullable();
            $table->string('paragraph_three_title')->nullable();
            $table->text('paragraph_three_description')->nullable();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_paragraphs');
    }
};
