<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useStore } from 'vuex';
import Navigation from './Navigation.vue';
import { ChefHat, PlusCircle, Utensils, Search, Filter, Star, Clock, Users, Loader, X } from 'lucide-vue-next';
import RecipeDetailModal from './RecipeDetailModal.vue';
import axios from '../axios';

const store = useStore();
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 9;
const selectedCuisine = ref('');
const selectedRecipe = ref(null);
const showModal = ref(false);
const showRatingModal = ref(false);
const selectedRating = ref(1);
const recipeToMark = ref(null);
const selectedDifficulty = ref('');
const maxCookingTime = ref('');
const minServings = ref('');

// Get user-specific favorites from the store
const isFavorite = (recipe) => store.getters.isFavorite(recipe.id);
const toggleFavorite = async (recipe) => {
  await store.dispatch('toggleFavorite', recipe);
};

// Search functionality
const searchRecipes = () => {
  // Force a re-computation of filteredRecipes
  const query = searchQuery.value;
  searchQuery.value = '';
  nextTick(() => {
    searchQuery.value = query;
    currentPage.value = 1; // Reset to first page when searching
  });
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
    // Search query matches title, description, or ingredients
    const matchesSearch = !searchQuery.value || 
      recipe.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      recipe.description.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      recipe.ingredients.some(ingredient => 
        ingredient.toLowerCase().includes(searchQuery.value.toLowerCase())
      );
    
    // Cuisine filter
    const matchesCuisine = !selectedCuisine.value || 
      (recipe.cuisines && recipe.cuisines.includes(selectedCuisine.value));
    
    // Difficulty filter
    const matchesDifficulty = !selectedDifficulty.value ||
      recipe.difficulty === selectedDifficulty.value;
    
    // Cooking time filter
    const matchesCookingTime = !maxCookingTime.value ||
      recipe.cooking_time <= parseInt(maxCookingTime.value);
    
    // Servings filter
    const matchesServings = !minServings.value ||
      recipe.servings >= parseInt(minServings.value);
    
    return matchesSearch && matchesCuisine && matchesDifficulty && 
           matchesCookingTime && matchesServings;
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
  selectedDifficulty.value = '';
  maxCookingTime.value = '';
  minServings.value = '';
  currentPage.value = 1;
};

const openRatingModal = (recipe) => {
  recipeToMark.value = recipe;
  selectedRating.value = 1;
  showRatingModal.value = true;
};

const closeRatingModal = () => {
  showRatingModal.value = false;
  recipeToMark.value = null;
};

