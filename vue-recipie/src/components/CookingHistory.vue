<script setup>
import { ref, onMounted, computed } from 'vue';
import { useStore } from 'vuex';
import Navigation from './Navigation.vue';
import { History, Clock, ChefHat, Star, Edit2, Trash2, TrendingUp, ChefHat as ChefIcon } from 'lucide-vue-next';
import axios from '../axios';

const store = useStore();
const loading = ref(false);
const error = ref(null);
const cookingHistory = ref([]);
const editingEntry = ref(null);
const recommendations = ref([]);
const showRecommendations = ref(false);
const editForm = ref({
  rating: 0,
  notes: ''
});

const fetchCookingHistory = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    const response = await axios.get('/api/v1/cooking-history');
    cookingHistory.value = response.data;
    
    // If we have cooking history, fetch recommendations
    if (cookingHistory.value.length > 0) {
      await fetchRecommendations();
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to fetch cooking history';
  } finally {
    loading.value = false;
  }
};

const fetchRecommendations = async () => {
  try {
    // Get the most recent recipe from history
    const recentRecipe = cookingHistory.value[0].recipe;
    const response = await axios.get(`/api/v1/ml/recipes/${recentRecipe.id}/similar`);
    recommendations.value = response.data.similar_recipes;
  } catch (err) {
    console.error('Failed to fetch recommendations:', err);
  }
};

const startEditing = (entry) => {
  editingEntry.value = entry;
  editForm.value = {
    rating: entry.rating,
    notes: entry.notes || ''
  };
};

const cancelEditing = () => {
  editingEntry.value = null;
  editForm.value = {
    rating: 0,
    notes: ''
  };
};

const updateEntry = async () => {
  try {
    const response = await axios.put(`/api/v1/cooking-history/${editingEntry.value.id}`, editForm.value);
    const index = cookingHistory.value.findIndex(entry => entry.id === editingEntry.value.id);
    cookingHistory.value[index] = response.data;
    editingEntry.value = null;
    
    // Refresh recommendations after updating
    await fetchRecommendations();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to update cooking history';
  }
};

