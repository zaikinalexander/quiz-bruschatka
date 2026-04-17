import { createRouter, createWebHistory } from 'vue-router';
import Home from '@/views/Home.vue';
import LeadList from '@/views/Leads/LeadList.vue';
import SlideList from '@/views/Slides/SlideList.vue';
import SlideEdit from '@/views/Slides/SlideEdit.vue';
import QuizSettings from '@/views/Settings/QuizSettings.vue';

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: Home,
    },
    {
      path: '/slides',
      name: 'slides',
      component: SlideList,
    },
    {
      path: '/leads',
      name: 'leads',
      component: LeadList,
    },
    {
      path: '/slide/:id',
      name: 'slide_edit',
      component: SlideEdit,
    },
    {
      path: '/settings',
      name: 'settings',
      component: QuizSettings,
    },
  ],
});

export default router;