const markAsCooked = async () => {
  if (!recipeToMark.value) return;
  
  if (!store.getters.isAuthenticated) {
    alert('Please log in to mark recipes as cooked.');
    return;
  }
  
  try {
    const response = await axios.post('/api/v1/cooking-history', {
      recipe_id: recipeToMark.value.id,
      rating: selectedRating.value,
      notes: ''
    });
    
    alert('Recipe added to your cooking history! You can update your rating and add notes later.');
    closeRatingModal();
  } catch (err) {
    console.error('Error marking recipe as cooked:', err);
    alert('Failed to add recipe to cooking history. Please try again.');
  }
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
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <Navigation />
    <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <ChefHat class="h-16 w-16 text-indigo-600 mx-auto mb-4" />
        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">Recipe Collection</h1>
        <p class="mt-3 text-xl text-gray-600">Discover and explore our curated recipes</p>
      </div>

      <!-- Search and Filter Section -->
      <div class="max-w-4xl mx-auto mb-12">
        <div class="bg-white rounded-xl shadow-lg p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Search Input -->
            <div class="col-span-full">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search by title, description, or ingredients..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              />
            </div>
            
            <!-- Cuisine Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Cuisine</label>
              <select
                v-model="selectedCuisine"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              >
                <option value="">All Cuisines</option>
                <option v-for="cuisine in availableCuisines" :key="cuisine" :value="cuisine">
                  {{ cuisine }}
                </option>
              </select>
            </div>

            <!-- Difficulty Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Difficulty</label>
              <select
                v-model="selectedDifficulty"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              >
                <option value="">All Difficulties</option>
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
              </select>
            </div>

            <!-- Cooking Time Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Max Cooking Time (min)</label>
              <input
                v-model="maxCookingTime"
                type="number"
                min="0"
                placeholder="No limit"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              />
            </div>

            <!-- Servings Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Min Servings</label>
              <input
                v-model="minServings"
                type="number"
                min="1"
                placeholder="No minimum"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              />
            </div>

            <!-- Reset Button -->
            <div class="col-span-full flex justify-end">
              <button
                @click="resetFilters"
                class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition duration-200 flex items-center"
              >
                <X class="w-5 h-5 mr-2" />
                <span>Reset Filters</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Recipe Grid -->
      <div v-if="loading" class="flex justify-center my-12">
        <Loader class="w-12 h-12 text-indigo-600 animate-spin" />
      </div>
      <div v-else-if="error" class="text-center py-12">
        <p class="text-red-600">{{ error }}</p>
      </div>
      <div v-else-if="filteredRecipes.length > 0">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="recipe in paginatedRecipes"
            :key="recipe.id"
            class="bg-white rounded-xl shadow-lg overflow-hidden transform hover:scale-105 transition-transform duration-200 cursor-pointer"
            @click="(e) => { selectedRecipe = recipe; showModal = true; }"
          >
            <div class="relative">
              <img
                :src="recipe.image_url || '/default-recipe.jpg'"
                :alt="recipe.title"
                class="w-full h-48 object-cover"
              />
              <div class="absolute top-0 right-0 m-2">
                <button
                  @click.stop="toggleFavorite(recipe)"
                  class="p-2 rounded-full bg-white shadow-md hover:bg-gray-100 transition-colors duration-200"
                  :class="{ 'text-red-500': isFavorite(recipe) }"
                >
                  <Star class="h-6 w-6" :fill="isFavorite(recipe) ? 'currentColor' : 'none'" />
                </button>
              </div>
            </div>
            <div class="p-6">
              <h3 class="text-xl font-bold mb-2 text-gray-800">{{ recipe.title }}</h3>
              <p class="text-gray-600 mb-4 line-clamp-2">{{ recipe.description }}</p>
              <div class="flex justify-between text-sm text-gray-500 mb-4">
                <span class="flex items-center">
                  <Clock class="w-5 h-5 mr-1" />
                  {{ recipe.cooking_time }} min
                </span>
                <span class="flex items-center">
                  <Users class="w-5 h-5 mr-1" />
                  {{ recipe.servings }} servings
                </span>
              </div>
              <div class="flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0 justify-between items-center mt-4">
                <button
                  @click.stop="openRatingModal(recipe)"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                  <ChefHat class="w-5 h-5 mr-2" />
                  Mark as Cooked
                </button>
                <router-link
                  :to="`/recipes/${recipe.id}/similar`"
                  @click.stop
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  <ChefHat class="w-5 h-5 mr-2" />
                  Find Similar
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="mt-8 flex justify-center items-center space-x-2">
          <button
            @click="currentPage = Math.max(1, currentPage - 1)"
            :disabled="currentPage === 1"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Previous
          </button>
          
          <div class="flex items-center space-x-1">
            <button
              v-for="page in pageNumbers"
              :key="page"
              @click="currentPage = page"
              :class="[
                'px-4 py-2 rounded-md',
                currentPage === page
                  ? 'bg-indigo-600 text-white'
                  : 'text-gray-700 hover:bg-gray-100'
              ]"
            >
              {{ page }}
            </button>
          </div>

          <button
            @click="currentPage = Math.min(totalPages, currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Next
          </button>
        </div>
      </div>
      <div v-else class="text-center py-12">
        <p class="text-gray-600">No recipes found. Try adjusting your search criteria.</p>
      </div>
    </div>

    <!-- Rating Modal -->
    <div v-if="showRatingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold text-gray-900">Rate Recipe</h3>
          <button @click="closeRatingModal" class="text-gray-400 hover:text-gray-500">
            <X class="w-6 h-6" />
          </button>
        </div>
        
        <div class="mb-6">
          <p class="text-gray-600 mb-4">How would you rate this recipe?</p>
          <div class="flex justify-center space-x-2">
            <button
              v-for="rating in 5"
              :key="rating"
              @click="selectedRating = rating"
              class="w-12 h-12 rounded-full flex items-center justify-center text-2xl transition-colors duration-200"
              :class="{
                'bg-yellow-400 text-white': selectedRating >= rating,
                'bg-gray-200 text-gray-600 hover:bg-gray-300': selectedRating < rating
              }"
            >
              {{ rating }}
            </button>
          </div>
        </div>
        
        <div class="flex justify-end space-x-3">
          <button
            @click="closeRatingModal"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Cancel
          </button>
          <button
            @click="markAsCooked"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Add to History
          </button>
        </div>
      </div>
    </div>

    <RecipeDetailModal
      v-if="showModal"
      :recipe="selectedRecipe"
      :onClose="() => { showModal = false; selectedRecipe = null; }"
    />
  </div>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>