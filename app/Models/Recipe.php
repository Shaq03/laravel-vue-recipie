<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recipe extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'ingredients',
        'instructions',
        'cooking_time',
        'difficulty',
        'cuisines',
        'tags',
        'nutritional_info',
        'popularity_score',
        'average_rating',
        'user_id',
        'source',
        'image',
        'servings',
        'preparation_time',
        'total_time',
        'calories',
        'protein',
        'carbs',
        'fat',
        'fiber',
        'sugar',
        'sodium',
        'cholesterol',
        'is_vegetarian',
        'is_vegan',
        'is_gluten_free',
        'is_dairy_free',
        'is_nut_free',
        'is_halal',
        'is_kosher',
        'seasonal',
        'total_ratings',
        'total_favorites',
        'total_views',
        'last_cooked_at',
        'image_url',
        'dietary_restrictions'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ingredients' => 'array',
        'instructions' => 'array',
        'cuisines' => 'array',
        'tags' => 'array',
        'nutritional_info' => 'array',
        'popularity_score' => 'float',
        'average_rating' => 'float',
        'cooking_time' => 'integer',
        'servings' => 'integer',
        'preparation_time' => 'integer',
        'total_time' => 'integer',
        'calories' => 'integer',
        'protein' => 'float',
        'carbs' => 'float',
        'fat' => 'float',
        'fiber' => 'float',
        'sugar' => 'float',
        'sodium' => 'integer',
        'cholesterol' => 'integer',
        'is_vegetarian' => 'boolean',
        'is_vegan' => 'boolean',
        'is_gluten_free' => 'boolean',
        'is_dairy_free' => 'boolean',
        'is_nut_free' => 'boolean',
        'is_halal' => 'boolean',
        'is_kosher' => 'boolean',
        'seasonal' => 'boolean',
        'total_ratings' => 'integer',
        'total_favorites' => 'integer',
        'total_views' => 'integer',
        'last_cooked_at' => 'datetime',
        'dietary_restrictions' => 'array'
    ];

    /**
     * Get the user that created the recipe.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the users who have favorited this recipe.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites', 'recipe_id', 'user_id')
            ->withTimestamps();
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(RecipeRating::class);
    }

    public function updateAverageRating()
    {
        $this->average_rating = $this->ratings()->avg('rating') ?? 0;
        $this->save();
    }

    public function incrementPopularity()
    {
        $this->popularity_score += 1;
        $this->save();
    }

    public function cookingHistory()
    {
        return $this->hasMany(CookingHistory::class);
    }

    // Scope to get only user-created recipes
    public function scopeUserCreated($query)
    {
        return $query->where('source', 'user');
    }

    // Scope to get only AI-generated recipes
    public function scopeAIGenerated($query)
    {
        return $query->where('source', 'ai');
    }

    public function getAverageRatingAttribute()
    {
        return $this->ratings()->avg('rating') ?? 0;
    }

    public function getTotalRatingsAttribute()
    {
        return $this->ratings()->count();
    }

    public function getIsFavoritedAttribute()
    {
        if (!auth()->check()) {
            return false;
        }
        return $this->favoritedBy()->where('user_id', auth()->id())->exists();
    }
} 