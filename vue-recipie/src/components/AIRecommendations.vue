<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import { ChefHat, Search, Loader, X, Star, Clock, Users, Settings, Brain } from 'lucide-vue-next';
import Navigation from './Navigation.vue';
import axios from '../axios';
import RecipeDetailModal from './RecipeDetailModal.vue';

const store = useStore();
const currentIngredient = ref('');
const selectedIngredients = ref([]);
const loading = ref(false);
const error = ref(null);
const recommendations = computed(() => store.getters.aiSearchResults);
const showPreferences = ref(false);
const statusMessage = ref('');
const searched = ref(false);
const selectedRecipe = ref(null);
const showModal = ref(false);
const mlInsights = ref(null);
const showMLDetails = ref(false);
const showRatingModal = ref(false);
const selectedRating = ref(1);
const recipeToMark = ref(null);

// Add a computed property for authentication status
const isAuthenticated = computed(() => store.getters.isAuthenticated);

const preferences = reactive({
  cooking_skill_level: 'beginner',
  preferred_cuisines: [],
  dietary_restrictions: [],
  seasonal_preferences: true,
  ml_learning_rate: 0.7,
  ml_confidence_threshold: 0.6
});

const availableCuisines = [
  'Italian', 'Mexican', 'Chinese', 'Japanese', 'Indian', 
  'Thai', 'American', 'Mediterranean', 'French', 'Korean'
];

const availableRestrictions = [
  'Vegetarian', 'Vegan', 'Gluten-Free', 'Dairy-Free', 
  'Nut-Free', 'Halal', 'Kosher'
];

const isFavorite = (recipe) => store.getters.isFavorite(recipe.id);

const calculateMLScore = (recipe) => {
  const features = {
    ingredientMatch: calculateIngredientMatch(recipe),
    complexityMatch: calculateComplexityMatch(recipe),
    preferenceMatch: calculatePreferenceMatch(recipe),
    seasonalMatch: calculateSeasonalMatch(recipe)
  };

  const weights = {
    ingredientMatch: 0.4,
    complexityMatch: 0.2,
    preferenceMatch: 0.2,
    seasonalMatch: 0.2
  };

  return Object.entries(features).reduce((score, [key, value]) => {
    return score + (value * weights[key]);
  }, 0);
};

const calculateIngredientMatch = (recipe) => {
  if (!recipe?.ingredients || !selectedIngredients.value) {
    return 0;
  }

  const recipeIngredients = Array.isArray(recipe.ingredients) 
    ? recipe.ingredients 
    : JSON.parse(recipe.ingredients || '[]');
    
  const selectedIngs = selectedIngredients.value || [];
  
  const recipeIngs = recipeIngredients.map(i => i.toLowerCase());
  const selectedIngsLower = selectedIngs.map(i => i.toLowerCase());
  
  const matches = selectedIngsLower.filter(i => 
    recipeIngs.some(ri => ri.includes(i) || i.includes(ri))
  );
  
  return selectedIngs.length > 0 ? (matches.length / selectedIngs.length) * 100 : 0;
};

const calculateComplexityMatch = (recipe) => {
  const userSkill = preferences.cooking_skill_level || 'beginner';
  const skillLevels = { beginner: 1, intermediate: 2, advanced: 3 };
  const userLevel = skillLevels[userSkill] || 1;
  const difficulty = recipe.difficulty ? recipe.difficulty.toLowerCase() : 'medium';
  const recipeLevel = skillLevels[difficulty] || 2;
  return Math.max(0, 100 - Math.abs(userLevel - recipeLevel) * 33);
};

const calculatePreferenceMatch = (recipe) => {
  let matchScore = 100;
  
  if (preferences.dietary_restrictions?.length) {
    const hasRestrictedIngredient = recipe.ingredients.some(ingredient =>
      preferences.dietary_restrictions.some(restriction =>
        ingredient.toLowerCase().includes(restriction.toLowerCase())
      )
    );
    if (hasRestrictedIngredient) matchScore -= 50;
  }
  
  if (preferences.preferred_cuisines?.length) {
    const cuisineMatch = preferences.preferred_cuisines.some(cuisine =>
      recipe.cuisine.toLowerCase().includes(cuisine.toLowerCase())
    );
    if (!cuisineMatch) matchScore -= 30;
  }
  
  return Math.max(0, matchScore);
};

