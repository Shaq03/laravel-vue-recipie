<template>
  <nav class="bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <router-link to="/" class="text-white font-bold text-xl">
              Recipe App
            </router-link>
          </div>
          <div class="hidden md:block">
            <div class="ml-10 flex items-baseline space-x-4">
              <router-link 
                to="/"
                class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium"
                active-class="bg-gray-900 text-white"
              >
                Home
              </router-link>
              
              <router-link 
                to="/recipes"
                class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium"
                active-class="bg-gray-900 text-white"
              >
                Recipes
              </router-link>

              <router-link 
                to="/cooking-history"
                class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium"
                active-class="bg-gray-900 text-white"
              >
                Cooking History
              </router-link>

              <router-link 
                to="/favorites"
                class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium"
                active-class="bg-gray-900 text-white"
              >
                Favorites
              </router-link>
            </div>
          </div>
        </div>

        <!-- User menu -->
        <div class="hidden md:block">
          <div class="ml-4 flex items-center md:ml-6">
            <template v-if="isAuthenticated">
              <div class="relative ml-3">
                <div>
                  <button 
                    @click="toggleUserMenu"
                    class="max-w-xs bg-gray-800 rounded-full flex items-center text-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white"
                  >
                    <span class="sr-only">Open user menu</span>
                    <div class="h-8 w-8 rounded-full bg-gray-500 flex items-center justify-center text-white">
                      {{ userInitials }}
                    </div>
                  </button>
                </div>
                
                <!-- Dropdown menu -->
                <div 
                  v-if="showUserMenu"
                  class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none"
                >
                  <router-link 
                    to="/profile"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    Your Profile
                  </router-link>
                  
                  <button 
                    @click="logout"
                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                  >
                    Sign out
                  </button>
                </div>
              </div>
            </template>
            
            <template v-else>
              <router-link 
                to="/login"
                class="text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium"
              >
                Login
              </router-link>
              <router-link 
                to="/register"
                class="ml-4 text-gray-300 hover:bg-gray-700 hover:text-white px-3 py-2 rounded-md text-sm font-medium"
              >
                Register
              </router-link>
            </template>
          </div>
        </div>

        <!-- Mobile menu button -->
        <div class="-mr-2 flex md:hidden">
          <button 
            @click="toggleMobileMenu"
            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white"
          >
            <span class="sr-only">Open main menu</span>
            <svg 
              class="h-6 w-6" 
              :class="{ 'hidden': showMobileMenu, 'block': !showMobileMenu }"
              xmlns="http://www.w3.org/2000/svg" 
              fill="none" 
              viewBox="0 0 24 24" 
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg 
              class="h-6 w-6" 
              :class="{ 'block': showMobileMenu, 'hidden': !showMobileMenu }"
              xmlns="http://www.w3.org/2000/svg" 
              fill="none" 
              viewBox="0 0 24 24" 
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile menu -->
    <div 
      v-if="showMobileMenu"
      class="md:hidden"
    >
      <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
        <router-link 
          to="/"
          class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium"
          active-class="bg-gray-900 text-white"
        >
          Home
        </router-link>
        
        <router-link 
          to="/recipes"
          class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium"
          active-class="bg-gray-900 text-white"
        >
          Recipes
        </router-link>

        <router-link 
          to="/cooking-history"
          class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium"
          active-class="bg-gray-900 text-white"
        >
          Cooking History
        </router-link>

        <router-link 
          to="/favorites"
          class="text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium"
          active-class="bg-gray-900 text-white"
        >
          Favorites
        </router-link>
      </div>

      <!-- Mobile user menu -->
      <div class="pt-4 pb-3 border-t border-gray-700">
        <template v-if="isAuthenticated">
          <div class="flex items-center px-5">
            <div class="flex-shrink-0">
              <div class="h-10 w-10 rounded-full bg-gray-500 flex items-center justify-center text-white">
                {{ userInitials }}
              </div>
            </div>
            <div class="ml-3">
              <div class="text-base font-medium leading-none text-white">{{ userName }}</div>
              <div class="text-sm font-medium leading-none text-gray-400">{{ userEmail }}</div>
            </div>
          </div>
          <div class="mt-3 px-2 space-y-1">
            <router-link 
              to="/profile"
              class="block px-3 py-2 rounded-md text-base font-medium text-gray-400 hover:text-white hover:bg-gray-700"
            >
              Your Profile
            </router-link>
            
            <button 
              @click="logout"
              class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-400 hover:text-white hover:bg-gray-700"
            >
              Sign out
            </button>
          </div>
        </template>
        
        <template v-else>
          <div class="mt-3 px-2 space-y-1">
            <router-link 
              to="/login"
              class="block px-3 py-2 rounded-md text-base font-medium text-gray-400 hover:text-white hover:bg-gray-700"
            >
              Login
            </router-link>
            <router-link 
              to="/register"
              class="block px-3 py-2 rounded-md text-base font-medium text-gray-400 hover:text-white hover:bg-gray-700"
            >
              Register
            </router-link>
          </div>
        </template>
      </div>
    </div>
  </nav>
</template>

<script>
export default {
  name: 'Navigation',
  
  data() {
    return {
      showUserMenu: false,
      showMobileMenu: false
    };
  },

  computed: {
    isAuthenticated() {
      return !!localStorage.getItem('token');
    },
    
    userName() {
      return localStorage.getItem('user_name') || 'User';
    },
    
    userEmail() {
      return localStorage.getItem('user_email') || '';
    },
    
    userInitials() {
      const name = this.userName;
      return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase();
    }
  },

  methods: {
    toggleUserMenu() {
      this.showUserMenu = !this.showUserMenu;
    },
    
    toggleMobileMenu() {
      this.showMobileMenu = !this.showMobileMenu;
    },
    
    async logout() {
      try {
        await this.$axios.post('/api/v1/logout');
        localStorage.removeItem('token');
        localStorage.removeItem('user_name');
        localStorage.removeItem('user_email');
        this.$router.push('/login');
      } catch (error) {
        console.error('Logout failed:', error);
      }
    }
  },

  mounted() {
    // Close menus when clicking outside
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.relative')) {
        this.showUserMenu = false;
      }
    });
  }
};
</script> 