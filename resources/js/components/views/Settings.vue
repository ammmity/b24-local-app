<template>
  <div>
    <h1>Настройки</h1>
    <router-link to="/app/">Главная</router-link>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'Deal',
});
</script>

<style scoped>
/* Ваши стили для компонента Home */
h1 {
  color: #42b983;
}
</style>
