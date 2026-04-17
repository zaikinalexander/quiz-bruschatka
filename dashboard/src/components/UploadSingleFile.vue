<script setup>
import { ref } from 'vue';
import http from '@/helpers/http.js';

const emit = defineEmits(['update:modelValue']);

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: 'Загрузить изображение',
  },
});

const isUploading = ref(false);
const inputId = `upload-${Math.random().toString(36).slice(2)}`;

function onChange(event) {
  const file = event.target.files?.[0];

  if (!file) {
    return;
  }

  const formData = new FormData();
  formData.append('file', file);

  isUploading.value = true;

  http.post('/file/upload', formData)
    .then((response) => {
      const image = response.data?.result?.image?.[0];

      if (image?.source) {
        emit('update:modelValue', image.source);
      }
    })
    .finally(() => {
      isUploading.value = false;
      event.target.value = '';
    });
}

function remove() {
  emit('update:modelValue', '');
}
</script>

<template>
  <div class="upload-single-file">
    <input :id="inputId" type="file" accept="image/*" hidden @change="onChange">

    <div v-if="modelValue" class="upload-single-file__preview">
      <img :src="modelValue" alt="Предпросмотр">
    </div>

    <div class="upload-single-file__actions">
      <label class="btn btn--ghost" :for="inputId">
        {{ isUploading ? 'Загружаем...' : label }}
      </label>

      <button v-if="modelValue" class="btn btn--text" type="button" @click="remove">
        Убрать
      </button>
    </div>

    <p v-if="modelValue" class="upload-single-file__path">{{ modelValue }}</p>
  </div>
</template>

