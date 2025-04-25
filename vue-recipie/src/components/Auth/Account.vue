<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const user = ref(null);
const username = ref('');
const email = ref('');
const currentPassword = ref('');
const newPassword = ref('');
const confirmPassword = ref('');
const error = ref('');
const success = ref('');

onMounted(() => {
  const storedUser = localStorage.getItem('user');
  if (storedUser) {
    user.value = JSON.parse(storedUser);
    username.value = user.value.username;
    email.value = user.value.email;
  }
});

const updateProfile = async () => {
  try {
    const response = await axios.put('/api/user/profile', {
      username: username.value,
      email: email.value
    });
    
    user.value = response.data.user;
    localStorage.setItem('user', JSON.stringify(response.data.user));
    success.value = 'Profile updated successfully';
    error.value = '';
  } catch (err) {
    error.value = err.response?.data?.message || 'An error occurred while updating profile';
    success.value = '';
  }
};

const updatePassword = async () => {
  if (newPassword.value !== confirmPassword.value) {
    error.value = 'New passwords do not match';
    return;
  }

  try {
    await axios.put('/api/user/password', {
      current_password: currentPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: confirmPassword.value
    });
    
    success.value = 'Password updated successfully';
    error.value = '';
    currentPassword.value = '';
    newPassword.value = '';
    confirmPassword.value = '';
  } catch (err) {
    error.value = err.response?.data?.message || 'An error occurred while updating password';
    success.value = '';
  }
};

const logout = () => {
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  router.push('/login');
};
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
      <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
          <h3 class="text-lg leading-6 font-medium text-gray-900">Account Settings</h3>
          
          <div v-if="error" class="mt-4 text-red-500 text-sm">
            {{ error }}
          </div>
          
          <div v-if="success" class="mt-4 text-green-500 text-sm">
            {{ success }}
          </div>

          <!-- Profile Information -->
          <div class="mt-6">
            <h4 class="text-md font-medium text-gray-900">Profile Information</h4>
            <div class="mt-4 grid grid-cols-1 gap-4">
              <div>
                <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                <input
                  id="username"
                  v-model="username"
                  type="text"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input
                  id="email"
                  v-model="email"
                  type="email"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
            </div>
            <div class="mt-4">
              <button
                @click="updateProfile"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Update Profile
              </button>
            </div>
          </div>

          <!-- Change Password -->
          <div class="mt-8">
            <h4 class="text-md font-medium text-gray-900">Change Password</h4>
            <div class="mt-4 grid grid-cols-1 gap-4">
              <div>
                <label for="current-password" class="block text-sm font-medium text-gray-700">Current Password</label>
                <input
                  id="current-password"
                  v-model="currentPassword"
                  type="password"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label for="new-password" class="block text-sm font-medium text-gray-700">New Password</label>
                <input
                  id="new-password"
                  v-model="newPassword"
                  type="password"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
              <div>
                <label for="confirm-password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                <input
                  id="confirm-password"
                  v-model="confirmPassword"
                  type="password"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                />
              </div>
            </div>
            <div class="mt-4">
              <button
                @click="updatePassword"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Update Password
              </button>
            </div>
          </div>

          <!-- Logout -->
          <div class="mt-8">
            <button
              @click="logout"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template> 