const deleteEntry = async (entry) => {
  if (!confirm('Are you sure you want to delete this entry?')) return;
  
  try {
    await axios.delete(`/api/v1/cooking-history/${entry.id}`);
    cookingHistory.value = cookingHistory.value.filter(e => e.id !== entry.id);
    
    // Refresh recommendations after deleting
    if (cookingHistory.value.length > 0) {
      await fetchRecommendations();
    } else {
      recommendations.value = [];
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to delete cooking history';
  }
};

const addToHistory = async (recipe) => {
  try {
    const response = await axios.post('/api/v1/cooking-history', {
      recipe_id: recipe.id,
      rating: 0,
      notes: 'Added from recommendations'
    });
    
    cookingHistory.value.unshift(response.data);
    await fetchRecommendations();
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to add recipe to history';
  }
};

// Computed properties for statistics
const averageRating = computed(() => {
  if (cookingHistory.value.length === 0) return 0;
  const sum = cookingHistory.value.reduce((acc, entry) => acc + entry.rating, 0);
  return (sum / cookingHistory.value.length).toFixed(1);
});

const mostCookedRecipe = computed(() => {
  if (cookingHistory.value.length === 0) return null;
  const recipeCounts = {};
  cookingHistory.value.forEach(entry => {
    recipeCounts[entry.recipe.id] = (recipeCounts[entry.recipe.id] || 0) + 1;
  });
  const maxCount = Math.max(...Object.values(recipeCounts));
  const mostCookedId = Object.keys(recipeCounts).find(id => recipeCounts[id] === maxCount);
  return cookingHistory.value.find(entry => entry.recipe.id === parseInt(mostCookedId))?.recipe;
});

onMounted(() => {
  fetchCookingHistory();
});
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Navigation />
    
    <div class="max-w-7xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <History class="h-16 w-16 text-indigo-600 mx-auto mb-4" />
        <h1 class="text-4xl font-extrabold text-gray-900 sm:text-5xl">Your Cooking History</h1>
        <p class="mt-3 text-xl text-gray-600">Track your culinary journey</p>
      </div>

      <!-- Statistics Section -->
      <div v-if="cookingHistory.length > 0" class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <ChefIcon class="h-6 w-6 text-indigo-600" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total Recipes Cooked</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ cookingHistory.length }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <Star class="h-6 w-6 text-yellow-400" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Average Rating</dt>
                  <dd class="text-lg font-medium text-gray-900">{{ averageRating }}/5</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <TrendingUp class="h-6 w-6 text-green-600" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Most Cooked Recipe</dt>
                  <dd class="text-lg font-medium text-gray-900 truncate">
                    {{ mostCookedRecipe?.title || 'None' }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="mt-16 text-center">
        <div class="bg-white rounded-lg shadow-sm p-8 max-w-2xl mx-auto">
          <ChefHat class="h-12 w-12 text-indigo-400 mx-auto mb-4 animate-spin" />
          <p class="text-lg text-gray-600">Loading your cooking history...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="mt-16 text-center">
        <div class="bg-white rounded-lg shadow-sm p-8 max-w-2xl mx-auto">
          <p class="text-lg text-red-600">{{ error }}</p>
          <button 
            @click="fetchCookingHistory"
            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          >
            Try Again
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="cookingHistory.length === 0" class="mt-16 text-center">
        <div class="bg-white rounded-lg shadow-sm p-8 max-w-2xl mx-auto">
          <History class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900">No cooking history yet</h3>
          <p class="mt-2 text-gray-500">
            Start cooking recipes to build your history and get personalized recommendations!
          </p>
          <div class="mt-6 space-x-4">
            <router-link
              to="/recipes"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
            >
              Browse Recipes
            </router-link>
            <router-link
              to="/ai-recommendations"
              class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
              Get AI Recommendations
            </router-link>
          </div>
        </div>
      </div>

      <!-- Cooking History List -->
      <div v-else class="mt-16">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-bold text-gray-900">Your Cooking History</h2>
          <button
            @click="showRecommendations = !showRecommendations"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          >
            {{ showRecommendations ? 'Hide' : 'Show' }} Recommendations
          </button>
        </div>

        <!-- Recommendations Section -->
        <div v-if="showRecommendations && recommendations.length > 0" class="mb-8">
          <h3 class="text-lg font-medium text-gray-900 mb-4">Recommended Recipes</h3>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="rec in recommendations" :key="rec.recipe.id" class="bg-white rounded-lg shadow-sm overflow-hidden">
              <img 
                :src="rec.recipe.image_url" 
                :alt="rec.recipe.title"
                class="w-full h-48 object-cover"
              />
              <div class="p-4">
                <h4 class="text-lg font-medium text-gray-900">{{ rec.recipe.title }}</h4>
                <p class="mt-1 text-sm text-gray-500">
                  Similarity: {{ isNaN(rec.similarity) || rec.similarity === null || rec.similarity === undefined ? 'N/A' : (rec.similarity * 100).toFixed(1) + '%' }}
                </p>
                <div class="mt-4 flex justify-between items-center">
                  <router-link
                    :to="`/recipes/${rec.recipe.id}`"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    View Recipe
                  </router-link>
                  <button
                    @click="addToHistory(rec.recipe)"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                  >
                    Add to History
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-md">
          <ul class="divide-y divide-gray-200">
            <li v-for="entry in cookingHistory" :key="entry.id" class="px-6 py-4 hover:bg-gray-50">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <img 
                      :src="entry.recipe.image_url" 
                      :alt="entry.recipe.title"
                      class="h-12 w-12 rounded-full object-cover"
                    />
                  </div>
                  <div class="ml-4">
                    <h3 class="text-lg font-medium text-gray-900">
                      {{ entry.recipe.title }}
                    </h3>
                    <div class="flex items-center mt-1 text-sm text-gray-500">
                      <Clock class="h-4 w-4 mr-1" />
                      <span>{{ new Date(entry.cooked_at).toLocaleDateString() }}</span>
                      <span class="mx-2">•</span>
                      <div class="flex items-center">
                        <Star class="h-4 w-4 mr-1 text-yellow-400" />
                        <span>{{ entry.rating }}/5</span>
                      </div>
                    </div>
                    <p v-if="entry.notes" class="mt-2 text-sm text-gray-600">
                      {{ entry.notes }}
                    </p>
                  </div>
                </div>
                <div class="flex items-center space-x-4">
                  <router-link
                    :to="`/recipes/${entry.recipe.id}`"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200"
                  >
                    View Recipe
                  </router-link>
                  <button
                    @click="startEditing(entry)"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200"
                  >
                    <Edit2 class="h-4 w-4 mr-1" />
                    Edit
                  </button>
                  <button
                    @click="deleteEntry(entry)"
                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200"
                  >
                    <Trash2 class="h-4 w-4 mr-1" />
                    Delete
                  </button>
                </div>
              </div>

              <!-- Edit Form -->
              <div v-if="editingEntry?.id === entry.id" class="mt-4 pt-4 border-t border-gray-200">
                <form @submit.prevent="updateEntry" class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Rating</label>
                    <input
                      type="number"
                      v-model="editForm.rating"
                      min="1"
                      max="5"
                      step="0.5"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea
                      v-model="editForm.notes"
                      rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    ></textarea>
                  </div>
                  <div class="flex justify-end space-x-3">
                    <button
                      type="button"
                      @click="cancelEditing"
                      class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                      Save Changes
                    </button>
                  </div>
                </form>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template> 