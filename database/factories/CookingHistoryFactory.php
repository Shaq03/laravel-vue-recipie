<?php

namespace Database\Factories;

use App\Models\CookingHistory;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CookingHistoryFactory extends Factory
{
    protected $model = CookingHistory::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'recipe_id' => Recipe::factory(),
            'cooked_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'rating' => $this->faker->randomFloat(1, 1, 5),
            'notes' => $this->faker->paragraph()
        ];
    }
} 