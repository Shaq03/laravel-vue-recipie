<script setup>
import { ref, computed } from 'vue';
import { useStore } from 'vuex';
import { ChefHat, Search, Loader, X, Star, ExternalLink, Save, AlertTriangle } from 'lucide-vue-next';
import Navigation from './Navigation.vue';
import axios from 'axios';

const store = useStore();
const currentIngredient = ref('');
const selectedIngredients = ref([]);
const loading = ref(false);
const error = ref(null);
const recipes = ref([]);
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
  recipes.value = [];
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
  recipes.value = [];
  statusMessage.value = '';
  
  try {
    const response = await axios.post('/v1/web/recipes/search', {
      ingredients: selectedIngredients.value
    });
    
    recipes.value = response.data.recipes || [];
    statusMessage.value = response.data.message || '';
    searched.value = true;
    
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
    
    // Try to get recipes from error response if available
    if (err.response?.data?.recipes && err.response.data.recipes.length > 0) {
      recipes.value = err.response.data.recipes;
      statusMessage.value = err.response.data.message || 'Showing suggested recipes instead.';
      searched.value = true;
    }
  } finally {
    loading.value = false;
  }
};

const saveRecipe = async (recipe) => {
  try {
    const response = await axios.post('/v1/web/recipes/save', recipe);
    store.dispatch('addFavorite', response.data.recipe || response.data);
    alert(response.data.message || 'Recipe saved successfully!');
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
  <div class="min-h-screen bg-gray-50">
    <Navigation />
    
    <main class="container mx-auto px-4 py-8">
      <div class="flex items-center mb-8">
        <ChefHat class="w-8 h-8 text-green-600 mr-3" />
        <h1 class="text-3xl font-bold text-gray-800">Find Recipes Online</h1>
      </div>
      
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Enter Your Ingredients</h2>
        
        <div class="mb-6">
          <div class="flex">
            <div class="relative flex-grow">
              <input 
                v-model="currentIngredient" 
                @keydown="handleKeyDown"
                type="text" 
                class="w-full border border-gray-300 rounded-l-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                placeholder="Type an ingredient and press Enter"
              />
            </div>
            <button 
              @click="addIngredient" 
              class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-r-lg transition duration-200 flex items-center"
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
              class="bg-green-100 text-green-800 px-3 py-1 rounded-full flex items-center"
            >
              <span>{{ ingredient }}</span>
              <button 
                @click="() => removeIngredient(index)" 
                class="ml-2 text-green-600 hover:text-green-800 focus:outline-none"
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
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-200 flex items-center"
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
      
      <!-- Loading indicator -->
      <div v-if="loading" class="flex justify-center my-12">
        <Loader class="w-12 h-12 text-green-600 animate-spin" />
      </div>
      
      <!-- Results -->
      <div v-else-if="hasRecipes" id="results-section" class="space-y-8">
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
            class="bg-white rounded-lg shadow-md overflow-hidden transition duration-200 hover:shadow-lg"
          >
            <div v-if="recipe.image_url" class="h-48 overflow-hidden">
              <img :src="recipe.image_url" :alt="recipe.title" class="w-full h-full object-cover" />
            </div>
            <div v-else class="h-48 bg-gray-200 flex items-center justify-center">
              <ChefHat class="w-12 h-12 text-gray-400" />
            </div>
            
            <div class="p-6">
              <h3 class="text-xl font-bold mb-2 text-gray-800">{{ recipe.title }}</h3>
              
              <p v-if="recipe.description" class="text-gray-600 mb-4 line-clamp-2">
                {{ recipe.description }}
              </p>
              
              <div class="flex justify-between text-sm text-gray-500 mb-4">
                <div class="flex items-center">
                  <span>{{ recipe.cooking_time }}</span>
                </div>
                <div class="flex items-center">
                  <span>{{ recipe.servings }} servings</span>
                </div>
                <div class="flex items-center">
                  <span class="capitalize">{{ recipe.difficulty }}</span>
                </div>
              </div>
              
              <div class="mb-4">
                <h4 class="font-semibold mb-2 text-gray-700">Ingredients:</h4>
                <ul class="list-disc list-inside">
                  <li v-for="(ingredient, i) in recipe.ingredients.slice(0, 5)" :key="i" class="text-sm text-gray-600">
                    {{ ingredient }}
                  </li>
                  <li v-if="recipe.ingredients.length > 5" class="text-sm text-gray-500">
                    And {{ recipe.ingredients.length - 5 }} more...
                  </li>
                </ul>
              </div>
              
              <div class="flex justify-between">
                <a 
                  v-if="recipe.source_url" 
                  :href="recipe.source_url" 
                  target="_blank"
                  class="text-blue-600 hover:text-blue-800 flex items-center"
                >
                  <ExternalLink class="w-4 h-4 mr-1" />
                  <span>View Original</span>
                </a>
                
                <button 
                  @click="() => saveRecipe(recipe)" 
                  class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center"
                >
                  <Save class="w-4 h-4 mr-1" />
                  <span>Save Recipe</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- No results message -->
      <div 
        v-else-if="!loading && searched" 
        class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-6 py-4 rounded-lg flex items-start"
      >
        <div class="flex-shrink-0 mr-3">
          <AlertTriangle class="w-5 h-5 text-yellow-400" />
        </div>
        <div>
          <p class="font-medium">No recipes found with these ingredients.</p>
          <p class="text-sm">Try adding more ingredients or using different ones. Common ingredients like chicken, pasta, rice, or vegetables often yield better results.</p>
        </div>
      </div>
    </main>
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