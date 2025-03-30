<template>
  <div v-if="deal && !hasErrors">
    <h3>Схема производства</h3>

    <el-card class="deal-info-card">
      <el-descriptions :column="2" border>
        <el-descriptions-item label="Статус">
          <el-tag :type="getStatusType">
            {{ getStatusText }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="Приоритет">
          <el-tag :type="getTypeType">
            {{ getTypeText }}
          </el-tag>
        </el-descriptions-item>
      </el-descriptions>

      <div class="actions-panel">

        <el-button
          type="warning"
          :loading="isLoadingStatuses"
          @click="loadOperationStatuses"
          :disabled="productionScheme?.status === 'done' || productionScheme?.status !== 'progress'"
          plain
        >
          Получить актуальные статусы операций
        </el-button>

        <el-button
          type="primary"
          :loading="isSaving"
          @click="saveProductionScheme"
          :disabled="hasErrors || !tableData.length || productionScheme?.status === 'done'"
        >
          {{ productionScheme ? 'Обновить производство' : 'Создать производство' }}
        </el-button>

        <el-button
          v-if="productionScheme && productionScheme.status === 'prepare'"
          type="success"
          :loading="isStarting"
          @click="startProduction"
          :disabled="hasErrors || !tableData.length || productionScheme?.status === 'done'"
        >
          Запустить производство
        </el-button>
      </div>
    </el-card>

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

        />
        <el-table-column
          prop="product"
          label="Продукт"

        />
        <el-table-column
          prop="operation"
          label="Операция"

        />
        <el-table-column
          prop="part"
          label="Деталь"

        />
        <el-table-column
          prop="quantity"
          label="Кол-во"

        />
        <el-table-column
          v-if="productionScheme?.status === 'progress'"
          prop="status"
          label="Статус"
        >
          <template #default="{ row }">
            <span v-if="row.status !== '0'" :class="getStatusClass(row.status)">{{ row.status }}</span>
            <span v-else></span>
          </template>
        </el-table-column>
        <el-table-column
          label="Исполнитель"
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
              :disabled="productionScheme?.status === 'done' || productionScheme?.status === 'progress'"
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
              :disabled="!productionScheme || productionScheme?.status === 'done'"
              @change="handleTransferChange(row)"
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
import { ElMessage } from 'element-plus';

export default defineComponent({
  name: 'DealProductionScheme',
  setup() {
    const dealId = inject('dealId');
    const deal = ref(null);
    const operationTypes = ref(null);
    const errors = ref([]);
    const loading = ref(false);
    const executors = ref([]);
    const productionScheme = ref(null);
    const isSaving = ref(false);
    const isStarting = ref(false);
    const isLoadingStatuses = ref(false);
    const systemUserId = ref(null);
    const hasErrors = computed(() => errors.value.length > 0);

    // Вычисляемое свойство для данных таблицы
    const tableData = computed(() => {
      if (!deal.value || !deal.value.dealProducts) return [];

      const rows = [];

      deal.value.dealProducts.forEach(product => {
        if (!product.parts) return;

        product.parts.forEach(part => {
          if (!part.production_stages) return;

          part.production_stages.forEach(stage => {
            const schemeStage = productionScheme.value?.stages?.find(s =>
              Number(s.product_part_id) === Number(part.id) &&
              Number(s.operation_type_id) === Number(stage.operation_type_id)
            );

            rows.push({
              stage: stage.stage || '',
              product: `${product.product?.name || 'Неизвестный продукт'} (${product.QUANTITY || 1}шт.)`,
              part: part.name || 'Неизвестная деталь',
              part_id: part.id,
              operation: stage.operation_type?.name || 'Неизвестная операция',
              operation_type_id: stage.operation_type_id,
              machine: stage.operation_type?.machine || 'Неизвестная операция',
              quantity: part.quantity || 0,
              executor: schemeStage?.executor_id?.toString() || '',
              transfer: schemeStage?.transfer_to_id?.toString() || '',
              id: stage.id,
              status: schemeStage?.status || ''
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

      // Сортируем каждую группу по полю stage
      Object.keys(grouped).forEach(key => {
        grouped[key].sort((a, b) => a.stage - b.stage);
      });

      return grouped;
    });

    const fetchDealData = async (id) => {
      try {
        const response = await apiClient.get(`/deals/${id}`);
        if (response.data.error) {
          errors.value.push(response.data.error);
          return; // Прерываем выполнение функции
        }
        deal.value = response.data;
        checkDealProducts();
      } catch (error) {
        console.error('Error fetching deal:', error);
        errors.value.push(error.message);
      }
    };

    const fetchSystemUserId = async () => {
      try {
        const response = await apiClient.get('/system-user');
        systemUserId.value = response.data;
      } catch (error) {
        console.error('Error fetching system user id:', error);
        errors.value.push('Ошибка при получении id системного пользователя: ' + error.message);
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

      // Проверка наличия production_stages и количества в каждой детали (parts) продуктов
      if (deal.value && deal.value.dealProducts && deal.value.dealProducts.length > 0) {
        deal.value.dealProducts.forEach(product => {
          if (product.parts && product.parts.length > 0) {
            const productName = product.product.name || product.product.title || product.product.id || 'Неизвестный продукт';
            
            product.parts.forEach(part => {
              const partName = part.name || part.title || part.id || 'Неизвестная деталь';
              
              // Проверка количества
              if (part.quantity === 0) {
                errors.value.push(`Для детали "${partName}" продукта "${productName}" не установлено количество`);
              }
              
              // Проверка наличия этапов производства
              if (!part.production_stages || part.production_stages.length === 0) {
                errors.value.push(`У детали "${partName}" продукта "${productName}" отсутствуют этапы производства`);
              }
            });
          }
        });
      }
    };

    const fetchProductionScheme = async (id) => {
      try {
        const response = await apiClient.get(`/production-schemes/${id}`);
        productionScheme.value = response.data;
      } catch (error) {
        console.error('Error fetching production scheme:', error);
        // Не добавляем ошибку в errors.value, так как отсутствие схемы - это нормальное состояние
      }
    };

    const prepareStagesData = () => {
      return tableData.value.map(row => ({
        product_part_id: row.part_id,
        operation_type_id: row.operation_type_id,
        stage_number: row.stage,
        quantity: row.quantity,
        status: row.status,
        executor_id: row.transfer ? parseInt(row.transfer) : (row.executor ? parseInt(row.executor) : null),
        transfer_to_id: row.transfer ? parseInt(row.transfer) : null
      }));
    };

    const getStatusType = computed(() => {
      if (!productionScheme.value) return 'info';
      switch (productionScheme.value.status) {
        case 'prepare': return 'info';
        case 'progress': return 'primary';
        case 'done': return 'success';
        default: return 'info';
      }
    });

    const getStatusText = computed(() => {
      if (!productionScheme.value) return 'Ожидает настройки';
      switch (productionScheme.value.status) {
        case 'prepare': return 'Подготовка';
        case 'progress': return 'В производстве';
        case 'done': return 'Завершено';
        default: return 'Ожидает настройки';
      }
    });

    const getTypeType = computed(() => {
      if (!productionScheme.value) return 'info';
      return productionScheme.value.type === 'important' ? 'danger' : 'success';
    });

    const getTypeText = computed(() => {
      if (!productionScheme.value) return 'Стандартный';
      return productionScheme.value.type === 'important' ? 'Важный' : 'Стандартный';
    });

    const getStatusClass = (status) => {
      switch (status) {
        case 'В ожидании': return 'status-default';
        case 'Завершены': return 'status-done';
        case 'В работе': return 'status-in-progress';
        case 'Нет сырья': return 'status-error';
        default: return 'status-default';
      }
    };

    const saveProductionScheme = async () => {
      try {
        isSaving.value = true;
        const stages = prepareStagesData();
        const payload = {
          deal_id: dealId,
          stages: stages
        };

        let response;
        if (productionScheme.value) {
          response = await apiClient.patch(
            `/production-schemes/${dealId}`,
            payload
          );
        } else {
          response = await apiClient.post('/production-schemes', payload);
        }

        productionScheme.value = response.data;
        ElMessage({
          type: 'success',
          message: productionScheme.value ? 'Схема производства обновлена' : 'Схема производства создана'
        });
      } catch (error) {
        console.error('Error saving production scheme:', error);
        ElMessage({
          type: 'error',
          message: error.response?.data?.error || 'Ошибка при сохранении схемы производства'
        });
      } finally {
        isSaving.value = false;
      }
    };

    // Добавляем метод запуска производства
    const startProduction = async () => {
      try {
        isStarting.value = true;
        
        // Автоматически назначаем системного пользователя для всех стадий
        tableData.value.forEach(row => {
          if (!row.executor) {
            row.executor = systemUserId.value?.toString();
          }
        });

        // Сначала сохраняем изменения с системным пользователем
        await saveProductionScheme();

        // Затем запускаем производство
        const response = await apiClient.patch(`/production-schemes/${dealId}`, {
          status: 'progress'
        });

        productionScheme.value = response.data;

        ElMessage({
          type: 'success',
          message: 'Производство запущено'
        });
      } catch (error) {
        console.error('Error starting production:', error);
        ElMessage({
          type: 'error',
          message: error.response?.data?.error || 'Ошибка при запуске производства'
        });
      } finally {
        isStarting.value = false;
      }
    };

    const loadOperationStatuses = async () => {
      try {
        isLoadingStatuses.value = true;
        const response = await apiClient.get(`/production-schemes/${dealId}/sync`);
        productionScheme.value = response.data;

        ElMessage({
          type: 'success',
          message: 'Статусы операций обновлены'
        });
      } catch (error) {
        console.error('Error loading operation statuses:', error);
        ElMessage({
          type: 'error',
          message: 'Ошибка при загрузке статусов операций'
        });
      } finally {
        isLoadingStatuses.value = false;
      }
    };

    const handleTransferChange = (row) => {
      if (row.transfer) {
        row.executor = row.transfer;
      }
    };

    // Следим за изменением dealId
    // watch(() => dealId, async (newDealId) => {
    //   if (newDealId) {
    //     // Убедимся что пользователи загружены
    //     if (!executors.value.length) {
    //       await fetchUsers();
    //     }
    //     await Promise.all([
    //       fetchDealData(newDealId),
    //       fetchProductionScheme(newDealId)
    //     ]);
    //   }
    // }, { immediate: true });

    // Следим за изменениями в deal
    watch(() => deal.value, () => {
      checkDealProducts();
    });

    onMounted(async () => {
      // Сначала загружаем пользователей
      await fetchUsers();
      await fetchSystemUserId();

      if (dealId) {
        // Только после загрузки пользователей выполняем остальные запросы
        await Promise.all([
          fetchDealData(dealId),
          fetchOperationTypesData(),
          fetchProductionScheme(dealId)
        ]);
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
      remoteSearch,
      productionScheme,
      isSaving,
      saveProductionScheme,
      getStatusType,
      getStatusText,
      getTypeType,
      getTypeText,
      isStarting,
      startProduction,
      isLoadingStatuses,
      loadOperationStatuses,
      handleTransferChange,
      getStatusClass,
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

.deal-info-card {
  margin-bottom: 20px;
}

:deep(.el-descriptions__label) {
  font-weight: bold;
}

.actions-panel {
  margin-top: 16px;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.status-done {
  color: #67c23a;
  font-weight: bold;
}

.status-in-progress {
  color: #409eff;
  font-weight: bold;
}

.status-waiting {
  color: #e6a23c;
  font-weight: bold;
}

.status-error {
  color: #f56c6c;
  font-weight: bold;
}

.status-default {
  color: #909399;
}
</style>
