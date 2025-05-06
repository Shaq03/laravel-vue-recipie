<template>
  <div class="cooking-history-page">
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-8">Cooking History</h1>
      
      <CookingHistory @history-updated="refreshHistory" />
    </div>
  </div>
</template>

<script>
import CookingHistory from '../components/CookingHistory.vue';

export default {
  name: 'CookingHistoryPage',
  
  components: {
    CookingHistory
  },

  methods: {
    refreshHistory() {
      // This method will be called when history is updated
      // The CookingHistory component will handle the refresh internally
    }
  }
};
</script>

<style scoped>
.cooking-history-page {
  min-height: 100vh;
  background-color: #f7f7f7;
}
</style> 