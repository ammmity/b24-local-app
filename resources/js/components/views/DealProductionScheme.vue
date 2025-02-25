<template>
  <div v-if="deal && !hasErrors">
    <div v-for="(tableGroup, operationType) in groupedTableData" :key="operationType" class="table-group">
      <h3 class="operation-type-title">{{ operationType }}</h3>
      <el-table
        :data="tableGroup"
        style="width: 100%;"
        border
        class="custom-table"
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
          prop="quantity"
          label="Количество"
          width="120"
        />
        <el-table-column
          label="Исполнитель"
          width="200"
        >
          <template #default="{ row }">
            <el-select
              v-model="row.executor"
              filterable
              remote
              placeholder="Выберите исполнителя"
              :remote-method="remoteSearch"
              :loading="loading"
              size="small"
            >
              <el-option
                v-for="item in executors"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
          </template>
        </el-table-column>
        <el-table-column
          label="Передать"
          width="200"
        >
          <template #default="{ row }">
            <el-select
              v-model="row.transfer"
              filterable
              remote
              placeholder="Новый исполнитель"
              :remote-method="remoteSearch"
              :loading="loading"
              size="small"
            >
              <el-option
                v-for="item in executors"
                :key="item.value"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <h2>{{ deal.TITLE }}</h2>
    <p>dealId: {{ dealId }}</p>
    <p>Сумма: {{ deal.OPPORTUNITY }}</p>

    <div class="products-container">
    <div v-for="(item, index) in deal.dealProducts" :key="index" class="product-card">
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
  <div v-else>
    <div v-if="errors.length > 0" class="error-container">
      <div v-for="(errorMsg, index) in errors" :key="index" class="error-message">
        {{ errorMsg }}
      </div>
    </div>
  </div>
</template>

<script>
import {defineComponent, inject, watch, onMounted, ref, computed} from 'vue';
import apiClient from '../../api';

