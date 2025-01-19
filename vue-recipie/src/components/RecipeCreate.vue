<script setup>
import { ref } from 'vue';
import Navigation from './Navigation.vue';
import { useRouter } from 'vue-router';
import { useStore } from 'vuex';
import { PlusCircle, MinusCircle, Clock, Users, ChefHat, Image as ImageIcon } from 'lucide-vue-next';

const router = useRouter();
const store = useStore();
const loading = ref(false);
const error = ref(null);

const recipe = ref({
  title: '',
  description: '',
  cooking_time: '',
  servings: '',
  difficulty: 'easy',
  ingredients: [''],
  instructions: [''],
  image_url: ''
});

const addIngredient = () => {
  recipe.value.ingredients.push('');
};

const removeIngredient = (index) => {
  recipe.value.ingredients.splice(index, 1);
};

const addInstruction = () => {
  recipe.value.instructions.push('');
};

const removeInstruction = (index) => {
  recipe.value.instructions.splice(index, 1);
};

const handleSubmit = async () => {
  loading.value = true;
  error.value = null;

  try {
    await store.dispatch('createRecipe', recipe.value);
    router.push('/recipes');
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to create recipe';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-amber-50 to-orange-100">
    <Navigation />
    
    <div class="max-w-4xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <ChefHat class="h-16 w-16 text-orange-500 mx-auto mb-4" />
        <h1 class="text-4xl font-extrabold text-gray-900">Create New Recipe</h1>
        <p class="mt-4 text-xl text-gray-600">Share your culinary masterpiece with the world</p>
      </div>

      <form @submit.prevent="handleSubmit" class="bg-white shadow-xl rounded-lg p-8 space-y-8">
        <!-- Basic Info -->
        <div class="space-y-6">
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Recipe Title</label>
            <input
              type="text"
              id="title"
              v-model="recipe.title"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
            />
          </div>

          <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea
              id="description"
              v-model="recipe.description"
              rows="3"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
            ></textarea>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div>
              <label for="cooking_time" class="block text-sm font-medium text-gray-700">Cooking Time</label>
              <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Clock class="h-5 w-5 text-gray-400" />
                </div>
                <input
                  type="text"
                  id="cooking_time"
                  v-model="recipe.cooking_time"
                  required
                  class="pl-10 block w-full rounded-md border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                  placeholder="e.g., 30 minutes"
                />
              </div>
            </div>

            <div>
              <label for="servings" class="block text-sm font-medium text-gray-700">Servings</label>
              <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Users class="h-5 w-5 text-gray-400" />
                </div>
                <input
                  type="number"
                  id="servings"
                  v-model="recipe.servings"
                  required
                  min="1"
                  class="pl-10 block w-full rounded-md border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                />
              </div>
            </div>

            <div>
              <label for="difficulty" class="block text-sm font-medium text-gray-700">Difficulty</label>
              <select
                id="difficulty"
                v-model="recipe.difficulty"
                required
                class="mt-1 block w-full rounded-md border-gray-300 focus:border-orange-500 focus:ring-orange-500"
              >
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Ingredients -->
        <div>
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Ingredients</h2>
            <button
              type="button"
              @click="addIngredient"
              class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
            >
              <PlusCircle class="h-5 w-5 mr-2" />
              Add Ingredient
            </button>
          </div>
          <div class="space-y-4">
            <div
              v-for="(ingredient, index) in recipe.ingredients"
              :key="index"
              class="flex items-center gap-4"
            >
              <input
                type="text"
                v-model="recipe.ingredients[index]"
                required
                class="block w-full rounded-md border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                :placeholder="`Ingredient ${index + 1}`"
              />
              <button
                type="button"
                @click="removeIngredient(index)"
                class="text-red-600 hover:text-red-700 focus:outline-none"
                :disabled="recipe.ingredients.length === 1"
              >
                <MinusCircle class="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Instructions -->
        <div>
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Instructions</h2>
            <button
              type="button"
              @click="addInstruction"
              class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-orange-700 bg-orange-100 hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
            >
              <PlusCircle class="h-5 w-5 mr-2" />
              Add Step
            </button>
          </div>
          <div class="space-y-4">
            <div
              v-for="(instruction, index) in recipe.instructions"
              :key="index"
              class="flex items-center gap-4"
            >
              <input
                type="text"
                v-model="recipe.instructions[index]"
                required
                class="block w-full rounded-md border-gray-300 focus:border-orange-500 focus:ring-orange-500"
                :placeholder="`Step ${index + 1}`"
              />
              <button
                type="button"
                @click="removeInstruction(index)"
                class="text-red-600 hover:text-red-700 focus:outline-none"
                :disabled="recipe.instructions.length === 1"
              >
                <MinusCircle class="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Image URL -->
        <div>
          <label for="image_url" class="block text-sm font-medium text-gray-700">Image URL (optional)</label>
          <div class="mt-1 relative rounded-md shadow-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <ImageIcon class="h-5 w-5 text-gray-400" />
            </div>
            <input
              type="url"
              id="image_url"
              v-model="recipe.image_url"
              class="pl-10 block w-full rounded-md border-gray-300 focus:border-orange-500 focus:ring-orange-500"
              placeholder="https://example.com/image.jpg"
            />
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <p class="text-sm text-red-700">
                {{ error }}
              </p>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
          <button
            type="submit"
            :disabled="loading"
            class="inline-flex justify-center items-center rounded-md border border-transparent bg-orange-600 px-6 py-3 text-base font-medium text-white shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition duration-150 ease-in-out"
            :class="{ 'opacity-75 cursor-not-allowed': loading }"
          >
            <svg
              v-if="loading"
              class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            {{ loading ? 'Creating Recipe...' : 'Create Recipe' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

body {
  font-family: 'Poppins', sans-serif;
}
</style>