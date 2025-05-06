<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Clock, Users, ChefHat, ArrowLeft, Percent, Utensils } from 'lucide-vue-next';
import Navigation from './Navigation.vue';
import axios from '../axios';

const route = useRoute();
const router = useRouter();
const loading = ref(false);
const error = ref(null);
const similarRecipes = ref([]);

const getSimilarRecipes = async (recipeId) => {
  loading.value = true;
  error.value = null;

  try {
    // First try to get the recipe details to determine its source
    const recipeResponse = await axios.get(`/api/v1/recipes/${recipeId}`);
    const recipe = recipeResponse.data.recipe;

    // Then get similar recipes
    const response = await axios.get(`/api/v1/ml/recipes/${recipeId}/similar`);
    similarRecipes.value = response.data.similar_recipes.map(item => ({
      ...item,
      recipe: {
        ...item.recipe,
        source: recipe.source // Preserve the source information
      }
    }));
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to fetch similar recipes';
  } finally {
    loading.value = false;
  }
};

const viewRecipe = (recipeId) => {
  router.push(`/recipes/${recipeId}`);
};

onMounted(() => {
  if (route.params.recipeId) {
    getSimilarRecipes(route.params.recipeId);
  }
});
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <Navigation />
    
    <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
      
      <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <button 
          @click="router.back()" 
          class="inline-flex items-center px-4 py-2 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
        >
          <ArrowLeft class="h-5 w-5 mr-2" />
          Back to Recipe
        </button>

        <div class="text-center mt-6">
          <Utensils class="h-12 w-12 text-indigo-600 mx-auto mb-4" />
          <h2 class="text-3xl font-bold text-gray-900">Similar Recipes</h2>
          <p class="mt-2 text-gray-600">Discover more recipes that match your taste</p>
        </div>
      </div>
      
      <div v-if="loading" class="text-center py-12 bg-white rounded-xl shadow-lg">
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Finding similar recipes...</p>
      </div>
      
      <div v-else-if="error" class="text-center py-12 bg-white rounded-xl shadow-lg">
        <div class="max-w-md mx-auto bg-red-50 border-l-4 border-red-500 p-4 rounded-md">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-red-700">{{ error }}</p>
            </div>
          </div>
        </div>
      </div>
      
      <div v-else class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="item in similarRecipes"
          :key="item.recipe.id"
          @click="viewRecipe(item.recipe.id)"
          class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 cursor-pointer"
        >
          <div class="relative pb-2/3">
            <img
              v-if="item.recipe.image_url"
              :src="item.recipe.image_url"
              :alt="item.recipe.title"
              class="absolute inset-0 w-full h-full object-cover"
            />
            <div
              v-else
              class="absolute inset-0 bg-gray-100 flex items-center justify-center"
            >
              <ChefHat class="w-16 h-16 text-gray-400" />
            </div>
            <div class="absolute top-4 right-4">
              <div class="flex items-center bg-white rounded-full px-3 py-1 shadow-md">
                <Percent class="h-4 w-4 text-indigo-600 mr-1" />
                <span class="text-sm font-semibold text-indigo-600">
                  {{ item.similarity_score }}
                </span>
              </div>
            </div>
            <!-- Source Badge -->
            <div class="absolute top-4 left-4">
              <span 
                class="px-2 py-1 text-xs font-medium rounded-full"
                :class="{
                  'bg-blue-100 text-blue-800': item.recipe.source === 'web',
                  'bg-purple-100 text-purple-800': item.recipe.source === 'ai',
                  'bg-green-100 text-green-800': item.recipe.source === 'user'
                }"
              >
                {{ item.recipe.source === 'web' ? 'Web Recipe' : 
                   item.recipe.source === 'ai' ? 'AI Generated' : 'User Recipe' }}
              </span>
            </div>
          </div>

          <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ item.recipe.title }}</h3>
            <p class="text-gray-600 mb-4 line-clamp-2">{{ item.recipe.description }}</p>
            
            <div class="flex items-center justify-between text-sm text-gray-500 border-t pt-4">
              <div class="flex items-center">
                <Clock class="h-4 w-4 mr-1" />
                <span>{{ item.recipe.cooking_time }} mins</span>
              </div>
              <div class="flex items-center">
                <Users class="h-4 w-4 mr-1" />
                <span>{{ item.recipe.servings }} servings</span>
              </div>
              <span class="capitalize px-3 py-1 rounded-full text-xs font-medium" 
                :class="{
                  'bg-green-100 text-green-800': item.recipe.difficulty === 'easy',
                  'bg-yellow-100 text-yellow-800': item.recipe.difficulty === 'medium',
                  'bg-red-100 text-red-800': item.recipe.difficulty === 'hard'
                }"
              >
                {{ item.recipe.difficulty }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pb-2\/3 {
  padding-bottom: 66.666667%;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style> 