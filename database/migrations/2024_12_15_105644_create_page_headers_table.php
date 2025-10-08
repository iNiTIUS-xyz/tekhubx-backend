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
        Schema::create('page_headers', function (Blueprint $table) {
            $table->id();

            // Home Page
            $table->string('home_page_title')->nullable();
            $table->string('home_page_short_description')->nullable();
            $table->text('home_page_description')->nullable();
            $table->string('home_page_image')->nullable();
            $table->text('home_page_meta_keywords')->nullable();

            // How It Works Client
            $table->string('how_it_works_client_title')->nullable();
            $table->string('how_it_works_client_short_description')->nullable();
            $table->text('how_it_works_client_description')->nullable();
            $table->string('how_it_works_client_image')->nullable();
            $table->text('how_it_works_client_meta_keywords')->nullable();

            // How It Works Provider
            $table->string('how_it_works_provider_title')->nullable();
            $table->string('how_it_works_provider_short_description')->nullable();
            $table->text('how_it_works_provider_description')->nullable();
            $table->string('how_it_works_provider_image')->nullable();
            $table->text('how_it_works_provider_meta_keywords')->nullable();

            // Client Sign Up
            $table->string('client_sign_up_title')->nullable();
            $table->string('client_sign_up_short_description')->nullable();
            $table->text('client_sign_up_description')->nullable();
            $table->string('client_sign_up_image')->nullable();
            $table->text('client_sign_up_meta_keywords')->nullable();

            // Provider Sign Up
            $table->string('provider_sign_up_title')->nullable();
            $table->string('provider_sign_up_short_description')->nullable();
            $table->text('provider_sign_up_description')->nullable();
            $table->string('provider_sign_up_image')->nullable();
            $table->text('provider_sign_up_meta_keywords')->nullable();

            // Client Pricing
            $table->string('client_pricing_title')->nullable();
            $table->string('client_pricing_short_description')->nullable();
            $table->text('client_pricing_description')->nullable();
            $table->string('client_pricing_image')->nullable();
            $table->text('client_pricing_meta_keywords')->nullable();

            // Contact
            $table->string('contact_title')->nullable();
            $table->string('contact_short_description')->nullable();
            $table->text('contact_description')->nullable();
            $table->string('contact_image')->nullable();
            $table->text('contact_meta_keywords')->nullable();

            // About
            $table->string('about_title')->nullable();
            $table->string('about_short_description')->nullable();
            $table->text('about_description')->nullable();
            $table->string('about_image')->nullable();
            $table->text('about_meta_keywords')->nullable();

            // Mission & Vision
            $table->string('mission_vision_title')->nullable();
            $table->string('mission_vision_short_description')->nullable();
            $table->text('mission_vision_description')->nullable();
            $table->string('mission_vision_image')->nullable();
            $table->text('mission_vision_meta_keywords')->nullable();

            // FAQ
            $table->string('faq_title')->nullable();
            $table->string('faq_short_description')->nullable();
            $table->text('faq_description')->nullable();
            $table->string('faq_image')->nullable();
            $table->text('faq_meta_keywords')->nullable();

            // Career
            $table->string('career_title')->nullable();
            $table->string('career_short_description')->nullable();
            $table->text('career_description')->nullable();
            $table->string('career_image')->nullable();
            $table->text('career_meta_keywords')->nullable();

            // Teams
            $table->string('teams_title')->nullable();
            $table->string('teams_short_description')->nullable();
            $table->text('teams_description')->nullable();
            $table->string('teams_image')->nullable();
            $table->text('teams_meta_keywords')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('page_headers');
    }
};
