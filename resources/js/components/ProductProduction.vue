<template>
  <div class="product-production">
    <div class="header-actions">
      <el-button type="primary" @click="showAddStageDialog = true">
        Добавить этап
      </el-button>
    </div>

    <el-table
      v-if="stages.length"
      :data="stages"
      row-key="id"
      style="width: 100%; margin-top: 20px;"
      ref="dragTable"
    >
      <el-table-column width="40">
        <template #default="{ row }">
          <el-icon class="drag-handle" style="cursor: move;">
            <Rank />
          </el-icon>
        </template>
      </el-table-column>
      <el-table-column prop="stage" label="№ этапа" width="120" />
      <el-table-column label="Операция">
        <template #default="{ row }">
          {{ row.operation_type?.name }}
        </template>
      </el-table-column>
      <el-table-column label="Рабочее место">
        <template #default="{ row }">
          {{ row.operation_type?.machine }}
        </template>
      </el-table-column>
      <el-table-column label="Действия" width="100">
        <template #default="{ row }">
          <el-button
            type="danger"
            size="small"
            @click="deleteStage(row.id)"
          >
            Удалить
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-empty v-else description="Нет этапов производства" />

    <!-- Диалог добавления этапа -->
    <el-dialog
      v-model="showAddStageDialog"
      title="Добавление этапа производства"
      width="500px"
    >
      <el-form
        ref="addFormRef"
        :model="addForm"
        :rules="rules"
        label-width="140px"
      >
        <el-form-item label="Тип операции" prop="operation_type_id">
          <el-select
            v-model="addForm.operation_type_id"
            placeholder="Выберите тип операции"
            filterable
          >
            <el-option
              v-for="type in operationTypes"
              :key="type.id"
              :label="type.name"
              :value="type.id"
            />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAddStageDialog = false">Отмена</el-button>
        <el-button type="primary" @click="submitAddForm">Добавить</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { defineComponent, ref, onMounted, nextTick, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { Rank } from '@element-plus/icons-vue';
import Sortable from 'sortablejs';
import apiClient from '../api';

export default defineComponent({
  name: 'ProductProduction',
  components: { Rank },
  props: {
    productId: {
      type: [Number, String],
      required: true
    },
    productName: {
      type: String,
      required: true
    }
  },
  setup(props) {
    const stages = ref([]);
    const operationTypes = ref([]);
    const showAddStageDialog = ref(false);
    const addFormRef = ref(null);
    const dragTable = ref(null);

    const addForm = ref({
      operation_type_id: '',
      product_part_id: props.productId
    });

    const rules = {
      operation_type_id: [
        { required: true, message: 'Выберите тип операции', trigger: 'change' }
      ]
    };

    const fetchStages = async () => {
      try {
        const response = await apiClient.get('/product-operation-stages', {
          params: { product_part_id: props.productId }
        });
        stages.value = Array.isArray(response.data) ? response.data : [];
      } catch (error) {
        console.error('API Error:', error);
        ElMessage.error('Ошибка при загрузке этапов производства');
        stages.value = [];
      }
    };

    const fetchOperationTypes = async () => {
      try {
        const response = await apiClient.get('/operation-types');
        operationTypes.value = Array.isArray(response.data) ? response.data : [];
      } catch (error) {
        console.error('API Error:', error);
        ElMessage.error('Ошибка при загрузке типов операций');
        operationTypes.value = [];
      }
    };

    const submitAddForm = async () => {
      if (!addFormRef.value) return;

      await addFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            await apiClient.post('/product-operation-stages', addForm.value);
            ElMessage.success('Этап успешно добавлен');
            showAddStageDialog.value = false;
            // Сбрасываем форму
            addForm.value = {
              operation_type_id: '',
              product_part_id: props.productId
            };
            await fetchStages();
          } catch (error) {
            ElMessage.error(error.response?.data?.error || 'Ошибка при добавлении этапа');
          }
        }
      });
    };

    const deleteStage = async (id) => {
      try {
        await apiClient.delete(`/product-operation-stages/${id}`);
        ElMessage.success('Этап удален');
        await fetchStages();
      } catch (error) {
        ElMessage.error('Ошибка при удалении этапа');
      }
    };

    const initSortable = () => {
      nextTick(() => {
        const el = dragTable.value.$el.querySelector('.el-table__body-wrapper tbody');
        if (!el) return;

        new Sortable(el, {
          handle: '.drag-handle',
          animation: 150,
          onEnd: async (evt) => {
            // Получаем новый порядок элементов
            const oldIndex = evt.oldIndex;
            const newIndex = evt.newIndex;

            // Создаем копию массива stages
            const updatedStagesArray = [...stages.value];

            // Перемещаем элемент в массиве
            const [movedItem] = updatedStagesArray.splice(oldIndex, 1);
            updatedStagesArray.splice(newIndex, 0, movedItem);

            // Создаем массив с обновленными stage
            const updatedStages = updatedStagesArray.map((stage, index) => ({
              id: stage.id,
              stage: index + 1
            }));

            console.log('Sending updated stages:', updatedStages); // для отладки

            try {
              await apiClient.patch('/product-operation-stages/reorder/', {
                items: updatedStages
              });
              await fetchStages();
              ElMessage.success('Порядок успешно обновлен');
            } catch (error) {
              console.error('Reorder error:', error);
              ElMessage.error('Ошибка при изменении порядка');
              await fetchStages();
            }
          }
        });
      });
    };

    // Вызываем initSortable при монтировании и при обновлении stages
    watch(() => stages.value, () => {
      nextTick(() => {
        initSortable();
      });
    });

    onMounted(async () => {
      await Promise.all([fetchStages(), fetchOperationTypes()]);
      initSortable();
    });

    return {
      stages,
      operationTypes,
      showAddStageDialog,
      addForm,
      addFormRef,
      rules,
      submitAddForm,
      deleteStage,
      dragTable
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

.drag-handle {
  cursor: move;
  color: #909399;
}

.drag-handle:hover {
  color: #409EFF;
}

/* Стили для перетаскивания */
.sortable-ghost {
  opacity: 0.5;
  background: #c8ebfb;
}

.sortable-drag {
  opacity: 0.8;
  background: #fff;
}
</style>
