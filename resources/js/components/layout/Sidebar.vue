<template>
  <el-menu
    :default-active="activeRoute"
    class="sidebar-menu"
    :collapse="isCollapse"
  >
    <el-menu-item index="/app/" @click="$router.push('/app/')">
      <el-icon><DataLine /></el-icon>
      <template #title>Главная</template>
    </el-menu-item>

    <el-menu-item index="/app/details/" @click="$router.push('/app/details/')">
      <el-icon><Tickets /></el-icon>
      <template #title>Комплектующие</template>
    </el-menu-item>

    <el-menu-item v-if="dealId" index="/app/deal-production-scheme/" @click="$router.push('/app/deal-production-scheme/')">
      <el-icon><Tickets /></el-icon>
      <template #title>Процесс производства</template>
    </el-menu-item>

    <el-menu-item index="/app/operation-types/" @click="$router.push('/app/operation-types/')">
      <el-icon><Setting /></el-icon>
      <template #title>Типы операций</template>
    </el-menu-item>

    <el-menu-item index="/app/settings/" @click="$router.push('/app/settings/')">
      <el-icon><Setting /></el-icon>
      <template #title>Настройки</template>
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
} from '@element-plus/icons-vue';

export default defineComponent({
  name: 'Sidebar',
  components: {
    DataLine,
    Tickets,
    Setting,
  },
  setup() {
    const route = useRoute();
    const isCollapse = ref(false);
    const dealId = inject('dealId');

    const activeRoute = computed(() => route.path);

    return {
      isCollapse,
      activeRoute,
      dealId
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
