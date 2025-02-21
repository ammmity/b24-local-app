<template>
  <div>
    <h1>Главная</h1>
    <router-link to="/app/settings/">Настройки</router-link>
  </div>
</template>

<script>
import { defineComponent } from 'vue';

export default defineComponent({
  name: 'Home',
});
</script>

<style scoped>
/* Ваши стили для компонента Home */
h1 {
  color: #42b983;
}
</style>
