<script setup lang="ts">
import type { Apartment, ApartmentPayload, FieldErrors } from '~/types/api'

definePageMeta({ middleware: 'realtor' })
useHead({ title: 'New listing — Homely' })

const api = useApi()

const errors = ref<FieldErrors>({})
const message = ref('')
const submitting = ref(false)

async function create(payload: ApartmentPayload) {
  submitting.value = true
  errors.value = {}
  message.value = ''

  try {
    // Photos travel as base64 strings inside the JSON body.
    const apartment = await api<Apartment>('/apartments', { method: 'POST', body: payload })
    await navigateTo(`/apartments/${apartment.id}`)
  } catch (e) {
    errors.value = fieldErrors(e)
    message.value = errorMessage(e)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="mx-auto flex max-w-[860px] flex-col gap-4">
    <NuxtLink to="/my" class="text-sm text-sage">← My listings</NuxtLink>
    <h1 class="m-0 text-[clamp(32px,4vw,46px)] font-extrabold tracking-[-.035em]">New listing</h1>

    <p v-if="message" class="m-0 rounded-2xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ message }}</p>

    <div class="panel p-6 sm:p-7">
      <ApartmentForm with-photos submit-label="Publish listing" :submitting="submitting" :errors="errors" @submit="create" />
    </div>
  </div>
</template>
