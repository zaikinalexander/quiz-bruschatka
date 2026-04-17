<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import http from '@/helpers/http.js';

const isLoading = ref(true);
const isRefreshing = ref(false);
const errorMessage = ref('');
const leads = ref([]);
let refreshTimer = null;

onMounted(async () => {
  await loadLeads();
  refreshTimer = window.setInterval(() => {
    loadLeads({ silent: true });
  }, 10000);
  window.addEventListener('focus', handleFocus);
});

onBeforeUnmount(() => {
  if (refreshTimer) {
    window.clearInterval(refreshTimer);
  }

  window.removeEventListener('focus', handleFocus);
});

async function loadLeads({ silent = false } = {}) {
  if (silent) {
    isRefreshing.value = true;
  } else {
    isLoading.value = true;
  }

  errorMessage.value = '';

  try {
    const response = await http.get('/leads');
    leads.value = response.data.result.leads ?? [];
  } catch (error) {
    errorMessage.value = 'Не удалось загрузить заявки';
  } finally {
    isLoading.value = false;
    isRefreshing.value = false;
  }
}

function handleFocus() {
  loadLeads({ silent: true });
}

function formatDate(value) {
  if (!value) {
    return '—';
  }

  return new Date(value).toLocaleString('ru-RU');
}

function formatMailStatus(status) {
  if (status === 'sent') {
    return 'Отправлено';
  }

  if (status === 'failed') {
    return 'Ошибка';
  }

  return 'В очереди';
}

function getLeadAnswers(lead) {
  if (Array.isArray(lead.answers) && lead.answers.length) {
    return lead.answers;
  }

  return [lead.purpose, lead.area, lead.crew].filter(Boolean);
}
</script>

<template>
  <section class="panel">
    <div class="panel__header">
      <div>
        <p class="panel__eyebrow">Лиды</p>
        <h2 class="panel__title">Заявки из квиза</h2>
      </div>

      <button class="btn btn--ghost" type="button" :disabled="isLoading || isRefreshing" @click="loadLeads()">
        {{ isRefreshing ? 'Обновляем...' : 'Обновить' }}
      </button>
    </div>

    <p v-if="errorMessage" class="panel__text">{{ errorMessage }}</p>
    <p v-if="isLoading" class="panel__text">Загрузка...</p>
    <p v-else-if="!leads.length" class="panel__text">Заявок пока нет.</p>

    <div v-else class="lead-list">
      <article v-for="lead in leads" :key="lead.number" class="lead-card">
        <div class="lead-card__row">
          <div>
            <p class="lead-card__number">Заявка #{{ lead.number }}</p>
            <h3 class="lead-card__phone">{{ lead.phone }}</h3>
          </div>

          <span class="lead-card__status" :class="`lead-card__status--${lead.mailStatus}`">
            {{ formatMailStatus(lead.mailStatus) }}
          </span>
        </div>

        <div class="lead-card__grid">
          <p v-for="answer in getLeadAnswers(lead)" :key="`${lead.number}-${answer.field || answer.title}`">
            <strong>{{ answer.title || 'Ответ' }}:</strong> {{ answer.label || '—' }}
          </p>
          <p><strong>Дата:</strong> {{ formatDate(lead.createdAt) }}</p>
          <p><strong>Страница:</strong> {{ lead.pageUrl || '—' }}</p>
        </div>

        <p v-if="lead.mailError" class="lead-card__error">
          <strong>Ошибка отправки:</strong> {{ lead.mailError }}
        </p>
      </article>
    </div>
  </section>
</template>