export default defineComponent({
  name: 'DealProductionScheme',
  setup() {
    const dealId = inject('dealId');
    const deal = ref(null);
    const operationTypes = ref(null);
    const errors = ref([]);
    const loading = ref(false);
    const executors = ref([]);

    const hasErrors = computed(() => errors.value.length > 0);

    // Вычисляемое свойство для данных таблицы
    const tableData = computed(() => {
      if (!deal.value || !deal.value.dealProducts) return [];

      const rows = [];

      deal.value.dealProducts.forEach(product => {
        if (!product.parts) return;

        const productName = product.product?.name || 'Неизвестный продукт';
        const quantity = product.QUANTITY || '';

        product.parts.forEach(part => {
          if (!part.production_stages) return;

          const partName = part.name || 'Неизвестная деталь';

          part.production_stages.forEach(stage => {
            rows.push({
              stage: stage.stage || '',
              product: productName,
              part: partName,
              operation: stage.operation_type?.name || 'Неизвестная операция',
              machine: stage.operation_type?.machine || 'Неизвестная операция',
              quantity: quantity,
              executor: '',
              transfer: ''
            });
          });
        });
      });

      return rows;
    });

    // Группировка данных таблицы по типу операции
    const groupedTableData = computed(() => {
      const grouped = {};

      tableData.value.forEach(row => {
        const operationType = row.machine || 'Без операции';

        if (!grouped[operationType]) {
          grouped[operationType] = [];
        }

        grouped[operationType].push(row);
      });

      return grouped;
    });

    const fetchDealData = async (id) => {
      try {
        const response = await apiClient.get(`/deals/${id}`);
        if (response.data.error) {
          errors.value.push(response.data.error.message);
          return; // Прерываем выполнение функции
        }
        deal.value = response.data;
        checkDealProducts();
      } catch (error) {
        console.error('Error fetching deal:', error);
        errors.value.push(error.message);
      }
    };

    const fetchOperationTypesData = async () => {
      try {
        const response = await apiClient.get(`/operation-types`);
        operationTypes.value = response.data;
      } catch (error) {
        console.error('Error fetching operation types:', error);
        errors.value.push('Ошибка при получении типов операций: ' + error.message);
      }
    };

    const fetchUsers = async () => {
      try {
        loading.value = true;
        const response = await apiClient.get('/users');
        executors.value = response.data.map(user => ({
            value: user.id.toString(),
            label: `${user.last_name} ${user.name}`
          }));
      } catch (error) {
        console.error('Error fetching users:', error);
        errors.value.push('Ошибка при получении списка пользователей: ' + error.message);
      } finally {
        loading.value = false;
      }
    };

    // Поиск исполнителей с использованием API
    const remoteSearch = async (query) => {
      try {
        loading.value = true;

        // Добавляем debounce для задержки запроса
        if (window.searchTimeout) {
          clearTimeout(window.searchTimeout);
        }

        window.searchTimeout = setTimeout(async () => {
          if (query) {
            const response = await apiClient.get('/users', { params: { find: query } });
            executors.value = response.data.map(user => ({
              value: user.id.toString(),
              label: `${user.last_name} ${user.name}`
            }));
          } else {
            // Если запрос пустой, загружаем всех пользователей
            await fetchUsers();
          }
          loading.value = false;
        }, 500); // Задержка в 500 мс (пол секунды)

      } catch (error) {
        console.error('Error searching users:', error);
        loading.value = false;
      }
    };

    const checkDealProducts = () => {
      // Очищаем массив ошибок перед проверками
      errors.value = [];

      if (deal.value && (!deal.value.dealProducts || deal.value.dealProducts.length === 0)) {
        errors.value.push('У этой сделки нет привязанных продуктов');
      }

      // Проверка наличия свойства parts в каждом элементе dealProducts
      if (deal.value && deal.value.dealProducts && deal.value.dealProducts.length > 0) {
        const productsWithoutParts = deal.value.dealProducts.filter(
          product => !product.parts || product.parts.length === 0
        );

        if (productsWithoutParts.length > 0) {
          if (productsWithoutParts.length === deal.value.dealProducts.length) {
            errors.value.push('У всех продуктов отсутствуют детали (parts)');
          } else {
            // Добавляем отдельное сообщение для каждого продукта без деталей
            productsWithoutParts.forEach(product => {
              const productName = product.product.name || product.product.title || product.product.id || 'Неизвестный продукт';
              errors.value.push(`У продукта "${productName}" отсутствуют детали`);
            });
          }
        }
      }

      // Проверка наличия production_stages в каждой детали (parts) продуктов
      if (deal.value && deal.value.dealProducts && deal.value.dealProducts.length > 0) {
        deal.value.dealProducts.forEach(product => {
          if (product.parts && product.parts.length > 0) {
            const partsWithoutStages = product.parts.filter(
              part => !part.production_stages || part.production_stages.length === 0
            );

            if (partsWithoutStages.length > 0) {
              const productName = product.product.name || product.product.title || product.product.id || 'Неизвестный продукт';

              partsWithoutStages.forEach(part => {
                const partName = part.name || part.title || part.id || 'Неизвестная деталь';
                errors.value.push(`У детали "${partName}" продукта "${productName}" отсутствуют этапы производства`);
              });
            }
          }
        });
      }
    };

    // Следим за изменением dealId
    watch(() => dealId, (newDealId) => {
      if (newDealId) {
        fetchDealData(newDealId);
      }
    }, { immediate: true });

    // Следим за изменениями в deal
    watch(() => deal.value, () => {
      checkDealProducts();
    });

    onMounted(() => {
      if (dealId) {
        // fetchDealData(dealId);
        fetchOperationTypesData();
        fetchUsers(); // Загружаем список пользователей при монтировании компонента
      }
    });

    return {
      dealId,
      deal,
      operationTypes,
      errors,
      hasErrors,
      tableData,
      groupedTableData,
      executors,
      loading,
      remoteSearch
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

.error-container {
  margin-bottom: 20px;
}

.error-message {
  color: #f56c6c;
  padding: 12px;
  text-align: center;
  font-size: 16px;
  background-color: #fef0f0;
  border-radius: 4px;
  margin-bottom: 10px;
}

.table-group {
  margin-bottom: 0;
  padding: 15px;
  background-color: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.05);
}

.operation-type-title {
  text-align: left;
  font-weight: bold;
  margin-bottom: 15px;
  font-size: 20px;
  color: #303133;
  padding-bottom: 10px;
}

.custom-table {
  border: 1px solid #EBEEF5;
  border-radius: 4px;
  overflow: hidden;
}

:deep(.el-table__header-wrapper th) {
  background-color: #f5f7fa;
  color: #606266;
  font-weight: bold;
}

:deep(.el-table__row:hover) {
  background-color: #f0f9ff !important;
}
</style>
