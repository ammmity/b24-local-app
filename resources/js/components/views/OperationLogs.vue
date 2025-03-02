<template>
  <div class="">
    <h3>Журнал работ</h3>

    <!-- Фильтры -->
    <div class="filters-container">
      <el-form :inline="true" :model="filters" class="filter-form">
        <el-form-item label="Сотрудник">
          <el-select 
            v-model="filters.userId" 
            placeholder="Все сотрудники"
            clearable
            style="width: 220px;"
          >
            <el-option
              v-for="user in users"
              :key="user.userId"
              :label="user.username"
              :value="user.userId"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="Период">
          <el-select 
            v-model="filters.period" 
            placeholder="Выберите период"
            @change="handlePeriodChange"
            style="width: 200px;"
          >
            <el-option label="За сегодня" value="today" />
            <el-option label="За месяц" value="month" />
            <el-option label="За квартал" value="quarter" />
            <el-option label="Произвольный период" value="custom" />
          </el-select>
        </el-form-item>
        
        <template v-if="filters.period === 'custom'">
          <el-form-item label="С">
            <el-date-picker
              v-model="filters.startDate"
              type="date"
              placeholder="Выберите дату"
              format="DD.MM.YYYY"
              value-format="YYYY-MM-DD"
              style="width: 160px;"
            />
          </el-form-item>
          
          <el-form-item label="По">
            <el-date-picker
              v-model="filters.endDate"
              type="date"
              placeholder="Выберите дату"
              format="DD.MM.YYYY"
              value-format="YYYY-MM-DD"
              style="width: 160px;"
            />
          </el-form-item>
        </template>
        
        <el-form-item>
          <el-button type="primary" @click="applyFilters" :loading="loading">Применить</el-button>
          <el-button @click="resetFilters">Сбросить</el-button>
        </el-form-item>
      </el-form>
    </div>

    <el-table
      v-if="operationLogs && operationLogs.length"
      :data="operationLogs"
      style="width: 100%; margin-top: 20px;"
    >
      <el-table-column label="Дата">
        <template #default="{ row }">
          {{ formatDate(row.created_date) }}
        </template>
      </el-table-column>
      <el-table-column prop="deal_id" label="Сделка" />
      <el-table-column label="Задача">
        <template #default="{ row }">
          <a :href="row.task_link" target="_blank">{{ row.bitrix_task_id }}</a>
        </template>
      </el-table-column>
      <el-table-column prop="username" label="Пользователь" />
      <el-table-column prop="operation" label="Операция" />
      <el-table-column prop="detail_name" label="Деталь" />
      <el-table-column prop="quantity" label="Количество" width="120" />
      <el-table-column label="Цена">
        <template #default="{ row }">
          {{ row.price }} ₽
        </template>
      </el-table-column>
    </el-table>
    
    <div v-else-if="!loading && (!operationLogs || operationLogs.length === 0)" class="no-data">
      <p>Нет данных, соответствующих выбранным фильтрам</p>
    </div>
    
    <div v-if="loading" class="loading-container">
      <el-skeleton :rows="10" animated />
    </div>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import apiClient from '../../api';
import { ElMessage } from 'element-plus';
import ru from 'element-plus/es/locale/lang/ru';

export default defineComponent({
  name: 'OperationLogs',
  setup() {
    const locale = ref(ru);
    
    const operationLogs = ref(null);
    const users = ref([]);
    const error = ref(null);
    const loading = ref(false);
    
    // Фильтры
    const filters = ref({
      userId: null,
      period: null,
      startDate: null,
      endDate: null
    });
    
    const fetchUsers = async () => {
      try {
        const response = await apiClient.get('/operation-logs/users');
        users.value = response.data;
      } catch (err) {
        error.value = 'Ошибка при получении списка пользователей: ' + err.message;
      }
    };
    
    const fetchOperationLogs = async (params = {}) => {
      loading.value = true;
      error.value = null;
      
      try {
        const response = await apiClient.get('/operation-logs', { params });
        operationLogs.value = response.data;
      } catch (err) {
        error.value = 'Ошибка при получении данных: ' + err.message;
        operationLogs.value = [];
      } finally {
        loading.value = false;
      }
    };
    
    // Обработка изменения периода
    const handlePeriodChange = () => {
      const today = new Date();
      const formatDate = (date) => {
        return date.toISOString().split('T')[0]; // YYYY-MM-DD
      };
      
      switch (filters.value.period) {
        case 'today':
          filters.value.startDate = formatDate(today);
          filters.value.endDate = formatDate(today);
          break;
          
        case 'month':
          const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
          filters.value.startDate = formatDate(monthStart);
          filters.value.endDate = formatDate(today);
          break;
          
        case 'quarter':
          const quarterMonth = Math.floor(today.getMonth() / 3) * 3;
          const quarterStart = new Date(today.getFullYear(), quarterMonth, 1);
          filters.value.startDate = formatDate(quarterStart);
          filters.value.endDate = formatDate(today);
          break;
          
        case 'custom':
          // Оставляем поля пустыми для ручного ввода
          filters.value.startDate = null;
          filters.value.endDate = null;
          break;
      }
    };
    
    // Применение фильтров
    const applyFilters = async () => {
      if (filters.value.period === 'custom' && (!filters.value.startDate || !filters.value.endDate)) {
        ElMessage.warning('Пожалуйста, укажите начальную и конечную даты для произвольного периода');
        return;
      }
      
      const params = {};
      
      if (filters.value.userId) {
        params.userId = filters.value.userId;
      }
      
      if (filters.value.startDate) {
        params.startDate = filters.value.startDate;
      }
      
      if (filters.value.endDate) {
        params.endDate = filters.value.endDate;
      }
      
      await fetchOperationLogs(params);
    };
    
    // Сброс фильтров
    const resetFilters = async () => {
      filters.value = {
        userId: null,
        period: null,
        startDate: null,
        endDate: null
      };
      
      await fetchOperationLogs();
    };

    const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleString('ru-RU');
    };

    onMounted(async () => {
      await fetchUsers();
      await fetchOperationLogs();
    });

    return {
      operationLogs,
      users,
      error,
      loading,
      filters,
      handlePeriodChange,
      applyFilters,
      resetFilters,
      formatDate,
      locale
    };
  }
});
</script>

<style scoped>
.operation-logs-container {
  padding: 20px;
}

.filters-container {
  margin-bottom: 20px;
  padding: 15px;
  background-color: #f5f7fa;
  border-radius: 4px;
}

.filter-form {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  align-items: center;
}

:deep(.el-form-item__label) {
  font-weight: 500;
}

:deep(.el-select) {
  min-width: 150px;
}

.error-message {
  color: red;
  margin-top: 10px;
}

.no-data {
  text-align: center;
  margin-top: 30px;
  color: #909399;
}

.loading-container {
  margin-top: 20px;
}
</style> 