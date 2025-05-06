<script setup>
import { ref, computed, onMounted } from 'vue';
import { useStore } from 'vuex';
import Navigation from './Navigation.vue';
import { ChefHat, PlusCircle, Utensils, Search, Filter, Star } from 'lucide-vue-next';
import RecipeDetailModal from './RecipeDetailModal.vue';

const store = useStore();
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 9;
const selectedCuisine = ref('');
const selectedRecipe = ref(null);
const showModal = ref(false);

// Get user-specific favorites from the store
const isFavorite = (recipe) => store.getters.isFavorite(recipe.id);
const toggleFavorite = async (recipe) => {
  await store.dispatch('toggleFavorite', recipe);
};

// Only get user-created recipes
const recipes = computed(() => store.getters.allRecipes.filter(recipe => recipe.source === 'user'));
const loading = computed(() => store.state.loading);
const error = computed(() => store.state.error);

// Get unique cuisines from recipes
const availableCuisines = computed(() => {
  const cuisineSet = new Set();
  recipes.value.forEach(recipe => {
    if (recipe.cuisines) {
      recipe.cuisines.forEach(cuisine => cuisineSet.add(cuisine));
    }
  });
  return Array.from(cuisineSet).sort();
});

// Filtered recipes
const filteredRecipes = computed(() => {
  return recipes.value.filter(recipe => {
    const matchesSearch = recipe.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         recipe.description.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                         recipe.ingredients.some(ingredient => 
                           ingredient.toLowerCase().includes(searchQuery.value.toLowerCase())
                         );
    
    const matchesCuisine = !selectedCuisine.value || 
                          (recipe.cuisines && recipe.cuisines.includes(selectedCuisine.value));
    
    return matchesSearch && matchesCuisine;
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
  selectedCuisine.value = '';
  currentPage.value = 1;
};

onMounted(async () => {
  await store.dispatch('fetchRecipes');
  // Fetch user's favorites when component mounts
  if (store.getters.isAuthenticated) {
    await store.dispatch('fetchFavorites');
  }
});
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-amber-50 to-orange-100">
    <Navigation />
    
    <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
      <div class="text-center">
        <ChefHat class="h-16 w-16 text-orange-500 mx-auto mb-4" />
        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl sm:tracking-tight lg:text-6xl">Your Recipes</h1>
        <p class="mt-5 text-xl text-gray-600">Discover recipes created by our community</p>
      </div>

      <!-- Filters Section -->
      <div class="mt-12 bg-white rounded-xl shadow-xl p-6">
        <div class="flex flex-col md:flex-row md:items-center md:space-x-6 space-y-4 md:space-y-0">
          <!-- Search -->
          <div class="flex-1">
            <div class="relative">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search recipes or ingredients..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
              >
            </div>
          </div>

          <!-- Cuisine Filter -->
          <div class="flex-1">
            <div class="relative">
              <select
                v-model="selectedCuisine"
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-orange-500 focus:border-orange-500"
              >
                <option value="">All Cuisines</option>
                <option v-for="cuisine in availableCuisines" :key="cuisine" :value="cuisine">{{ cuisine }}</option>
              </select>
            </div>
          </div>

          <!-- Reset Filters -->
          <button
            @click="resetFilters"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
          >
            <Filter class="h-4 w-4 mr-2" />
            Reset Search
          </button>
        </div>
      </div>

      <!-- Add New Recipe Button -->
      <div class="mt-8 flex justify-center">
        <router-link
          to="/recipes/create"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-full shadow-sm text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-colors duration-200"
        >
          <PlusCircle class="w-5 h-5 mr-2" />
          Add New Recipe
        </router-link>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="mt-16 flex justify-center">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-orange-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="mt-16 text-center text-red-600">
        {{ error }}
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredRecipes.length === 0" class="mt-16 text-center">
        <Utensils class="w-16 h-16 mx-auto text-gray-400" />
        <p class="mt-4 text-gray-600 text-xl">No recipes found. Try adjusting your filters or add a new recipe!</p>
        <router-link
          to="/recipes/create"
          class="mt-8 inline-block bg-orange-600 text-white px-8 py-3 rounded-full text-lg font-medium hover:bg-orange-700 transition-colors duration-200"
        >
          Start Cooking
        </router-link>
      </div>

      <!-- Recipe Grid -->
      <div v-else class="mt-16 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="recipe in paginatedRecipes"
          :key="recipe.id"
          class="bg-white rounded-xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1"
          @click="selectedRecipe = recipe; showModal = true"
          style="cursor:pointer;"
        >
          <div class="p-6">
            <div class="flex justify-between items-start">
              <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ recipe.title }}</h2>
              <button
                v-if="store.getters.isAuthenticated"
                @click.prevent="toggleFavorite(recipe)"
                class="p-2 bg-white rounded-full hover:bg-gray-50 transition-colors"
              >
                <Star 
                  class="w-6 h-6" 
                  :class="isFavorite(recipe) ? 'text-yellow-400 fill-current' : 'text-gray-400'"
                />
              </button>
            </div>
            
            <p class="text-gray-600 mb-4 line-clamp-2">{{ recipe.description }}</p>
            
            <!-- Recipe Details -->
            <div class="flex items-center text-sm text-gray-600 mb-4">
              <span class="capitalize">{{ recipe.difficulty }}</span>
              <span class="mx-2">•</span>
              <span>{{ recipe.cooking_time }} mins</span>
            </div>

            <!-- Cuisine Tags -->
            <div class="flex flex-wrap gap-2 mb-4">
              <span
                v-for="cuisine in recipe.cuisines"
                :key="cuisine"
                class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full"
              >
                {{ cuisine }}
              </span>
            </div>

            <!-- Dietary Tags -->
            <div class="flex flex-wrap gap-2 mb-4">
              <span
                v-for="tag in recipe.tags"
                :key="tag"
                class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full"
              >
                {{ tag }}
              </span>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center mt-4 pt-4 border-t">
              <router-link
                :to="`/recipes/${recipe.id}/similar`"
                @click.stop
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ChefHat class="w-4 h-4 mr-2" />
                Similar Recipes
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
          class="px-4 py-2 rounded-md transition-colors duration-200"
          :class="{
            'bg-orange-600 text-white': currentPage === page,
            'bg-gray-200 text-gray-700 hover:bg-gray-300': currentPage !== page
          }"
        >
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Render the modal -->
    <RecipeDetailModal v-if="showModal" :recipe="selectedRecipe" :onClose="() => { showModal = false; selectedRecipe = null; }" />
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