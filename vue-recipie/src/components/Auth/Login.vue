<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useStore } from 'vuex';

const router = useRouter();
const store = useStore();
const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

const login = async () => {
  if (!email.value || !password.value) {
    error.value = 'Please enter both email and password';
    return;
  }
  loading.value = true;
  error.value = '';
  try {
    await store.dispatch('login', {
      email: email.value,
      password: password.value
    });
    router.push('/');
  } catch (err) {
    if (err.response?.status === 422) {
      error.value = err.response.data.message || 'Invalid email or password';
    } else {
      error.value = err.message || 'An error occurred during login';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-center text-3xl font-extrabold text-gray-900 mb-6">
          Sign in to your account
        </h2>
        <form class="space-y-6" @submit.prevent="login">
          <div class="space-y-4">
            <input
              id="email"
              v-model="email"
              name="email"
              type="email"
              required
              class="appearance-none rounded-md relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Email address"
            />
            <input
              id="password"
              v-model="password"
              name="password"
              type="password"
              required
              class="appearance-none rounded-md relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Password"
            />
          </div>
          <div v-if="error" class="text-red-500 text-sm text-center mt-2">
            {{ error }}
          </div>
          <button
            type="submit"
            :disabled="loading"
            class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed mt-4"
          >
            <span v-if="loading">Signing in...</span>
            <span v-else>Sign in</span>
          </button>
          <div class="text-sm text-center mt-4">
            <router-link to="/register" class="font-medium text-indigo-600 hover:text-indigo-500">
              Don't have an account? Register
            </router-link>
          </div>
        </form>
      </div>
    </div>
  </div>
</template> 