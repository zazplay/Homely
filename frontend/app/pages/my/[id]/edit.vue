<script setup lang="ts">
import type { Apartment, ApartmentPayload, FieldErrors } from '~/types/api'

definePageMeta({ middleware: 'realtor' })

const route = useRoute()
const api = useApi()
const auth = useAuthStore()

const { data: apartment, error, refresh } = await useAsyncData(
  `edit-apartment-${route.params.id}`,
  () => api<Apartment>(`/apartments/${route.params.id}`),
)

if (error.value || (apartment.value && !auth.owns(apartment.value.realtor.id))) {
  throw createError({ statusCode: error.value?.statusCode ?? 403, fatal: true })
}

useHead({ title: 'Edit listing — Homely' })

// --- Listing fields (PATCH) ---
const errors = ref<FieldErrors>({})
const message = ref('')
const saving = ref(false)
const saved = ref(false)

async function save(payload: ApartmentPayload) {
  saving.value = true
  saved.value = false
  errors.value = {}
  message.value = ''

  try {
    apartment.value = await api<Apartment>(`/apartments/${route.params.id}`, { method: 'PATCH', body: payload })
    saved.value = true
  } catch (e) {
    errors.value = fieldErrors(e)
    message.value = errorMessage(e)
  } finally {
    saving.value = false
  }
}

// --- Photos: separate endpoints ---
const newPhotos = ref<string[]>([])
const photoErrors = ref<FieldErrors>({})
const uploading = ref(false)

async function uploadPhotos() {
  uploading.value = true
  photoErrors.value = {}

  try {
    apartment.value = await api<Apartment>(`/apartments/${route.params.id}/photos`, {
      method: 'POST',
      body: { photos: newPhotos.value },
    })
    newPhotos.value = []
  } catch (e) {
    photoErrors.value = fieldErrors(e)
  } finally {
    uploading.value = false
  }
}

async function deletePhoto(photoId: number) {
  if (!confirm('Delete this photo?')) return

  await api(`/apartments/${route.params.id}/photos/${photoId}`, { method: 'DELETE' })
  await refresh()
}
</script>

<template>
  <div v-if="apartment" class="mx-auto flex max-w-[860px] flex-col gap-4">
    <NuxtLink :to="`/apartments/${apartment.id}`" class="text-sm text-sage">← Back to listing</NuxtLink>
    <h1 class="m-0 text-[clamp(32px,4vw,46px)] font-extrabold tracking-[-.035em]">Edit listing</h1>

    <p v-if="message" class="m-0 rounded-2xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">{{ message }}</p>
    <p v-if="saved" class="m-0 rounded-2xl border border-[#b8dc94] bg-[#eef8e2] p-3 text-sm font-semibold text-leaf">✓ Saved.</p>

    <div class="panel p-6 sm:p-7">
      <ApartmentForm :initial="apartment" :submitting="saving" :errors="errors" @submit="save" />
    </div>

    <div class="panel flex flex-col gap-4 p-6 sm:p-7">
      <h2 class="m-0 text-[22px] font-extrabold tracking-[-.02em]">Photos ({{ apartment.photos.length }})</h2>

      <div v-if="apartment.photos.length" class="grid grid-cols-3 gap-3 sm:grid-cols-5">
        <div v-for="photo in apartment.photos" :key="photo.id" class="relative">
          <img :src="photo.url" alt="" class="aspect-square w-full rounded-2xl border-[3px] border-white object-cover shadow-[0_10px_20px_-14px_rgba(50,90,20,.5)]">
          <button
            class="absolute right-1 top-1 grid size-6 cursor-pointer place-items-center rounded-full bg-black/60 text-xs text-white"
            aria-label="Delete photo"
            @click="deletePhoto(photo.id)"
          >
            ✕
          </button>
        </div>
      </div>

      <div class="border-t border-line pt-4">
        <span class="label">Add photos</span>
        <PhotoPicker v-model="newPhotos" :max="20 - apartment.photos.length" :errors="photoErrors" />
        <button v-if="newPhotos.length" class="btn-primary mt-3" :disabled="uploading" @click="uploadPhotos">
          {{ uploading ? 'Uploading…' : `Upload ${newPhotos.length} photo(s)` }}
        </button>
      </div>
    </div>
  </div>
</template>
