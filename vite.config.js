import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  base: '/app/',
  root: 'resources/js', // Укажите новую корневую папку
  plugins: [vue()],
  server: {
    port: 3003, // Укажите желаемый порт
  },
  build: {
    outDir: '../../public/dist', // Укажите выходную папку для сборки
  },
});
