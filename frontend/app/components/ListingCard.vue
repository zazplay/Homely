<script setup lang="ts">
import type { Apartment } from '~/types/api'

const props = defineProps<{ apartment: Apartment }>()

const favorites = useFavorites()
const saved = computed(() => favorites.isFavorite(props.apartment.id))
const link = computed(() => `/apartments/${props.apartment.id}`)
</script>

<template>
  <article v-tilt class="card flex flex-col overflow-hidden rounded-3xl">
    <div class="relative mx-2 mt-2 aspect-[4/3] overflow-hidden rounded-[18px] bg-[repeating-linear-gradient(135deg,#e6f0dc_0_11px,#dce8cf_11px_22px)] font-mono">
      <NuxtLink :to="link" class="absolute inset-0" :aria-label="apartment.title">
        <img
          v-if="apartment.photos[0]"
          :src="apartment.photos[0].url"
          :alt="apartment.title"
          loading="lazy"
          class="size-full object-cover"
        >
      </NuxtLink>
      <div class="pointer-events-none absolute inset-x-0 top-0 h-2/5 bg-gradient-to-b from-white/55 to-transparent" />

      <span class="tag absolute left-2.5 top-2.5">{{ listingTag(apartment) }}</span>
      <span v-if="!apartment.is_published" class="pill absolute left-2.5 top-11 !bg-amber-100 !text-amber-800">Draft</span>

      <button
        class="absolute right-2.5 top-2.5 grid size-[38px] cursor-pointer place-items-center rounded-full border border-[#d3ddcb] text-lg leading-none transition duration-500 hover:scale-110"
        :class="saved ? 'text-leaf' : 'text-muted'"
        style="background:linear-gradient(#fff,#eef3ea);box-shadow:inset 0 1px 0 #fff,0 4px 10px -3px rgba(0,0,0,.2)"
        :aria-label="saved ? 'Remove from saved' : 'Save'"
        :aria-pressed="saved"
        @click="favorites.toggle(apartment.id)"
      >
        {{ saved ? '♥' : '♡' }}
      </button>

      <span
        v-if="apartment.photos.length"
        class="absolute bottom-2.5 left-2.5 rounded-lg bg-[rgba(30,45,25,.6)] px-2 py-1 text-xs font-semibold text-white backdrop-blur-md"
      >
        {{ apartment.photos.length }} photos
      </span>
    </div>

    <div class="flex flex-1 flex-col gap-2.5 px-[18px] pb-[18px] pt-4">
      <div class="text-2xl font-extrabold tracking-[-.02em]">
        {{ formatMoney(apartment.price_cents) }}<span v-if="apartment.deal_type === 'rent'" class="text-sm font-medium text-muted"> /mo</span>
      </div>
      <NuxtLink :to="link" class="text-[15px] font-semibold text-ink hover:text-leaf">{{ apartment.title }}</NuxtLink>
      <div class="text-sm text-sage">{{ apartment.address }}, {{ apartment.city }}</div>

      <div class="flex flex-wrap gap-1.5">
        <span v-for="spec in listingSpecs(apartment)" :key="spec" class="pill">{{ spec }}</span>
      </div>

      <div class="mt-auto flex items-center gap-2.5 border-t border-[#e3eadc] pt-3">
        <img
          v-if="apartment.realtor.avatar_url"
          :src="apartment.realtor.avatar_url"
          alt=""
          loading="lazy"
          class="size-[30px] rounded-full border-2 border-white object-cover shadow-[0_0_0_1px_#cfe0c2]"
        >
        <span v-else class="grid size-[30px] place-items-center rounded-full bg-mist text-xs font-bold text-leaf">
          {{ apartment.realtor.name.charAt(0) }}
        </span>
        <span class="flex-1 truncate text-[13px] font-semibold">{{ apartment.realtor.name }}</span>
        <NuxtLink :to="link" class="btn-soft">Contact</NuxtLink>
      </div>
    </div>
  </article>
</template>