const calculateSeasonalMatch = (recipe) => {
  if (!preferences.seasonal_preferences) return 100;
  
  const currentSeason = getCurrentSeason();
  const seasonalIngredients = getSeasonalIngredients(currentSeason);
  const recipeIngredients = recipe.ingredients.map(i => i.toLowerCase());
  
  let matchCount = 0;
  seasonalIngredients.forEach(ingredient => {
    if (recipeIngredients.some(ri => ri.includes(ingredient))) {
      matchCount++;
    }
  });
  
  return (matchCount / seasonalIngredients.length) * 100;
};

const getCurrentSeason = () => {
  const month = new Date().getMonth();
  if (month >= 2 && month <= 4) return 'spring';
  if (month >= 5 && month <= 7) return 'summer';
  if (month >= 8 && month <= 10) return 'fall';
  return 'winter';
};

const getSeasonalIngredients = (season) => {
  const seasonalMap = {
    spring: ['asparagus', 'peas', 'radishes', 'spinach', 'artichokes'],
    summer: ['tomatoes', 'corn', 'zucchini', 'eggplant', 'bell peppers'],
    fall: ['pumpkin', 'squash', 'sweet potatoes', 'brussels sprouts', 'cauliflower'],
    winter: ['citrus', 'kale', 'brussels sprouts', 'root vegetables', 'winter squash']
  };
  return seasonalMap[season] || [];
};

const toggleFavorite = async (recipe) => {
  try {
    await store.dispatch('toggleFavorite', recipe);
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update favorite';
  }
};

const addIngredient = () => {
  const ingredient = currentIngredient.value.trim().toLowerCase();
  
  if (!ingredient) return;
  
  if (selectedIngredients.value.includes(ingredient)) {
    error.value = 'This ingredient is already added';
    return;
  }

  selectedIngredients.value.push(ingredient);
  currentIngredient.value = '';
  error.value = null;
};

const removeIngredient = (index) => {
  selectedIngredients.value.splice(index, 1);
};

const getRecommendations = async () => {
  if (!store.getters.isAuthenticated) {
    error.value = 'Please log in to get AI recommendations';
    return;
  }

  if (!selectedIngredients.value || selectedIngredients.value.length === 0) {
    error.value = 'Please add at least one ingredient';
    return;
  }

  loading.value = true;
  error.value = null;
  store.commit('CLEAR_AI_SEARCH_RESULTS');

  try {
    const token = store.state.token;
    if (!token) {
      throw new Error('No authentication token found');
    }

    const response = await axios.post('/api/v1/ai/recommendations', {
      ingredients: selectedIngredients.value,
      preferences: {
        ...preferences,
        ml_parameters: {
          learning_rate: preferences.ml_learning_rate,
          confidence_threshold: preferences.ml_confidence_threshold
        }
      }
    }, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });

    if (response.data) {
      if (response.data.error) {
        error.value = response.data.error;
      } else if (response.data.recommendations) {
        const enhancedRecommendations = response.data.recommendations.map(rec => {
          const mlScore = calculateMLScore(rec.recipe);
          const mlInsights = {
            ingredientMatch: rec.recipe.ingredient_match * 100,
            complexityMatch: rec.recipe.complexity_match * 100,
            preferenceMatch: rec.recipe.preference_match * 100,
            seasonalMatch: rec.recipe.seasonal_match * 100
          };
          
          return {
            ...rec,
            ml_score: mlScore,
            ml_insights: mlInsights
          };
        });

        const sortedRecommendations = enhancedRecommendations.sort((a, b) => {
          const scoreA = (a.normalized_score * 0.6) + (a.ml_score * 0.4);
          const scoreB = (b.normalized_score * 0.6) + (b.ml_score * 0.4);
          return scoreB - scoreA;
        });

        store.commit('SET_AI_SEARCH_RESULTS', sortedRecommendations);
        mlInsights.value = {
          ingredientMatch: sortedRecommendations[0]?.ingredient_match * 100,
          complexityMatch: sortedRecommendations[0]?.complexity_match * 100,
          preferenceMatch: sortedRecommendations[0]?.preference_match * 100,
          seasonalMatch: sortedRecommendations[0]?.seasonal_match * 100
        };
        statusMessage.value = `Found ${sortedRecommendations.length} recipes matching your ingredients and preferences`;
      }
    }
  } catch (err) {
    console.error('Error getting recommendations:', err.response?.data || err.message);
    if (err.response?.status === 401) {
      store.dispatch('logout');
      error.value = 'Your session has expired. Please log in again.';
    } else if (err.response?.status === 404) {
      error.value = err.response.data.error || 'No recipes found matching your ingredients. Try adding different ingredients.';
    } else {
      error.value = err.response?.data?.error || err.response?.data?.message || 'Failed to get recommendations. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};

const updatePreferences = async () => {
  console.log('Auth state:', {
    isAuthenticated: store.getters.isAuthenticated,
    token: store.state.token,
    user: store.state.user
  });

  if (!store.getters.isAuthenticated) {
    error.value = 'Please log in to save preferences';
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const token = store.state.token;
    if (!token) {
      throw new Error('No authentication token found');
    }

    const response = await axios.put('/api/v1/ai/preferences', preferences, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });
    
    if (response.data) {
      error.value = null;
      showPreferences.value = false;
      console.log('Preferences updated successfully:', response.data);
    }
  } catch (err) {
    console.error('Preferences update error:', err.response?.data || err.message);
    if (err.response?.status === 401) {
      // Token might be expired, try to refresh or logout
      store.dispatch('logout');
      error.value = 'Your session has expired. Please log in again.';
    } else {
      error.value = err.response?.data?.error || err.response?.data?.message || 'Failed to update preferences';
    }
  } finally {
    loading.value = false;
  }
};

const handleKeydown = (event) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    addIngredient();
  }
};

