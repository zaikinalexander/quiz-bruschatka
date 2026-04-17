<script setup>
import { onMounted, ref } from 'vue';
import http from '@/helpers/http.js';

const slides = ref([]);
const isLoading = ref(true);

onMounted(async () => {
  const response = await http.get('/slides');
  slides.value = response.data.result.slides || [];
  isLoading.value = false;
});
</script>

<template>
  <section class="panel">
    <div class="panel__header">
      <div>
        <p class="panel__eyebrow">Контент</p>
        <h2 class="panel__title">Слайды квиза</h2>
      </div>
    </div>

    <p v-if="isLoading" class="panel__text">Загрузка...</p>

    <div v-else class="card-grid">
      <article v-for="slide in slides" :key="slide.id" class="card">
        <div class="card__media" :style="{ backgroundImage: `url(${slide.backgroundImage})` }"></div>
        <p class="card__meta">{{ slide.flow === 'mobile' ? 'Мобильный' : 'Десктоп' }} / {{ slide.counter }}</p>
        <h3 class="card__title">{{ slide.title }}</h3>
        <p class="card__text">ID: {{ slide.id }}</p>
        <router-link class="btn" :to="{ name: 'slide_edit', params: { id: slide.id } }">
          Редактировать
        </router-link>
      </article>
    </div>
  </section>
</template>
