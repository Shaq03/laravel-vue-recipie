<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\User;

class RecipeSeeder extends Seeder
{
    public function run()
    {
        // Get or create a default user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now()
            ]
        );

        $recipes = [
            // Easy Recipes
            [
                'title' => 'Simple Pasta Primavera',
                'description' => 'A light and fresh pasta dish loaded with seasonal vegetables.',
                'ingredients' => [
                    '8 oz pasta',
                    '2 cups mixed vegetables (bell peppers, zucchini, cherry tomatoes)',
                    '2 cloves garlic',
                    '1/4 cup olive oil',
                    '1/4 cup parmesan cheese',
                    'Fresh basil',
                    'Salt and pepper to taste'
                ],
                'instructions' => [
                    'Cook pasta according to package instructions.',
                    'In a large pan, heat olive oil and sauté garlic until fragrant.',
                    'Add vegetables and cook until tender-crisp.',
                    'Drain pasta and add to the pan with vegetables.',
                    'Toss with parmesan cheese and fresh basil.',
                    'Season with salt and pepper to taste.'
                ],
                'cooking_time' => 30,
                'servings' => 4,
                'difficulty' => 'easy',
                'cuisines' => ['Italian', 'Mediterranean'],
                'tags' => ['Vegetarian', 'Quick', 'Healthy'],
                'dietary_restrictions' => ['Vegetarian'],
                'source' => 'ai',
                'user_id' => $user->id,
                'image_url' => 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
            ],
            [
                'title' => 'Easy Chicken Stir Fry',
                'description' => 'A quick and healthy stir fry with chicken and vegetables.',
                'ingredients' => [
                    '1 lb chicken breast',
                    '2 cups mixed vegetables',
                    '2 tbsp soy sauce',
                    '1 tbsp ginger',
                    '2 cloves garlic',
                    '1 tbsp vegetable oil',
                    'Rice for serving'
                ],
                'instructions' => [
                    'Cut chicken into bite-sized pieces.',
                    'Heat oil in a large pan or wok.',
                    'Add chicken and cook until browned.',
                    'Add vegetables and stir fry for 3-4 minutes.',
                    'Add soy sauce, ginger, and garlic.',
                    'Serve over rice.'
                ],
                'cooking_time' => 25,
                'servings' => 4,
                'difficulty' => 'easy',
                'cuisines' => ['Asian', 'Chinese'],
                'tags' => ['Quick', 'Healthy', 'High Protein'],
                'dietary_restrictions' => ['Halal'],
                'source' => 'ai',
                'user_id' => $user->id,
                'image_url' => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
            ],
            [
                'title' => 'Simple Vegetable Soup',
                'description' => 'A comforting vegetable soup perfect for any season.',
                'ingredients' => [
                    '2 carrots',
                    '2 celery stalks',
                    '1 onion',
                    '2 potatoes',
                    '4 cups vegetable broth',
                    '1 bay leaf',
                    'Salt and pepper to taste'
                ],
                'instructions' => [
                    'Chop all vegetables into similar-sized pieces.',
                    'In a large pot, sauté onion until translucent.',
                    'Add remaining vegetables and cook for 5 minutes.',
                    'Add broth and bay leaf.',
                    'Simmer for 20 minutes until vegetables are tender.',
                    'Season with salt and pepper.'
                ],
                'cooking_time' => 35,
                'servings' => 6,
                'difficulty' => 'easy',
                'cuisines' => ['American', 'Healthy'],
                'tags' => ['Vegetarian', 'Vegan', 'Soup'],
                'dietary_restrictions' => ['Vegetarian', 'Vegan', 'Gluten-Free'],
                'source' => 'ai',
                'user_id' => $user->id,
                'image_url' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
            ],

            // Medium Difficulty Recipes
            [
                'title' => 'Classic Beef Stew',
                'description' => 'A hearty beef stew with vegetables and herbs.',
                'ingredients' => [
                    '2 lbs beef chuck',
                    '4 carrots',
                    '4 potatoes',
                    '2 onions',
                    '4 cloves garlic',
                    '2 cups beef broth',
                    '2 tbsp tomato paste',
                    'Fresh herbs (thyme, rosemary)',
                    'Salt and pepper to taste'
                ],
                'instructions' => [
                    'Cut beef into cubes and season with salt and pepper.',
                    'Brown beef in batches in a large pot.',
                    'Add onions and garlic, cook until softened.',
                    'Add broth, tomato paste, and herbs.',
                    'Simmer for 1.5 hours.',
                    'Add vegetables and cook for 30 more minutes.'
                ],
                'cooking_time' => 120,
                'servings' => 6,
                'difficulty' => 'medium',
                'cuisines' => ['American', 'Comfort Food'],
                'tags' => ['Meat', 'Stew', 'Winter'],
                'dietary_restrictions' => ['Halal'],
                'source' => 'ai',
                'user_id' => $user->id,
                'image_url' => 'https://images.unsplash.com/photo-1600334089648-b0d9d3028eb2?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
            ],
            [
                'title' => 'Vegetarian Lasagna',
                'description' => 'A layered pasta dish with vegetables and cheese.',
                'ingredients' => [
                    '12 lasagna noodles',
                    '2 cups ricotta cheese',
                    '2 cups mozzarella',
                    '2 cups marinara sauce',
                    '2 cups mixed vegetables',
                    '1 onion',
                    '3 cloves garlic',
                    'Fresh basil',
                    'Salt and pepper to taste'
                ],
                'instructions' => [
                    'Cook lasagna noodles according to package.',
                    'Sauté vegetables, onion, and garlic.',
                    'Layer noodles, sauce, vegetables, and cheese.',
                    'Repeat layers, ending with cheese.',
                    'Bake at 375°F for 30 minutes.',
                    'Let rest for 10 minutes before serving.'
                ],
                'cooking_time' => 60,
                'servings' => 8,
                'difficulty' => 'medium',
                'cuisines' => ['Italian', 'Mediterranean'],
                'tags' => ['Vegetarian', 'Pasta', 'Baked'],
                'dietary_restrictions' => ['Vegetarian'],
                'source' => 'ai',
                'user_id' => $user->id,
                'image_url' => 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
            ],

            // Hard Difficulty Recipes
            [
                'title' => 'Beef Wellington',
                'description' => 'A classic dish of beef fillet wrapped in puff pastry.',
                'ingredients' => [
                    '2 lbs beef tenderloin',
                    '1 lb puff pastry',
                    '8 oz mushrooms',
                    '2 shallots',
                    '2 cloves garlic',
                    '2 tbsp Dijon mustard',
                    '2 tbsp olive oil',
                    'Fresh thyme',
                    'Salt and pepper to taste'
                ],
                'instructions' => [
                    'Season beef and sear on all sides.',
                    'Sauté mushrooms, shallots, and garlic.',
                    'Spread mustard on beef.',
                    'Wrap beef in mushroom mixture and pastry.',
                    'Bake at 400°F for 40 minutes.',
                    'Let rest for 10 minutes before slicing.'
                ],
                'cooking_time' => 90,
                'servings' => 6,
                'difficulty' => 'hard',
                'cuisines' => ['British', 'French'],
                'tags' => ['Meat', 'Special Occasion', 'Baked'],
                'dietary_restrictions' => ['Halal'],
                'source' => 'ai',
                'user_id' => $user->id,
                'image_url' => 'https://images.unsplash.com/photo-1600891964092-4316c288032e?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
            ],
            [
                'title' => 'Complex Sushi Roll',
                'description' => 'A multi-ingredient sushi roll with various fillings.',
                'ingredients' => [
                    '2 cups sushi rice',
                    '4 sheets nori',
                    '1/2 lb fresh fish',
                    '1 avocado',
                    '1 cucumber',
                    '1 carrot',
                    'Wasabi',
                    'Soy sauce',
                    'Pickled ginger'
                ],
                'instructions' => [
                    'Cook and season sushi rice.',
                    'Prepare all fillings in thin strips.',
                    'Place nori on bamboo mat.',
                    'Spread rice evenly on nori.',
                    'Add fillings in the center.',
                    'Roll tightly and slice into pieces.'
                ],
                'cooking_time' => 60,
                'servings' => 4,
                'difficulty' => 'hard',
                'cuisines' => ['Japanese', 'Asian'],
                'tags' => ['Seafood', 'Raw', 'Healthy'],
                'dietary_restrictions' => ['Halal'],
                'source' => 'ai',
                'user_id' => $user->id,
                'image_url' => 'https://images.unsplash.com/photo-1579871494447-9811cf80d66c?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
            ],

            // Vegan Recipes
            [
                'title' => 'Vegan Buddha Bowl',
                'description' => 'A nourishing bowl packed with protein-rich ingredients and fresh vegetables.',
                'ingredients' => [
                    '1 cup quinoa',
                    '1 can chickpeas',
                    '2 cups mixed greens',
                    '1 avocado',
                    '1 cup roasted sweet potatoes',
                    '1/4 cup tahini',
                    '2 tbsp lemon juice',
                    '1 tbsp maple syrup',
                    'Salt and pepper to taste'
                ],
                'instructions' => [
                    'Cook quinoa according to package instructions.',
                    'Drain and rinse chickpeas, then season with salt and pepper.',
                    'Prepare tahini dressing by mixing tahini, lemon juice, maple syrup, and water.',
                    'Assemble bowl with quinoa, chickpeas, greens, avocado, and sweet potatoes.',
                    'Drizzle with tahini dressing and serve.'
                ],
                'cooking_time' => 45,
                'servings' => 2,
                'difficulty' => 'medium',
                'cuisines' => ['Mediterranean', 'Healthy'],
                'tags' => ['Vegan', 'Gluten-Free', 'Protein-Rich'],
                'dietary_restrictions' => ['Vegan', 'Gluten-Free'],
                'source' => 'ai',
                'user_id' => $user->id,
                'image_url' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
            ],

            // Gluten-Free Recipes
            [
                'title' => 'Gluten-Free Chocolate Cake',
                'description' => 'A rich and moist chocolate cake that everyone can enjoy.',
                'ingredients' => [
                    '2 cups almond flour',
                    '1/2 cup cocoa powder',
                    '1 tsp baking soda',
                    '1/2 tsp salt',
                    '3 eggs',
                    '1 cup maple syrup',
                    '1/2 cup coconut oil',
                    '1 tsp vanilla extract',
                    '1 cup dairy-free chocolate chips'
                ],
                'instructions' => [
                    'Preheat oven to 350°F and grease a cake pan.',
                    'Mix dry ingredients: almond flour, cocoa powder, baking soda, and salt.',
                    'In a separate bowl, whisk eggs, maple syrup, coconut oil, and vanilla.',
                    'Combine wet and dry ingredients, then fold in chocolate chips.',
                    'Pour into pan and bake for 30-35 minutes.',
                    'Let cool before serving.'
                ],
                'cooking_time' => 60,
                'servings' => 8,
                'difficulty' => 'medium',
                'cuisines' => ['American', 'Dessert'],
                'tags' => ['Gluten-Free', 'Dairy-Free', 'Dessert'],
                'dietary_restrictions' => ['Gluten-Free', 'Dairy-Free'],
                'source' => 'ai',
                'user_id' => $user->id,
                'image_url' => 'https://images.unsplash.com/photo-1578985545062-69928b1d9587?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
            ],

            // Halal Recipes
            [
                'title' => 'Halal Chicken Biryani',
                'description' => 'A fragrant and flavorful rice dish with tender chicken and aromatic spices.',
                'ingredients' => [
                    '2 cups basmati rice',
                    '1 lb chicken thighs',
                    '2 onions',
                    '4 cloves garlic',
                    '2 inch ginger',
                    '2 tbsp biryani masala',
                    '1 cup yogurt',
                    'Fresh mint and cilantro',
                    'Saffron threads',
                    'Ghee or oil'
                ],
                'instructions' => [
                    'Marinate chicken with yogurt, biryani masala, and spices for 2 hours.',
                    'Cook rice until 70% done, then drain.',
                    'In a large pot, layer rice and marinated chicken.',
                    'Add saffron milk and fresh herbs between layers.',
                    'Cook on low heat for 30 minutes.',
                    'Let rest for 10 minutes before serving.'
                ],
                'cooking_time' => 90,
                'servings' => 6,
                'difficulty' => 'hard',
                'cuisines' => ['Indian', 'Middle Eastern'],
                'tags' => ['Halal', 'Spicy', 'Family Meal'],
                'dietary_restrictions' => ['Halal'],
                'source' => 'ai',
                'user_id' => $user->id,
                'image_url' => 'https://images.unsplash.com/photo-1563379091339-03b21ab4a4f8?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
            ],

            // Kosher Recipes
            [
                'title' => 'Kosher Matzo Ball Soup',
                'description' => 'A comforting traditional Jewish soup with fluffy matzo balls.',
                'ingredients' => [
                    '1 whole chicken',
                    '2 onions',
                    '4 carrots',
                    '4 celery stalks',
                    'Fresh dill',
                    'Matzo meal',
                    'Eggs',
                    'Chicken fat or oil',
                    'Salt and pepper'
                ],
                'instructions' => [
                    'Prepare chicken soup by simmering chicken with vegetables for 2 hours.',
                    'Make matzo balls by mixing matzo meal, eggs, and fat.',
                    'Form balls and refrigerate for 30 minutes.',
                    'Cook matzo balls in simmering soup for 20 minutes.',
                    'Serve hot with fresh dill.'
                ],
                'cooking_time' => 150,
                'servings' => 8,
                'difficulty' => 'medium',
                'cuisines' => ['Jewish', 'Traditional'],
                'tags' => ['Kosher', 'Comfort Food', 'Soup'],
                'dietary_restrictions' => ['Kosher'],
                'source' => 'ai',
                'user_id' => $user->id,
                'image_url' => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80'
            ],

            // More Easy Recipes
            [
                'title' => 'Quick Tuna Salad',
                'description' => 'A simple and healthy tuna salad perfect for sandwiches or wraps.',
                'ingredients' => [
                    '2 cans tuna',
                    '1/4 cup mayonnaise',
                    '1/4 cup diced celery',
                    '1/4 cup diced onion',
                    '1 tbsp lemon juice',
                    'Salt and pepper to taste'
                ],
                'instructions' => [
                    'Drain tuna and flake with a fork.',
                    'Mix all ingredients in a bowl.',
                    'Season with salt and pepper.',
                    'Serve on bread or with crackers.'
                ],
                'cooking_time' => 10,
                'servings' => 4,
                'difficulty' => 'easy',
                'cuisines' => ['American', 'Healthy'],
                'tags' => ['Quick', 'High Protein', 'Lunch'],
                'dietary_restrictions' => ['Halal'],
                'source' => 'ai',
                'user_id' => $user->id
            ],
            [
                'title' => 'Easy Fruit Smoothie',
                'description' => 'A refreshing and nutritious fruit smoothie.',
                'ingredients' => [
                    '1 banana',
                    '1 cup mixed berries',
                    '1 cup yogurt',
                    '1/2 cup milk',
                    '1 tbsp honey',
                    'Ice cubes'
                ],
                'instructions' => [
                    'Add all ingredients to a blender.',
                    'Blend until smooth.',
                    'Add more milk if too thick.',
                    'Serve immediately.'
                ],
                'cooking_time' => 5,
                'servings' => 2,
                'difficulty' => 'easy',
                'cuisines' => ['Healthy', 'Drinks'],
                'tags' => ['Vegetarian', 'Quick', 'Breakfast'],
                'dietary_restrictions' => ['Vegetarian'],
                'source' => 'ai',
                'user_id' => $user->id
            ],

            // More Medium Recipes
            [
                'title' => 'Chicken Curry',
                'description' => 'A flavorful Indian-style chicken curry.',
                'ingredients' => [
                    '1 lb chicken',
                    '1 onion',
                    '2 tomatoes',
                    '2 tbsp curry powder',
                    '1 cup coconut milk',
                    '2 cloves garlic',
                    '1 inch ginger',
                    'Fresh cilantro',
                    'Salt to taste'
                ],
                'instructions' => [
                    'Sauté onions, garlic, and ginger.',
                    'Add chicken and cook until browned.',
                    'Add curry powder and cook for 1 minute.',
                    'Add tomatoes and coconut milk.',
                    'Simmer for 20 minutes.',
                    'Garnish with cilantro.'
                ],
                'cooking_time' => 45,
                'servings' => 4,
                'difficulty' => 'medium',
                'cuisines' => ['Indian', 'Asian'],
                'tags' => ['Spicy', 'Family Meal'],
                'dietary_restrictions' => ['Halal'],
                'source' => 'ai',
                'user_id' => $user->id
            ],

            // More Hard Recipes
            [
                'title' => 'Beef Bourguignon',
                'description' => 'A classic French beef stew with wine and mushrooms.',
                'ingredients' => [
                    '2 lbs beef chuck',
                    '1 bottle red wine',
                    '8 oz mushrooms',
                    '2 onions',
                    '4 carrots',
                    '4 cloves garlic',
                    'Fresh herbs',
                    'Bacon',
                    'Pearl onions'
                ],
                'instructions' => [
                    'Marinate beef in wine overnight.',
                    'Brown beef and bacon.',
                    'Sauté vegetables.',
                    'Combine all ingredients in a Dutch oven.',
                    'Simmer for 3 hours.',
                    'Serve with crusty bread.'
                ],
                'cooking_time' => 240,
                'servings' => 6,
                'difficulty' => 'hard',
                'cuisines' => ['French', 'European'],
                'tags' => ['Special Occasion', 'Stew'],
                'dietary_restrictions' => ['Halal'],
                'source' => 'ai',
                'user_id' => $user->id
            ]
        ];

        // Clear existing recipes to avoid duplicates
        Recipe::where('source', 'ai')->delete();

        // Create new recipes
        foreach ($recipes as $recipeData) {
            Recipe::create($recipeData);
        }
    }
} 