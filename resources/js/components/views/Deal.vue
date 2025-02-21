<template>
  <div v-if="deal">
    <h2>{{ deal.deal.TITLE }}</h2>
    <p>dealId: {{ dealId }}</p>
    <p>Сумма: {{ deal.deal.OPPORTUNITY }}</p>

    <div class="products-container">
    <div v-for="(item, index) in deal.products" :key="item.ID" class="product-card">
      <div class="product-header">ID: {{ item.ID }}</div>
      <div class="product-details">
        <div v-for="(value, key) in item" :key="key" class="detail-row">
          <span class="detail-label">{{ key }}:</span>
          <span class="detail-value">{{ value !== null ? value : 'Нет значения' }}</span>
        </div>
      </div>
    </div>
  </div>
  </div>
  <div v-else>Сделка не найдена</div>
</template>

<script>
import {defineComponent, inject, onMounted, ref} from 'vue';
import apiClient from '../../api';

export default defineComponent({
  name: 'Deal',
  setup() {
    // Получение переменной из provide
    const dealId = inject('dealId');
    const deal = ref(null);
    const error = ref(null);

    const fetchDeal = async () => {
      try {
        const response = await apiClient.get('/deals/'+dealId);
        deal.value = response.data;
      } catch (err) {
        error.value = 'Ошибка при получении данных: ' + err.message;
      }
    };

    onMounted(fetchDeal); // Вызываем fetchData при монтировании компонента
    // Возвращаем переменную для использования в шаблоне
    return { dealId, deal };
  },
});
</script>

<style scoped>
/* Ваши стили для компонента Home */
h1 {
  color: #42b983;
}
.products-container {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1rem;
  padding: 1rem;
}

.product-card {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.product-header {
  font-size: 1.1rem;
  font-weight: bold;
  color: #2c3e50;
  margin-bottom: 0.8rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e9ecef;
}

.product-details {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 0.3rem 0;
}

.detail-label {
  font-weight: 600;
  color: #6c757d;
}

.detail-value {
  color: #495057;
}
</style>
