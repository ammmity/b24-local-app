<template>
  <div class="">
    <h3>Цены операций</h3>

    <div class="header-actions">
      <el-button
        type="primary"
        @click="showCreateModal = true"
      >
        Добавить цену типу операции
      </el-button>
    </div>

    <el-table
      v-if="operationPrices"
      :data="operationPrices"
      style="width: 100%; margin-top: 20px;"
    >
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column label="Операция">
        <template #default="{ row }">
          {{ row.operation_type.name }}
        </template>
      </el-table-column>
      <el-table-column label="Рабочее место">
        <template #default="{ row }">
          {{ row.operation_type.machine }}
        </template>
      </el-table-column>
      <el-table-column label="Деталь">
        <template #default="{ row }">
          {{ row.detail_name || 'Общая цена' }}
        </template>
      </el-table-column>
      <el-table-column label="Цена">
        <template #default="{ row }">
          {{ row.price }} ₽
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
      title="Добавление цены операции"
      width="500px"
    >
      <el-form
        ref="createFormRef"
        :model="createForm"
        :rules="formRules"
        label-width="160px"
      >
        <el-form-item label="Тип операции" prop="operation_type_id">
          <el-select
            v-model="createForm.operation_type_id"
            placeholder="Выберите тип операции"
            filterable
          >
            <el-option
              v-for="type in operationTypes"
              :key="type.id"
              :label="`${type.name} (${type.machine})`"
              :value="type.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Деталь">
          <el-select
            v-model="createForm.product_part_id"
            placeholder="Выберите деталь (необязательно)"
            filterable
            clearable
          >
            <el-option
              v-for="part in details"
              :key="part.id"
              :label="part.name"
              :value="part.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Цена" prop="price">
          <el-input-number
            v-model="createForm.price"
            :min="0"
            :precision="0"
            :step="100"
            style="width: 100%"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="showCreateModal = false">Отмена</el-button>
        <el-button type="primary" @click="submitCreate">Создать</el-button>
      </template>
    </el-dialog>

    <!-- Модальное окно редактирования -->
    <el-dialog
      v-model="showEditModal"
      title="Редактирование цены операции"
      width="500px"
    >
      <el-form
        ref="editFormRef"
        :model="editForm"
        :rules="formRules"
        label-width="160px"
      >
        <el-form-item label="Тип операции" prop="operation_type_id">
          <el-select
            v-model="editForm.operation_type_id"
            placeholder="Выберите тип операции"
            filterable
          >
            <el-option
              v-for="type in operationTypes"
              :key="type.id"
              :label="`${type.name} (${type.machine})`"
              :value="type.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Деталь">
          <el-select
            v-model="editForm.product_part_id"
            placeholder="Выберите деталь (необязательно)"
            filterable
            clearable
          >
            <el-option
              v-for="part in details"
              :key="part.id"
              :label="part.name"
              :value="part.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item label="Цена" prop="price">
          <el-input-number
            v-model="editForm.price"
            :min="0"
            :precision="0"
            :step="100"
            style="width: 100%"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="showEditModal = false">Отмена</el-button>
        <el-button type="primary" @click="submitEdit">Сохранить</el-button>
      </template>
    </el-dialog>

    <el-dialog
      v-model="showDeleteConfirm"
      title="Подтверждение удаления"
      width="400px"
    >
      <p>Вы действительно хотите удалить цену для операции "{{ deleteItem?.operation_type?.name }}"?</p>
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
  name: 'OperationPrices',
  setup() {
    const operationPrices = ref(null);
    const operationTypes = ref([]);
    const error = ref(null);
    const showCreateModal = ref(false);
    const showEditModal = ref(false);
    const showDeleteConfirm = ref(false);
    const createFormRef = ref(null);
    const editFormRef = ref(null);
    const deleteItem = ref(null);
    const details = ref([]);

    const createForm = ref({
      operation_type_id: '',
      product_part_id: null,
      price: 0
    });

    const editForm = ref({
      id: null,
      operation_type_id: '',
      product_part_id: null,
      price: 0
    });

    const formRules = {
      operation_type_id: [
        { required: true, message: 'Выберите тип операции', trigger: 'change' }
      ],
      price: [
        { required: true, message: 'Введите цену', trigger: 'blur' },
        { type: 'number', min: 0, message: 'Цена должна быть положительным числом', trigger: 'blur' }
      ]
    };

    const fetchOperationPrices = async () => {
      try {
        const response = await apiClient.get('/operation-prices');
        operationPrices.value = response.data;
      } catch (err) {
        error.value = 'Ошибка при получении данных: ' + err.message;
      }
    };

    const fetchOperationTypes = async () => {
      try {
        const response = await apiClient.get('/operation-types');
        operationTypes.value = response.data;
      } catch (err) {
        error.value = 'Ошибка при получении типов операций: ' + err.message;
      }
    };

    const fetchDetails = async () => {
      try {
        const response = await apiClient.get('/product-parts');
        details.value = response.data.map(part => ({
          id: part.id,
          name: part.name || part.title || 'Без названия'
        }));
      } catch (err) {
        error.value = 'Ошибка при получении списка деталей: ' + err.message;
      }
    };

    const submitCreate = async () => {
      if (!createFormRef.value) return;

      await createFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            // Проверяем существование цены для комбинации операции и детали
            const existingPrice = operationPrices.value.find(
              price => price.operation_type_id === createForm.value.operation_type_id &&
                      price.product_part_id === createForm.value.product_part_id
            );
            
            if (existingPrice) {
              ElMessage.error('Цена для этой комбинации операции и детали уже существует');
              return;
            }

            await apiClient.post('/operation-prices', createForm.value);
            ElMessage.success('Цена операции успешно добавлена');
            showCreateModal.value = false;
            createForm.value = { operation_type_id: '', product_part_id: null, price: 0 };
            await fetchOperationPrices();
          } catch (error) {
            ElMessage.error(error.response?.data?.error || 'Произошла ошибка при создании');
          }
        }
      });
    };

    const handleEdit = (row) => {
      editForm.value = {
        id: row.id,
        operation_type_id: row.operation_type_id,
        product_part_id: row.product_part_id,
        price: row.price
      };
      showEditModal.value = true;
    };

    const submitEdit = async () => {
      if (!editFormRef.value) return;

      await editFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            // Проверяем существование цены для комбинации операции и детали
            const existingPrice = operationPrices.value.find(
              price => price.operation_type_id === editForm.value.operation_type_id &&
                      price.product_part_id === editForm.value.product_part_id &&
                      price.id !== editForm.value.id
            );
            
            if (existingPrice) {
              ElMessage.error('Цена для этой комбинации операции и детали уже существует');
              return;
            }

            await apiClient.patch(`/operation-prices/${editForm.value.id}`, {
              operation_type_id: editForm.value.operation_type_id,
              product_part_id: editForm.value.product_part_id,
              price: editForm.value.price
            });
            ElMessage.success('Цена операции успешно обновлена');
            showEditModal.value = false;
            await fetchOperationPrices();
          } catch (error) {
            ElMessage.error(error.response?.data?.error || 'Произошла ошибка при обновлении');
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
        await apiClient.delete(`/operation-prices/${deleteItem.value.id}`);
        ElMessage.success('Цена операции успешно удалена');
        showDeleteConfirm.value = false;
        await fetchOperationPrices();
      } catch (error) {
        ElMessage.error(error.response?.data?.error || 'Произошла ошибка при удалении');
      }
    };

    onMounted(async () => {
      await Promise.all([
        fetchOperationPrices(),
        fetchOperationTypes(),
        fetchDetails()
      ]);
    });

    return {
      operationPrices,
      operationTypes,
      error,
      showCreateModal,
      showEditModal,
      showDeleteConfirm,
      createFormRef,
      editFormRef,
      createForm,
      editForm,
      formRules,
      submitCreate,
      handleEdit,
      submitEdit,
      handleDelete,
      confirmDelete,
      deleteItem,
      details,
    };
  }
});
</script>

<style scoped>
.operation-prices-container {
  padding: 20px;
}

.header-actions {
  display: flex;
  gap: 16px;
  margin-bottom: 20px;
}

.error-message {
  color: red;
  margin-top: 10px;
}

:deep(.el-select),
:deep(.el-input-number) {
  width: 100%;
}
</style>
