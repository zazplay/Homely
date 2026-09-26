<script setup lang="ts">
import type { Apartment } from '~/types/api'

/**
 * Compact listing card from the Search / Agent / Listing ("Similar homes") mockups:
 * the whole card is a link; optional tag on the photo and spec pills.
 */
withDefaults(defineProps<{
  apartment: Apartment
  tag?: string | null
  showSpecs?: boolean
}>(), {
  tag: null,
  showSpecs: false,
})
</script>

<template>
  <NuxtLink
    v-tilt="{ x: 9, y: 7, lift: 4 }"
    :to="`/apartments/${apartment.id}`"
    class="flex flex-col overflow-hidden rounded-[22px] border border-[#d9e3d1] text-ink hover:text-ink"
    style="background:linear-gradient(#ffffff,#f5f8f2);box-shadow:inset 0 1px 0 #fff,0 1px 0 #cbd8c2,0 18px 36px -22px rgba(50,90,20,.45)"
  >
    <div class="relative mx-2 mt-2 aspect-[4/3] overflow-hidden rounded-2xl bg-[#e4efd9]">
      <img
        v-if="apartment.photos[0]"
        :src="apartment.photos[0].url"
        :alt="apartment.title"
        loading="lazy"
        class="absolute inset-0 size-full object-cover"
      >
      <div class="absolute inset-x-0 top-0 h-2/5 bg-gradient-to-b from-white/50 to-transparent" />
      <span v-if="tag" class="tag absolute left-2.5 top-2.5 !shadow-[inset_0_1px_0_rgba(255,255,255,.9)]">{{ tag }}</span>
    </div>
    <div class="flex flex-col gap-1.5 px-4 pb-4 pt-3.5">
      <div class="text-[22px] font-extrabold tracking-[-.02em]">{{ formatPrice(apartment.price_cents, apartment.deal_type) }}</div>
      <div class="text-[15px] font-semibold">{{ apartment.title }}</div>
      <div class="text-[13px] text-sage">{{ apartment.address }}, {{ apartment.city }}</div>
      <div v-if="showSpecs" class="mt-1 flex flex-wrap gap-1.5">
        <span class="pill !rounded-lg !px-[9px] !py-1 !shadow-none">{{ bedsLabel(apartment) }}</span>
        <span class="pill !rounded-lg !px-[9px] !py-1 !shadow-none">{{ formatArea(apartment.area, 'ft²') }}</span>
      </div>
    </div>
  </NuxtLink>
</template>
