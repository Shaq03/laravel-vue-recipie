<script setup>
import { computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import Navigation from './Navigation.vue';
import { Star, Clock, Users, ChefHat, ExternalLink } from 'lucide-vue-next';

const store = useStore();
const favorites = computed(() => store.getters.allFavorites);

const removeFavorite = (recipe) => {
  store.dispatch('toggleFavorite', recipe);
};

// Load favorites from localStorage on component mount
onMounted(() => {
  store.dispatch('fetchFavorites');
});
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Navigation />
    
    <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <Star class="h-16 w-16 text-yellow-400 mx-auto mb-4" />
        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">Your Favorite Recipes</h1>
        <p class="mt-3 text-xl text-gray-600">All your saved recipes in one place</p>
      </div>

      <!-- Empty State -->
      <div v-if="favorites.length === 0" class="mt-16 text-center">
        <div class="bg-white rounded-lg shadow-sm p-8 max-w-2xl mx-auto">
          <Star class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900">No favorites yet</h3>
          <p class="mt-2 text-gray-500">
            Start adding recipes to your favorites by clicking the star icon on any recipe.
          </p>
        </div>
      </div>

      <!-- Favorites Grid -->
      <div v-else class="mt-16 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="recipe in favorites"
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
            <button
              @click="removeFavorite(recipe)"
              class="absolute top-4 right-4 p-2 bg-white rounded-full shadow-md hover:bg-gray-50 transition-colors"
            >
              <Star class="w-6 h-6 text-yellow-400 fill-current" />
            </button>
          </div>
          
          <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ recipe.title }}</h2>
            <p class="text-gray-600 mb-4 line-clamp-2">{{ recipe.description }}</p>
            
            <div class="space-y-4">
              <div>
                <h4 class="font-medium text-lg text-indigo-600">Ingredients:</h4>
                <ul class="mt-2 list-disc list-inside text-gray-600">
                  <li v-for="ingredient in recipe.ingredients" :key="ingredient">
                    {{ ingredient }}
                  </li>
                </ul>
              </div>
              
              <div v-if="recipe.source === 'web' && recipe.source_url">
                <a 
                  :href="recipe.source_url" 
                  target="_blank" 
                  rel="noopener noreferrer"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  <ExternalLink class="w-5 h-5 mr-2" />
                  View Original Recipe
                </a>
              </div>
              <div v-else>
                <h4 class="font-medium text-lg text-indigo-600">Instructions:</h4>
                <ol class="mt-2 list-decimal list-inside text-gray-600">
                  <li v-for="(instruction, index) in recipe.instructions" :key="index">
                    {{ instruction }}
                  </li>
                </ol>
              </div>
            </div>
            
            <div class="flex items-center justify-between text-sm text-gray-500 mt-4 pt-4 border-t">
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

            <!-- Action Buttons -->
            <div class="flex justify-between items-center mt-4 pt-4 border-t">
              <router-link
                :to="`/recipes/${recipe.id}/similar`"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ChefHat class="w-4 h-4 mr-2" />
                Similar Recipes
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

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>