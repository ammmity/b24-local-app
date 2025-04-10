<template>
  <div>
    <h3>Типы операций</h3>
    
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
      <el-table-column label="Группа Bitrix">
        <template #default="{ row }">
          {{ getBitrixGroupName(row.bitrix_group_id) }}
        </template>
      </el-table-column>
      <el-table-column label="Действия" width="200">
        <template #default="{ row }">
          <el-button
            type="primary"
            size="small"
            @click="handleEdit(row)"
          >
            Изменить
          </el-button>
          <el-button
            type="danger"
            size="small"
            @click="handleDelete(row)"
          >
            Удалить
          </el-button>
        </template>
      </el-table-column>
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

        <el-form-item label="Группа Bitrix" prop="bitrix_group_id">
          <el-select 
            v-model="createForm.bitrix_group_id" 
            placeholder="Выберите группу"
          >
            <el-option
              v-for="group in bitrixGroups"
              :key="group.id"
              :label="group.name"
              :value="group.id"
            />
          </el-select>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="showCreateModal = false">Отмена</el-button>
        <el-button type="primary" @click="submitCreate">Создать</el-button>
      </template>
    </el-dialog>

    <!-- Добавляем модальное окно редактирования -->
    <el-dialog
      v-model="showEditModal"
      title="Редактирование типа операции"
      width="500px"
    >
      <el-form
        ref="editFormRef"
        :model="editForm"
        :rules="formRules"
        label-width="160px"
      >
        <el-form-item label="Операция" prop="name">
          <el-input v-model="editForm.name" />
        </el-form-item>

        <el-form-item label="Рабочее место" prop="machine">
          <el-input v-model="editForm.machine" />
        </el-form-item>

        <el-form-item label="Группа Bitrix" prop="bitrix_group_id">
          <el-select 
            v-model="editForm.bitrix_group_id" 
            placeholder="Выберите группу"
          >
            <el-option
              v-for="group in bitrixGroups"
              :key="group.id"
              :label="group.name"
              :value="group.id"
            />
          </el-select>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="showEditModal = false">Отмена</el-button>
        <el-button type="primary" @click="submitEdit">Сохранить</el-button>
      </template>
    </el-dialog>

    <!-- Добавляем модальное окно подтверждения удаления -->
    <el-dialog
      v-model="showDeleteConfirm"
      title="Подтверждение удаления"
      width="400px"
    >
      <p>Вы действительно хотите удалить тип операции "{{ deleteItem?.name }}"?</p>
      <template #footer>
        <el-button @click="showDeleteConfirm = false">Отмена</el-button>
        <el-button type="danger" @click="confirmDelete">Удалить</el-button>
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
      machine: '',
      bitrix_group_id: ''
    });

    const formRules = {
      name: [
        { required: true, message: 'Введите название', trigger: 'blur' }
      ],
      machine: [
        { required: true, message: 'Введите название рабочего места', trigger: 'blur' }
      ],
      bitrix_group_id: [
        { required: true, message: 'Выберите группу Bitrix', trigger: 'blur' }
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
            createForm.value = { name: '', machine: '', bitrix_group_id: '' };
            await fetchOperationTypes();
          } catch (error) {
            ElMessage.error(error.response.data.error);
          }
        }
      });
    };

    const bitrixGroups = ref([]);

    const fetchGroups = async () => {
      try {
        const response = await apiClient.get('/groups');
        bitrixGroups.value = response.data;
      } catch (error) {
        console.error('Error fetching groups:', error);
        ElMessage.error('Ошибка при загрузке списка групп');
      }
    };

    const getBitrixGroupName = (groupId) => {
      const group = bitrixGroups.value.find(g => g.id === groupId);
      return group ? group.name : 'Не найдена';
    };

    const showEditModal = ref(false);
    const showDeleteConfirm = ref(false);
    const editFormRef = ref(null);
    const deleteItem = ref(null);
    const editForm = ref({
      id: null,
      name: '',
      machine: '',
      bitrix_group_id: ''
    });

    const handleEdit = (row) => {
      editForm.value = { ...row };
      showEditModal.value = true;
    };

    const submitEdit = async () => {
      if (!editFormRef.value) return;

      await editFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            await apiClient.patch(`/operation-types/${editForm.value.id}`, editForm.value);
            ElMessage.success('Тип операции успешно обновлен');
            showEditModal.value = false;
            await fetchOperationTypes();
          } catch (error) {
            ElMessage.error(error.response?.data?.error || 'Ошибка при обновлении');
          }
        }
      });
    };

    const handleDelete = (row) => {
      deleteItem.value = row;
      showDeleteConfirm.value = true;
    };

    const confirmDelete = async () => {
      try {
        await apiClient.delete(`/operation-types/${deleteItem.value.id}`);
        ElMessage.success('Тип операции успешно удален');
        showDeleteConfirm.value = false;
        await fetchOperationTypes();
      } catch (error) {
        ElMessage.error(error.response?.data?.error || 'Ошибка при удалении');
      }
    };

    onMounted(async () => {
      await Promise.all([
        fetchOperationTypes(),
        fetchGroups()
      ]);
    });

    return {
      operationTypes,
      error,
      searchQuery,
      debouncedSearch,
      showCreateModal,
      createFormRef,
      createForm,
      formRules,
      submitCreate,
      bitrixGroups,
      getBitrixGroupName,
      showEditModal,
      showDeleteConfirm,
      editFormRef,
      editForm,
      deleteItem,
      handleEdit,
      submitEdit,
      handleDelete,
      confirmDelete
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

:deep(.el-select) {
  width: 100%;
}
</style>
