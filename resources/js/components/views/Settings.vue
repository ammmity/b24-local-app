<template>
  <div>
    <div class="header-actions">
      <div class="sync-container">
        <span class="sync-label">Синхронизация комплектующих</span>
        <el-button
          type="primary"
          @click="syncProducts"
          :loading="isSyncing"
        >
          Запустить
        </el-button>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent, ref } from 'vue';
import { ElMessage } from 'element-plus';
import apiClient from '../../api';

export default defineComponent({
  name: 'Settings',
  setup() {
    const isSyncing = ref(false);

    const syncProducts = async () => {
      isSyncing.value = true;
      try {
        await apiClient.get('/product-parts/import/');
        ElMessage.success('Синхронизация успешно завершена');
      } catch (error) {
        ElMessage.error('Ошибка при синхронизации: ' + (error.response?.data?.error || error.message));
      } finally {
        isSyncing.value = false;
      }
    };

    return {
      syncProducts,
      isSyncing
    };
  }
});
</script>

<style scoped>
.header-actions {
  margin-bottom: 20px;
}

.sync-container {
  display: flex;
  align-items: center;
  gap: 16px;
}

.sync-label {
  font-size: 14px;
  color: #606266;
}
</style>
