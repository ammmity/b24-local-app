<template>
  <div>
    <el-input
      v-model="searchQuery"
      placeholder="Поиск продуктов"
      @input="debouncedSearch"
    />

    <el-table
      v-if="productParts"
      :data="productParts"
      style="width: 100%; margin-top: 20px;"
    >
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="Название" />
      <el-table-column prop="bitrix_id" label="Артикул" width="120" />
      <el-table-column label="Действия" width="150">
        <template #default="{ row }">
          <el-button
            type="primary"
            size="small"
            @click="openProductionModal(row.id)"
          >
            Производство
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <!-- Добавляем модальное окно -->
    <el-dialog
      v-model="showProductionModal"
      title="Производство продукта"
      width="70%"
      destroy-on-close
    >
      <ProductProduction
        :product-id="selectedProductId"
        @success="handleProductionSuccess"
      />
    </el-dialog>
  </div>
</template>

<script>
import {defineComponent, inject, onMounted, ref} from 'vue';
import apiClient from '../../api';
import { ElButton } from 'element-plus'
import ProductProduction from './ProductProduction.vue';

export default defineComponent({
  name: 'Home',
  components: {
    ProductProduction
  },
  setup() {
    // Получение переменной из provide
    const dealId = inject('dealId');
    const productParts = ref(null);
    const error = ref(null);
    const searchQuery = ref('');
    const showProductionModal = ref(false);
    const selectedProductId = ref(null);

    const fetchProductParts = async () => {
      try {
        const response = await apiClient.get('/products');
        productParts.value = response.data;
        window.console.log(productParts.value);
      } catch (err) {
        error.value = 'Ошибка при получении данных: ' + err.message;
      }
    };

    const searchProductParts = async () => {
      try {
        const response = await apiClient.get('/products', {
          params: {
            name: searchQuery.value
          }
        });
        productParts.value = response.data;
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
        searchProductParts();
      }, 300); // Задержка 300мс
    };

    const openProductionModal = (id) => {
      selectedProductId.value = id;
      showProductionModal.value = true;
    };

    const handleProductionSuccess = () => {
      showProductionModal.value = false;
      fetchProductParts(); // Обновляем список после успешного создания
    };

    onMounted(fetchProductParts); // Вызываем fetchData при монтировании компонента
    // Возвращаем переменную для использования в шаблоне
    return { dealId, productParts, searchQuery, debouncedSearch, showProductionModal, selectedProductId, openProductionModal, handleProductionSuccess };
  }
});
</script>

<style scoped>
</style>
