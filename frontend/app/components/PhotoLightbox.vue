<script setup lang="ts">
import type { Photo } from '~/types/api'

/**
 * Fullscreen photo viewer: arrows / keyboard (← → Esc) / swipe, thumbnails strip.
 * v-model:index is the current photo; v-model:open toggles it.
 */
const props = defineProps<{ photos: Photo[], title: string }>()
const open = defineModel<boolean>('open', { default: false })
const index = defineModel<number>('index', { default: 0 })

const current = computed(() => props.photos[index.value])
const count = computed(() => props.photos.length)

function go(delta: number) {
  if (count.value < 2) return
  index.value = (index.value + delta + count.value) % count.value
}

function onKey(e: KeyboardEvent) {
  if (!open.value) return
  if (e.key === 'Escape') open.value = false
  if (e.key === 'ArrowRight') go(1)
  if (e.key === 'ArrowLeft') go(-1)
}

// Swipe on touch screens.
let touchX: number | null = null
function onTouchStart(e: TouchEvent) {
  touchX = e.touches[0]?.clientX ?? null
}
function onTouchEnd(e: TouchEvent) {
  const endX = e.changedTouches[0]?.clientX
  if (touchX === null || endX === undefined) return
  const dx = endX - touchX
  if (Math.abs(dx) > 50) go(dx < 0 ? 1 : -1)
  touchX = null
}

// Lock page scroll while open; the thumbnail of the current photo stays in view.
const strip = ref<HTMLElement | null>(null)
watch(open, (value) => {
  if (import.meta.client) document.documentElement.style.overflow = value ? 'hidden' : ''
})
watch(index, async () => {
  await nextTick()
  strip.value?.querySelector('[aria-current="true"]')?.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' })
})

onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKey)
  document.documentElement.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="lightbox">
      <div
        v-if="open && current"
        class="fixed inset-0 z-[200] flex flex-col bg-[rgba(14,22,12,.94)] text-white backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        :aria-label="`${title} — photo ${index + 1} of ${count}`"
        @touchstart.passive="onTouchStart"
        @touchend.passive="onTouchEnd"
      >
        <div class="flex items-center justify-between gap-4 px-5 py-4">
          <div class="min-w-0 truncate text-sm font-semibold opacity-90">{{ title }}</div>
          <div class="flex items-center gap-3">
            <span class="rounded-lg bg-white/10 px-2.5 py-1 text-[13px] font-semibold">{{ index + 1 }} / {{ count }}</span>
            <button class="lb-btn" aria-label="Close" @click="open = false">✕</button>
          </div>
        </div>

        <div class="relative flex min-h-0 flex-1 items-center justify-center px-4 sm:px-20" @click.self="open = false">
          <Transition name="lb-photo" mode="out-in">
            <img
              :key="current.id"
              :src="current.url"
              :alt="`${title} — photo ${index + 1}`"
              class="max-h-full max-w-full select-none rounded-2xl object-contain shadow-[0_30px_80px_-20px_rgba(0,0,0,.7)]"
              draggable="false"
            >
          </Transition>
          <template v-if="count > 1">
            <button class="lb-btn lb-arrow left-3 sm:left-6" aria-label="Previous photo" @click="go(-1)">‹</button>
            <button class="lb-btn lb-arrow right-3 sm:right-6" aria-label="Next photo" @click="go(1)">›</button>
          </template>
        </div>

        <div v-if="count > 1" ref="strip" class="flex justify-center gap-2 overflow-x-auto px-4 py-4">
          <button
            v-for="(photo, i) in photos"
            :key="photo.id"
            class="h-14 w-20 shrink-0 cursor-pointer overflow-hidden rounded-lg border-2 p-0 transition-[opacity,border-color] duration-300"
            :class="i === index ? 'border-[#8fd84a] opacity-100' : 'border-transparent opacity-50 hover:opacity-90'"
            :aria-current="i === index"
            :aria-label="`Photo ${i + 1}`"
            @click="index = i"
          >
            <img :src="photo.url" alt="" class="size-full object-cover" draggable="false">
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.lb-btn {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 22px;
  font-weight: 700;
  color: #1d2a1a;
  background: linear-gradient(#fff, #eef3ea);
  border: 1px solid #d3ddcb;
  box-shadow: inset 0 1px 0 #fff, 0 6px 14px -4px rgba(0, 0, 0, .4);
  transition: transform .3s ease;
}

.lb-btn:hover { transform: scale(1.08); }

.lb-arrow {
  position: absolute;
  top: 50%;
  translate: 0 -50%;
  width: 52px;
  height: 52px;
  font-size: 26px;
}

.lightbox-enter-active, .lightbox-leave-active { transition: opacity .25s ease; }
.lightbox-enter-from, .lightbox-leave-to { opacity: 0; }

.lb-photo-enter-active, .lb-photo-leave-active { transition: opacity .18s ease, transform .18s ease; }
.lb-photo-enter-from { opacity: 0; transform: scale(.98); }
.lb-photo-leave-to { opacity: 0; }
</style>
