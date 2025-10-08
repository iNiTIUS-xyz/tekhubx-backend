<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PageParagraph extends Model
{
    use HasFactory;

    // Specify the table name if it's not the plural form of the model name
    protected $table = 'page_paragraphs';

    // Define the fields that are mass assignable
    protected $fillable = [
        'paragraph_one_image',
        'paragraph_one_title',
        'paragraph_one_description',
        'paragraph_two_image',
        'paragraph_two_title',
        'paragraph_two_description',
        'paragraph_three_image',
        'paragraph_three_title',
        'paragraph_three_description',
    ];

}
