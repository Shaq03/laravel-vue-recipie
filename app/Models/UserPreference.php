<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'preferred_cuisines',
        'dietary_restrictions',
        'cooking_skill_level',
        'seasonal_preferences',
        'favorite_ingredients',
        'disliked_ingredients',
        'cooking_history'
    ];

    protected $casts = [
        'preferred_cuisines' => 'array',
        'dietary_restrictions' => 'array',
        'seasonal_preferences' => 'boolean',
        'favorite_ingredients' => 'array',
        'disliked_ingredients' => 'array',
        'cooking_history' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function updateCookingHistory($recipeId, $recipeTitle)
    {
        $history = $this->cooking_history ?? [];
        $history[] = [
            'recipe_id' => $recipeId,
            'title' => $recipeTitle,
            'cooked_at' => now()->toDateTimeString()
        ];

        // Keep only last 50 recipes in history
        $history = array_slice($history, -50);

        $this->cooking_history = $history;
        $this->save();
    }
} 