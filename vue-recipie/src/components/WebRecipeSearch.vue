<script setup>
import { ref, computed } from 'vue';
import { useStore } from 'vuex';
import { ChefHat, Search, Loader, X, Star, ExternalLink, Save, AlertTriangle, Clock, Users } from 'lucide-vue-next';
import Navigation from './Navigation.vue';
import axios from '../axios';

const store = useStore();
const currentIngredient = ref('');
const selectedIngredients = ref([]);
const loading = ref(false);
const error = ref(null);
const recipes = computed(() => store.getters.webSearchResults);
const searched = ref(false);
const statusMessage = ref('');
const isFavorite = (recipe) => store.getters.isFavorite(recipe.id);

// Expanded list of common ingredients for better validation
const commonIngredients = [
  'chicken', 'beef', 'pork', 'fish', 'salmon', 'tuna', 'shrimp', 'tofu',
  'rice', 'pasta', 'noodles', 'spaghetti', 'macaroni', 'bread', 'flour', 'oats',
  'tomato', 'tomatoes', 'potato', 'potatoes', 'carrot', 'carrots', 'onion', 'onions',
  'garlic', 'lettuce', 'spinach', 'kale', 'broccoli', 'cauliflower', 'corn', 'peas',
  'beans', 'lentils', 'chickpeas', 'mushroom', 'mushrooms', 'pepper', 'peppers',
  'cheese', 'cheddar', 'mozzarella', 'parmesan', 'milk', 'cream', 'yogurt', 'butter',
  'egg', 'eggs', 'sugar', 'salt', 'pepper', 'oil', 'olive oil', 'vegetable oil',
  'apple', 'banana', 'orange', 'lemon', 'lime', 'avocado', 'cucumber', 'zucchini',
  'basil', 'oregano', 'thyme', 'rosemary', 'cilantro', 'parsley', 'mint',
  'cinnamon', 'vanilla', 'chocolate', 'honey', 'maple syrup', 'soy sauce', 'vinegar'
];

const validateIngredient = (ingredient) => {
  // Basic validation rules
  // Check if ingredient is too short
  if (ingredient.length < 2) return false;
  
  // Check if ingredient contains numbers
  if (/\d/.test(ingredient)) return false;
  
  // Check if ingredient is in our common ingredients list (case-insensitive)
  // Or allow it if it's at least 3 characters long (more permissive)
  return commonIngredients.some(common => 
    common.toLowerCase() === ingredient.toLowerCase()
  ) || ingredient.length >= 3;
};

const addIngredient = () => {
  const ingredient = currentIngredient.value.trim().toLowerCase();
  
  if (!ingredient) return;
  
  // Check if ingredient already exists
  if (selectedIngredients.value.includes(ingredient)) {
    error.value = 'This ingredient is already added';
    return;
  }

  if (!validateIngredient(ingredient)) {
    error.value = `"${ingredient}" might be misspelled or invalid. Please check your spelling or try a different ingredient.`;
    return;
  }
  
  selectedIngredients.value.push(ingredient);
  currentIngredient.value = '';
  error.value = null;
};

const removeIngredient = (index) => {
  selectedIngredients.value.splice(index, 1);
};

const clearIngredients = () => {
  selectedIngredients.value = [];
  store.commit('CLEAR_WEB_SEARCH_RESULTS');
  searched.value = false;
  error.value = null;
  statusMessage.value = '';
};

const searchRecipes = async () => {
  if (selectedIngredients.value.length === 0) {
    error.value = 'Please add at least one ingredient';
    return;
  }
  
  loading.value = true;
  error.value = null;
  searched.value = true;
  
  try {
    await store.dispatch('searchWebRecipes', selectedIngredients.value);
    
    // Scroll to results
    setTimeout(() => {
      const resultsElement = document.getElementById('results-section');
      if (resultsElement) {
        resultsElement.scrollIntoView({ behavior: 'smooth' });
      }
    }, 100);
  } catch (err) {
    console.error('Error searching recipes:', err);
    error.value = err.response?.data?.error || 'Failed to search for recipes. Please try again.';
  } finally {
    loading.value = false;
  }
};

const saveRecipe = async (recipe) => {
  try {
    // First save the recipe
    const response = await axios.post('/api/v1/web/recipes/save', {
      ...recipe,
      cooking_time: recipe.cooking_time.toString(),
      servings: parseInt(recipe.servings),
      ingredients: Array.isArray(recipe.ingredients) ? recipe.ingredients : [],
      instructions: Array.isArray(recipe.instructions) ? recipe.instructions : [],
      description: recipe.description || `A delicious recipe for ${recipe.title} using ${recipe.ingredients?.join(', ') || 'selected ingredients'}.`
    });

    // Then add it to favorites
    if (response.data.recipe) {
      await store.dispatch('toggleFavorite', response.data.recipe);
      alert('Recipe saved successfully!');
    }
  } catch (err) {
    console.error('Error saving recipe:', err);
    alert('Error saving recipe: ' + (err.response?.data?.error || 'Unknown error'));
  }
};

// Computed property to check if we have any recipes
const hasRecipes = computed(() => recipes.value.length > 0);

