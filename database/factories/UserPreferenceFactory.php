<?php

namespace Database\Factories;

use App\Models\UserPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserPreferenceFactory extends Factory
{
    protected $model = UserPreference::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'preferred_cuisines' => $this->faker->randomElements(['italian', 'mexican', 'chinese', 'indian', 'japanese'], 2),
            'dietary_restrictions' => $this->faker->randomElements(['vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'nut-free'], 2),
            'cooking_skill_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'seasonal_preferences' => $this->faker->boolean(),
            'favorite_ingredients' => $this->faker->words(5),
            'disliked_ingredients' => $this->faker->words(3),
            'cooking_history' => []
        ];
    }
} 