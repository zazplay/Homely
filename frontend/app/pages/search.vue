<script setup lang="ts">
import type { Apartment, Feature, Paginated, PropertyType } from '~/types/api'

const route = useRoute()
const router = useRouter()
const api = useApi()

// ---------- State lives in the URL (?deal_type=rent&property_types=loft,apartment&…) ----------
const query = computed(() => route.query as Record<string, string | undefined>)
const list = (value?: string) => (value ? value.split(',').filter(Boolean) : [])

const mode = computed<'sale' | 'rent'>(() => (query.value.deal_type === 'rent' ? 'rent' : 'sale'))
const selectedTypes = computed(() => list(query.value.property_types) as PropertyType[])
const selectedFeatures = computed(() => list(query.value.features) as Feature[])
const roomsMin = computed(() => Number(query.value.rooms_min ?? 0))
const verified = computed(() => query.value.verified === '1')
const sort = computed(() => query.value.sort ?? 'newest')

// Price slider works in dollars; "max" position means "any price".
const priceRange = computed(() => (mode.value === 'rent'
  ? { min: 1500, max: 10000, step: 100 }
  : { min: 300000, max: 2500000, step: 25000 }))
const priceMax = computed(() => (query.value.price_max ? Number(query.value.price_max) / 100 : priceRange.value.max))

function update(patch: Record<string, string | number | undefined | null>) {
  const next = { ...route.query, ...patch }
  delete next.page
  router.replace({ query: cleanQuery(next) as Record<string, string> })
}

function toggleIn(key: 'property_types' | 'features', current: string[], value: string) {
  const next = current.includes(value) ? current.filter(v => v !== value) : [...current, value]
  update({ [key]: next.join(',') || undefined })
}

// Text and slider are debounced so we don't fire a request per keystroke.
const location = ref(query.value.location ?? '')
const priceDraft = ref(priceMax.value)
let locationTimer: ReturnType<typeof setTimeout> | undefined
let priceTimer: ReturnType<typeof setTimeout> | undefined
watch(location, (value) => {
  clearTimeout(locationTimer)
  locationTimer = setTimeout(() => update({ location: value.trim() || undefined }), 300)
})
watch(priceMax, value => (priceDraft.value = value))
function onPriceInput(value: number) {
  priceDraft.value = value
  clearTimeout(priceTimer)
  priceTimer = setTimeout(() => update({ price_max: value >= priceRange.value.max ? undefined : value * 100 }), 250)
}

