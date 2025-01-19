<?php

namespace Database\Seeders;

use App\Models\Recipe;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $recipes = [
            [
                'title' => 'Classic Spaghetti Carbonara',
                'description' => 'A traditional Italian pasta dish with eggs, cheese, pancetta, and black pepper.',
                'cooking_time' => 30,
                'servings' => 4,
                'difficulty' => 'medium',
                'ingredients' => [
                    '400g spaghetti',
                    '200g pancetta',
                    '4 large eggs',
                    '100g Pecorino Romano cheese',
                    '100g Parmigiano-Reggiano',
                    'Black pepper',
                    'Salt'
                ],
                'instructions' => [
                    'Bring a large pot of salted water to boil',
                    'Cook spaghetti according to package instructions',
                    'Meanwhile, cook pancetta until crispy',
                    'Beat eggs and mix with grated cheese',
                    'Combine pasta with egg mixture and pancetta',
                    'Season with black pepper'
                ],
                'image_url' => 'https://images.unsplash.com/photo-1612874742237-6526221588e3'
            ],
            [
                'title' => 'Homemade Margherita Pizza',
                'description' => 'Classic Neapolitan pizza with fresh mozzarella, tomatoes, and basil.',
                'cooking_time' => 45,
                'servings' => 2,
                'difficulty' => 'easy',
                'ingredients' => [
                    'Pizza dough',
                    'San Marzano tomatoes',
                    'Fresh mozzarella',
                    'Fresh basil',
                    'Olive oil',
                    'Salt'
                ],
                'instructions' => [
                    'Preheat oven to 450°F',
                    'Roll out pizza dough',
                    'Add tomato sauce',
                    'Top with mozzarella',
                    'Bake for 12-15 minutes',
                    'Add fresh basil'
                ],
                'image_url' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002'
            ]
        ];

        foreach ($recipes as $recipe) {
            Recipe::create($recipe);
        }
    }
} 