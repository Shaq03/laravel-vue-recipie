<?php

namespace App\Http\Controllers;

use App\Models\CookingHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\AIRecommendationService;

class CookingHistoryController extends Controller
{
    private $aiService;
    public function __construct(AIRecommendationService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index()
    {
        $history = CookingHistory::with('recipe')
            ->where('user_id', Auth::id())
            ->orderBy('cooked_at', 'desc')
            ->get();

        return response()->json($history);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'rating' => 'required|numeric|min:1|max:5',
            'notes' => 'nullable|string|max:1000'
        ]);

        $history = CookingHistory::create([
            'user_id' => Auth::id(),
            'recipe_id' => $validated['recipe_id'],
            'rating' => $validated['rating'],
            'notes' => $validated['notes'] ?? null,
            'cooked_at' => now()
        ]);

        $this->aiService->trainModel();

        return response()->json($history->load('recipe'), 201);
    }

    public function update(Request $request, CookingHistory $cookingHistory)
    {
        // Ensure user owns this history entry
        if ($cookingHistory->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'notes' => 'nullable|string|max:1000'
        ]);

        $cookingHistory->update($validated);

        return response()->json($cookingHistory->load('recipe'));
    }

    public function destroy(CookingHistory $cookingHistory)
    {
        // Ensure user owns this history entry
        if ($cookingHistory->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cookingHistory->delete();

        return response()->json(null, 204);
    }
} 