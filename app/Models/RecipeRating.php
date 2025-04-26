<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RecipeRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'user_id',
        'rating',
        'comment'
    ];

    protected $casts = [
        'rating' => 'float'
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 