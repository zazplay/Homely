<script setup lang="ts">
import type { Agent, Apartment, CatalogStats, Category, Paginated } from '~/types/api'

definePageMeta({ fullWidth: true })
useHead({ title: 'Homely — find a home you\'ll love living in' })

const api = useApi()

// ---------- Data: three independent requests run in parallel ----------
const freshCategory = ref<Category | ''>('')

const [{ data: stats }, { data: fresh, status: freshStatus }, { data: agents, status: agentsStatus }] = await Promise.all([
  useAsyncData('home-stats', () => api<CatalogStats>('/catalog/stats'), { lazy: true }),
  useAsyncData(
    'home-fresh',
    () => api<Paginated<Apartment>>('/apartments', { query: cleanQuery({ per_page: 6, category: freshCategory.value }) }),
    { watch: [freshCategory], lazy: true },
  ),
  useAsyncData('home-agents', () => api<Agent[]>('/agents', { query: { limit: 4, sort: 'deals' } }), { lazy: true }),
])

// ---------- Hero search ----------
const searchTabs = [
  { key: 'buy', label: 'Buy' },
  { key: 'rent', label: 'Rent' },
  { key: 'sell', label: 'Sell' },
] as const
type SearchTab = typeof searchTabs[number]['key']

const search = reactive({
  tab: 'buy' as SearchTab,
  location: '',
  roomsMin: '',
  priceMax: '',
})

/** "850000" -> "$850,000" as in the mockup; digits are parsed back on search. */
const priceDigits = () => Number(search.priceMax.replace(/\D/g, '')) || 0
function formatPriceInput() {
  const n = priceDigits()
  search.priceMax = n ? `$${n.toLocaleString('en-US')}` : ''
}

function submitSearch() {
  if (search.tab === 'sell') {
    return navigateTo('/my/new')
  }

  return navigateTo({
    path: '/search',
    query: cleanQuery({
      deal_type: search.tab === 'buy' ? 'sale' : 'rent',
      location: search.location.trim(),
      rooms_min: search.roomsMin,
      price_max: priceDigits() ? priceDigits() * 100 : '',
    }),
  })
}

// ---------- Categories ----------
const categories = computed(() => [
  { name: 'Apartments', count: stats.value?.apartments, image: '/images/homely/category-apartments.jpg', to: { path: '/search', query: { category: 'apartments' } } },
  { name: 'Houses', count: stats.value?.houses, image: '/images/homely/category-houses.jpg', to: { path: '/search', query: { category: 'houses' } } },
  { name: 'New builds', count: stats.value?.new_builds, image: '/images/homely/category-new-builds.jpg', to: { path: '/search', query: { new_build: '1' } } },
  { name: 'Commercial', count: stats.value?.commercial, image: '/images/homely/category-commercial.jpg', to: { path: '/search', query: { category: 'commercial' } } },
])

// ---------- Fresh listings filter ----------
const freshFilters: { label: string, value: Category | '' }[] = [
  { label: 'All', value: '' },
  { label: 'Apartments', value: 'apartments' },
  { label: 'Houses', value: 'houses' },
  { label: 'Commercial', value: 'commercial' },
]

// Keep the grid height while skeletons replace cards, so the page doesn't jump.
const freshGrid = ref<HTMLElement | null>(null)
const freshMinHeight = ref(0)
function selectFresh(value: Category | '') {
  if (value === freshCategory.value) return
  freshMinHeight.value = Math.max(freshMinHeight.value, freshGrid.value?.offsetHeight ?? 0)
  freshCategory.value = value
}

