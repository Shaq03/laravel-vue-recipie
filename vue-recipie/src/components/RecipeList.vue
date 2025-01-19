<script setup>
import { onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import Navigation from './Navigation.vue';
import { Clock, Users, ChefHat, PlusCircle, Utensils } from 'lucide-vue-next';

const store = useStore();

const recipes = computed(() => store.getters.allRecipes);
const loading = computed(() => store.state.loading);
const error = computed(() => store.state.error);

onMounted(async () => {
  await store.dispatch('fetchRecipes');
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 bg-opacity-50 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCI+CjxyZWN0IHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgZmlsbD0iI2YxZjVmOSI+PC9yZWN0Pgo8cGF0aCBkPSJNMzYgNDZMMjQgMzRMMzYgMjJMMjQgMTBMMTIgMjJMMCAzNEwxMiA0NkwyNCAzNEwzNiA0NloiIGZpbGw9IiNlMmU4ZjAiPjwvcGF0aD4KPC9zdmc+')]">
    <Navigation />
    
    <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
      <div class="text-center">
        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl sm:tracking-tight lg:text-6xl">Culinary Delights</h1>
        <p class="mt-5 text-xl text-gray-500">Discover our handpicked collection of mouthwatering recipes</p>
      </div>

      <!-- Add New Recipe Button -->
      <div class="mt-8 flex justify-center">
        <router-link
          to="/recipes/create"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
        >
          <PlusCircle class="w-5 h-5 mr-2" />
          Add New Recipe
        </router-link>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="mt-16 flex justify-center">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-indigo-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="mt-16 text-center text-red-600">
        {{ error }}
      </div>

      <!-- Empty State -->
      <div v-else-if="recipes.length === 0" class="mt-16 text-center">
        <Utensils class="w-16 h-16 mx-auto text-gray-400" />
        <p class="mt-4 text-gray-500 text-xl">No recipes found. Be the first to add one!</p>
        <router-link
          to="/recipes/create"
          class="mt-8 inline-block bg-indigo-600 text-white px-8 py-3 rounded-full text-lg font-medium hover:bg-indigo-700 transition-colors duration-200"
        >
          Start Cooking
        </router-link>
      </div>

      <!-- Recipe Grid -->
      <div v-else class="mt-16 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="recipe in recipes"
          :key="recipe.id"
          class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300"
        >
          <div class="relative pb-2/3">
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
              <ChefHat class="w-16 h-16 text-gray-400" />
            </div>
          </div>
          
          <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ recipe.title }}</h2>
            <p class="text-gray-600 mb-4 line-clamp-2">{{ recipe.description }}</p>
            
            <div class="flex items-center justify-between text-sm text-gray-500">
              <span class="flex items-center">
                <Clock class="w-5 h-5 mr-1" />
                {{ recipe.cooking_time }} mins
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

            <div class="mt-6">
              <router-link
                :to="{ name: 'recipes.similar', params: { recipeId: recipe.id }}"
                class="text-indigo-600 hover:text-indigo-500 text-sm font-medium"
              >
                Find Similar Recipes
              </router-link>
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
</style>