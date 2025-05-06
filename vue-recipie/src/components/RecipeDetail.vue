<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Clock, Users, ChefHat, ArrowLeft, Star, Heart } from 'lucide-vue-next';
import Navigation from './Navigation.vue';
import axios from '../axios';

const route = useRoute();
const router = useRouter();
const recipe = ref(null);
const loading = ref(true);
const error = ref(null);
const userPreferences = ref(null);

const fetchUserPreferences = async () => {
  try {
    const response = await axios.get(`/api/v1/user?t=${Date.now()}`);
    userPreferences.value = response.data.preferences;
  } catch (err) {
    console.error('Error fetching user preferences:', err);
  }
};

const fetchRecipe = async () => {
  loading.value = true;
  error.value = null;
  recipe.value = null;
  try {
    const id = route.params.recipeId || route.params.id || route.params.recipe_id;
    console.log('Fetching recipe with ID:', id);
    

    try {
      const aiResponse = await axios.get(`/api/v1/ai/recipes/${id}?t=${Date.now()}`);
      console.log('AI recipe response:', aiResponse.data);
      if (aiResponse.data && aiResponse.data.recipe) {
        recipe.value = aiResponse.data.recipe;
        return;
      } else {
        console.warn('AI endpoint returned 200 but no recipe:', aiResponse.data);
      }
    } catch (aiErr) {
      console.log('Recipe not found in AI recipes, trying main recipes...', aiErr);
    }
    
    try {
      const mainResponse = await axios.get(`/api/v1/recipes/${id}?t=${Date.now()}`);
      console.log('Main recipe response:', mainResponse.data);
      if (mainResponse.data && mainResponse.data.recipe) {
        recipe.value = mainResponse.data.recipe;
        return;
      } else {
        console.warn('Main endpoint returned 200 but no recipe:', mainResponse.data);
      }
    } catch (mainErr) {
      console.error('Error fetching main recipe:', mainErr);
      if (mainErr.response?.status === 404) {
        error.value = 'Recipe not found. Please try generating new recommendations.';
      } else {
        error.value = 'Failed to load recipe. Please try again.';
      }
      return;
    }
    
    error.value = 'Recipe not found or API returned unexpected data. Please try generating new recommendations.';
  } catch (err) {
    console.error('Error fetching recipe:', err);
    error.value = err.response?.data?.message || 'Failed to load recipe. Please try again.';
  } finally {
    loading.value = false;
  }
};

const toggleFavorite = async () => {
  if (!recipe.value) return;
  
  try {
    if (recipe.value.is_favorited) {
      await axios.delete(`/api/v1/user/favorites/${recipe.value.id}`);
    } else {
      await axios.post('/api/v1/user/favorites', { recipe_id: recipe.value.id });
    }
    recipe.value.is_favorited = !recipe.value.is_favorited;
  } catch (err) {
    console.error('Error toggling favorite:', err);
  }
};

const markAsCooked = async () => {
  try {
    const response = await axios.post('/api/v1/cooking-history', {
      recipe_id: recipe.value.id,
      rating: 0,
      notes: ''
    });
    
    // Show success message
    alert('Recipe added to your cooking history! You can now rate it and add notes in your cooking history page.');
    

    await fetchRecipe();
  } catch (err) {
    console.error('Error marking recipe as cooked:', err);
    alert('Failed to add recipe to cooking history. Please try again.');
  }
};

