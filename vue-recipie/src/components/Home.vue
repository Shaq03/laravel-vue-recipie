<script setup>
import { ref } from 'vue';
import Navigation from './Navigation.vue';

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
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <Navigation />
    
    <!-- Hero Section -->
    <div class="relative pt-16">
      <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80">
        <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-indigo-200 to-indigo-400 opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"></div>
      </div>
      
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-24 pb-16">
        <div class="text-center">
          <h1 class="text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl md:text-6xl">
            Discover & Share Amazing Recipes
          </h1>
          <p class="mt-6 text-lg leading-8 text-gray-600 max-w-2xl mx-auto">
            Join our community of food lovers and explore thousands of delicious recipes. 
            From quick weekday meals to gourmet dishes, find your next culinary inspiration here.
          </p>
          <div class="mt-10 flex items-center justify-center gap-x-6">
            <button
              disabled
              class="rounded-md bg-gray-400 px-4 sm:px-6 py-2 sm:py-3 text-base sm:text-lg font-semibold text-white shadow-sm opacity-50 cursor-not-allowed"
            >
              Get Started
            </button>
            <router-link
              to="/recipes"
              class="text-base sm:text-lg font-semibold leading-6 text-gray-900 hover:text-indigo-600"
            >
              Browse Recipes
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Features Section -->
    <div class="py-16 sm:py-24">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl lg:text-center">
          <h2 class="text-base font-semibold leading-7 text-indigo-600">Everything You Need</h2>
          <p class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
            Cook with Confidence
          </p>
          <p class="mt-6 text-lg leading-8 text-gray-600">
            Whether you're a beginner or a seasoned chef, our platform provides everything you need to explore, create, and share amazing recipes.
          </p>
        </div>
        <div class="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-none">
          <dl class="grid max-w-xl grid-cols-1 gap-x-8 gap-y-16 lg:max-w-none lg:grid-cols-3">
            <div v-for="feature in features" :key="feature.title" class="flex flex-col">
              <dt class="flex items-center gap-x-3 text-base font-semibold leading-7 text-gray-900">
                <span class="text-3xl">{{ feature.icon }}</span>
                {{ feature.title }}
              </dt>
              <dd class="mt-4 flex flex-auto flex-col text-base leading-7 text-gray-600">
                <p class="flex-auto">{{ feature.description }}</p>
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="bg-white py-16">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-lg bg-indigo-600 shadow-lg">
          <div class="px-6 py-12 sm:px-12 sm:py-16 lg:flex lg:items-center lg:justify-between">
            <div>
              <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                Ready to start cooking?
                <br class="hidden sm:inline">
                Join our community today.
              </h2>
            </div>
            <div class="mt-8 flex lg:mt-0 lg:flex-shrink-0">
              <div class="inline-flex rounded-md shadow">
                <router-link
                  to="/signup"
                  class="inline-flex items-center justify-center rounded-md border border-transparent bg-white px-5 py-3 text-base font-medium text-indigo-600 hover:bg-indigo-50 transition duration-150 ease-in-out"
                >
                  Get Started
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>