// ---------- Hero 3D scene follows the mouse ----------
// Anywhere on the page: a gentle parallax driven by the window position (as in the mockup).
// Over the scene itself: a much stronger tilt towards the cursor, so it clearly reacts on its whole area.
const scene = ref<HTMLElement | null>(null)
const sceneArea = ref<HTMLElement | null>(null)
function onMouseMove(e: MouseEvent) {
  if (!scene.value || !sceneArea.value) return
  const r = sceneArea.value.getBoundingClientRect()
  const over = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom

  if (over) {
    const x = (e.clientX - r.left) / r.width - 0.5
    const y = (e.clientY - r.top) / r.height - 0.5
    scene.value.style.transform = `rotateY(${-10 + x * 26}deg) rotateX(${2 - y * 18}deg) translateZ(20px)`
  } else {
    const x = e.clientX / window.innerWidth - 0.5
    const y = e.clientY / window.innerHeight - 0.5
    scene.value.style.transform = `rotateY(${-14 + x * 12}deg) rotateX(${4 - y * 8}deg)`
  }
}
onMounted(() => {
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    window.addEventListener('mousemove', onMouseMove)
  }
})
onBeforeUnmount(() => window.removeEventListener('mousemove', onMouseMove))
</script>

<template>
  <div>
    <!-- ================= HERO ================= -->
    <section class="mx-auto grid max-w-[1240px] items-center gap-12 px-6 pb-10 pt-12 [grid-template-columns:repeat(auto-fit,minmax(min(100%,480px),1fr))]">
      <div>
        <div
          class="inline-flex items-center gap-2 rounded-full border border-[#d6e4cc] py-1.5 pl-1.5 pr-3 text-[13px] font-medium text-[#3d5a31]"
          style="background:linear-gradient(#fff,#f0f6ea);box-shadow:inset 0 1px 0 #fff,0 2px 6px -2px rgba(60,100,30,.2)"
        >
          <span class="rounded-full border border-[#6bb530] px-2 py-0.5 text-[11px] font-bold text-[#20500a]" style="background:linear-gradient(#cdf78a,#86d343);box-shadow:inset 0 1px 0 rgba(255,255,255,.8)">NEW</span>
          <template v-if="stats">{{ stats.total.toLocaleString('en-US') }} verified listings from licensed agents</template>
          <template v-else>Verified listings from licensed agents</template>
        </div>

        <h1 class="mb-[18px] mt-[22px] text-[clamp(40px,5.4vw,68px)] font-extrabold leading-[1.02] tracking-[-.035em] [text-wrap:balance]">
          Find a home<br>you'll
          <span class="bg-clip-text text-transparent" style="background-image:linear-gradient(#9fe05a,#4f9a1f)">love living in</span>
        </h1>
        <p class="mb-[30px] max-w-[520px] text-[19px] leading-normal text-moss [text-wrap:pretty]">
          Apartments, houses and commercial spaces listed directly by licensed realtors. No duplicates, no fake photos.
        </p>

        <!-- Search panel -->
        <form
          class="rounded-3xl border border-[#d5e0cd] p-2"
          style="background:linear-gradient(#ffffff,#eef3ea);box-shadow:inset 0 1px 0 #fff,0 1px 0 #c9d6c0,0 24px 50px -20px rgba(60,110,30,.35)"
          @submit.prevent="submitSearch"
        >
          <div class="flex gap-1 p-1" role="tablist">
            <button
              v-for="tab in searchTabs"
              :key="tab.key"
              type="button"
              role="tab"
              :aria-selected="search.tab === tab.key"
              class="cursor-pointer rounded-xl border px-[18px] py-[9px] text-sm transition duration-500 hover:-translate-y-px hover:scale-[1.03]"
              :class="search.tab === tab.key
                ? 'border-[#6ab332] font-bold text-leaf-dark [animation:h-pop_.7s_cubic-bezier(.25,.8,.25,1)]'
                : 'border-transparent font-medium text-moss hover:bg-[#e9f1e2]'"
              :style="search.tab === tab.key ? 'background:linear-gradient(#d4fa92,#8fd84a);box-shadow:inset 0 1px 0 rgba(255,255,255,.9),0 3px 8px -3px rgba(98,173,42,.6)' : ''"
              @click="search.tab = tab.key"
            >
              {{ tab.label }}
            </button>
          </div>

          <div class="grid gap-1.5 p-1 [grid-template-columns:repeat(auto-fit,minmax(140px,1fr))]">
            <label class="flex flex-col gap-0.5 rounded-[14px] border border-[#dde6d6] bg-white px-3.5 py-2.5 shadow-[inset_0_2px_3px_rgba(40,70,20,.06)]">
              <span class="text-[11px] font-semibold uppercase tracking-[.06em] text-muted">Location</span>
              <input v-model="search.location" placeholder="Brooklyn, NY" class="border-0 bg-transparent p-0 text-[15px] font-medium text-ink outline-none">
            </label>
            <label class="flex flex-col gap-0.5 rounded-[14px] border border-[#dde6d6] bg-white px-3.5 py-2.5 shadow-[inset_0_2px_3px_rgba(40,70,20,.06)]">
              <span class="text-[11px] font-semibold uppercase tracking-[.06em] text-muted">Bedrooms</span>
              <select v-model="search.roomsMin" class="cursor-pointer appearance-none border-0 bg-transparent p-0 text-[15px] font-medium text-ink outline-none">
                <option value="">Any</option>
                <option v-for="n in 4" :key="n" :value="String(n)">{{ n }}+</option>
              </select>
            </label>
            <label class="flex flex-col gap-0.5 rounded-[14px] border border-[#dde6d6] bg-white px-3.5 py-2.5 shadow-[inset_0_2px_3px_rgba(40,70,20,.06)]">
              <span class="text-[11px] font-semibold uppercase tracking-[.06em] text-muted">Max price</span>
              <input v-model="search.priceMax" inputmode="numeric" placeholder="$850,000" class="border-0 bg-transparent p-0 text-[15px] font-medium text-ink outline-none" @blur="formatPriceInput">
            </label>
            <button
              type="submit"
              class="relative min-h-[58px] cursor-pointer overflow-hidden rounded-[14px] border border-[#5ea626] text-base font-extrabold text-leaf-dark transition duration-500 hover:-translate-y-0.5 hover:scale-[1.03] hover:brightness-110 active:scale-[.97]"
              style="background:linear-gradient(#dcfca2,#9ae055 48%,#7ccc38 52%,#92da4c);box-shadow:inset 0 1px 0 rgba(255,255,255,.95),inset 0 -2px 0 rgba(60,120,20,.25),0 8px 18px -6px rgba(98,173,42,.7);text-shadow:0 1px 0 rgba(255,255,255,.55)"
            >
              <span class="pointer-events-none absolute inset-y-0 left-0 w-2/5 [animation:h-shine_3.5s_ease-in-out_infinite]" style="background:linear-gradient(90deg,rgba(255,255,255,0),rgba(255,255,255,.7),rgba(255,255,255,0))" />
              <span class="relative">Search</span>
            </button>
          </div>
        </form>
      </div>

      <!-- Hero art -->
      <div ref="sceneArea" class="relative px-5 pb-10 pt-2.5" style="perspective:1400px">
        <div ref="scene" class="relative [transform-style:preserve-3d] [transform:rotateY(-14deg)_rotateX(4deg)] [transition:transform_.6s_cubic-bezier(.2,.8,.2,1)]">
          <div
            class="relative aspect-[4/3.2] overflow-hidden rounded-[28px] border-[6px] border-white"
            style="background:repeating-linear-gradient(135deg,#e4efd9 0 12px,#dbe8ce 12px 24px);box-shadow:0 1px 0 #cfdcc5,0 40px 70px -30px rgba(50,90,20,.5),0 18px 30px -18px rgba(0,0,0,.2);-webkit-box-reflect:below 14px linear-gradient(transparent 70%,rgba(255,255,255,.28))"
          >
            <img src="/images/homely/hero-living-room.jpg" alt="Bright living room" class="absolute inset-0 size-full object-cover">
            <div class="absolute inset-x-0 top-0 h-[42%] bg-gradient-to-b from-white/55 to-transparent" />
          </div>

          <div
            class="absolute -left-7 bottom-9 min-w-[220px] rounded-[18px] border border-[#d7e3cf] px-[18px] py-3.5 [--z:60px] [animation:h-float_5s_ease-in-out_infinite]"
            style="background:linear-gradient(#fff,#f2f7ee);box-shadow:inset 0 1px 0 #fff,0 20px 40px -14px rgba(50,90,20,.45)"
          >
            <div class="text-xs font-semibold text-muted">3 bd · 2 ba · 1,420 sq ft</div>
            <div class="mt-0.5 text-2xl font-extrabold tracking-[-.02em]">$785,000</div>
            <div class="mt-2 flex items-center gap-2 text-[13px] text-moss">
              <span class="size-2.5 rounded-full" style="background:radial-gradient(circle at 35% 30%,#e8ffc4,#6fc22f 60%,#4a9418);box-shadow:0 0 0 3px rgba(125,205,60,.2)" />
              Park Slope · 5 min to subway
            </div>
          </div>

          <div
            class="absolute -right-[18px] top-7 flex items-center gap-2.5 rounded-full border border-[#d7e3cf] py-2 pl-2 pr-3.5 [--z:80px] [animation:h-float_6s_ease-in-out_-2s_infinite]"
            style="background:linear-gradient(#fff,#f2f7ee);box-shadow:inset 0 1px 0 #fff,0 16px 30px -12px rgba(50,90,20,.45)"
          >
            <img src="/images/homely/hero-agent.jpg" alt="" class="size-9 rounded-full border-2 border-white object-cover shadow-[0_0_0_1px_#cfe0c2]">
            <div>
              <div class="text-[13px] font-bold">Emma Carter</div>
              <div class="text-[11px] font-semibold text-leaf">★ 4.9 · Verified agent</div>
            </div>
          </div>

          <div
            class="absolute bottom-[-14px] right-[30px] flex size-[92px] flex-col items-center justify-center rounded-full border-2 border-white text-center text-leaf-dark [animation:h-spin_7s_ease-in-out_infinite]"
            style="background:radial-gradient(circle at 35% 28%,#f2ffd9 0%,#b6ee6c 30%,#76c632 70%,#58a51f);box-shadow:0 12px 24px -8px rgba(80,150,30,.6),inset 0 -4px 8px rgba(40,90,10,.25)"
          >
            <div class="text-[22px] font-extrabold leading-none">0%</div>
            <div class="mt-0.5 text-[10px] font-bold leading-[1.1]">buyer<br>fees</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ================= CATEGORIES ================= -->
    <section v-reveal class="mx-auto max-w-[1240px] px-6 pb-10 pt-2.5">
      <div class="grid gap-4 [grid-template-columns:repeat(auto-fit,minmax(220px,1fr))]">
        <NuxtLink
          v-for="category in categories"
          :key="category.name"
          v-tilt
          :to="category.to"
          class="flex flex-col gap-3.5 rounded-[22px] border border-[#d7e2cf] p-[18px] text-ink"
          style="background:linear-gradient(#ffffff,#eef4e9);box-shadow:inset 0 1px 0 #fff,0 1px 0 #cad7c1,0 14px 28px -18px rgba(50,90,20,.4)"
        >
          <div class="relative h-[110px] overflow-hidden rounded-[14px] border border-[#d3dfca]">
            <img :src="category.image" :alt="category.name" loading="lazy" class="absolute inset-0 size-full object-cover">
            <div class="absolute inset-x-0 top-0 h-[45%] bg-gradient-to-b from-white/60 to-transparent" />
          </div>
          <div class="flex items-end justify-between gap-2">
            <div>
              <div class="text-lg font-bold tracking-[-.01em]">{{ category.name }}</div>
              <div v-if="category.count !== undefined" class="mt-0.5 text-[13px] text-muted">{{ category.count }} listings</div>
              <div v-else class="skeleton mt-1.5 h-3 w-20 rounded-md" />
            </div>
            <div
              class="grid size-[34px] place-items-center rounded-full border border-[#6ab332] font-extrabold text-leaf-dark"
              style="background:linear-gradient(#d4fa92,#86d343);box-shadow:inset 0 1px 0 rgba(255,255,255,.9),0 4px 8px -3px rgba(98,173,42,.6)"
            >
              →
            </div>
          </div>
        </NuxtLink>
      </div>
    </section>

    <!-- ================= FRESH LISTINGS ================= -->
    <section id="listings" v-reveal class="mx-auto max-w-[1240px] px-6 py-10">
      <div class="mb-6 flex flex-wrap items-end justify-between gap-5">
        <div>
          <h2 class="m-0 text-[40px] font-extrabold tracking-[-.03em]">Fresh listings</h2>
          <p class="mt-1.5 text-base text-sage">Posted by agents in the last 24 hours</p>
        </div>
        <div class="segment" role="tablist">
          <button
            v-for="filter in freshFilters"
            :key="filter.label"
            type="button"
            role="tab"
            :aria-selected="freshCategory === filter.value"
            :class="freshCategory === filter.value ? 'segment-item-active' : 'segment-item'"
            @click="selectFresh(filter.value)"
          >
            {{ filter.label }}
          </button>
        </div>
      </div>

      <div
        ref="freshGrid"
        class="grid content-start gap-[22px] [grid-template-columns:repeat(auto-fill,minmax(min(100%,280px),1fr))]"
        :style="{ minHeight: freshMinHeight ? `${freshMinHeight}px` : undefined }"
      >
        <template v-if="freshStatus === 'pending'">
          <ListingCardSkeleton v-for="n in 6" :key="n" />
        </template>
        <template v-else-if="fresh?.data.length">
          <div
            v-for="(apartment, index) in fresh.data"
            :key="apartment.id"
            :style="{ animation: `h-card-in .7s cubic-bezier(.2,.8,.2,1) ${index * 70}ms backwards` }"
          >
            <ListingCard :apartment="apartment" class="h-full" />
          </div>
        </template>
        <p v-else class="col-span-full card p-10 text-center text-sage">No listings in this category yet.</p>
      </div>
    </section>

    <!-- ================= TOP AGENTS ================= -->
    <section id="agents" v-reveal class="mx-auto max-w-[1240px] px-6 py-10">
      <h2 class="mb-6 mt-0 text-[40px] font-extrabold tracking-[-.03em]">Top agents this month</h2>
      <div class="grid gap-[18px] [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]">
        <template v-if="agentsStatus === 'pending'">
          <AgentCardSkeleton v-for="n in 4" :key="n" />
        </template>
        <AgentCard v-for="agent in agents ?? []" v-else :key="agent.id" :agent="agent" />
      </div>
    </section>

    <!-- ================= CTA FOR REALTORS ================= -->
    <section id="post" v-reveal class="mx-auto max-w-[1240px] px-6 pb-[60px] pt-10">
      <div
        class="relative grid items-center gap-8 overflow-hidden rounded-[32px] border border-[#8fcf55] p-8 sm:p-12 [grid-template-columns:repeat(auto-fit,minmax(min(100%,360px),1fr))]"
        style="background:linear-gradient(#e9fbcf,#c4ef8e 55%,#aee46f);box-shadow:inset 0 2px 0 rgba(255,255,255,.9),inset 0 -3px 0 rgba(60,120,20,.15),0 30px 60px -30px rgba(80,150,30,.6)"
      >
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[48%] rounded-[32px_32px_50%_50%/32px_32px_30px_30px] bg-gradient-to-b from-white/60 to-transparent" />
        <div class="relative">
          <h2 class="mb-3 mt-0 text-[clamp(32px,4vw,48px)] font-extrabold tracking-[-.03em] text-[#183d05] [text-wrap:balance]">
            Are you a realtor? List your first 10 properties free.
          </h2>
          <p class="m-0 max-w-[480px] text-[17px] leading-normal text-[#2f5518]">
            Reach buyers actively searching in your area. Verified badge, lead inbox and listing analytics included.
          </p>
        </div>
        <div class="relative flex flex-wrap justify-end gap-3">
          <NuxtLink :to="{ path: '/register', query: { role: 'realtor' } }" class="btn-dark !rounded-2xl !px-[26px] !py-4 !text-[17px]">
            Create agent account
          </NuxtLink>
          <a href="#" class="btn-secondary !rounded-2xl !px-[26px] !py-4 !text-[17px] !font-bold !text-leaf-dark">See pricing</a>
        </div>
      </div>
    </section>
  </div>
</template>
