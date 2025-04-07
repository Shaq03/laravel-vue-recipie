<script setup>
import { ref } from 'vue';
import { Home, ChefHat, BookOpen, Info, Star, Globe } from 'lucide-vue-next';

const isMenuOpen = ref(false);

const toggleMenu = () => {
  isMenuOpen.value = !isMenuOpen.value;
};
</script>

<template>
  <nav class="bg-white shadow-lg fixed w-full z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <div class="flex">
          <div class="flex-shrink-0 flex items-center">
            <ChefHat class="h-8 w-8 text-indigo-600" />
          </div>
        </div>

        <!-- Desktop Menu -->
        <div class="hidden sm:flex sm:items-center sm:space-x-4">
          <router-link
            to="/"
            class="inline-flex items-center px-1 pt-1 text-sm font-medium"
            :class="[$route.name === 'home' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent']"
          >
            <Home class="h-4 w-4 mr-2" />
            Home
          </router-link>
          <router-link
            to="/about"
            class="inline-flex items-center px-1 pt-1 text-sm font-medium"
            :class="[$route.name === 'about' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent']"
          >
            <Info class="h-4 w-4 mr-2" />
            About
          </router-link>
          <router-link
            to="/recipes"
            class="inline-flex items-center px-1 pt-1 text-sm font-medium"
            :class="[$route.name === 'recipes' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent']"
          >
            <BookOpen class="h-4 w-4 mr-2" />
            Recipes
          </router-link>
          <router-link
            to="/ai-recommendations"
            class="inline-flex items-center px-1 pt-1 text-sm font-medium"
            :class="[$route.name === 'ai-recommendations' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent']"
          >
            <ChefHat class="h-4 w-4 mr-2" />
            AI Recommendations
          </router-link>
          <router-link
            to="/web-recipes"
            class="inline-flex items-center px-1 pt-1 text-sm font-medium"
            :class="[$route.name === 'web-recipes' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent']"
          >
            <Globe class="h-4 w-4 mr-2" />
            Web Recipes
          </router-link>
          <router-link
  to="/favorites"
  class="inline-flex items-center px-1 pt-1 text-sm font-medium"
  :class="[$route.name === 'favorites' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent']"
>
  <Star class="h-4 w-4 mr-2" />
  Favorites
</router-link>
          <router-link
            to="/recipes/create"
            class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700 transition duration-150 ease-in-out"
          >
            Add Recipe
          </router-link>
        </div>

        <!-- Mobile menu button -->
        <div class="flex items-center sm:hidden">
          <button
            @click="toggleMenu"
            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition duration-150 ease-in-out"
          >
            <span class="sr-only">Open main menu</span>
            <svg
              class="h-6 w-6"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                v-if="!isMenuOpen"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
              <path
                v-else
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile menu -->
    <div
      v-show="isMenuOpen"
      class="sm:hidden bg-white border-b border-gray-200"
    >
      <div class="px-2 pt-2 pb-3 space-y-1">
        <router-link
          to="/"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-gray-50 transition duration-150 ease-in-out"
        >
          <Home class="h-4 w-4 mr-2" />
          Home
        </router-link>
        <router-link
          to="/about"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-gray-50 transition duration-150 ease-in-out"
        >
          <Info class="h-4 w-4 mr-2" />
          About
        </router-link>
        <router-link
          to="/recipes"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-gray-50 transition duration-150 ease-in-out"
        >
          <BookOpen class="h-4 w-4 mr-2" />
          Recipes
        </router-link>
        <router-link
          to="/ai-recommendations"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-gray-50 transition duration-150 ease-in-out"
        >
          <ChefHat class="h-4 w-4 mr-2" />
          AI Recommendations
        </router-link>
        <router-link
          to="/web-recipes"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-gray-50 transition duration-150 ease-in-out"
        >
          <Globe class="h-4 w-4 mr-2" />
          Web Recipes
        </router-link>
        <router-link
  to="/favorites"
  class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-indigo-600 hover:bg-gray-50 transition duration-150 ease-in-out"
>
  <Star class="h-4 w-4 mr-2" />
  Favorites
</router-link>
        <router-link
          to="/recipes/create"
          class="block w-full px-3 py-2 rounded-md text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700 transition duration-150 ease-in-out"
        >
          Add Recipe
        </router-link>
      </div>
    </div>
  </nav>
</template>