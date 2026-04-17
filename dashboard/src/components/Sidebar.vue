<script setup>
const links = [
  { label: 'Главная', route: 'home' },
  { label: 'Заявки', route: 'leads' },
  { label: 'Слайды', route: 'slides' },
  { label: 'Настройки', route: 'settings' },
];
</script>

<template>
  <aside class="sidebar">
    <router-link
      v-for="link in links"
      :key="link.route"
      :to="{ name: link.route }"
      class="sidebar__link"
    >
      {{ link.label }}
    </router-link>
  </aside>
</template>
