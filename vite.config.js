import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'
// import path from 'path';
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers'

// https://vite.dev/config/

export default defineConfig(({ command, mode }) => {
  const env = loadEnv(mode, process.cwd());
  const isProd = mode === 'production';
  return {
    base: isProd ? env.VITE_APP_BASE_PATH + 'dist/' : env.VITE_APP_BASE_PATH,
    root: 'resources/js', // Укажите новую корневую папку
    plugins: [
      vue(),
      AutoImport({
        resolvers: [ElementPlusResolver()],
      }),
      Components({
        resolvers: [ElementPlusResolver()],
      }),
    ],
    define: {
      __VUE_OPTIONS_API__: true,
      __VUE_PROD_DEVTOOLS__: false,
      'import.meta.env.VITE_APP_API_URL': JSON.stringify(env.VITE_APP_API_URL),
      'import.meta.env.VITE_APP_BASE_PATH': JSON.stringify(env.VITE_APP_BASE_PATH),
      'import.meta.env.VITE_APP_ENV': JSON.stringify(env.VITE_APP_ENV),
    },
    server: {
      // port: 3000, // prod
      proxy: {
        '/api': {
          target: isProd ? env.VITE_APP_API_URL : 'http://localhost:8080',
          changeOrigin: true,
          // rewrite: (path) => path.replace(/^\/api/, ''),
        },
      },
    },
    build: {
      outDir: '../../public/dist', // Укажите выходную папку для сборки
      // minify: isProd ? 'terser' : false,
      // sourcemap: !isProd,
      // terserOptions: isProd ? {
      //   compress: {
      //     drop_console: true,
      //     drop_debugger: true,
      //   }
      // } : {},
      // rollupOptions: {
      //   output: {
      //     manualChunks: {
      //       'vue-vendor': ['vue', 'vue-router'],
      //       'element-plus': ['element-plus', '@element-plus/icons-vue'],
      //     },
      //     // Настройка имен файлов для лучшего кэширования
      //     entryFileNames: isProd ? 'assets/[name].[hash].js' : 'assets/[name].js',
      //     chunkFileNames: isProd ? 'assets/[name].[hash].js' : 'assets/[name].js',
      //     assetFileNames: isProd ? 'assets/[name].[hash].[ext]' : 'assets/[name].[ext]',
      //   }
      // }
    },
    // resolve: {
    //   alias: {
    //     '@': path.resolve(__dirname, 'resources/js'),
    //   },
    // },
  };
});
