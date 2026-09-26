<script setup lang="ts">
// The lime toggle from the Search mockup ("Verified agents only").
defineProps<{ on: boolean }>()
</script>

<template>
  <span
    class="relative h-7 w-12 shrink-0 rounded-full border shadow-[inset_0_2px_4px_rgba(40,70,20,.2)] transition-[background] duration-400"
    :class="on ? 'border-[#5ea626]' : 'border-[#cfdac7] bg-[#e1e8db]'"
    :style="on ? 'background:linear-gradient(#b6ee6c,#6fc22f)' : ''"
    aria-hidden="true"
  >
    <span
      class="absolute top-0.5 size-[22px] rounded-full border border-[#cfdac7] shadow-[0_3px_6px_-1px_rgba(0,0,0,.25),inset_0_1px_0_#fff] transition-[left] duration-[450ms] ease-[cubic-bezier(.25,.8,.25,1)]"
      :style="{ left: on ? '22px' : '2px', background: 'linear-gradient(#fff,#e9efe4)' }"
    />
  </span>
</template>
