<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import Navigation from './Navigation.vue';

const router = useRouter();
const features = [
  {
    title: 'Share Your Recipes',
    description: 'Share your favorite recipes with our community and inspire others to create delicious meals.',
    icon: '👨‍🍳'
  },
  {
    title: 'Discover New Dishes',
    description: 'Explore a vast collection of recipes from around the world and expand your culinary horizons.',
    icon: '🌎'
  },
  {
    title: 'Save Favorites',
    description: 'Save your favorite recipes and create your personal cookbook for easy access.',
    icon: '❤️'
  }
];

const isAuthenticated = computed(() => !!localStorage.getItem('token'));
const goToLogin = () => router.push('/login');
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <Navigation />
    <div class="relative pt-24 pb-20">
      <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80">
        <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-indigo-200 to-indigo-400 opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"></div>
      </div>
      <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-2xl shadow-2xl p-12 text-center">
          <h1 class="text-5xl font-extrabold tracking-tight text-gray-900 mb-6 drop-shadow-sm">
            Discover & Share Amazing Recipes
          </h1>
          <p class="mt-2 text-xl leading-8 text-gray-600 max-w-2xl mx-auto mb-8">
            Join our community of food lovers and explore thousands of delicious recipes. From quick weekday meals to gourmet dishes, find your next culinary inspiration here.
          </p>
          <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
            <button
              v-if="!isAuthenticated"
              @click="goToLogin"
              class="rounded-md bg-indigo-600 px-6 py-3 text-lg font-semibold text-white shadow-md hover:bg-indigo-700 transition"
            >
              Get Started
            </button>
            <router-link
              to="/recipes"
              class="text-lg font-semibold leading-6 text-indigo-700 hover:text-indigo-900 transition"
            >
              Browse Recipes
            </router-link>
          </div>
        </div>
      </div>
    </div>
    <div class="py-20 sm:py-28">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl lg:text-center mb-12">
          <h2 class="text-base font-semibold leading-7 text-indigo-600">Everything You Need</h2>
          <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
            Cook with Confidence
          </p>
          <p class="mt-6 text-lg leading-8 text-gray-600">
            Whether you're a beginner or a seasoned chef, our platform provides everything you need to explore, create, and share amazing recipes.
          </p>
        </div>
        <div class="mx-auto mt-10 max-w-4xl grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="feature in features" :key="feature.title" class="flex flex-col items-center bg-white rounded-xl shadow-md p-8 hover:shadow-xl transition-shadow duration-200">
            <div class="text-5xl mb-4">{{ feature.icon }}</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ feature.title }}</h3>
            <p class="text-gray-600 text-center">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </div>
    <div class="bg-indigo-600 py-16">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-lg shadow-lg flex flex-col md:flex-row md:items-center md:justify-between px-8 py-10">
          <div>
            <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl mb-4 md:mb-0">
              Ready to start cooking?<br class="hidden sm:inline">Join our community today.
            </h2>
          </div>
          <div class="mt-8 md:mt-0 md:ml-8 flex-shrink-0">
            <button
              @click="goToLogin"
              :disabled="isAuthenticated"
              class="inline-flex items-center justify-center rounded-md border border-transparent bg-white px-6 py-3 text-lg font-medium text-indigo-600 hover:bg-indigo-50 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed shadow"
            >
              Get Started
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>