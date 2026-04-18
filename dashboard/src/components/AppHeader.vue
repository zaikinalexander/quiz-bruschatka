<template>
  <header class="app-header">
    <div>
      <p class="app-header__eyebrow">Quiz 2</p>
      <h1 class="app-header__title">Админка квиза</h1>
    </div>

    <div class="app-header__actions">
      <a class="app-header__link" href="/" target="_blank" rel="noreferrer">Открыть сайт</a>
      <button class="app-header__link app-header__link--button" type="button" @click="$emit('logout')">Выйти</button>
    </div>
  </header>
</template>

<script setup>
defineEmits(['logout']);
</script>
