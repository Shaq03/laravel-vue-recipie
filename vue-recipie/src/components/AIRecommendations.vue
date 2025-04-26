<script setup>
import { ref, reactive, computed } from 'vue';
import { useStore } from 'vuex';
import { ChefHat, Search, Loader, X, Star, Clock, Users, Settings } from 'lucide-vue-next';
import Navigation from './Navigation.vue';
import axios from 'axios';

const store = useStore();
const currentIngredient = ref('');
const selectedIngredients = ref([]);
const loading = ref(false);
const error = ref(null);
const recommendations = ref([]);
const showPreferences = ref(false);
const statusMessage = ref('');
const searched = ref(false);

// Add a computed property for authentication status
const isAuthenticated = computed(() => store.getters.isAuthenticated);

const preferences = reactive({
  cooking_skill_level: 'beginner',
  preferred_cuisines: [],
  dietary_restrictions: [],
  seasonal_preferences: true
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

  if (selectedIngredients.value.length === 0) {
    error.value = 'Please add at least one ingredient';
    return;
  }

  loading.value = true;
  error.value = null;
  recommendations.value = [];

  try {
    const token = store.state.token;
    if (!token) {
      throw new Error('No authentication token found');
    }

    const response = await axios.post('/api/v1/ai/recommendations', {
      ingredients: selectedIngredients.value
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
        recommendations.value = response.data.recommendations;
      }
    }
  } catch (err) {
    console.error('Error getting recommendations:', err.response?.data || err.message);
    if (err.response?.status === 401) {
      // Token might be expired, try to refresh or logout
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
  if (selectedIngredients.value.length === 0) {
    error.value = 'Please add at least one ingredient';
    return;
  }
  
  loading.value = true;
  error.value = null;
  recommendations.value = [];
  
  try {
    const response = await axios.post('/api/v1/ai/recommendations', {
      ingredients: selectedIngredients.value
    }, {
      headers: {
        'Authorization': `Bearer ${store.state.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    });
    
    if (response.data) {
      if (response.data.error) {
        error.value = response.data.error;
      } else if (response.data.recommendations) {
        recommendations.value = response.data.recommendations;
      }
    }
    
    // Scroll to results
    setTimeout(() => {
      const resultsElement = document.getElementById('results-section');
      if (resultsElement) {
        resultsElement.scrollIntoView({ behavior: 'smooth' });
      }
    }, 100);
  } catch (err) {
    console.error('Error searching recipes:', err);
    if (err.response?.status === 401) {
      // Token might be expired, try to refresh or logout
      store.dispatch('logout');
      error.value = 'Your session has expired. Please log in again.';
    } else if (err.response?.status === 500) {
      // Show friendly message for server errors
      error.value = `We couldn't find any recipes matching your ingredients: ${selectedIngredients.value.join(', ')}. 
      
Try these suggestions:
• Add more common ingredients
• Check for spelling mistakes
• Try different ingredient combinations
• Browse our recipe collection for inspiration`;
    } else {
      error.value = err.response?.data?.error || 'Failed to search for recipes. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <Navigation />
    
    <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <ChefHat class="h-16 w-16 text-indigo-600 mx-auto mb-4" />
        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">AI Recipe Recommendations</h1>
        <p class="mt-3 text-xl text-gray-600">Discover delicious recipes with ingredients you have on hand!</p>
      </div>

      <!-- Search Form -->
      <div class="mt-12 max-w-xl mx-auto">
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

            <!-- Selected Ingredients Tags -->
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
              type="submit"
              :disabled="loading || selectedIngredients.length === 0"
              class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out"
              :class="{ 'opacity-75 cursor-not-allowed': loading || selectedIngredients.length === 0 }"
            >
              <Loader v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" />
              {{ loading ? 'Searching...' : 'Get Recommendations' }}
            </button>
          </div>
        </form>

        <!-- Preferences Panel -->
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

        <!-- Error Message -->
        <div v-if="error" class="mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md">
          <p class="font-bold">Error</p>
          <p>{{ error }}</p>
        </div>
      </div>

      <!-- Recipe Recommendations -->
      <div v-if="recommendations.length > 0" class="mt-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div v-for="rec in recommendations" :key="rec.recipe.id" class="bg-white rounded-lg shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-200">
          <div class="relative">
            <img :src="rec.recipe.image_url || '/default-recipe.jpg'" :alt="rec.recipe.title" class="w-full h-48 object-cover" />
            <div class="absolute top-0 right-0 m-2">
              <button
                @click="toggleFavorite(rec.recipe)"
                class="p-2 rounded-full bg-white shadow-md hover:bg-gray-100 transition-colors duration-200"
                :class="{ 'text-red-500': isFavorite(rec.recipe) }"
              >
                <Star class="h-6 w-6" :fill="isFavorite(rec.recipe) ? 'currentColor' : 'none'" />
              </button>
            </div>
          </div>

          <div class="p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ rec.recipe.title }}</h3>
            
            <!-- Match Score -->
            <div class="mb-3">
              <div class="flex items-center">
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                  <div class="bg-indigo-600 h-2.5 rounded-full" :style="{ width: `${rec.normalized_score * 100}%` }"></div>
                </div>
                <span class="ml-2 text-sm font-medium text-gray-600">
                  {{ Math.round(rec.normalized_score * 100) }}% Match
                </span>
              </div>
            </div>

            <!-- Recipe Details -->
            <div class="space-y-2">
              <div class="flex items-center text-sm text-gray-600">
                <Clock class="h-4 w-4 mr-1" />
                <span>{{ rec.recipe.cooking_time || '?' }} mins</span>
                <span class="mx-2">•</span>
                <span class="capitalize">{{ rec.recipe.difficulty }}</span>
              </div>
              
              <!-- Cuisine Tags -->
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="cuisine in rec.recipe.cuisines"
                  :key="cuisine"
                  class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full"
                >
                  {{ cuisine }}
                </span>
              </div>

              <!-- Dietary Tags -->
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="tag in rec.recipe.tags"
                  :key="tag"
                  class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full"
                >
                  {{ tag }}
                </span>
              </div>
            </div>

            <button
              @click="$router.push(`/recipe/${rec.recipe.id}`)"
              class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              View Recipe
            </button>
          </div>
        </div>
      </div>

      <!-- No Results Message -->
      <div v-else-if="!loading && selectedIngredients.length > 0" class="mt-8 text-center">
        <p class="text-gray-600">No recommendations found. Try adding different ingredients!</p>
      </div>

      <!-- Status Message -->
      <div v-if="statusMessage" class="mt-4 p-4 bg-white rounded-md">
        <p class="text-sm text-gray-700">{{ statusMessage }}</p>
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