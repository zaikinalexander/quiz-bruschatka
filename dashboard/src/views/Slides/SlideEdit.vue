<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import http from '@/helpers/http.js';
import UploadSingleFile from '@/components/UploadSingleFile.vue';

const route = useRoute();
const slide = ref(null);
const isLoading = ref(true);
const isSaving = ref(false);
const saveMessage = ref('');

const isOptionsSlide = computed(() => slide.value?.type === 'options');
const isContactSlide = computed(() => slide.value?.type === 'contact');

onMounted(async () => {
  const response = await http.get(`/slide/${route.params.id}`);
  slide.value = response.data.result.slide;
  isLoading.value = false;
});

function addOption() {
  slide.value.options.push({
    label: 'Новый вариант',
    value: `value-${Date.now()}`,
    image: '',
  });
}

function removeOption(index) {
  slide.value.options.splice(index, 1);
}

async function save() {
  isSaving.value = true;
  saveMessage.value = '';

  await http.post(`/slide/${route.params.id}`, slide.value);

  saveMessage.value = 'Слайд сохранен';
  isSaving.value = false;
}
</script>

<template>
  <section class="panel">
    <div class="panel__header">
      <div>
        <p class="panel__eyebrow">Редактирование</p>
        <h2 class="panel__title">Слайд {{ route.params.id }}</h2>
      </div>

      <button class="btn" type="button" :disabled="isSaving || isLoading" @click="save">
        {{ isSaving ? 'Сохраняем...' : 'Сохранить' }}
      </button>
    </div>

    <p v-if="isLoading" class="panel__text">Загрузка...</p>

    <template v-else>
      <div class="form-grid">
        <label class="field">
          <span class="field__label">Счетчик</span>
          <input v-model="slide.counter" class="field__input" type="text">
        </label>

        <label class="field">
          <span class="field__label">Текст прогресса</span>
          <input v-model="slide.progressText" class="field__input" type="text">
        </label>

        <label class="field field--full">
          <span class="field__label">Заголовок слайда</span>
          <textarea v-model="slide.title" class="field__textarea" rows="2"></textarea>
        </label>

        <label class="field">
          <span class="field__label">Ширина прогресса (%)</span>
          <input v-model.number="slide.progressWidth" class="field__input" type="number" min="0" max="100" step="0.01">
        </label>

        <div class="field field--full">
          <span class="field__label">Фоновая картинка слайда</span>
          <UploadSingleFile v-model="slide.backgroundImage" label="Загрузить фон" />
        </div>
      </div>

      <section v-if="isOptionsSlide" class="subpanel">
        <div class="subpanel__header">
          <h3 class="subpanel__title">Варианты ответа</h3>
          <button class="btn btn--ghost" type="button" @click="addOption">Добавить вариант</button>
        </div>

        <div class="option-editor" v-for="(option, index) in slide.options" :key="`${option.value}-${index}`">
          <label class="field">
            <span class="field__label">Текст</span>
            <input v-model="option.label" class="field__input" type="text">
          </label>

          <label class="field">
            <span class="field__label">Значение</span>
            <input v-model="option.value" class="field__input" type="text">
          </label>

          <div class="field field--full">
            <span class="field__label">Картинка варианта</span>
            <UploadSingleFile v-model="option.image" label="Загрузить изображение" />
          </div>

          <button class="btn btn--text" type="button" @click="removeOption(index)">Удалить вариант</button>
        </div>
      </section>

      <section v-if="isContactSlide" class="subpanel">
        <h3 class="subpanel__title">Контент формы</h3>

        <div class="form-grid">
          <label class="field field--full">
            <span class="field__label">Зеленый лид</span>
            <input v-model="slide.leadTitle" class="field__input" type="text">
          </label>

          <label class="field field--full">
            <span class="field__label">Описание</span>
            <textarea v-model="slide.leadText" class="field__textarea" rows="3"></textarea>
          </label>

          <label class="field">
            <span class="field__label">Подпись поля</span>
            <input v-model="slide.formLabel" class="field__input" type="text">
          </label>

          <label class="field">
            <span class="field__label">Плейсхолдер телефона</span>
            <input v-model="slide.phonePlaceholder" class="field__input" type="text">
          </label>

          <label class="field">
            <span class="field__label">Текст кнопки</span>
            <input v-model="slide.buttonText" class="field__input" type="text">
          </label>

          <label class="field field--full">
            <span class="field__label">Текст под полем телефона</span>
            <textarea v-model="slide.phoneNote" class="field__textarea" rows="2"></textarea>
          </label>

          <label class="field">
            <span class="field__label">Сообщение после отправки</span>
            <input v-model="slide.successMessage" class="field__input" type="text">
          </label>
        </div>
      </section>

      <p v-if="saveMessage" class="panel__success">{{ saveMessage }}</p>
    </template>
  </section>
</template>
