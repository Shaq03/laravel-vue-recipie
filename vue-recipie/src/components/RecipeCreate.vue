<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useStore } from 'vuex';
import Navigation from './Navigation.vue';

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
  <div class="min-h-screen bg-gray-50">
    <Navigation />
    
    <div class="max-w-4xl mx-auto py-24 px-4 sm:px-6 lg:px-8">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900">Create New Recipe</h1>
        <p class="mt-4 text-gray-500">Share your favorite recipe with the community</p>
      </div>

      <form @submit.prevent="handleSubmit" class="mt-12 space-y-8">
        <!-- Basic Info -->
        <div class="space-y-6">
          <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Recipe Title</label>
            <input
              type="text"
              id="title"
              v-model="recipe.title"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea
              id="description"
              v-model="recipe.description"
              rows="3"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            ></textarea>
          </div>

          <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div>
              <label for="cooking_time" class="block text-sm font-medium text-gray-700">Cooking Time (minutes)</label>
              <input
                type="text"
                id="cooking_time"
                v-model="recipe.cooking_time"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="e.g., 30 minutes"
              />
            </div>

            <div>
              <label for="servings" class="block text-sm font-medium text-gray-700">Servings</label>
              <input
                type="number"
                id="servings"
                v-model="recipe.servings"
                required
                min="1"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              />
            </div>

            <div>
              <label for="difficulty" class="block text-sm font-medium text-gray-700">Difficulty</label>
              <select
                id="difficulty"
                v-model="recipe.difficulty"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900">Ingredients</h2>
            <button
              type="button"
              @click="addIngredient"
              class="text-sm text-indigo-600 hover:text-indigo-500"
            >
              Add Ingredient
            </button>
          </div>
          <div class="mt-4 space-y-4">
            <div
              v-for="(ingredient, index) in recipe.ingredients"
              :key="index"
              class="flex gap-4"
            >
              <input
                type="text"
                v-model="recipe.ingredients[index]"
                required
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="e.g., 2 cups flour"
              />
              <button
                type="button"
                @click="removeIngredient(index)"
                class="text-red-600 hover:text-red-500"
                :disabled="recipe.ingredients.length === 1"
              >
                Remove
              </button>
            </div>
          </div>
        </div>

        <!-- Instructions -->
        <div>
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-medium text-gray-900">Instructions</h2>
            <button
              type="button"
              @click="addInstruction"
              class="text-sm text-indigo-600 hover:text-indigo-500"
            >
              Add Step
            </button>
          </div>
          <div class="mt-4 space-y-4">
            <div
              v-for="(instruction, index) in recipe.instructions"
              :key="index"
              class="flex gap-4"
            >
              <input
                type="text"
                v-model="recipe.instructions[index]"
                required
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                :placeholder="`Step ${index + 1}`"
              />
              <button
                type="button"
                @click="removeInstruction(index)"
                class="text-red-600 hover:text-red-500"
                :disabled="recipe.instructions.length === 1"
              >
                Remove
              </button>
            </div>
          </div>
        </div>

        <!-- Image URL -->
        <div>
          <label for="image_url" class="block text-sm font-medium text-gray-700">Image URL (optional)</label>
          <input
            type="url"
            id="image_url"
            v-model="recipe.image_url"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="https://example.com/image.jpg"
          />
        </div>

        <!-- Error Message -->
        <div v-if="error" class="text-red-600 text-sm">
          {{ error }}
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end">
          <button
            type="submit"
            :disabled="loading"
            class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
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
            Create Recipe
          </button>
        </div>
      </form>
    </div>
  </div>
</template> 