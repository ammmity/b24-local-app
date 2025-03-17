<template>
  <div>
    <h3>Виртуальные детали</h3>
    
    <div class="header-actions">
      <div class="sync-container">
        <el-button
          type="primary"
          @click="importVirtualParts"
          :loading="loading"
        >
          Импортировать виртуальные детали
        </el-button>
      </div>

      <el-input
        v-model="searchQuery"
        placeholder="Поиск деталей"
        @input="handleSearch"
      />
    </div>
    
    <el-table
      v-loading="loading"
      :data="filteredVirtualParts"
      style="width: 100%; margin-top: 20px;"
    >
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="Название" />
      <el-table-column prop="bitrix_id" label="Bitrix ID" width="120" />
    </el-table>
    
    <el-pagination
      v-if="totalFilteredItems > pageSize"
      layout="prev, pager, next"
      :total="totalFilteredItems"
      :page-size="pageSize"
      :current-page="currentPage"
      @current-change="handlePageChange"
      class="pagination"
    />
  </div>
</template>

<script>
import { defineComponent, ref, onMounted, computed } from 'vue'
import apiClient from '../../api'
import { ElMessage } from 'element-plus'

export default defineComponent({
  name: 'VirtualParts',
  
  setup() {
    const virtualParts = ref([])
    const loading = ref(false)
    const currentPage = ref(1)
    const pageSize = ref(20)
    const searchQuery = ref('')
    
    const filteredVirtualParts = computed(() => {
      let filtered = virtualParts.value
      
      if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase()
        filtered = filtered.filter(part => 
          part.name.toLowerCase().includes(query) || 
          part.bitrix_id.toString().includes(query)
        )
      }
      
      // Применяем пагинацию
      const startIndex = (currentPage.value - 1) * pageSize.value
      return filtered.slice(startIndex, startIndex + pageSize.value)
    })
    
    const totalFilteredItems = computed(() => {
      if (!searchQuery.value) {
        return virtualParts.value.length
      }
      
      const query = searchQuery.value.toLowerCase()
      return virtualParts.value.filter(part => 
        part.name.toLowerCase().includes(query) || 
        part.bitrix_id.toString().includes(query)
      ).length
    })
    
    const fetchVirtualParts = async () => {
      loading.value = true
      try {
        const response = await apiClient.get('/virtual-parts')
        virtualParts.value = response.data
      } catch (error) {
        ElMessage.error('Ошибка при загрузке виртуальных деталей')
        console.error(error)
      } finally {
        loading.value = false
      }
    }
    
    const importVirtualParts = async () => {
      loading.value = true
      try {
        const response = await apiClient.post('/virtual-parts/import/')
        ElMessage.success(`Импорт завершен: добавлено ${response.data.imported}, обновлено ${response.data.updated}`)
        fetchVirtualParts()
      } catch (error) {
        ElMessage.error('Ошибка при импорте виртуальных деталей')
        console.error(error)
      } finally {
        loading.value = false
      }
    }
    
    const handlePageChange = (page) => {
      currentPage.value = page
    }
    
    const handleSearch = () => {
      currentPage.value = 1 // Сбрасываем на первую страницу при поиске
    }
    
    onMounted(() => {
      fetchVirtualParts()
    })
    
    return {
      virtualParts,
      filteredVirtualParts,
      loading,
      currentPage,
      pageSize,
      searchQuery,
      totalFilteredItems,
      importVirtualParts,
      handlePageChange,
      handleSearch
    }
  }
})
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

.pagination {
  margin-top: 20px;
  display: flex;
  justify-content: center;
}
</style> 