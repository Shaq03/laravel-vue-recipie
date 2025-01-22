<script setup>
import { ref } from 'vue';
import { useStore } from 'vuex';
import { ChefHat, Search, Loader, X, Star } from 'lucide-vue-next';
import Navigation from './Navigation.vue';

const store = useStore();
const currentIngredient = ref('');
const selectedIngredients = ref([]);
const loading = ref(false);
const error = ref(null);
const recommendations = ref([]);
const isFavorite = (recipe) => store.getters.isFavorite(recipe.id);
const toggleFavorite = (recipe) => store.dispatch('toggleFavorite', recipe);

const validateIngredient = (ingredient) => {
  // Basic validation rules
  const commonIngredients = [
    'chicken', 'beef', 'pork', 'fish', 'rice', 'pasta', 'tomato', 'tomatoes',
    'onion', 'garlic', 'potato', 'carrot', 'lettuce', 'cheese', 'milk', 'egg',
    'eggs', 'flour', 'sugar', 'salt', 'pepper', 'oil', 'butter', 'bread'
  ];

  // Check if ingredient is too short
  if (ingredient.length < 2) return false;
  
  // Check if ingredient contains numbers
  if (/\d/.test(ingredient)) return false;
  
  // Check if ingredient is in our common ingredients list (case-insensitive)
  return commonIngredients.some(common => 
    common.toLowerCase() === ingredient.toLowerCase()
  );
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

const getRecommendations = async () => {
  if (selectedIngredients.value.length === 0) {
    error.value = 'Please add at least one ingredient';
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    const response = await fetch('http://localhost:8000/api/v1/ai/recommendations', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-XSRF-TOKEN': document.cookie.match(/XSRF-TOKEN=([\w-]+)/)?.[1],
      },
      body: JSON.stringify({
        ingredients: selectedIngredients.value
      })
    });

    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.message || 'Failed to get recommendations');
    }

    const data = await response.json();
    recommendations.value = data.recipes;
  } catch (err) {
    error.value = 'Unable to get recommendations. Please try again with different ingredients.';
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
        <form @submit.prevent="getRecommendations" class="bg-white shadow-md rounded-lg p-6">
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
                placeholder="Type an ingredient and press Enter"
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

          <div class="flex justify-center">
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

        <!-- Error Message -->
        <div v-if="error" class="mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md">
          <p class="font-bold">Error</p>
          <p>{{ error }}</p>
        </div>
      </div>

      <!-- Results -->
      <div v-if="recommendations.length > 0" class="mt-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Your Personalized Recipes</h2>
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="recipe in recommendations"
            :key="recipe.id"
            class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300 ease-in-out"
          >
            <div class="p-6">
              <div class="flex justify-between items-start mb-2">
              <h3 class="text-2xl font-semibold text-gray-900 mb-2">{{ recipe.title }}</h3>
              <button
      @click="toggleFavorite(recipe)"
      class="p-2 hover:bg-gray-100 rounded-full transition-colors"
    >
      <Star 
        class="w-6 h-6" 
        :class="isFavorite(recipe) ? 'text-yellow-400 fill-current' : 'text-gray-400'"
      />
    </button>
  </div>
              <p class="text-gray-600 mb-4">{{ recipe.description }}</p>
              
              <div class="space-y-4">
                <div>
                  <h4 class="font-medium text-lg text-indigo-600">Ingredients:</h4>
                  <ul class="mt-2 list-disc list-inside text-gray-600">
                    <li v-for="ingredient in recipe.ingredients" :key="ingredient">
                      {{ ingredient }}
                    </li>
                  </ul>
                </div>
                
                <div>
                  <h4 class="font-medium text-lg text-indigo-600">Instructions:</h4>
                  <ol class="mt-2 list-decimal list-inside text-gray-600">
                    <li v-for="instruction in recipe.instructions" :key="instruction" class="mb-2">
                      {{ instruction }}
                    </li>
                  </ol>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

body {
  font-family: 'Poppins', sans-serif;
}
</style>