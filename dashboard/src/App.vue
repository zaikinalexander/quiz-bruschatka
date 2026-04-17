<script setup>
import AppHeader from '@/components/AppHeader.vue';
import Sidebar from '@/components/Sidebar.vue';
</script>

<template>
  <div class="dashboard-layout">
    <app-header />

    <div class="dashboard-layout__body">
      <sidebar />

      <main class="dashboard-layout__content">
        <router-view />
      </main>
    </div>
  </div>
</template>

