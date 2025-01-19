import { createStore } from 'vuex';
import axios from 'axios';

// Add base URL for API requests
axios.defaults.baseURL = 'http://localhost:8000/api';

export default createStore({
  state: {
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    recipes: [],
    loading: false,
    error: null
  },
  
  getters: {
    allRecipes: state => state.recipes,
    isAuthenticated: state => !!state.token,
  },
  
  mutations: {
    SET_LOADING(state, loading) {
      state.loading = loading;
    },
    SET_ERROR(state, error) {
      state.error = error;
    },
    SET_RECIPES(state, recipes) {
      state.recipes = recipes;
    },
    ADD_RECIPE(state, recipe) {
      state.recipes.push(recipe);
    },
    SET_USER(state, user) {
      state.user = user;
    },
    SET_TOKEN(state, token) {
      state.token = token;
    }
  },
  
  actions: {
    // Initialize axios headers
    initializeAuth({ state }) {
      if (state.token) {
        axios.defaults.headers.common['Authorization'] = `Bearer ${state.token}`;
      }
    },

    async fetchRecipes({ commit }) {
      commit('SET_LOADING', true);
      commit('SET_ERROR', null);
      
      try {
        const response = await axios.get('/v1/recipes');
        commit('SET_RECIPES', response.data.recipes);
      } catch (error) {
        const errorMessage = error.response?.data?.message || 'Failed to fetch recipes';
        commit('SET_ERROR', errorMessage);
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async createRecipe({ commit }, recipe) {
      commit('SET_LOADING', true);
      commit('SET_ERROR', null);
      
      try {
        // Ensure cooking_time is a string
        const recipeData = {
          ...recipe,
          cooking_time: String(recipe.cooking_time),
          servings: Number(recipe.servings)
        };
        
        const response = await axios.post('/v1/recipes', recipeData);
        commit('ADD_RECIPE', response.data);
        return response.data;
      } catch (error) {
        const errorMessage = error.response?.data?.message || 'Failed to create recipe';
        commit('SET_ERROR', errorMessage);
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    }
  }
}); 