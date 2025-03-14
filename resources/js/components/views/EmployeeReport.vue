<template>
  <div>
    <h3>Отчет по сотрудникам</h3>

    <!-- Фильтры -->
    <div class="filters-container">
      <el-form :inline="true" :model="filters">
        <el-form-item label="Период">
          <el-date-picker
            v-model="dateRange"
            type="daterange"
            range-separator="по"
            start-placeholder="Начало"
            end-placeholder="Конец"
            format="DD.MM.YYYY"
            value-format="YYYY-MM-DD"
          />
        </el-form-item>

        <el-form-item label="Сотрудник">
          <el-select
            v-model="filters.userId"
            clearable
            style="width: 300px;"
            placeholder="Выберите сотрудника"
          >
            <el-option
              v-for="user in users"
              :key="user.id"
              :label="user.name"
              :value="user.id"
            />
          </el-select>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="generateReport">
            Сформировать отчет
          </el-button>
          <el-button @click="exportToExcel">
            Экспорт в Excel
          </el-button>
        </el-form-item>
      </el-form>
    </div>

    <!-- Сводная информация -->
    <div v-if="report.summary" class="summary-container">
      <h4>Итого по сотруднику:</h4>
      <el-table :data="summaryData" border>
        <el-table-column prop="employee" label="Сотрудник" />
        <el-table-column prop="amount" label="Сумма">
          <template #default="{ row }">
            {{ formatCurrency(row.amount) }}
          </template>
        </el-table-column>
      </el-table>
    </div>

    <!-- Детальная информация -->
    <el-table
      v-if="report.details"
      :data="report.details"
      border
      style="margin-top: 20px;"
    >
      <el-table-column prop="date" label="Дата" />
      <el-table-column prop="employee" label="Сотрудник" />
      <el-table-column prop="detail" label="Деталь" />
      <el-table-column prop="quantity" label="Количество" align="right" />
      <el-table-column prop="price" label="Цена" align="right">
        <template #default="{ row }">
          {{ formatCurrency(row.price) }}
        </template>
      </el-table-column>
      <el-table-column prop="amount" label="Сумма" align="right">
        <template #default="{ row }">
          {{ formatCurrency(row.amount) }}
        </template>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
import { defineComponent, ref, computed, onMounted } from 'vue'
import apiClient from '../../api'
import { ElMessage } from 'element-plus'
import * as XLSX from 'xlsx'

export default defineComponent({
  name: 'EmployeeReport',

  setup() {
    const filters = ref({
      userId: null
    })
    const dateRange = ref([])
    const report = ref({ details: [], summary: {} })
    const users = ref([])

    const generateReport = async () => {
      try {
        const response = await apiClient.get('/reports/employee-operations', {
          params: {
            startDate: dateRange.value[0],
            endDate: dateRange.value[1],
            userId: filters.value.userId
          }
        })
        report.value = response.data
      } catch (error) {
        ElMessage.error('Ошибка при формировании отчета')
      }
    }

    const exportToExcel = () => {
      // Подготовка данных для детального отчета с русскими заголовками
      const detailsData = report.value.details.map(item => ({
        'Дата': item.date,
        'Сотрудник': item.employee,
        'Деталь': item.detail,
        'Количество': item.quantity,
        'Цена': item.price,
        'Сумма': item.amount
      }))

      // Подготовка данных для сводного отчета с русскими заголовками
      const summaryData = Object.entries(report.value.summary || {}).map(([employee, amount]) => ({
        'Сотрудник': employee,
        'Итоговая сумма': amount
      }))

      const wb = XLSX.utils.book_new()

      // Детальный отчет
      const wsDetails = XLSX.utils.json_to_sheet(detailsData)
      XLSX.utils.book_append_sheet(wb, wsDetails, 'Детализация')

      // Сводный отчет
      const wsSummary = XLSX.utils.json_to_sheet(summaryData)
      XLSX.utils.book_append_sheet(wb, wsSummary, 'Итоги')

      XLSX.writeFile(wb, `Отчет_по_сотруднику_${new Date().toLocaleDateString()}.xlsx`)
    }

    const formatCurrency = (value) => {
      return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
      }).format(value)
    }

    const summaryData = computed(() => {
      return Object.entries(report.value.summary || {}).map(([employee, amount]) => ({
        employee,
        amount
      }))
    })

    const fetchUsers = async () => {
      try {
        const response = await apiClient.get('/reports/operation-users')
        users.value = response.data.map(user => ({
          id: user.userId,
          name: user.username
        }))
      } catch (error) {
        ElMessage.error('Ошибка при загрузке списка сотрудников')
      }
    }

    onMounted(() => {
      fetchUsers()
    })

    return {
      filters,
      dateRange,
      report,
      users,
      generateReport,
      exportToExcel,
      formatCurrency,
      summaryData
    }
  }
})
</script>

<style scoped>
.filters-container {
  margin-bottom: 20px;
}

.summary-container {
  margin: 20px 0;
}
</style>