const searchRecipes = async () => {
  if (!selectedIngredients.value || selectedIngredients.value.length === 0) {
    error.value = 'Please add at least one ingredient';
    return;
  }
  await getRecommendations();
};

const clearIngredients = () => {
  selectedIngredients.value = [];
  store.commit('CLEAR_AI_SEARCH_RESULTS');
  searched.value = false;
  error.value = null;
  statusMessage.value = '';
  mlInsights.value = null;
  console.log('Cleared ingredients and search results');
};

const openRatingModal = (recipe) => {
  recipeToMark.value = recipe;
  selectedRating.value = 1;
  showRatingModal.value = true;
};

const closeRatingModal = () => {
  showRatingModal.value = false;
  recipeToMark.value = null;
};

const submitCookedRating = async () => {
  if (!recipeToMark.value) return;
  if (!store.getters.isAuthenticated) {
    alert('Please log in to mark recipes as cooked.');
    return;
  }
  try {
    await axios.post('/api/v1/cooking-history', {
      recipe_id: recipeToMark.value.id,
      rating: selectedRating.value,
      notes: ''
    });
    alert('Recipe added to your cooking history! You can update your rating and add notes later.');
    closeRatingModal();
  } catch (err) {
    console.error('Error marking recipe as cooked:', err);
    alert('Failed to add recipe to cooking history. Please try again.');
  }
};

