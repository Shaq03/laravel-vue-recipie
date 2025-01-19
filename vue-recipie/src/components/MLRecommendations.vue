<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Navigation from './Navigation.vue';

const route = useRoute();
const loading = ref(false);
const error = ref(null);
const similarRecipes = ref([]);

const getSimilarRecipes = async (recipeId) => {
  loading.value = true;
  error.value = null;

  try {
    const response = await fetch(`http://localhost:8000/api/v1/ml/recipes/${recipeId}/similar`, {
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
      }
    });

    if (!response.ok) {
      throw new Error('Failed to fetch similar recipes');
    }

    const data = await response.json();
    similarRecipes.value = data.similar_recipes;
  } catch (err) {
    error.value = err.message;
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  if (route.params.recipeId) {
    getSimilarRecipes(route.params.recipeId);
  }
});
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Navigation />
    
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
      <h2 class="text-2xl font-bold mb-6">Similar Recipes</h2>
      
      <div v-if="loading" class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
      </div>
      
      <div v-else-if="error" class="text-red-600 text-center">
        {{ error }}
      </div>
      
      <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="item in similarRecipes"
          :key="item.recipe.id"
          class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
        >
          <div class="p-6">
            <div class="flex justify-between items-start mb-2">
              <h3 class="text-xl font-semibold">{{ item.recipe.title }}</h3>
              <span class="text-sm text-indigo-600 font-medium">
                {{ item.similarity_score }} similar
              </span>
            </div>
            <p class="text-gray-600 mb-4">{{ item.recipe.description }}</p>
            
            <div class="flex items-center justify-between text-sm text-gray-500">
              <span>{{ item.recipe.cooking_time }} mins</span>
              <span>{{ item.recipe.servings }} servings</span>
              <span class="capitalize">{{ item.recipe.difficulty }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template> 