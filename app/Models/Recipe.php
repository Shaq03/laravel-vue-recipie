<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'cooking_time',
        'servings',
        'difficulty',
        'ingredients',
        'instructions',
        'image_url'
    ];

    protected $casts = [
        'ingredients' => 'array',
        'instructions' => 'array'
    ];
} 