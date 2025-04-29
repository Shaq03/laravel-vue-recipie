<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecipeFactory extends Factory
{
    protected $model = Recipe::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'ingredients' => $this->faker->words(5),
            'instructions' => $this->faker->paragraphs(3),
            'cooking_time' => $this->faker->numberBetween(15, 120),
            'difficulty' => $this->faker->randomElement(['easy', 'medium', 'hard']),
            'cuisines' => $this->faker->randomElements(['italian', 'mexican', 'chinese', 'indian', 'japanese'], 2),
            'tags' => $this->faker->words(3),
            'nutritional_info' => [
                'calories' => $this->faker->numberBetween(200, 800),
                'protein' => $this->faker->numberBetween(10, 40),
                'carbs' => $this->faker->numberBetween(20, 100),
                'fat' => $this->faker->numberBetween(5, 30)
            ],
            'popularity_score' => 0,
            'average_rating' => 0,
            'servings' => $this->faker->numberBetween(1, 8),
            'preparation_time' => $this->faker->numberBetween(5, 60),
            'total_time' => $this->faker->numberBetween(20, 180),
            'calories' => $this->faker->numberBetween(200, 800),
            'protein' => $this->faker->randomFloat(1, 10, 40),
            'carbs' => $this->faker->randomFloat(1, 20, 100),
            'fat' => $this->faker->randomFloat(1, 5, 30),
            'fiber' => $this->faker->randomFloat(1, 0, 20),
            'sugar' => $this->faker->randomFloat(1, 0, 30),
            'sodium' => $this->faker->numberBetween(0, 2000),
            'cholesterol' => $this->faker->numberBetween(0, 300),
            'is_vegetarian' => $this->faker->boolean(),
            'is_vegan' => $this->faker->boolean(),
            'is_gluten_free' => $this->faker->boolean(),
            'is_dairy_free' => $this->faker->boolean(),
            'is_nut_free' => $this->faker->boolean(),
            'is_halal' => $this->faker->boolean(),
            'is_kosher' => $this->faker->boolean(),
            'seasonal' => $this->faker->boolean(),
            'total_ratings' => 0,
            'total_favorites' => 0,
            'total_views' => 0,
            'source' => $this->faker->randomElement(['user', 'ai']),
            'dietary_restrictions' => $this->faker->randomElements(['vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'nut-free'], 2)
        ];
    }
} 