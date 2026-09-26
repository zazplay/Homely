<script setup lang="ts">
// The "window" logo: 2x2 panes, the top-left one glows and blinks now and then.
withDefaults(defineProps<{ size?: number }>(), { size: 38 })
</script>

<template>
  <span
    class="relative grid shrink-0 grid-cols-2 overflow-hidden border border-[#142010]"
    :style="{
      width: `${size}px`,
      height: `${size}px`,
      gap: `${size / 12}px`,
      padding: `${size / 4.75}px`,
      borderRadius: `${size / 3.45}px`,
      background: 'linear-gradient(#2f4a26,#1d2a1a)',
      boxShadow: 'inset 0 1px 0 rgba(255,255,255,.25),0 5px 10px -3px rgba(20,40,10,.5)',
    }"
    aria-hidden="true"
  >
    <span class="rounded-[3px_2px_2px_2px] [animation:h-blink_5s_ease-in-out_infinite]" style="background:radial-gradient(circle at 40% 30%,#fffbe0,#ffe066 50%,#f5b800);box-shadow:0 0 7px rgba(255,214,60,.95)" />
    <span class="rounded-[2px_3px_2px_2px] bg-[#43583b]" />
    <span class="rounded-[2px_2px_2px_3px] bg-[#43583b]" />
    <span class="rounded-[2px_2px_3px_2px] bg-[#43583b]" />
    <span class="pointer-events-none absolute inset-x-0 top-0 h-1/2 bg-gradient-to-b from-white/20 to-transparent" />
  </span>
</template>
