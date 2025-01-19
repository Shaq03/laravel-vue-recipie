<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import Navigation from './Navigation.vue';
import { Clock, Users, ChefHat, PlusCircle, Utensils, Search, Filter } from 'lucide-vue-next';

const store = useStore();
const searchQuery = ref('');
const selectedDifficulty = ref('all');
const currentPage = ref(1);
const itemsPerPage = 9;

// Filter options
const difficulties = ['all', 'easy', 'medium', 'hard'];
const maxCookingTime = ref('all');
const cookingTimeOptions = [
  { value: 'all', label: 'All Times' },
  { value: '30', label: 'Under 30 mins' },
  { value: '60', label: 'Under 1 hour' },
  { value: '120', label: 'Under 2 hours' },
];

const recipes = computed(() => store.getters.allRecipes);
const loading = computed(() => store.state.loading);
const error = computed(() => store.state.error);

// Filtered recipes
const filteredRecipes = computed(() => {
  return recipes.value.filter(recipe => {
    const matchesSearch = recipe.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         recipe.description.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesDifficulty = selectedDifficulty.value === 'all' || recipe.difficulty === selectedDifficulty.value;
    const matchesCookingTime = maxCookingTime.value === 'all' || 
                              parseInt(recipe.cooking_time) <= parseInt(maxCookingTime.value);
    
    return matchesSearch && matchesDifficulty && matchesCookingTime;
  });
});

// Paginated recipes
const paginatedRecipes = computed(() => {
  const startIndex = (currentPage.value - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  return filteredRecipes.value.slice(startIndex, endIndex);
});

// Total pages
const totalPages = computed(() => {
  return Math.ceil(filteredRecipes.value.length / itemsPerPage);
});

// Page numbers array for pagination
const pageNumbers = computed(() => {
  const pages = [];
  for (let i = 1; i <= totalPages.value; i++) {
    pages.push(i);
  }
  return pages;
});

const resetFilters = () => {
  searchQuery.value = '';
  selectedDifficulty.value = 'all';
  maxCookingTime.value = 'all';
  currentPage.value = 1;
};

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

      <!-- Filters Section -->
      <div class="mt-12 bg-white rounded-xl shadow-md p-6">
        <div class="flex flex-col md:flex-row md:items-center md:space-x-6 space-y-4 md:space-y-0">
          <!-- Search -->
          <div class="flex-1">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search recipes..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
              >
            </div>
          </div>

          <!-- Difficulty Filter -->
          <div class="w-full md:w-48">
            <select
              v-model="selectedDifficulty"
              class="w-full py-2 pl-3 pr-10 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option value="all">All Difficulties</option>
              <option value="easy">Easy</option>
              <option value="medium">Medium</option>
              <option value="hard">Hard</option>
            </select>
          </div>

          <!-- Cooking Time Filter -->
          <div class="w-full md:w-48">
            <select
              v-model="maxCookingTime"
              class="w-full py-2 pl-3 pr-10 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
              <option v-for="option in cookingTimeOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- Reset Filters -->
          <button
            @click="resetFilters"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <Filter class="h-4 w-4 mr-2" />
            Reset Filters
          </button>
        </div>
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
      <div v-else-if="filteredRecipes.length === 0" class="mt-16 text-center">
        <Utensils class="w-16 h-16 mx-auto text-gray-400" />
        <p class="mt-4 text-gray-500 text-xl">No recipes found. Try adjusting your filters or add a new recipe!</p>
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
          v-for="recipe in paginatedRecipes"
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

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="mt-12 flex justify-center space-x-2">
        <button
          v-for="page in pageNumbers"
          :key="page"
          @click="currentPage = page"
          class="px-4 py-2 rounded-md"
          :class="{
            'bg-indigo-600 text-white': currentPage === page,
            'bg-gray-200 text-gray-700 hover:bg-gray-300': currentPage !== page
          }"
        >
          {{ page }}
        </button>
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