function goToPage(page: number) {
  router.replace({ query: { ...route.query, page } })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function setMode(next: 'sale' | 'rent') {
  if (next === mode.value) return
  update({ deal_type: next, price_max: undefined })
}

function reset() {
  location.value = ''
  router.replace({ query: cleanQuery({ deal_type: query.value.deal_type }) as Record<string, string> })
}

const bedOptions = [
  { label: 'Any', value: 0 },
  { label: '1+', value: 1 },
  { label: '2+', value: 2 },
  { label: '3+', value: 3 },
  { label: '4+', value: 4 },
]
const sortOptions = [
  { label: 'Newest', value: 'newest' },
  { label: 'Price ↑', value: 'price_asc' },
  { label: 'Price ↓', value: 'price_desc' },
]

// ---------- Data ----------
const { data, status, error } = await useAsyncData(
  'search',
  () => api<Paginated<Apartment>>('/apartments', { query: { ...query.value, deal_type: mode.value, per_page: 24 } }),
  { watch: [query], lazy: true },
)

// Skeletons take the place of the previous results, so the grid doesn't jump.
const skeletonCount = ref(6)
watch(status, (value, previous) => {
  if (value === 'pending' && previous === 'success') skeletonCount.value = Math.max(data.value?.data.length ?? 0, 1)
})

// ---------- Texts & chips ----------
const heading = computed(() => (mode.value === 'rent' ? 'Rentals in New York' : 'Homes for sale in New York'))
const countLabel = computed(() => {
  const n = data.value?.meta.total ?? 0
  return `${n} ${n === 1 ? 'listing' : 'listings'} from verified agents`
})
const priceLabel = computed(() => (priceDraft.value >= priceRange.value.max
  ? 'Any'
  : `$${priceDraft.value.toLocaleString('en-US')}${mode.value === 'rent' ? '/mo' : ''}`))

const activeChips = computed(() => {
  const chips: { label: string, remove: () => void }[] = []
  if (query.value.category) chips.push({ label: query.value.category.replace(/^./, c => c.toUpperCase()), remove: () => update({ category: undefined }) })
  if (query.value.new_build) chips.push({ label: 'New builds', remove: () => update({ new_build: undefined }) })
  if (query.value.location) chips.push({ label: query.value.location, remove: () => { location.value = '' } })
  selectedTypes.value.forEach(t => chips.push({ label: propertyTypeLabels[t], remove: () => toggleIn('property_types', selectedTypes.value, t) }))
  selectedFeatures.value.forEach(f => chips.push({ label: featureLabels[f], remove: () => toggleIn('features', selectedFeatures.value, f) }))
  if (roomsMin.value) chips.push({ label: `${roomsMin.value}+ bd`, remove: () => update({ rooms_min: undefined }) })
  if (verified.value) chips.push({ label: 'Verified', remove: () => update({ verified: undefined }) })
  if (query.value.price_max) chips.push({ label: `Up to ${formatMoney(Number(query.value.price_max))}`, remove: () => update({ price_max: undefined }) })
  return chips
})

useHead({ title: () => `${heading.value} — Homely` })
</script>

<template>
  <div>
    <div class="mb-[22px] flex flex-wrap items-end justify-between gap-5">
      <div>
        <h1 class="m-0 text-[clamp(32px,4vw,46px)] font-extrabold tracking-[-.035em]">{{ heading }}</h1>
        <div class="mt-1.5 text-base text-sage">{{ countLabel }}</div>
      </div>
      <div class="flex gap-1.5 rounded-[18px] bg-[#e6ede0] p-1.5 shadow-[inset_0_2px_4px_rgba(40,70,20,.12),0_1px_0_#fff]">
        <button
          v-for="option in ([{ key: 'sale', label: 'Buy' }, { key: 'rent', label: 'Rent' }] as const)"
          :key="option.key"
          :class="mode === option.key ? 'chip-on' : 'chip'"
          class="!rounded-[13px] !px-[26px] !py-[11px] !text-base !font-extrabold"
          @click="setMode(option.key)"
        >
          {{ option.label }}
        </button>
      </div>
    </div>

    <div class="flex flex-wrap items-start gap-7">
      <!-- Filters -->
      <aside class="panel flex max-w-[320px] flex-[1_1_260px] flex-col gap-5 p-5 md:sticky md:top-[110px] max-md:max-w-none">
        <label class="field-box !gap-1">
          <span class="field-caption">Location</span>
          <input v-model="location" placeholder="Neighborhood or street" class="field-input">
        </label>

        <div class="flex flex-col gap-2.5">
          <div class="text-[13px] font-bold">Property type</div>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="(label, type) in propertyTypeLabels"
              :key="type"
              :class="selectedTypes.includes(type) ? 'chip-on' : 'chip'"
              @click="toggleIn('property_types', selectedTypes, type)"
            >
              {{ label }}
            </button>
          </div>
        </div>

        <div class="flex flex-col gap-2.5">
          <div class="flex justify-between text-[13px] font-bold"><span>Max price</span><span class="text-[#3f8f1c]">{{ priceLabel }}</span></div>
          <input
            type="range"
            :min="priceRange.min"
            :max="priceRange.max"
            :step="priceRange.step"
            :value="priceDraft"
            class="w-full"
            aria-label="Max price"
            @input="onPriceInput(Number(($event.target as HTMLInputElement).value))"
          >
        </div>

        <div class="flex flex-col gap-2.5">
          <div class="text-[13px] font-bold">Bedrooms</div>
          <div class="track">
            <button
              v-for="option in bedOptions"
              :key="option.value"
              :class="roomsMin === option.value ? 'chip-on' : 'chip'"
              class="flex-1 !rounded-[9px] !px-0 !py-[7px] !font-bold hover:!scale-105 hover:!translate-y-0"
              @click="update({ rooms_min: option.value || undefined })"
            >
              {{ option.label }}
            </button>
          </div>
        </div>

        <div class="flex flex-col gap-2.5">
          <div class="text-[13px] font-bold">Features</div>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="feature in searchFeatures"
              :key="feature"
              :class="selectedFeatures.includes(feature) ? 'chip-on' : 'chip'"
              @click="toggleIn('features', selectedFeatures, feature)"
            >
              {{ featureLabels[feature] }}
            </button>
          </div>
        </div>

        <button
          class="flex cursor-pointer items-center justify-between gap-2.5 border-0 bg-transparent p-0 text-sm font-semibold text-ink"
          role="switch"
          :aria-checked="verified"
          @click="update({ verified: verified ? undefined : '1' })"
        >
          Verified agents only
          <HomelySwitch :on="verified" />
        </button>

        <button class="btn-secondary !rounded-[13px] !p-3 !text-sm !font-bold" @click="reset">Reset filters</button>
      </aside>

      <!-- Results -->
      <section class="min-h-[calc(100vh-140px)] min-w-0 flex-[3_1_560px] [overflow-anchor:none]">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div class="flex min-h-8 flex-wrap gap-1.5">
            <button
              v-for="chip in activeChips"
              :key="chip.label"
              class="flex cursor-pointer items-center gap-1.5 rounded-[9px] border border-[#86c94d] px-2.5 py-1.5 text-[13px] font-bold text-leaf-dark shadow-[inset_0_1px_0_rgba(255,255,255,.9)] [animation:h-pop-soft_.5s_cubic-bezier(.25,.8,.25,1)]"
              style="background:linear-gradient(#e6fdc0,#b4ea7c)"
              @click="chip.remove"
            >
              {{ chip.label }} <span class="opacity-60">✕</span>
            </button>
          </div>
          <div class="flex gap-1 rounded-[14px] bg-[#e6ede0] p-1 shadow-[inset_0_2px_3px_rgba(40,70,20,.12)]">
            <button
              v-for="option in sortOptions"
              :key="option.value"
              :class="sort === option.value ? 'chip-on' : 'chip'"
              class="hover:!translate-y-0 hover:!scale-[1.04]"
              @click="update({ sort: option.value === 'newest' ? undefined : option.value })"
            >
              {{ option.label }}
            </button>
          </div>
        </div>

        <p v-if="error" class="card p-6 text-red-700">Couldn't load listings: {{ errorMessage(error) }}</p>

        <div v-else class="grid gap-5 [grid-template-columns:repeat(auto-fill,minmax(min(100%,260px),1fr))]">
          <template v-if="status === 'pending'">
            <ListingTileSkeleton v-for="n in skeletonCount" :key="n" />
          </template>
          <template v-else>
            <div
              v-for="(apartment, index) in data?.data ?? []"
              :key="apartment.id"
              :style="{ animation: `h-tile-in .65s cubic-bezier(.25,.8,.25,1) ${index * 50}ms backwards` }"
            >
              <ListingTile :apartment="apartment" :tag="propertyTypeLabels[apartment.property_type]" show-specs class="h-full" />
            </div>
          </template>
        </div>

        <div
          v-if="status !== 'pending' && !error && !data?.data.length"
          class="rounded-3xl border border-dashed border-[#cfdcc5] px-5 py-[60px] text-center"
          style="background:linear-gradient(#ffffff,#f5f8f2)"
        >
          <div class="text-[22px] font-extrabold">No homes match these filters</div>
          <div class="mt-1.5 text-[15px] text-sage">Try raising the max price or removing a feature.</div>
        </div>

        <div v-if="data && data.meta.last_page > 1 && status !== 'pending'" class="mt-8 flex items-center justify-center gap-2">
          <button class="btn-secondary" :disabled="data.meta.current_page <= 1" @click="goToPage(data.meta.current_page - 1)">← Prev</button>
          <span class="text-sm text-sage">Page {{ data.meta.current_page }} of {{ data.meta.last_page }}</span>
          <button class="btn-secondary" :disabled="data.meta.current_page >= data.meta.last_page" @click="goToPage(data.meta.current_page + 1)">Next →</button>
        </div>
      </section>
    </div>
  </div>
</template>