onMounted(async () => {
  await Promise.all([fetchRecipe(), fetchUserPreferences()]);
});
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <Navigation />
    <div class="max-w-3xl mx-auto py-16 px-4 sm:px-6 lg:px-8">
      <button @click="router.back()" class="mb-6 mt-6 flex items-center text-indigo-600 hover:underline">
        <ArrowLeft class="w-5 h-5 mr-2" /> Back
      </button>
      <div v-if="loading" class="text-center py-16">
        <ChefHat class="w-12 h-12 mx-auto text-indigo-400 animate-spin" />
        <p class="mt-4 text-lg text-gray-600">Loading recipe...</p>
      </div>
      <div v-else-if="error" class="text-center py-16 text-red-600">
        <p>{{ error }}</p>
        <p class="mt-4 text-gray-500">Try going back and selecting another recipe.</p>
      </div>
      <div v-else-if="recipe" class="bg-white rounded-xl shadow-lg p-8">
        <div class="flex flex-col md:flex-row md:items-start md:space-x-8">
          <img
            :src="recipe.image_url || '/default-recipe.jpg'"
            :alt="recipe.title"
            class="w-full md:w-64 h-48 object-cover rounded-lg mb-6 md:mb-0"
          />
          <div class="flex-1">
            <div class="flex justify-between items-start">
              <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ recipe.title }}</h1>
              <button 
                @click="toggleFavorite"
                class="text-2xl"
                :class="recipe.is_favorited ? 'text-red-500' : 'text-gray-400'"
              >
                <Heart :fill="recipe.is_favorited ? 'currentColor' : 'none'" />
              </button>
            </div>
            <p class="text-gray-600 mb-4">{{ recipe.description }}</p>
            <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
              <span class="flex items-center"><Clock class="w-4 h-4 mr-1" /> {{ recipe.cooking_time }}</span>
              <span class="flex items-center"><Users class="w-4 h-4 mr-1" /> {{ recipe.servings }} servings</span>
              <span class="capitalize">{{ recipe.difficulty }}</span>
              <span v-if="recipe.cuisines && recipe.cuisines.length" class="flex items-center">
                <span v-for="cuisine in recipe.cuisines" :key="cuisine" class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">{{ cuisine }}</span>
              </span>
              <span v-if="recipe.tags && recipe.tags.length" class="flex items-center">
                <span v-for="tag in recipe.tags" :key="tag" class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">{{ tag }}</span>
              </span>
              <span v-if="recipe.average_rating" class="flex items-center">
                <Star class="w-4 h-4 mr-1 text-yellow-400" />
                {{ recipe.average_rating.toFixed(1) }} ({{ recipe.total_ratings }})
              </span>
            </div>
          </div>
        </div>

        <!-- Similar Recipes Button -->
        <div class="mt-8 flex justify-center space-x-4">
          <button
            @click="markAsCooked"
            class="inline-flex items-center px-8 py-4 border border-transparent text-lg font-medium rounded-full shadow-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 transform hover:scale-105"
          >
            <ChefHat class="w-6 h-6 mr-3" />
            Mark as Cooked
          </button>
          <router-link
            :to="`/recipes/${recipe.id}/similar`"
            class="inline-flex items-center px-8 py-4 border border-transparent text-lg font-medium rounded-full shadow-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform hover:scale-105"
          >
            <ChefHat class="w-6 h-6 mr-3" />
            Find Similar Recipes
          </router-link>
        </div>

        <!-- Ingredients Section -->
        <div class="mt-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">Ingredients</h2>
          <ul class="list-disc list-inside text-gray-700 space-y-2">
            <li v-for="ingredient in recipe.ingredients" :key="ingredient" class="text-lg">
              {{ ingredient }}
            </li>
          </ul>
        </div>

        <!-- Instructions Section -->
        <div class="mt-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">Instructions</h2>
          <ol class="list-decimal list-inside text-gray-700 space-y-4">
            <li v-for="(step, idx) in recipe.instructions" :key="idx" class="text-lg">
              {{ step }}
            </li>
          </ol>
        </div>

        <!-- Dietary Restrictions Section -->
        <div v-if="recipe.dietary_restrictions && recipe.dietary_restrictions.length" class="mt-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">Dietary Information</h2>
          <div class="flex flex-wrap gap-2">
            <span
              v-for="restriction in recipe.dietary_restrictions"
              :key="restriction"
              class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm font-medium"
            >
              {{ restriction }}
            </span>
          </div>
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
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style> 