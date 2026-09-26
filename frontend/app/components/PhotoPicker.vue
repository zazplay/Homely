<script setup lang="ts">
const MAX_BYTES = 5 * 1024 * 1024 // same limit as the API
const ALLOWED = ['image/jpeg', 'image/png', 'image/webp']

const props = withDefaults(defineProps<{ max?: number, errors?: Record<string, string> }>(), {
  max: 20,
  errors: () => ({}),
})

/** Selected photos as base64 data URIs — exactly what the API expects. */
const photos = defineModel<string[]>({ default: () => [] })

const localError = ref('')

async function onSelect(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  localError.value = ''

  for (const file of files) {
    if (photos.value.length >= props.max) {
      localError.value = `You can add up to ${props.max} photos.`
      break
    }
    if (!ALLOWED.includes(file.type)) {
      localError.value = `${file.name}: only JPEG, PNG or WebP.`
      continue
    }
    if (file.size > MAX_BYTES) {
      localError.value = `${file.name}: larger than 5 MB.`
      continue
    }
    photos.value = [...photos.value, await fileToBase64(file)]
  }

  input.value = '' // allow selecting the same file again
}

function remove(index: number) {
  photos.value = photos.value.filter((_, i) => i !== index)
}
</script>

<template>
  <div>
    <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
      <div v-for="(photo, index) in photos" :key="index" class="relative">
        <img
          :src="photo"
          alt=""
          class="aspect-square w-full rounded-lg object-cover"
          :class="errors[`photos.${index}`] ? 'ring-2 ring-red-500' : ''"
        >
        <button
          type="button"
          class="absolute right-1 top-1 grid size-6 cursor-pointer place-items-center rounded-full bg-black/60 text-xs text-white"
          aria-label="Remove photo"
          @click="remove(index)"
        >
          ✕
        </button>
        <p v-if="errors[`photos.${index}`]" class="field-error">{{ errors[`photos.${index}`] }}</p>
      </div>

      <label
        v-if="photos.length < max"
        class="grid aspect-square cursor-pointer place-items-center rounded-lg border-2 border-dashed border-[#d3ddcb] text-center text-sm text-sage hover:border-[#8fd84a] hover:text-leaf"
      >
        <span>+ Add<br>photos</span>
        <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="onSelect">
      </label>
    </div>

    <p v-if="localError" class="field-error">{{ localError }}</p>
    <p v-if="errors.photos" class="field-error">{{ errors.photos }}</p>
    <p class="mt-1 text-xs text-sage">JPEG, PNG or WebP, up to 5 MB each. Sent to the API as base64.</p>
  </div>
</template>
