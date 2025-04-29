<?php

namespace Database\Factories;

use App\Models\RecipeRating;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecipeRatingFactory extends Factory
{
    protected $model = RecipeRating::class;

    public function definition()
    {
        return [
            'recipe_id' => Recipe::factory(),
            'user_id' => User::factory(),
            'rating' => $this->faker->randomFloat(1, 1, 5),
            'comment' => $this->faker->paragraph()
        ];
    }
} 