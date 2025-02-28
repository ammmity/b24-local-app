<template>
  <div>
    <div class="header-actions">
      <el-button
        type="primary"
        @click="showCreateModal = true"
      >
        Добавить тип операции
      </el-button>

      <el-input
        v-model="searchQuery"
        placeholder="Поиск типов операций"
        @input="debouncedSearch"
      />
    </div>

    <el-table
      v-if="operationTypes"
      :data="operationTypes"
      style="width: 100%; margin-top: 20px;"
    >
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="Операция" />
      <el-table-column prop="machine" label="Рабочее место" />
    </el-table>

    <!-- Модальное окно создания -->
    <el-dialog
      v-model="showCreateModal"
      title="Создание типа операции"
      width="500px"
    >
      <el-form
        ref="createFormRef"
        :model="createForm"
        :rules="formRules"
        label-width="160px"
      >
        <el-form-item label="Операция" prop="name">
          <el-input v-model="createForm.name" />
        </el-form-item>

        <el-form-item label="Рабочее место" prop="machine">
          <el-input v-model="createForm.machine" />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="showCreateModal = false">Отмена</el-button>
        <el-button type="primary" @click="submitCreate">Создать</el-button>
      </template>
    </el-dialog>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import apiClient from '../../api';
import { ElMessage } from 'element-plus';

export default defineComponent({
  name: 'OperationTypes',
  setup() {
    const operationTypes = ref(null);
    const error = ref(null);
    const searchQuery = ref('');
    const showCreateModal = ref(false);
    const createFormRef = ref(null);
    const createForm = ref({
      name: '',
      machine: ''
    });

    const formRules = {
      name: [
        { required: true, message: 'Введите название', trigger: 'blur' }
      ],
      machine: [
        { required: true, message: 'Введите название рабочего места', trigger: 'blur' }
      ]
    };

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

    const submitCreate = async () => {
      if (!createFormRef.value) return;

      await createFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            await apiClient.post('/operation-types', createForm.value);
            ElMessage.success('Тип операции успешно создан');
            showCreateModal.value = false;
            createForm.value = { name: '', machine: '' };
            await fetchOperationTypes(); // Обновляем список
          } catch (error) {
            ElMessage.error(error.response.data.error);
          }
        }
      });
    };

    onMounted(fetchOperationTypes);

    return {
      operationTypes,
      error,
      searchQuery,
      debouncedSearch,
      showCreateModal,
      createFormRef,
      createForm,
      formRules,
      submitCreate
    };
  }
});
</script>

<style scoped>
.header-actions {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
}

.error-message {
  color: red;
  margin-top: 10px;
}
</style>
