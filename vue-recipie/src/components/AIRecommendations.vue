<script setup>
import { ref } from 'vue';
import { useStore } from 'vuex';
import Navigation from './Navigation.vue';

const store = useStore();
const ingredients = ref('');
const loading = ref(false);
const error = ref(null);
const recommendations = ref([]);

const getRecommendations = async () => {
  if (!ingredients.value.trim()) {
    error.value = 'Please enter some ingredients';
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
        ingredients: ingredients.value.split(',').map(i => i.trim())
      })
    });

    if (!response.ok) {
      const data = await response.json();
      throw new Error(data.message || 'Failed to get recommendations');
    }

    const data = await response.json();
    recommendations.value = data.recipes;
  } catch (err) {
    error.value = err.message || 'Failed to get recommendations';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Navigation />
    
    <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 sm:text-4xl">AI Recipe Recommendations</h1>
        <p class="mt-3 text-xl text-gray-500">Enter ingredients you have, and let AI suggest recipes!</p>
      </div>

      <!-- Search Form -->
      <div class="mt-12 max-w-xl mx-auto">
        <form @submit.prevent="getRecommendations" class="space-y-4">
          <div>
            <label for="ingredients" class="block text-sm font-medium text-gray-700">
              Ingredients (comma-separated)
            </label>
            <div class="mt-1">
              <input
                type="text"
                id="ingredients"
                v-model="ingredients"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="e.g., chicken, rice, tomatoes"
              />
            </div>
          </div>

          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="loading"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              :class="{ 'opacity-75 cursor-not-allowed': loading }"
            >
              <svg
                v-if="loading"
                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
              </svg>
              Get Recommendations
            </button>
          </div>
        </form>

        <!-- Error Message -->
        <div v-if="error" class="mt-4 text-red-600 text-sm text-center">
          {{ error }}
        </div>
      </div>

      <!-- Results -->
      <div v-if="recommendations.length > 0" class="mt-12">
        <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="recipe in recommendations"
            :key="recipe.id"
            class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
          >
            <div class="p-6">
              <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ recipe.title }}</h2>
              <p class="text-gray-600 mb-4">{{ recipe.description }}</p>
              
              <div class="space-y-4">
                <div>
                  <h3 class="font-medium text-gray-900">Ingredients:</h3>
                  <ul class="mt-2 list-disc list-inside text-gray-600">
                    <li v-for="ingredient in recipe.ingredients" :key="ingredient">
                      {{ ingredient }}
                    </li>
                  </ul>
                </div>
                
                <div>
                  <h3 class="font-medium text-gray-900">Instructions:</h3>
                  <ol class="mt-2 list-decimal list-inside text-gray-600">
                    <li v-for="instruction in recipe.instructions" :key="instruction">
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