// Handle Enter key in the ingredient input
const handleKeyDown = (event) => {
  if (event.key === 'Enter') {
    addIngredient();
  }
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <Navigation />
    <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <ChefHat class="h-16 w-16 text-indigo-600 mx-auto mb-4 mt-4" />
        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">Find Online Recipes</h1>
        <p class="mt-3 text-xl text-gray-600">Discover recipes from the web with ingredients you have!</p>
      </div>
      <div class="max-w-xl mx-auto">
        <div class="bg-white rounded-xl shadow-lg p-8">
          <h2 class="text-xl font-semibold mb-4 text-gray-700">Enter Your Ingredients</h2>
          <div class="mb-6">
            <div class="flex">
              <input 
                v-model="currentIngredient" 
                @keydown="handleKeyDown"
                type="text" 
                class="flex-1 px-4 py-3 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                placeholder="Type an ingredient and press Enter"
              />
              <button 
                @click="addIngredient" 
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-r-lg transition duration-200"
              >
                <span>Add</span>
              </button>
            </div>
            <div v-if="error" class="mt-2 text-red-600 text-sm">
              {{ error }}
            </div>
            <div class="mt-2 text-gray-500 text-sm">
              <p>Examples: chicken, pasta, tomatoes, garlic, etc.</p>
            </div>
          </div>
          <div v-if="selectedIngredients.length > 0" class="mb-6">
            <h3 class="text-sm font-medium text-gray-500 mb-2">Selected Ingredients:</h3>
            <div class="flex flex-wrap gap-2">
              <div 
                v-for="(ingredient, index) in selectedIngredients" 
                :key="index"
                class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full flex items-center"
              >
                <span>{{ ingredient }}</span>
                <button 
                  @click="() => removeIngredient(index)" 
                  class="ml-2 text-indigo-600 hover:text-indigo-800 focus:outline-none"
                  aria-label="Remove ingredient"
                >
                  <X class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
          <div class="flex justify-between">
            <button 
              @click="searchRecipes" 
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg transition duration-200 flex items-center"
              :disabled="loading || selectedIngredients.length === 0"
            >
              <Search v-if="!loading" class="w-5 h-5 mr-2" />
              <Loader v-else class="w-5 h-5 mr-2 animate-spin" />
              <span>{{ loading ? 'Searching...' : 'Search Recipes' }}</span>
            </button>
            <button 
              @click="clearIngredients" 
              class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-6 py-2 rounded-lg transition duration-200"
              :disabled="selectedIngredients.length === 0"
            >
              Clear All
            </button>
          </div>
        </div>
      </div>
      <div v-if="loading" class="flex justify-center my-12">
        <Loader class="w-12 h-12 text-indigo-600 animate-spin" />
      </div>
      <div v-else-if="hasRecipes" id="results-section" class="space-y-8 mt-12">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-bold text-gray-800">Found {{ recipes.length }} Recipes</h2>
          <div v-if="statusMessage" class="text-sm text-gray-600">
            {{ statusMessage }}
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div 
            v-for="(recipe, index) in recipes" 
            :key="index"
            class="bg-white rounded-xl shadow-lg overflow-hidden transition duration-200 hover:shadow-xl hover:-translate-y-1"
          >
            <div v-if="recipe.image_url" class="h-48 overflow-hidden">
              <img :src="recipe.image_url" :alt="recipe.title" class="w-full h-full object-cover"
                   @error="event => event.target.src = '/default-recipe.jpg'" />
            </div>
            <div v-else class="h-48 bg-gray-200 flex items-center justify-center">
              <ChefHat class="w-12 h-12 text-gray-400" />
            </div>
            <div class="p-6">
              <h3 class="text-xl font-bold mb-2 text-gray-800">{{ recipe.title }}</h3>
              <p class="text-gray-600 mb-4 line-clamp-2">{{ recipe.description }}</p>
              <div class="flex justify-between text-sm text-gray-500 mb-4">
                <span class="flex items-center">
                  <Clock class="w-5 h-5 mr-1" />
                  {{ recipe.cooking_time }}
                </span>
                <span class="flex items-center">
                  <Users class="w-5 h-5 mr-1" />
                  {{ recipe.servings }} servings
                </span>
                <span class="capitalize px-3 py-1 rounded-full text-xs font-medium" :class="{
                  'bg-green-100 text-green-800': recipe.difficulty === 'easy',
                  'bg-yellow-100 text-yellow-800': recipe.difficulty === 'medium',
                  'bg-red-100 text-red-800': recipe.difficulty === 'hard'
                }">
                  {{ recipe.difficulty }}
                </span>
              </div>
              <div v-if="recipe.dietary_restrictions && recipe.dietary_restrictions.length > 0" class="flex flex-wrap gap-2 mb-4">
                <span
                  v-for="restriction in recipe.dietary_restrictions"
                  :key="restriction"
                  class="bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs font-medium"
                >
                  {{ restriction.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') }}
                </span>
              </div>
              <div class="flex gap-2">
                <button 
                  @click="saveRecipe(recipe)" 
                  class="flex-1 bg-indigo-100 hover:bg-indigo-200 text-indigo-800 px-4 py-2 rounded-lg flex items-center justify-center gap-2 transition duration-200"
                  :class="{ 'bg-orange-100 text-orange-800': isFavorite(recipe) }"
                >
                  <Star class="w-5 h-5" />
                  <span>{{ isFavorite(recipe) ? 'Saved' : 'Save' }}</span>
                </button>
                <a 
                  :href="recipe.source_url" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center justify-center gap-2 transition duration-200"
                >
                  <ExternalLink class="w-5 h-5" />
                  <span>View Original</span>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div v-else-if="searched" class="text-center py-12">
        <AlertTriangle class="w-12 h-12 text-gray-400 mx-auto" />
        <h2 class="text-2xl font-bold text-gray-800 mt-4">No Recipes Found</h2>
        <p class="text-gray-600 mt-2">Try adjusting your ingredients or search criteria.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style> 