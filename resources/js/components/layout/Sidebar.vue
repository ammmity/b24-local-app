<template>
  <el-menu
    :default-active="activeRoute"
    class="sidebar-menu"
    :collapse="isCollapse"
  >
    <el-menu-item :index="basePath" @click="$router.push(basePath)">
      <el-icon><Goods /></el-icon>
      <template #title>Товары</template>
    </el-menu-item>

    <el-menu-item
      :index="basePath + 'details'"
      @click="$router.push(basePath + 'details')"
    >
      <el-icon><Tickets /></el-icon>
      <template #title>Комплектующие</template>
    </el-menu-item>

    <el-menu-item
      v-if="dealId"
      :index="basePath + 'deal-production-scheme'"
      @click="$router.push(basePath + 'deal-production-scheme')"
    >
      <el-icon><Tickets /></el-icon>
      <template #title>Процесс производства</template>
    </el-menu-item>

    <el-menu-item
      :index="basePath + 'operation-types'"
      @click="$router.push(basePath + 'operation-types')"
    >
      <el-icon><Setting /></el-icon>
      <template #title>Типы операций</template>
    </el-menu-item>

    <el-menu-item
      :index="basePath + 'operation-prices'"
      @click="$router.push(basePath + 'operation-prices')"
    >
      <el-icon><PriceTag /></el-icon>
      <template #title>Цены операций</template>
    </el-menu-item>

    <el-menu-item
      :index="basePath + 'operation-logs'"
      @click="$router.push(basePath + 'operation-logs')"
    >
      <el-icon><List /></el-icon>
      <template #title>Журнал работ</template>
    </el-menu-item>

    <el-menu-item
      :index="basePath + 'employee-report'"
      @click="$router.push(basePath + 'employee-report')"
    >
      <el-icon><Document /></el-icon>
      <template #title>Отчет по сотрудникам</template>
    </el-menu-item>

    <el-menu-item
      :index="basePath + 'virtual-parts'"
      @click="$router.push(basePath + 'virtual-parts')"
    >
      <el-icon><Box /></el-icon>
      <template #title>Виртуальные детали</template>
    </el-menu-item>

  </el-menu>
</template>

<script>
import {defineComponent, ref, computed, inject} from 'vue';
import { useRoute } from 'vue-router';
import {
  DataLine,
  Tickets,
  Setting,
  PriceTag,
  List,
  Goods,
  Document,
  Box
} from '@element-plus/icons-vue';

export default defineComponent({
  name: 'Sidebar',
  components: {
    DataLine,
    Tickets,
    Setting,
    PriceTag,
    List,
    Goods,
    Document,
    Box
  },
  setup() {
    const route = useRoute();
    const isCollapse = ref(false);
    const dealId = inject('dealId');

    // Получаем базовый путь из env
    const basePath = computed(() => import.meta.env.DEV ? import.meta.env.VITE_APP_BASE_PATH : import.meta.env.VITE_APP_BASE_PATH + 'app/');

    // Обновляем activeRoute с учетом базового пути
    const activeRoute = computed(() => {
      return route.path.replace(basePath.value, '');
    });

    return {
      isCollapse,
      activeRoute,
      dealId,
      basePath
    };
  }
});
</script>

<style scoped>
.sidebar-menu {
  height: 100vh;
  border-right: solid 1px var(--el-menu-border-color);
}

.sidebar-menu:not(.el-menu--collapse) {
  width: 250px;
}
</style>
