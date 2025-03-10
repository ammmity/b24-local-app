<template>
  <div>
    <div class="header-actions">
      <div class="content-placeholder">
        <el-empty description="Главная"></el-empty>
      </div>
    </div>
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
        const response = await apiClient.get('/product-parts');
        productParts.value = response.data;
        window.console.log(productParts.value);
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
