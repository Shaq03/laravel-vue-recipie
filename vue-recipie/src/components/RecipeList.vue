<script setup>
import { onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import Navigation from './Navigation.vue';

const store = useStore();

const recipes = computed(() => store.getters.allRecipes);
const loading = computed(() => store.state.loading);
const error = computed(() => store.state.error);

onMounted(async () => {
  await store.dispatch('fetchRecipes');
});
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Navigation />
    
    <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 sm:text-4xl">All Recipes</h1>
        <p class="mt-3 text-xl text-gray-500">Discover our collection of delicious recipes</p>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="mt-12 flex justify-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="mt-12 text-center text-red-600">
        {{ error }}
      </div>

      <!-- Empty State -->
      <div v-else-if="recipes.length === 0" class="mt-12 text-center">
        <p class="text-gray-500 text-lg">No recipes found. Be the first to add one!</p>
        <router-link
          to="/recipes/create"
          class="mt-4 inline-block bg-indigo-600 text-white px-6 py-3 rounded-md hover:bg-indigo-700 transition-colors"
        >
          Add Recipe
        </router-link>
      </div>

      <!-- Recipe Grid -->
      <div v-else class="mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="recipe in recipes"
          :key="recipe.id"
          class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
        >
          <div class="relative pb-[60%]">
            <img
              v-if="recipe.image_url"
              :src="recipe.image_url"
              :alt="recipe.title"
              class="absolute inset-0 w-full h-full object-cover"
            />
            <div
              v-else
              class="absolute inset-0 bg-gray-200 flex items-center justify-center"
            >
              <span class="text-4xl">🍳</span>
            </div>
          </div>
          
          <div class="p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ recipe.title }}</h2>
            <p class="text-gray-600 mb-4 line-clamp-2">{{ recipe.description }}</p>
            
            <div class="flex items-center justify-between text-sm text-gray-500">
              <span class="flex items-center">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ recipe.cooking_time }} mins
              </span>
              <span class="flex items-center">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
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
          </div>
        </div>
      </div>

      <!-- Add this button after the title section -->
      <div class="mt-6">
        <router-link
          to="/recipes/create"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Add New Recipe
        </router-link>
      </div>
    </div>
  </div>
</template> 