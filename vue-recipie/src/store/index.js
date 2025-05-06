import { createStore } from 'vuex';
import axios from '../axios';

const token = localStorage.getItem('token');
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

export default createStore({
  state: {
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    recipes: [],
    userRecipes: [],
    loading: false,
    error: null,
    favorites: [],
    aiSearchResults: [],
    webSearchResults: [],
  },
  
  getters: {
    allRecipes: state => state.recipes,
    userRecipes: state => state.userRecipes,
    isAuthenticated: state => !!state.token,
    isFavorite: (state) => (recipeId) => {
      return state.favorites.some(fav => fav.id === recipeId);
    },
    allFavorites: (state) => state.favorites,
    currentUser: state => state.user,
    aiSearchResults: state => state.aiSearchResults,
    webSearchResults: state => state.webSearchResults,
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
    SET_USER_RECIPES(state, recipes) {
      state.userRecipes = recipes;
    },
    ADD_RECIPE(state, recipe) {
      state.recipes.push(recipe);
      state.userRecipes.push(recipe);
    },
    SET_USER(state, user) {
      state.user = user;
      localStorage.setItem('user', JSON.stringify(user));
    },
    SET_TOKEN(state, token) {
      state.token = token;
      if (token) {
        localStorage.setItem('token', token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      } else {
        localStorage.removeItem('token');
        delete axios.defaults.headers.common['Authorization'];
      }
    },
    SET_FAVORITES(state, favorites) {
      state.favorites = favorites;
    },
    TOGGLE_FAVORITE(state, recipe) {
      const index = state.favorites.findIndex(fav => fav.id === recipe.id);
      if (index === -1) {
        state.favorites.push(recipe);
      } else {
        state.favorites.splice(index, 1);
      }
    },
    CLEAR_USER_DATA(state) {
      state.user = null;
      state.token = null;
      state.favorites = [];
      state.userRecipes = [];
      localStorage.removeItem('user');
      localStorage.removeItem('token');
      delete axios.defaults.headers.common['Authorization'];
    },
    SET_AI_SEARCH_RESULTS(state, results) {
      state.aiSearchResults = results;
    },
    SET_WEB_SEARCH_RESULTS(state, results) {
      state.webSearchResults = results;
    },
    CLEAR_AI_SEARCH_RESULTS(state) {
      state.aiSearchResults = [];
    },
    CLEAR_WEB_SEARCH_RESULTS(state) {
      state.webSearchResults = [];
    },
  },
  
  actions: {
    initializeAuth({ state, commit }) {
      const token = localStorage.getItem('token');
      const user = localStorage.getItem('user');
      
      if (token && user) {
        commit('SET_TOKEN', token);
        commit('SET_USER', JSON.parse(user));
        return Promise.all([
          this.dispatch('fetchUserRecipes'),
          this.dispatch('fetchFavorites')
        ]);
      }
      return Promise.resolve();
    },

    async register({ commit, dispatch }, credentials) {
      try {
        const response = await axios.post('/api/v1/register', credentials);
        if (!response.data.token || !response.data.user) {
          throw new Error('Invalid response from server');
        }
        commit('SET_USER', response.data.user);
        commit('SET_TOKEN', response.data.token);
        await dispatch('fetchUserRecipes');
        await dispatch('fetchFavorites');
      } catch (error) {
        if (error.response?.status === 422) {
          throw new Error(error.response.data.message || 'Registration failed');
        }
        throw error;
      }
    },

    async login({ commit, dispatch }, credentials) {
      try {
        const response = await axios.post('/api/v1/login', credentials);
        if (!response.data.token || !response.data.user) {
          throw new Error('Invalid response from server');
        }
        commit('SET_USER', response.data.user);
        commit('SET_TOKEN', response.data.token);
        await dispatch('fetchUserRecipes');
        await dispatch('fetchFavorites');
      } catch (error) {
        if (error.response?.status === 422) {
          throw new Error(error.response.data.message || 'Invalid credentials');
        }
        throw error;
      }
    },

    async logout({ commit }) {
      try {
        await axios.post('/api/v1/logout');
      } catch (error) {
        console.error('Logout error:', error);
      } finally {
        commit('CLEAR_USER_DATA');
      }
    },

    async refreshToken({ commit, state }) {
      try {
        const response = await axios.post('/api/v1/refresh-token');
        if (response.data.token) {
          commit('SET_TOKEN', response.data.token);
          return true;
        }
        return false;
      } catch (error) {
        console.error('Token refresh error:', error);
        return false;
      }
    },

    async fetchUserRecipes({ commit }) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.get('/api/v1/user/recipes');
        commit('SET_USER_RECIPES', response.data.recipes);
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Failed to fetch user recipes');
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async fetchFavorites({ commit }) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.get('/api/v1/user/favorites');
        commit('SET_FAVORITES', response.data.favorites);
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Failed to fetch favorites');
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async toggleFavorite({ commit, state, dispatch }, recipe) {
      try {
        if (state.favorites.some(fav => fav.id === recipe.id)) {
          await axios.delete(`/api/v1/user/favorites/${recipe.id}`);
        } else {
          await axios.post('/api/v1/user/favorites', { recipe_id: recipe.id });
        }
        await dispatch('fetchFavorites');
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.message || 'Failed to update favorite');
        throw error;
      }
    },

    async fetchRecipes({ commit }) {
      commit('SET_LOADING', true);
      commit('SET_ERROR', null);
      try {
        const response = await axios.get('/api/v1/recipes');
        commit('SET_RECIPES', response.data.recipes);
      } catch (error) {
        const errorMessage = error.response?.data?.message || 'Failed to fetch recipes';
        commit('SET_ERROR', errorMessage);
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async createRecipe({ commit, dispatch }, recipe) {
      commit('SET_LOADING', true);
      commit('SET_ERROR', null);
      try {
        const recipeData = {
          ...recipe,
          cooking_time: String(recipe.cooking_time),
          servings: Number(recipe.servings)
        };
        const response = await axios.post('/api/v1/recipes', recipeData);
        commit('ADD_RECIPE', response.data);
        await dispatch('fetchUserRecipes');
        return response.data;
      } catch (error) {
        const errorMessage = error.response?.data?.message || 'Failed to create recipe';
        commit('SET_ERROR', errorMessage);
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async searchAIRecipes({ commit }, ingredients) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.post('/api/v1/ai/recommendations', { ingredients });
        commit('SET_AI_SEARCH_RESULTS', response.data.recommendations || []);
        return response.data;
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.error || 'Failed to search recipes');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },

    async searchWebRecipes({ commit }, ingredients) {
      commit('SET_LOADING', true);
      try {
        const response = await axios.post('/api/v1/web/recipes/search', { ingredients });
        commit('SET_WEB_SEARCH_RESULTS', response.data.recipes || []);
        return response.data;
      } catch (error) {
        commit('SET_ERROR', error.response?.data?.error || 'Failed to search recipes');
        throw error;
      } finally {
        commit('SET_LOADING', false);
      }
    },
  }
}); 