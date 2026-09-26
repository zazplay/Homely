<script setup lang="ts">
import type { NuxtError } from '#app'

const props = defineProps<{ error: NuxtError }>()

const title = computed(() => {
  if (props.error.statusCode === 404) return 'Page not found'
  if (props.error.statusCode === 403) return 'You don\'t have access to this page'
  return 'Something went wrong'
})
</script>

<template>
  <NuxtLayout>
    <div class="mx-auto max-w-md py-20 text-center">
      <p class="text-6xl font-bold text-line">{{ error.statusCode }}</p>
      <h1 class="mt-4 text-xl font-semibold">{{ title }}</h1>
      <button class="btn-primary mt-6" @click="clearError({ redirect: '/' })">Back to catalog</button>
    </div>
  </NuxtLayout>
</template>
