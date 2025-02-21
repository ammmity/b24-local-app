<template>
  <div class="product-production">
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="140px"
      class="production-form"
    >
      <el-form-item label="Продукт" prop="product_id">
        <el-select
          v-model="form.product_id"
          placeholder="Выберите продукт"
          filterable
        >
          <el-option
            v-for="product in products"
            :key="product.id"
            :label="product.name"
            :value="product.id"
          />
        </el-select>
      </el-form-item>

      <el-form-item label="Количество" prop="quantity">
        <el-input-number
          v-model="form.quantity"
          :min="1"
          controls-position="right"
        />
      </el-form-item>

      <el-form-item label="Дата производства" prop="production_date">
        <el-date-picker
          v-model="form.production_date"
          type="date"
          placeholder="Выберите дату"
          format="DD.MM.YYYY"
        />
      </el-form-item>

      <el-form-item label="Комментарий" prop="comment">
        <el-input
          v-model="form.comment"
          type="textarea"
          rows="3"
          placeholder="Дополнительная информация"
        />
      </el-form-item>

      <el-form-item>
        <el-button type="primary" @click="submitForm">Создать</el-button>
        <el-button @click="resetForm">Сбросить</el-button>
      </el-form-item>
    </el-form>
  </div>
</template>

<script>
import { defineComponent, ref, onMounted } from 'vue';
import apiClient from '../../api';

export default defineComponent({
  name: 'ProductProduction',
  props: {
    productId: {
      type: [Number, String],
      required: true
    }
  },
  emits: ['success'],
  setup(props, { emit }) {
    const formRef = ref(null);
    const products = ref([]);

    const form = ref({
      product_id: '',
      quantity: 1,
      production_date: '',
      comment: ''
    });

    const rules = {
      product_id: [
        { required: true, message: 'Выберите продукт', trigger: 'change' }
      ],
      quantity: [
        { required: true, message: 'Укажите количество', trigger: 'change' },
        { type: 'number', min: 1, message: 'Минимальное количество 1', trigger: 'change' }
      ],
      production_date: [
        { required: true, message: 'Выберите дату производства', trigger: 'change' }
      ]
    };

    const fetchProduct = async () => {
      try {
        const response = await apiClient.get(`/products/${props.productId}`);
        products.value = [response.data];
        form.value.product_id = response.data.id;
      } catch (error) {
        console.error('Ошибка при загрузке продукта:', error);
        ElMessage.error('Ошибка при загрузке продукта');
      }
    };

    const submitForm = async () => {
      if (!formRef.value) return;

      await formRef.value.validate(async (valid) => {
        if (valid) {
          try {
            await apiClient.post('/production', form.value);
            ElMessage.success('Производство успешно создано');
            emit('success');
            resetForm();
          } catch (error) {
            ElMessage.error('Ошибка при создании производства');
          }
        }
      });
    };

    const resetForm = () => {
      if (formRef.value) {
        formRef.value.resetFields();
      }
    };

    onMounted(fetchProduct);

    return {
      formRef,
      form,
      rules,
      products,
      submitForm,
      resetForm
    };
  }
});
</script>

<style scoped>
.production-form {
  margin-top: 20px;
  background: #fff;
  padding: 20px;
  border-radius: 4px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
}
</style>
