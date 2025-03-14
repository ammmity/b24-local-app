<template>
  <div>
    <h3>Товары</h3>
    <div class="header-actions">
      <div class="sync-container">
        <el-button
          type="primary"
          @click="syncGoods"
          :loading="isSyncing"
        >
          Синхронизировать товары
        </el-button>
      </div>

      <el-input
        v-model="searchQuery"
        placeholder="Поиск товаров"
        @input="debouncedSearch"
      />
    </div>

    <el-table
      v-if="goods"
      :data="goods"
      style="width: 100%; margin-top: 20px;"
    >
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="Название" />
      <el-table-column prop="bitrix_id" label="Артикул" width="120" />
      <el-table-column label="Статус" width="180">
        <template #default="{ row }">
          <el-tag :type="getStatusType(row)">
            {{ getStatusText(row) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="Действия" width="200">
        <template #default="{ row }">
          <el-button
            type="primary"
            size="small"
            @click="openPartsModal(row)"
          >
            Комплектующие
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <!-- Модальное окно комплектующих -->
    <el-dialog
      v-model="showPartsModal"
      title="Настройка комплектующих"
      width="70%"
      destroy-on-close
    >
      <div v-if="selectedGood">
        <div class="parts-form">
          <div class="parts-list">
            <div 
              v-for="(part, index) in goodParts" 
              :key="index" 
              class="part-item"
            >
              <el-select
                v-model="part.product_part_id"
                filterable
                placeholder="Выберите комплектующую"
                style="width: 300px;"
                disabled
              >
                <el-option
                  v-for="item in availableParts"
                  :key="item.id"
                  :label="item.name"
                  :value="item.id"
                />
              </el-select>
              
              <el-input-number
                v-model="part.quantity"
                :min="0"
                controls-position="right"
                style="width: 120px; margin: 0 10px;"
              />
            </div>
          </div>
        </div>

        <div class="dialog-footer">
          <el-button @click="showPartsModal = false">Отмена</el-button>
          <el-button
            type="primary"
            @click="saveGoodParts"
            :loading="isSaving"
          >
            Сохранить
          </el-button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue'
import apiClient from '../../api'
import { ElMessage } from 'element-plus'

export default defineComponent({
  name: 'Goods',
  
  setup() {
    const goods = ref(null)
    const error = ref(null)
    const searchQuery = ref('')
    const isSyncing = ref(false)
    const showPartsModal = ref(false)
    const selectedGood = ref(null)
    const goodParts = ref([])
    const availableParts = ref([])
    const isSaving = ref(false)

    const fetchGoods = async () => {
      try {
        const response = await apiClient.get('/goods')
        goods.value = response.data
      } catch (err) {
        error.value = 'Ошибка при получении данных: ' + err.message
      }
    }

    const fetchAvailableParts = async () => {
      try {
        const response = await apiClient.get('/product-parts')
        availableParts.value = response.data
      } catch (err) {
        error.value = 'Ошибка при получении комплектующих: ' + err.message
      }
    }

    const syncGoods = async () => {
      isSyncing.value = true
      try {
        await apiClient.get('/goods/import/')
        ElMessage.success('Синхронизация успешно завершена')
        await fetchGoods()
      } catch (error) {
        ElMessage.error('Ошибка при синхронизации: ' + (error.response?.data?.error || error.message))
      } finally {
        isSyncing.value = false
      }
    }

    const searchTimeout = ref(null)
    const debouncedSearch = () => {
      if (searchTimeout.value) {
        clearTimeout(searchTimeout.value)
      }
      searchTimeout.value = setTimeout(() => {
        searchGoods()
      }, 300)
    }

    const searchGoods = async () => {
      try {
        const response = await apiClient.get('/goods', {
          params: {
            name: searchQuery.value
          }
        })
        goods.value = response.data
      } catch (err) {
        error.value = 'Ошибка при поиске данных: ' + err.message
      }
    }

    const openPartsModal = (good) => {
      selectedGood.value = good
      goodParts.value = good.parts?.map(part => ({
        product_part_id: part.product_part.id,
        quantity: part.quantity
      })) || []
      showPartsModal.value = true
    }

    const saveGoodParts = async () => {
      if (!selectedGood.value) return

      isSaving.value = true
      try {
        await apiClient.put(`/goods/${selectedGood.value.id}`, {
          parts: goodParts.value
        })
        
        ElMessage.success('Комплектующие успешно сохранены')
        showPartsModal.value = false
        await fetchGoods()
      } catch (error) {
        ElMessage.error('Ошибка при сохранении: ' + (error.response?.data?.error || error.message))
      } finally {
        isSaving.value = false
      }
    }

    const getStatusType = (row) => {
      return row.parts.some(part => part.quantity === 0) ? 'danger' : 'success'
    }

    const getStatusText = (row) => {
      return row.parts.some(part => part.quantity === 0) ? 'Не настроено кол-во' : 'Товар настроен'
    }

    onMounted(async () => {
      await Promise.all([
        fetchGoods(),
        fetchAvailableParts()
      ])
    })

    return {
      goods,
      error,
      searchQuery,
      debouncedSearch,
      isSyncing,
      syncGoods,
      showPartsModal,
      selectedGood,
      goodParts,
      availableParts,
      openPartsModal,
      saveGoodParts,
      isSaving,
      getStatusType,
      getStatusText
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

.error-message {
  color: red;
  margin-top: 10px;
}

.parts-form {
  margin: 20px 0;
}

.parts-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.part-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.dialog-footer {
  margin-top: 20px;
  text-align: right;
}
</style> 