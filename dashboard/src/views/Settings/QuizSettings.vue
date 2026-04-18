<script setup>
import { onMounted, ref } from 'vue';
import http from '@/helpers/http.js';

const general = ref(null);
const leadEmailsInput = ref('');
const isLoading = ref(true);
const isSaving = ref(false);
const saveMessage = ref('');

onMounted(async () => {
  const response = await http.get('/quiz-settings');
  general.value = response.data.result.general;
  leadEmailsInput.value = normalizeLeadEmails(general.value?.leadEmails).join('\n');
  isLoading.value = false;
});

async function save() {
  isSaving.value = true;
  saveMessage.value = '';

  await http.post('/quiz-settings', {
    ...general.value,
    leadEmails: normalizeLeadEmails(leadEmailsInput.value),
  });

  saveMessage.value = 'Настройки сохранены';
  isSaving.value = false;
}

function normalizeLeadEmails(value) {
  if (Array.isArray(value)) {
    return value
      .map((item) => String(item).trim())
      .filter(Boolean);
  }

  return String(value ?? '')
    .split(/[\n,;]+/)
    .map((item) => item.trim())
    .filter(Boolean);
}
</script>

<template>
  <section class="panel">
    <div class="panel__header">
      <div>
        <p class="panel__eyebrow">Общее</p>
        <h2 class="panel__title">Настройки квиза</h2>
      </div>

      <button class="btn" type="button" :disabled="isSaving || isLoading" @click="save">
        {{ isSaving ? 'Сохраняем...' : 'Сохранить' }}
      </button>
    </div>

    <p v-if="isLoading" class="panel__text">Загрузка...</p>

    <div v-else class="form-grid">
      <label class="field field--full">
        <span class="field__label">Верхний заголовок, первая строка</span>
        <input v-model="general.headlineBase" class="field__input" type="text">
      </label>

      <label class="field field--full">
        <span class="field__label">Зеленая строка</span>
        <input v-model="general.headlineAccent" class="field__input" type="text">
      </label>

      <label class="field">
        <span class="field__label">Режим отправки</span>
        <select v-model="general.submitMode" class="field__input">
          <option value="success">Локальный success</option>
          <option value="redirect">Редирект</option>
        </select>
      </label>

      <label class="field">
        <span class="field__label">ID счетчика Метрики</span>
        <input v-model.number="general.metrikaCounterId" class="field__input" type="number" min="1">
      </label>

      <label class="field field--full">
        <span class="field__label">URL редиректа</span>
        <input v-model="general.redirectUrl" class="field__input" type="text">
      </label>

      <label class="field">
        <span class="field__label">Задержка редиректа, мс</span>
        <input v-model.number="general.redirectDelayMs" class="field__input" type="number" min="0" step="500">
      </label>

      <label class="field">
        <span class="field__label">Передавать ответы в URL</span>
        <input v-model="general.redirectAppendParams" class="field__input" type="checkbox">
      </label>

      <label class="field field--full">
        <span class="field__label">Почты для уведомлений</span>
        <textarea
          v-model="leadEmailsInput"
          class="field__textarea"
          rows="5"
          placeholder="zaikinalexandr@gmail.com&#10;info@bruschatka.ru"
        ></textarea>
      </label>
    </div>

    <p v-if="saveMessage" class="panel__success">{{ saveMessage }}</p>
  </section>
</template>
