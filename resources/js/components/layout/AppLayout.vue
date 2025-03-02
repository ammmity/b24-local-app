<template>
  <el-container class="app-container">
    <el-aside width="auto">
      <Sidebar v-if="!dealId"/>
    </el-aside>
    <el-container>
      <el-main>
        <router-view></router-view>
      </el-main>
    </el-container>
  </el-container>
</template>

<script>
import { defineComponent, inject } from 'vue';
import Sidebar from './Sidebar.vue';

export default defineComponent({
  name: 'AppLayout',
  components: {
    Sidebar
  },
  setup() {
    const dealId = inject('dealId');

    return {
      dealId
    };
  }
});
</script>

<style>
/* Глобальные стили */
html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  width: 100%;
}

#app {
  height: 100%;
  width: 100% !important; /* Принудительно установить полную ширину */
  max-width: none !important; /* Убрать ограничение максимальной ширины */
  margin: 0 !important;
  padding: 0 !important;
}
</style>

<style scoped>
.app-container {
  min-height: 100vh;
  width: 100%;
  margin: 0;
  padding: 0;
}

.el-aside {
  background-color: var(--el-menu-bg-color);
  padding: 0;
}
</style>
