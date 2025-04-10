<template>
  <div>
    <h3>Комплектующие</h3>
    <div class="header-actions">
      <div class="sync-container">
        <el-button
          type="primary"
          @click="syncProducts"
          :loading="isSyncing"
        >
          Синхронизировать комплектующие
        </el-button>
      </div>

      <el-input
        v-model="searchQuery"
        placeholder="Поиск комплектующих"
        @input="debouncedSearch"
      />
    </div>

    <el-table
      v-if="productParts"
      :data="productParts"
      style="width: 100%; margin-top: 20px;"
    >
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="Название" />
      <el-table-column prop="bitrix_id" label="Артикул" width="120" />
      <el-table-column label="Действия" width="300">
        <template #default="{ row }">
          <el-button
            type="primary"
            size="small"
            @click="openProductionModal(row.id)"
          >
            Этапы производства
          </el-button>
          <el-button
            type="danger"
            size="small"
            @click="confirmDelete(row)"
          >
            Удалить
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <!-- Модальное окно -->
    <el-dialog
      v-model="showProductionModal"
      title="Настройка этапов производства"
      width="70%"
      destroy-on-close
    >
      <ProductProduction
        :product-id="selectedProductId"
        @success="handleProductionSuccess"
      />
    </el-dialog>

    <!-- Добавляем модальное окно подтверждения удаления -->
    <el-dialog
      v-model="showDeleteConfirm"
      title="Подтверждение удаления"
      width="400px"
    >
      <p>Вы действительно хотите удалить комплектующую "{{ deleteItem?.name }}"?</p>
      <template #footer>
        <el-button @click="showDeleteConfirm = false">Отмена</el-button>
        <el-button type="danger" @click="handleDelete">Удалить</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import {defineComponent, inject, onMounted, ref} from 'vue';
import apiClient from '../../api';
import ProductProduction from '../ProductProduction.vue';
import { ElMessage } from 'element-plus';

export default defineComponent({
  name: 'Details',
  components: {
    ProductProduction
  },
  setup() {
    const dealId = inject('dealId');
    const productParts = ref(null);
    const error = ref(null);
    const searchQuery = ref('');
    const showProductionModal = ref(false);
    const selectedProductId = ref(null);
    const isSyncing = ref(false);
    const showDeleteConfirm = ref(false);
    const deleteItem = ref(null);

    const fetchProductParts = async () => {
      try {
        const response = await apiClient.get('/product-parts');
        productParts.value = response.data;
      } catch (err) {
        error.value = 'Ошибка при получении данных: ' + err.message;
      }
    };

    const searchProductParts = async () => {
      try {
        const response = await apiClient.get('/product-parts', {
          params: {
            name: searchQuery.value
          }
        });
        productParts.value = response.data;
      } catch (err) {
        error.value = 'Ошибка при поиске данных: ' + err.message;
      }
    };

    const syncProducts = async () => {
      isSyncing.value = true;
      try {
        await apiClient.get('/product-parts/import/');
        ElMessage.success('Синхронизация успешно завершена');
        await fetchProductParts(); // Обновляем список после синхронизации
      } catch (error) {
        ElMessage.error('Ошибка при синхронизации: ' + (error.response?.data?.error || error.message));
      } finally {
        isSyncing.value = false;
      }
    };

    const searchTimeout = ref(null);
    const debouncedSearch = () => {
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value);
      }
      searchTimeout.value = setTimeout(() => {
        searchProductParts();
      }, 300);
    };

    const openProductionModal = (id) => {
      selectedProductId.value = id;
      showProductionModal.value = true;
    };

    const handleProductionSuccess = () => {
      showProductionModal.value = false;
      fetchProductParts();
    };

    const confirmDelete = (row) => {
      deleteItem.value = row;
      showDeleteConfirm.value = true;
    };

    const handleDelete = async () => {
      try {
        await apiClient.delete(`/product-parts/${deleteItem.value.id}`);
        ElMessage.success('Комплектующая успешно удалена');
        showDeleteConfirm.value = false;
        await fetchProductParts(); // Обновляем список
      } catch (error) {
        ElMessage.error('Ошибка при удалении: ' + (error.response?.data?.error || error.message));
      }
    };

    onMounted(() => {
      fetchProductParts();
    });

    return {
      dealId,
      productParts,
      searchQuery,
      debouncedSearch,
      showProductionModal,
      selectedProductId,
      openProductionModal,
      handleProductionSuccess,
      syncProducts,
      isSyncing,
      error,
      showDeleteConfirm,
      deleteItem,
      confirmDelete,
      handleDelete
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

.sync-container {
  display: flex;
  align-items: center;
}

.error-message {
  color: red;
  margin-top: 10px;
}
</style>
