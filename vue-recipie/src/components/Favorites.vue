<script setup>
import { computed, onMounted, ref } from 'vue';
import { useStore } from 'vuex';
import Navigation from './Navigation.vue';
import { Star, Clock, Users, ChefHat, ExternalLink, X, History, Edit2, Trash2 } from 'lucide-vue-next';
import axios from '../axios';

const store = useStore();
const favorites = computed(() => store.getters.allFavorites);
const loading = ref(false);
const error = ref(null);
const showRatingModal = ref(false);
const selectedRating = ref(1);
const recipeToMark = ref(null);
const cookingHistory = ref([]);
const editingEntry = ref(null);
const editForm = ref({
  rating: 0,
  notes: ''
});

const removeFavorite = (recipe) => {
  store.dispatch('toggleFavorite', recipe);
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
    await fetchCookingHistory(); // Refresh cooking history after adding
  } catch (err) {
    console.error('Error marking recipe as cooked:', err);
    alert('Failed to add recipe to cooking history. Please try again.');
  }
};

const fetchCookingHistory = async () => {
  try {
    const response = await axios.get('/api/v1/cooking-history');
    cookingHistory.value = response.data;
  } catch (err) {
    console.error('Error fetching cooking history:', err);
  }
};

const isRecipeCooked = (recipeId) => {
  return cookingHistory.value.some(entry => entry.recipe_id === recipeId);
};

const getRecipeRating = (recipeId) => {
  const entry = cookingHistory.value.find(entry => entry.recipe_id === recipeId);
  return entry ? entry.rating : null;
};

const getRecipeNotes = (recipeId) => {
  const entry = cookingHistory.value.find(entry => entry.recipe_id === recipeId);
  return entry ? entry.notes : '';
};

const startEdit = (recipe) => {
  const entry = cookingHistory.value.find(entry => entry.recipe_id === recipe.id);
  if (entry) {
    editingEntry.value = entry;
    editForm.value = {
      rating: entry.rating,
      notes: entry.notes
    };
  }
};

const cancelEdit = () => {
  editingEntry.value = null;
  editForm.value = {
    rating: 0,
    notes: ''
  };
};

const updateEntry = async () => {
  if (!editingEntry.value) return;
  
  try {
    await axios.put(`/api/v1/cooking-history/${editingEntry.value.id}`, editForm.value);
    await fetchCookingHistory();
    editingEntry.value = null;
    alert('Cooking history updated successfully!');
  } catch (err) {
    console.error('Error updating cooking history:', err);
    alert('Failed to update cooking history. Please try again.');
  }
};

const deleteEntry = async (recipeId) => {
  const entry = cookingHistory.value.find(entry => entry.recipe_id === recipeId);
  if (!entry) return;
  
  if (!confirm('Are you sure you want to remove this recipe from your cooking history?')) return;
  
  try {
    await axios.delete(`/api/v1/cooking-history/${entry.id}`);
    await fetchCookingHistory();
    alert('Recipe removed from cooking history!');
  } catch (err) {
    console.error('Error deleting cooking history entry:', err);
    alert('Failed to remove recipe from cooking history. Please try again.');
  }
};

// Load favorites from localStorage on component mount
onMounted(async () => {
  store.dispatch('fetchFavorites');
  await fetchCookingHistory();
});
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
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
            <div v-if="isRecipeCooked(recipe.id)" class="absolute top-4 left-4">
              <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium flex items-center">
                <History class="w-4 h-4 mr-1" />
                Cooked
              </div>
            </div>
          </div>
          
          <div class="p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ recipe.title }}</h2>
            <p class="text-gray-600 mb-4 line-clamp-2">{{ recipe.description }}</p>
            
            <!-- Cooking History Section -->
            <div v-if="isRecipeCooked(recipe.id)" class="mb-4 p-4 bg-gray-50 rounded-lg">
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center">
                  <span class="text-sm font-medium text-gray-700">Your Rating:</span>
                  <div class="ml-2 flex">
                    <Star
                      v-for="n in 5"
                      :key="n"
                      class="w-4 h-4"
                      :class="n <= getRecipeRating(recipe.id) ? 'text-yellow-400 fill-current' : 'text-gray-300'"
                    />
                  </div>
                </div>
                <div class="flex space-x-2">
                  <button
                    @click="startEdit(recipe)"
                    class="text-indigo-600 hover:text-indigo-800"
                  >
                    <Edit2 class="w-4 h-4" />
                  </button>
                  <button
                    @click="deleteEntry(recipe.id)"
                    class="text-red-600 hover:text-red-800"
                  >
                    <Trash2 class="w-4 h-4" />
                  </button>
                </div>
              </div>
              <p v-if="getRecipeNotes(recipe.id)" class="text-sm text-gray-600">
                {{ getRecipeNotes(recipe.id) }}
              </p>
            </div>
            
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
            <div class="mt-6 flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0 justify-between items-center">
              <button
                v-if="!isRecipeCooked(recipe.id)"
                @click="openRatingModal(recipe)"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                <ChefHat class="w-5 h-5 mr-2" />
                Mark as Cooked
              </button>
              <router-link
                :to="`/recipes/${recipe.id}/similar`"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                <ChefHat class="w-5 h-5 mr-2" />
                Find Similar Recipes
              </router-link>
            </div>
          </div>
        </div>
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

    <!-- Edit Rating Modal -->
    <div v-if="editingEntry" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-bold text-gray-900">Edit Rating</h3>
          <button @click="cancelEdit" class="text-gray-400 hover:text-gray-500">
            <X class="w-6 h-6" />
          </button>
        </div>
        
        <div class="mb-6">
          <p class="text-gray-600 mb-4">Update your rating:</p>
          <div class="flex justify-center space-x-2">
            <button
              v-for="rating in 5"
              :key="rating"
              @click="editForm.rating = rating"
              class="w-12 h-12 rounded-full flex items-center justify-center text-2xl transition-colors duration-200"
              :class="{
                'bg-yellow-400 text-white': editForm.rating >= rating,
                'bg-gray-200 text-gray-600 hover:bg-gray-300': editForm.rating < rating
              }"
            >
              {{ rating }}
            </button>
          </div>
          
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea
              v-model="editForm.notes"
              rows="3"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Add any notes about your cooking experience..."
            ></textarea>
          </div>
        </div>
        
        <div class="flex justify-end space-x-3">
          <button
            @click="cancelEdit"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Cancel
          </button>
          <button
            @click="updateEntry"
            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            Save Changes
          </button>
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