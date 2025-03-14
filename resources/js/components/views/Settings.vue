<template>
  <div>
    <div class="header-actions">
      <div class="content-placeholder">
        <el-empty description="Раздел находится в разработке"></el-empty>
      </div>
    </div>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'Settings',
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
