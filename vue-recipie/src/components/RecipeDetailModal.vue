<template>
    <div v-if="recipe" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
      <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full p-8 relative animate-fade-in">
        <button @click="onClose" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 text-2xl font-bold">&times;</button>
        <div class="flex flex-col md:flex-row md:items-start md:space-x-8">
          <img
            :src="recipe.image_url || '/default-recipe.jpg'"
            :alt="recipe.title"
            class="w-full md:w-64 h-48 object-cover rounded-lg mb-6 md:mb-0"
          />
          <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ recipe.title }}</h1>
            <p class="text-gray-600 mb-4">{{ recipe.description }}</p>
            <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
              <span v-if="recipe.cooking_time" class="flex items-center">⏱️ {{ recipe.cooking_time }}</span>
              <span v-if="recipe.servings" class="flex items-center">🍽️ {{ recipe.servings }} servings</span>
              <span v-if="recipe.difficulty" class="capitalize">{{ recipe.difficulty }}</span>
              <span v-if="recipe.cuisines && recipe.cuisines.length" class="flex items-center">
                <span v-for="cuisine in recipe.cuisines" :key="cuisine" class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">{{ cuisine }}</span>
              </span>
              <span v-if="recipe.tags && recipe.tags.length" class="flex items-center">
                <span v-for="tag in recipe.tags" :key="tag" class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">{{ tag }}</span>
              </span>
            </div>
          </div>
        </div>
        <div class="mt-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">Ingredients</h2>
          <ul class="list-disc list-inside text-gray-700 space-y-2">
            <li v-for="ingredient in recipe.ingredients" :key="ingredient" class="text-lg">
              {{ ingredient }}
            </li>
          </ul>
        </div>
        <div class="mt-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">Instructions</h2>
          <ol class="list-decimal list-inside text-gray-700 space-y-4">
            <li v-for="(step, idx) in recipe.instructions" :key="idx" class="text-lg">
              {{ step }}
            </li>
          </ol>
        </div>
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
  </template>
  
  <script setup>
  defineProps({
    recipe: Object,
    onClose: Function
  });
  </script>
  
  <style scoped>
  @keyframes fade-in {
    from { opacity: 0; transform: scale(0.98); }
    to { opacity: 1; transform: scale(1); }
  }
  .animate-fade-in {
    animation: fade-in 0.2s ease;
  }
  </style>