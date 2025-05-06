<template>
  <div class="add-cooking-history">
    <h3 class="text-xl font-bold mb-4">Add to Cooking History</h3>
    
    <form @submit.prevent="submitHistory" class="space-y-4">
      <div>
        <label class="block text-gray-700 text-sm font-bold mb-2">
          Rating
        </label>
        <div class="flex items-center space-x-2">
          <button 
            v-for="rating in 5" 
            :key="rating"
            type="button"
            @click="formData.rating = rating"
            class="text-2xl focus:outline-none"
            :class="formData.rating >= rating ? 'text-yellow-400' : 'text-gray-300'"
          >
            ★
          </button>
        </div>
      </div>

      <div>
        <label class="block text-gray-700 text-sm font-bold mb-2">
          Notes
        </label>
        <textarea 
          v-model="formData.notes"
          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
          rows="3"
          placeholder="How was your cooking experience? Any modifications or tips for next time?"
        ></textarea>
      </div>

      <div class="flex justify-end">
        <button 
          type="submit"
          class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline"
          :disabled="loading"
        >
          {{ loading ? 'Saving...' : 'Save to History' }}
        </button>
      </div>
    </form>

    <!-- Success Message -->
    <div 
      v-if="success"
      class="mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
    >
      {{ success }}
    </div>

    <!-- Error Message -->
    <div 
      v-if="error"
      class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
    >
      {{ error }}
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'AddCookingHistory',
  
  props: {
    recipeId: {
      type: [Number, String],
      required: true
    }
  },

  data() {
    return {
      formData: {
        rating: 0,
        notes: ''
      },
      loading: false,
      error: null,
      success: null
    };
  },

  methods: {
    async submitHistory() {
      if (this.formData.rating === 0) {
        this.error = 'Please select a rating';
        return;
      }

      try {
        this.loading = true;
        this.error = null;
        this.success = null;

        await axios.post('/api/cooking-history', {
          recipe_id: this.recipeId,
          rating: this.formData.rating,
          notes: this.formData.notes
        });

        this.success = 'Successfully added to cooking history!';
        this.formData = {
          rating: 0,
          notes: ''
        };

        // Emit event to notify parent component
        this.$emit('history-added');
      } catch (err) {
        this.error = 'Failed to add cooking history. Please try again later.';
        console.error('Error adding cooking history:', err);
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
.add-cooking-history {
  max-width: 600px;
  margin: 0 auto;
  padding: 1rem;
}
</style> 