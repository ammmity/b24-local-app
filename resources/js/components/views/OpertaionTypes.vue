<template>
  <div>
    <el-input
      v-model="searchQuery"
      placeholder="Поиск типов операций"
      @input="debouncedSearch"
    />

    <el-table
      v-if="operationTypes"
      :data="operationTypes"
      style="width: 100%; margin-top: 20px;"
    >
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="Название" />
      <el-table-column prop="machine" label="Рабочее место" />
      <el-table-column label="Действия" width="150">
        <template #default="{ row }">
          <el-button
            type="primary"
            size="small"
            @click="editOperationType(row)"
          >
            Редактировать
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import apiClient from '../../api';

export default defineComponent({
  name: 'OperationTypes',
  setup() {
    const operationTypes = ref(null);
    const error = ref(null);
    const searchQuery = ref('');

    const fetchOperationTypes = async () => {
      try {
        const response = await apiClient.get('/operation-types');
        operationTypes.value = response.data;
      } catch (err) {
        error.value = 'Ошибка при получении данных: ' + err.message;
      }
    };

    const searchOperationTypes = async () => {
      try {
        const response = await apiClient.get('/operation-types', {
          params: {
            name: searchQuery.value
          }
        });
        operationTypes.value = response.data;
      } catch (err) {
        error.value = 'Ошибка при поиске данных: ' + err.message;
      }
    };

    const searchTimeout = ref(null);
    const debouncedSearch = () => {
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
      }
      searchTimeout.value = setTimeout(() => {
        searchOperationTypes();
      }, 300);
    };

    const editOperationType = (row) => {
      // Здесь будет логика редактирования
      console.log('Редактирование типа операции:', row);
    };

    onMounted(fetchOperationTypes);

    return {
      operationTypes,
      error,
      searchQuery,
      debouncedSearch,
      editOperationType
    };
  }
});
</script>

<style scoped>
/* Ваши стили для компонента Home */
h1 {
  color: #42b983;
}

.error-message {
  color: red;
  margin-top: 10px;
}
</style>
