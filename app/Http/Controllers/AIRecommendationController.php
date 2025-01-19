<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AIRecommendationController extends Controller
{
    public function getRecommendations(Request $request)
    {
        $request->validate([
            'ingredients' => 'required|array',
            'ingredients.*' => 'string'
        ]);

        $ingredients = implode(', ', $request->ingredients);
        
        // Try to get from cache first
        $cacheKey = 'recipe_recommendations_' . md5($ingredients);
        if ($cachedResponse = Cache::get($cacheKey)) {
            return response()->json($cachedResponse);
        }

        try {
            // Using Google's Gemini API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . config('services.gemini.api_key'), [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => "Create 3 recipe recommendations using these ingredients: {$ingredients}. 
                                Format the response as JSON with this structure: 
                                {\"recipes\": [{
                                    \"id\": \"1\", 
                                    \"title\": \"\", 
                                    \"description\": \"\",
                                    \"cooking_time\": \"30\",
                                    \"servings\": 4,
                                    \"difficulty\": \"easy\",
                                    \"ingredients\": [], 
                                    \"instructions\": []
                                }]}
                                Make sure cooking_time is a string and servings is a number.
                                Be creative but practical with the recipes."
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1000,
                ]
            ]);

            if ($response->failed()) {
                throw new \Exception('AI API request failed: ' . $response->body());
            }

            $result = $response->json();
            
            if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \Exception('Unexpected API response format');
            }

            $content = $result['candidates'][0]['content']['parts'][0]['text'];
            
            // Extract JSON from the response
            if (preg_match('/{.*}/s', $content, $matches)) {
                $content = $matches[0];
            }
            
            // Ensure the content is valid JSON
            $decodedContent = json_decode($content);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from AI');
            }

            // Cache the response for 24 hours
            Cache::put($cacheKey, $decodedContent, 60 * 24);

            // Log successful response
            \Log::info('AI Recipe recommendations generated successfully', [
                'ingredients' => $ingredients,
                'recipes_count' => count($decodedContent->recipes ?? [])
            ]);

            return response()->json($decodedContent);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Failed to generate AI recipe recommendations', [
                'error' => $e->getMessage(),
                'ingredients' => $ingredients
            ]);

            return response()->json([
                'error' => 'Failed to generate recommendations: ' . $e->getMessage()
            ], 500);
        }
    }
} 