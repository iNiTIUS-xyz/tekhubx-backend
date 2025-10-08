<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageHeader extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'page_headers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        // Home Page
        'home_page_title',
        'home_page_short_description',
        'home_page_description',
        'home_page_image',
        'home_page_meta_keywords',

        // How It Works Client
        'how_it_works_client_title',
        'how_it_works_client_short_description',
        'how_it_works_client_description',
        'how_it_works_client_image',
        'how_it_works_client_meta_keywords',

        // How It Works Provider
        'how_it_works_provider_title',
        'how_it_works_provider_short_description',
        'how_it_works_provider_description',
        'how_it_works_provider_image',
        'how_it_works_provider_meta_keywords',

        // Client Sign Up
        'client_sign_up_title',
        'client_sign_up_short_description',
        'client_sign_up_description',
        'client_sign_up_image',
        'client_sign_up_meta_keywords',

        // Provider Sign Up
        'provider_sign_up_title',
        'provider_sign_up_short_description',
        'provider_sign_up_description',
        'provider_sign_up_image',
        'provider_sign_up_meta_keywords',

        // Client Pricing
        'client_pricing_title',
        'client_pricing_short_description',
        'client_pricing_description',
        'client_pricing_image',
        'client_pricing_meta_keywords',

        // Contact
        'contact_title',
        'contact_short_description',
        'contact_description',
        'contact_image',
        'contact_meta_keywords',

        // About
        'about_title',
        'about_short_description',
        'about_description',
        'about_image',
        'about_meta_keywords',

        // Mission & Vision
        'mission_vision_title',
        'mission_vision_short_description',
        'mission_vision_description',
        'mission_vision_image',
        'mission_vision_meta_keywords',

        // FAQ
        'faq_title',
        'faq_short_description',
        'faq_description',
        'faq_image',
        'faq_meta_keywords',

        // Career
        'career_title',
        'career_short_description',
        'career_description',
        'career_image',
        'career_meta_keywords',

        // Teams
        'teams_title',
        'teams_short_description',
        'teams_description',
        'teams_image',
        'teams_meta_keywords',
    ];
}

