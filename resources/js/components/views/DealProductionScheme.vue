<template>
  <div v-if="deal">
    <el-table
      :data="tableData"
      style="width: 100%; margin-bottom: 20px;"
    >
      <el-table-column
        prop="stage"
        label="Этап"
        width="80"
      />
      <el-table-column
        prop="product"
        label="Продукт"
      />
      <el-table-column
        prop="part"
        label="Деталь"
      />
      <el-table-column
        prop="operation"
        label="Операция"
      />
      <el-table-column
        prop="quantity"
        label="Количество"
        width="120"
      />
      <el-table-column
        prop="transfer"
        label="Передать"
        width="120"
      >
        <template #default="{ row }">
          <el-input v-model="row.transfer" size="small" />
        </template>
      </el-table-column>
    </el-table>

    <h2>{{ deal.deal.TITLE }}</h2>
    <p>dealId: {{ dealId }}</p>
    <p>Сумма: {{ deal.deal.OPPORTUNITY }}</p>

    <div class="products-container">
    <div v-for="(item, index) in deal.products" :key="index" class="product-card">
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
import {defineComponent, inject, watch, onMounted, ref} from 'vue';
import apiClient from '../../api';

export default defineComponent({
  name: 'DealProductionScheme',
  setup() {
    const dealId = inject('dealId');
    const deal = ref(null);
    const error = ref(null);

    // Тестовые данные для таблицы
    const tableData = ref([
      {
        stage: 1,
        product: 'Продукт 1',
        part: 'Деталь 1',
        operation: 'Операция 1',
        quantity: '10 шт',
        transfer: ''
      },
      {
        stage: 2,
        product: 'Продукт 2',
        part: 'Деталь 2',
        operation: 'Операция 2',
        quantity: '5 шт',
        transfer: ''
      }
    ]);

    const fetchDealData = async (id) => {
      try {
        const response = await apiClient.get(`/deals/${id}`);
        console.log('Deal data:', response.data);
        deal.value = response.data;
      } catch (error) {
        console.error('Error fetching deal:', error);
        error.value = 'Ошибка при получении данных: ' + error.message;
      }
    };

    // Следим за изменением dealId
    watch(() => dealId, (newDealId) => {
      if (newDealId) {
        fetchDealData(newDealId);
      }
    }, { immediate: true });

    // Также загружаем данные при монтировании компонента
    // onMounted(() => {
    //   if (dealId) {
    //     fetchDealData(dealId);
    //   }
    // });

    return {
      dealId,
      deal,
      error,
      tableData
    };
  },
});
</script>

<style scoped>
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

.el-table {
  margin-bottom: 20px;
}
</style>
