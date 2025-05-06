<template>
  <div class="cooking-history">
    <h2 class="text-2xl font-bold mb-4">My Cooking History</h2>
    
    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center items-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
    </div>

    <!-- Error state -->
    <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
      {{ error }}
    </div>

    <!-- Empty state -->
    <div v-else-if="!history.length" class="text-center py-8 text-gray-500">
      <p>You haven't cooked any recipes yet.</p>
      <p class="mt-2">Start cooking to build your history!</p>
    </div>

    <!-- History list -->
    <div v-else class="space-y-4">
      <div v-for="entry in history" :key="entry.id" class="bg-white rounded-lg shadow p-4">
        <div class="flex justify-between items-start">
          <div>
            <h3 class="text-lg font-semibold">{{ entry.recipe.title }}</h3>
            <p class="text-sm text-gray-500">
              Cooked on {{ formatDate(entry.cooked_at) }}
            </p>
          </div>
          <div class="flex items-center">
            <div v-if="entry.rating" class="flex items-center">
              <span class="text-yellow-400">★</span>
              <span class="ml-1">{{ entry.rating }}/5</span>
            </div>
          </div>
        </div>
        
        <div v-if="entry.notes" class="mt-2 text-gray-600">
          <p class="text-sm">{{ entry.notes }}</p>
        </div>

        <div class="mt-3 flex justify-end space-x-2">
          <button 
            @click="editEntry(entry)"
            class="text-blue-600 hover:text-blue-800 text-sm"
          >
            Edit
          </button>
          <button 
            @click="deleteEntry(entry.id)"
            class="text-red-600 hover:text-red-800 text-sm"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div v-if="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold mb-4">Edit Cooking History</h3>
        <form @submit.prevent="updateEntry">
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
              Rating
            </label>
            <input 
              v-model="editingEntry.rating"
              type="number"
              min="1"
              max="5"
              step="0.5"
              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            >
          </div>
          
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
              Notes
            </label>
            <textarea 
              v-model="editingEntry.notes"
              class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
              rows="3"
            ></textarea>
          </div>

          <div class="flex justify-end space-x-2">
            <button 
              type="button"
              @click="showEditModal = false"
              class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
            >
              Cancel
            </button>
            <button 
              type="submit"
              class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
            >
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'CookingHistory',
  
  data() {
    return {
      history: [],
      loading: true,
      error: null,
      showEditModal: false,
      editingEntry: null
    };
  },

  created() {
    this.fetchHistory();
  },

  methods: {
    async fetchHistory() {
      try {
        this.loading = true;
        const response = await axios.get('/api/cooking-history');
        this.history = response.data;
      } catch (err) {
        this.error = 'Failed to load cooking history. Please try again later.';
        console.error('Error fetching cooking history:', err);
      } finally {
        this.loading = false;
      }
    },

    formatDate(date) {
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    },

    editEntry(entry) {
      this.editingEntry = { ...entry };
      this.showEditModal = true;
    },

    async updateEntry() {
      try {
        await axios.put(`/api/cooking-history/${this.editingEntry.id}`, {
          rating: this.editingEntry.rating,
          notes: this.editingEntry.notes
        });

        // Update the local history array
        const index = this.history.findIndex(h => h.id === this.editingEntry.id);
        if (index !== -1) {
          this.history[index] = { ...this.editingEntry };
        }

        this.showEditModal = false;
        this.editingEntry = null;
      } catch (err) {
        this.error = 'Failed to update cooking history. Please try again later.';
        console.error('Error updating cooking history:', err);
      }
    },

    async deleteEntry(id) {
      if (!confirm('Are you sure you want to delete this entry?')) {
        return;
      }

      try {
        await axios.delete(`/api/cooking-history/${id}`);
        this.history = this.history.filter(entry => entry.id !== id);
      } catch (err) {
        this.error = 'Failed to delete cooking history entry. Please try again later.';
        console.error('Error deleting cooking history:', err);
      }
    }
  }
};
</script>

<style scoped>
.cooking-history {
  max-width: 800px;
  margin: 0 auto;
  padding: 1rem;
}
</style> 