<script setup lang="ts">
// Splash from the mockup: window panes light up one by one, then the whole overlay fades out.
// Pure CSS — it hides itself after ~2.9s; pointer-events:none so it never blocks clicks.
const panes = [
  { radius: '9px 5px 5px 5px', animation: 'h-lit .5s ease-out .5s both' },
  { radius: '5px 9px 5px 5px', animation: 'h-lit-off 1.3s ease-in-out .83s both' },
  { radius: '5px 5px 5px 9px', animation: 'h-lit-off 1.3s ease-in-out 1.01s both' },
  { radius: '5px 5px 9px 5px', animation: 'h-lit-off 1.3s ease-in-out 1.19s both' },
]
</script>

<template>
  <div
    data-preloader
    class="pointer-events-none fixed inset-0 z-[100] flex flex-col items-center justify-center gap-[26px]"
    style="background:radial-gradient(900px 500px at 50% 40%,#f5fcec,#e4ecdc);animation:h-out .8s cubic-bezier(.6,0,.3,1) 2.1s forwards"
    aria-hidden="true"
  >
    <div style="perspective:700px">
      <div
        class="relative grid size-32 grid-cols-2 gap-[9px] overflow-hidden rounded-[36px] border-2 border-[#142010] p-[27px]"
        style="background:linear-gradient(#2f4a26,#1d2a1a);box-shadow:inset 0 2px 0 rgba(255,255,255,.22),0 30px 50px -18px rgba(20,40,10,.55),0 8px 16px -6px rgba(20,40,10,.4);animation:h-tile 1.6s cubic-bezier(.25,.8,.25,1) both"
      >
        <div v-for="(pane, i) in panes" :key="i" class="relative bg-[#43583b]" :style="{ borderRadius: pane.radius, boxShadow: 'inset 1px 1px 2px rgba(0,0,0,.35)' }">
          <div
            class="absolute inset-0"
            :style="{
              borderRadius: pane.radius,
              background: 'radial-gradient(circle at 40% 30%,#fffbe0,#ffe066 50%,#f5b800)',
              boxShadow: '0 0 22px rgba(255,214,60,.9)',
              animation: pane.animation,
            }"
          />
        </div>
        <div class="pointer-events-none absolute inset-x-0 top-0 h-1/2 bg-gradient-to-b from-white/20 to-transparent" />
      </div>
    </div>

    <div class="text-[34px] font-extrabold tracking-[-.035em] text-ink" style="animation:h-up .8s cubic-bezier(.25,.8,.25,1) .4s both">
      Homely
    </div>

    <div
      class="h-2 w-[180px] overflow-hidden rounded-full bg-[#dbe5d4]"
      style="box-shadow:inset 0 2px 3px rgba(40,70,20,.2),0 1px 0 #fff;animation:h-up .8s cubic-bezier(.25,.8,.25,1) .55s both"
    >
      <div
        class="h-full rounded-full"
        style="background:linear-gradient(#dcfca2,#8fd84a 50%,#6fc22f 51%,#86d343);box-shadow:inset 0 1px 0 rgba(255,255,255,.9);animation:h-bar 1.8s cubic-bezier(.4,0,.2,1) .3s both"
      />
    </div>
  </div>
</template>