onMounted(async () => {
  if (store.getters.isAuthenticated) {
    try {
      const response = await axios.get('/api/v1/ai/preferences');
      Object.assign(preferences, response.data);
    } catch (err) {
      console.error('Error loading preferences:', err);
    }
  }
});
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <Navigation />
    <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <div class="flex items-center justify-center space-x-4">
          <ChefHat class="h-16 w-16 text-indigo-600" />
          <Brain class="h-16 w-16 text-indigo-600" />
        </div>
        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl mt-4">AI Recipe Recommendations</h1>
        <p class="mt-3 text-xl text-gray-600">Discover delicious recipes with ingredients you have on hand!</p>
      </div>

      <div class="max-w-xl mx-auto">
        <form @submit.prevent="searchRecipes" class="bg-white shadow-md rounded-lg p-6">
          <div class="mb-4">
            <label for="ingredients" class="block text-sm font-medium text-gray-700 mb-2">
              What ingredients do you have?
            </label>
            <div class="relative">
              <input
                type="text"
                id="ingredients"
                v-model="currentIngredient"
                @keydown="handleKeydown"
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="Type any ingredient and press Enter"
              />
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <Search class="h-5 w-5 text-gray-400" />
              </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-2">
              <div
                v-for="(ingredient, index) in selectedIngredients"
                :key="index"
                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-700"
              >
                {{ ingredient }}
                <button
                  type="button"
                  @click="removeIngredient(index)"
                  class="ml-2 inline-flex items-center"
                >
                  <X class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>

          <div class="flex justify-between items-center">
            <button
              type="button"
              @click="showPreferences = !showPreferences"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <Settings class="h-5 w-5 mr-2" />
              Preferences
            </button>
            <button 
              @click="clearIngredients" 
              class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg transition duration-200"
              :disabled="selectedIngredients.length === 0"
            >
              Clear All
            </button>
          </div>

          <div class="mt-4">
            <button
              @click="searchRecipes" 
              class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg transition duration-200 flex items-center justify-center"
              :disabled="loading || selectedIngredients.length === 0"
            >
              <Search v-if="!loading" class="w-5 h-5 mr-2" />
              <Loader v-else class="w-5 h-5 mr-2 animate-spin" />
              <span>{{ loading ? 'Searching...' : 'Get Recommendations' }}</span>
            </button>
          </div>
        </form>

        <div v-if="showPreferences" class="mt-4 bg-white shadow-md rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Your Preferences</h3>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Cooking Skill Level</label>
              <select
                v-model="preferences.cooking_skill_level"
                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              >
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Preferred Cuisines</label>
              <div class="mt-2 grid grid-cols-2 gap-2">
                <label v-for="cuisine in availableCuisines" :key="cuisine" class="inline-flex items-center">
                  <input
                    type="checkbox"
                    v-model="preferences.preferred_cuisines"
                    :value="cuisine"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">{{ cuisine }}</span>
                </label>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Dietary Restrictions</label>
              <div class="mt-2 grid grid-cols-2 gap-2">
                <label v-for="restriction in availableRestrictions" :key="restriction" class="inline-flex items-center">
                  <input
                    type="checkbox"
                    v-model="preferences.dietary_restrictions"
                    :value="restriction"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                  >
                  <span class="ml-2 text-sm text-gray-700">{{ restriction }}</span>
                </label>
              </div>
            </div>

            <div>
              <label class="inline-flex items-center">
                <input
                  type="checkbox"
                  v-model="preferences.seasonal_preferences"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                >
                <span class="ml-2 text-sm text-gray-700">Consider seasonal ingredients</span>
              </label>
            </div>

            <div class="border-t pt-4">
              <h4 class="text-sm font-medium text-gray-700 mb-2">ML Parameters</h4>
              <div class="space-y-2">
                <div>
                  <label class="block text-sm text-gray-600">Learning Rate</label>
                  <input
                    type="range"
                    v-model="preferences.ml_learning_rate"
                    min="0.1"
                    max="1"
                    step="0.1"
                    class="w-full"
                  >
                  <span class="text-sm text-gray-500">{{ preferences.ml_learning_rate }}</span>
                </div>
                <div>
                  <label class="block text-sm text-gray-600">Confidence Threshold</label>
                  <input
                    type="range"
                    v-model="preferences.ml_confidence_threshold"
                    min="0.1"
                    max="1"
                    step="0.1"
                    class="w-full"
                  >
                  <span class="text-sm text-gray-500">{{ preferences.ml_confidence_threshold }}</span>
                </div>
              </div>
            </div>

            <div class="flex justify-end">
              <button
                @click="updatePreferences"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Save Preferences
              </button>
            </div>
          </div>
        </div>

        <div v-if="error" class="mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md">
          <p class="font-bold">Error</p>
          <p>{{ error }}</p>
        </div>
      </div>

      <div v-if="recommendations.length > 0" id="results-section" class="mt-8">
        <div v-if="mlInsights" class="mb-6 bg-white rounded-lg shadow-md p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
            <Brain class="h-5 w-5 mr-2 text-indigo-600" />
            ML Insights
          </h3>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Ingredient Match</span>
                <span class="text-sm font-medium">{{ Math.round(mlInsights.ingredientMatch) }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-indigo-600 h-2 rounded-full" :style="{ width: `${mlInsights.ingredientMatch}%` }"></div>
              </div>
            </div>
            <div class="space-y-2">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Complexity Match</span>
                <span class="text-sm font-medium">{{ Math.round(mlInsights.complexityMatch) }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-indigo-600 h-2 rounded-full" :style="{ width: `${mlInsights.complexityMatch}%` }"></div>
              </div>
            </div>
            <div class="space-y-2">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Preference Match</span>
                <span class="text-sm font-medium">{{ Math.round(mlInsights.preferenceMatch) }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-indigo-600 h-2 rounded-full" :style="{ width: `${mlInsights.preferenceMatch}%` }"></div>
              </div>
            </div>
            <div class="space-y-2">
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Seasonal Match</span>
                <span class="text-sm font-medium">{{ Math.round(mlInsights.seasonalMatch) }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-indigo-600 h-2 rounded-full" :style="{ width: `${mlInsights.seasonalMatch}%` }"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <div v-for="rec in recommendations" :key="rec.recipe.id" class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-200" @click="selectedRecipe = rec.recipe; showModal = true" style="cursor:pointer;">
            <div class="relative">
              <img :src="rec.recipe.image_url || '/default-recipe.jpg'" :alt="rec.recipe.title" class="w-full h-48 object-cover" />
              <div class="absolute top-0 right-0 m-2">
                <button
                  @click.stop="toggleFavorite(rec.recipe)"
                  class="p-2 rounded-full bg-white shadow-md hover:bg-gray-100 transition-colors duration-200"
                  :class="{ 'text-red-500': isFavorite(rec.recipe) }"
                >
                  <Star class="h-6 w-6" :fill="isFavorite(rec.recipe) ? 'currentColor' : 'none'" />
                </button>
              </div>
              <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white p-2">
                <div class="flex items-center justify-between">
                  <span class="text-sm">AI Confidence</span>
                  <span class="text-sm font-bold">{{ Math.round(Math.max(0, Math.min(1, ((rec.normalized_score || 0) * 0.6 + (rec.ml_score || 0) * 0.4))) * 100) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                  <div class="bg-indigo-600 h-1.5 rounded-full" :style="{ width: `${Math.max(0, Math.min(1, ((rec.normalized_score || 0) * 0.6 + (rec.ml_score || 0) * 0.4))) * 100}%` }"></div>
                </div>
              </div>
              <div v-if="rec.ml_prediction !== null" class="mt-1 flex items-center">
                <span v-if="rec.ml_prediction == 1" class="bg-green-500 text-white text-xs font-semibold px-2 py-0.5 rounded mr-2">ML Match</span>
                <span v-else class="bg-gray-400 text-white text-xs font-semibold px-2 py-0.5 rounded mr-2">No ML Match</span>
                <span v-if="rec.ml_confidence !== null" class="ml-2 text-xs text-white">ML Confidence: {{ Math.round(Math.max(0, Math.min(1, rec.ml_confidence)) * 100) }}%</span>
              </div>
              <div v-if="rec.ml_confidence !== null" class="w-full bg-green-100 rounded-full h-1 mt-1">
                <div class="bg-green-500 h-1 rounded-full" :style="{ width: `${Math.max(0, Math.min(1, rec.ml_confidence)) * 100}%` }"></div>
              </div>
            </div>
            <div class="p-6">
              <h3 class="text-xl font-bold mb-2 text-gray-800">{{ rec.recipe.title }}</h3>
              <p class="text-gray-600 mb-4 line-clamp-2">{{ rec.recipe.description }}</p>
              <div class="flex justify-between text-sm text-gray-500 mb-4">
                <span class="flex items-center">
                  <Clock class="w-5 h-5 mr-1" />
                  {{ rec.recipe.cooking_time }} min
                </span>
                <span class="flex items-center">
                  <Users class="w-5 h-5 mr-1" />
                  {{ rec.recipe.servings }} servings
                </span>
              </div>
              <div class="mt-4 flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0">
                <button
                  @click.stop="openRatingModal(rec.recipe)"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                  <ChefHat class="w-5 h-5 mr-2" />
                  Mark as Cooked
                </button>
                <router-link
                  :to="`/recipes/${rec.recipe.id}/similar`"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  <ChefHat class="w-5 h-5 mr-2" />
                  Find Similar Recipes
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="!loading && selectedIngredients.length > 0" class="mt-8 text-center">
        <p class="text-gray-600">No recommendations found. Try adding different ingredients!</p>
      </div>

      <div v-if="statusMessage" class="mt-4 p-4 bg-white rounded-md">
        <p class="text-sm text-gray-700">{{ statusMessage }}</p>
      </div>
    </div>
    <RecipeDetailModal v-if="showModal" :recipe="selectedRecipe" :onClose="() => { showModal = false; selectedRecipe = null; }" />
    <div v-if="showRatingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold text-gray-900">Rate Recipe</h3>
          <button @click="closeRatingModal" class="text-gray-400 hover:text-gray-500">
            <X class="w-6 h-6" />
          </button>
        </div>
        <div class="mb-6">
          <p class="text-gray-600 mb-4">How would you rate this recipe?</p>
          <div class="flex justify-center space-x-2">
            <button
              v-for="rating in 5"
              :key="rating"
              @click="selectedRating = rating"
              class="w-12 h-12 rounded-full flex items-center justify-center text-2xl transition-colors duration-200"
              :class="{
                'bg-yellow-400 text-white': selectedRating >= rating,
                'bg-gray-200 text-gray-600 hover:bg-gray-300': selectedRating < rating
              }"
            >
              {{ rating }}
            </button>
          </div>
        </div>
        <div class="flex justify-end space-x-3">
          <button
            @click="closeRatingModal"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Cancel
          </button>
          <button
            @click="submitCookedRating"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Add to History
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>