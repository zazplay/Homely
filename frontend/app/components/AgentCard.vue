<script setup lang="ts">
import type { Agent } from '~/types/api'

defineProps<{ agent: Agent }>()
</script>

<template>
  <div
    v-tilt
    class="flex flex-col items-center gap-2 rounded-3xl border border-[#d7e2cf] px-[18px] py-6 text-center"
    style="background:linear-gradient(#ffffff,#eef4e9);box-shadow:inset 0 1px 0 #fff,0 1px 0 #cad7c1,0 16px 30px -20px rgba(50,90,20,.4)"
  >
    <img
      v-if="agent.avatar_url"
      :src="agent.avatar_url"
      :alt="agent.name"
      loading="lazy"
      class="size-[84px] rounded-full border-4 border-white object-cover shadow-[0_0_0_1px_#cfe0c2,0_10px_20px_-8px_rgba(50,90,20,.45)]"
    >
    <span v-else class="grid size-[84px] place-items-center rounded-full bg-mist text-2xl font-bold text-leaf">{{ agent.name.charAt(0) }}</span>

    <div class="mt-1.5 text-[17px] font-bold">{{ agent.name }}</div>
    <div class="text-[13px] text-sage">{{ agent.agency }}</div>

    <div class="mt-1 flex gap-2">
      <span v-if="agent.rating" class="pill-lime">★ {{ agent.rating.toFixed(1) }}</span>
      <span class="pill">{{ agent.deals_count }} deals</span>
    </div>
  </div>
</template>
