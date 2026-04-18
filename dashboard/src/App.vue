<script setup>
import { onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import AppHeader from '@/components/AppHeader.vue';
import Sidebar from '@/components/Sidebar.vue';
import http from '@/helpers/http';

const isReady = ref(false);
const isAuthenticated = ref(false);
const isSubmitting = ref(false);
const errorMessage = ref('');
const credentials = reactive({
  username: '',
  password: '',
});

async function refreshSession() {
  try {
    const { data } = await http.get('/admin/me');
    isAuthenticated.value = data?.result?.authenticated === true;
  } catch (error) {
    isAuthenticated.value = false;
  } finally {
    isReady.value = true;
  }
}

async function login() {
  errorMessage.value = '';
  isSubmitting.value = true;

  try {
    const { data } = await http.post('/admin/login', credentials);
    isAuthenticated.value = data?.result?.authenticated === true;
    credentials.password = '';
  } catch (error) {
    errorMessage.value = error?.response?.data?.errors?.auth || 'Не удалось войти в админку';
  } finally {
    isSubmitting.value = false;
  }
}

async function logout() {
  try {
    await http.post('/admin/logout');
  } finally {
    isAuthenticated.value = false;
    credentials.password = '';
  }
}

function handleUnauthorized() {
  isAuthenticated.value = false;
}

onMounted(() => {
  window.addEventListener('admin:unauthorized', handleUnauthorized);
  refreshSession();
});

onBeforeUnmount(() => {
  window.removeEventListener('admin:unauthorized', handleUnauthorized);
});
</script>

<template>
  <div v-if="!isReady" class="auth-screen">
    <div class="auth-card">
      <p class="app-header__eyebrow">Quiz 2</p>
      <h1 class="auth-card__title">Проверяем доступ</h1>
    </div>
  </div>

  <div v-else-if="!isAuthenticated" class="auth-screen">
    <form class="auth-card" @submit.prevent="login">
      <p class="app-header__eyebrow">Quiz 2</p>
      <h1 class="auth-card__title">Вход в админку</h1>
      <p class="auth-card__text">Введите логин и пароль, чтобы открыть заявки и настройки квиза.</p>

      <label class="field">
        <span class="field__label">Логин</span>
        <input v-model.trim="credentials.username" class="field__input" autocomplete="username" required>
      </label>

      <label class="field">
        <span class="field__label">Пароль</span>
        <input
          v-model="credentials.password"
          class="field__input"
          type="password"
          autocomplete="current-password"
          required
        >
      </label>

      <p v-if="errorMessage" class="auth-card__error">{{ errorMessage }}</p>

      <button class="btn" type="submit" :disabled="isSubmitting">
        {{ isSubmitting ? 'Входим...' : 'Войти' }}
      </button>
    </form>
  </div>

  <div v-else class="dashboard-layout">
    <app-header @logout="logout" />

    <div class="dashboard-layout__body">
      <sidebar />

      <main class="dashboard-layout__content">
        <router-view />
      </main>
    </div>
  </div>
</template>
