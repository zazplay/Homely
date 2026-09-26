<script setup lang="ts">
import type { Agent, Language, ServiceArea, Specialization } from '~/types/api'

useHead({ title: 'Find a verified agent — Homely' })

const route = useRoute()
const router = useRouter()
const api = useApi()

// ---------- State in the URL ----------
const query = computed(() => route.query as Record<string, string | undefined>)
const list = (value?: string) => (value ? value.split(',').filter(Boolean) : [])

const specs = computed(() => list(query.value.specializations) as Specialization[])
const areas = computed(() => list(query.value.areas) as ServiceArea[])
const langs = computed(() => list(query.value.languages) as Language[])
const minRating = computed(() => query.value.min_rating ?? '')
const minExperience = computed(() => Number(query.value.min_experience ?? 0))
const sort = computed(() => query.value.sort ?? 'rating')

function update(patch: Record<string, string | number | undefined>) {
  router.replace({ query: cleanQuery({ ...route.query, ...patch }) as Record<string, string> })
}
function toggleIn(key: 'specializations' | 'areas' | 'languages', current: string[], value: string) {
  const next = current.includes(value) ? current.filter(v => v !== value) : [...current, value]
  update({ [key]: next.join(',') || undefined })
}

const search = ref(query.value.q ?? '')
const experienceDraft = ref(minExperience.value)
let searchTimer: ReturnType<typeof setTimeout> | undefined
let experienceTimer: ReturnType<typeof setTimeout> | undefined
watch(search, (value) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => update({ q: value.trim() || undefined }), 300)
})
function onExperience(value: number) {
  experienceDraft.value = value
  clearTimeout(experienceTimer)
  experienceTimer = setTimeout(() => update({ min_experience: value || undefined }), 250)
}

function reset() {
  search.value = ''
  experienceDraft.value = 0
  router.replace({ query: {} })
}

// ---------- Data: filtered list + total (for "N of M agents") in parallel ----------
const [{ data: agents, status }, { data: allAgents }] = await Promise.all([
  useAsyncData('agents', () => api<Agent[]>('/agents', { query: query.value }), { watch: [query], lazy: true }),
  useAsyncData('agents-total', () => api<Agent[]>('/agents'), { lazy: true }),
])

const skeletonCount = ref(5)
watch(status, (value, previous) => {
  if (value === 'pending' && previous === 'success') skeletonCount.value = Math.max(agents.value?.length ?? 0, 1)
})

const countLabel = computed(() => (agents.value && allAgents.value
  ? `${agents.value.length} of ${allAgents.value.length} licensed agents match your search`
  : 'Licensed agents in New York'))

const sortOptions = [
  { label: 'Top rated', value: 'rating' },
  { label: 'Most deals', value: 'deals' },
  { label: 'Experience', value: 'experience' },
]
const ratingOptions = [
  { label: 'Any', value: '' },
  { label: '4.7+', value: '4.7' },
  { label: '4.9+', value: '4.9' },
]

/** Specializations + non-English languages, like the mockup's tags. */
function tagsOf(agent: Agent): string[] {
  return [
    ...agent.specializations.map(s => specializationLabels[s]),
    ...agent.languages.filter(l => l !== 'english').map(l => languageLabels[l]),
  ]
}
</script>

<template>
  <div>
    <section
      class="relative rounded-[30px] border border-[#8fcf55] p-8"
      style="background:linear-gradient(#e9fbcf,#c4ef8e 60%,#aee46f);box-shadow:inset 0 2px 0 rgba(255,255,255,.9),inset 0 -3px 0 rgba(60,120,20,.15),0 30px 60px -34px rgba(80,150,30,.6)"
    >
      <div class="pointer-events-none absolute inset-x-0 top-0 h-1/2 rounded-t-[30px] bg-gradient-to-b from-white/55 to-transparent" />
      <h1 class="relative m-0 text-[clamp(32px,4vw,46px)] font-extrabold tracking-[-.035em] text-[#183d05]">Find a verified agent</h1>
      <p class="relative mb-5 mt-1.5 text-[17px] text-[#2f5518]">{{ countLabel }}</p>
      <div
        class="relative flex flex-wrap gap-2 rounded-[20px] border border-[#b8dc94] p-2"
        style="background:linear-gradient(#ffffff,#f1f6ec);box-shadow:inset 0 1px 0 #fff,0 16px 30px -18px rgba(30,80,5,.45)"
      >
        <label class="field-box flex-[1_1_280px]">
          <span class="field-caption">Name, agency or area</span>
          <input v-model="search" placeholder="e.g. Greenleaf, Park Slope" class="field-input">
        </label>
        <div class="track max-w-full flex-none items-center overflow-x-auto !rounded-[14px]">
          <button
            v-for="option in sortOptions"
            :key="option.value"
            :class="sort === option.value ? 'chip-on' : 'chip'"
            class="!px-3.5 !py-2.5 !text-sm !font-bold hover:!translate-y-0 hover:!scale-[1.04]"
            @click="update({ sort: option.value === 'rating' ? undefined : option.value })"
          >
            {{ option.label }}
          </button>
        </div>
      </div>
    </section>

    <div class="mt-7 flex flex-wrap items-start gap-7">
      <aside class="panel flex max-w-[310px] flex-[1_1_250px] flex-col gap-5 p-5 md:sticky md:top-[110px] max-md:max-w-none">
        <div class="flex flex-col gap-2.5">
          <div class="text-[13px] font-bold">Specialization</div>
          <div class="flex flex-wrap gap-1.5">
            <button v-for="(label, key) in specializationLabels" :key="key" :class="specs.includes(key) ? 'chip-on' : 'chip'" @click="toggleIn('specializations', specs, key)">{{ label }}</button>
          </div>
        </div>
        <div class="flex flex-col gap-2.5">
          <div class="text-[13px] font-bold">Area</div>
          <div class="flex flex-wrap gap-1.5">
            <button v-for="(label, key) in areaLabels" :key="key" :class="areas.includes(key) ? 'chip-on' : 'chip'" @click="toggleIn('areas', areas, key)">{{ label }}</button>
          </div>
        </div>
        <div class="flex flex-col gap-2.5">
          <div class="text-[13px] font-bold">Languages</div>
          <div class="flex flex-wrap gap-1.5">
            <button v-for="(label, key) in languageLabels" :key="key" :class="langs.includes(key) ? 'chip-on' : 'chip'" @click="toggleIn('languages', langs, key)">{{ label }}</button>
          </div>
        </div>
        <div class="flex flex-col gap-2.5">
          <div class="text-[13px] font-bold">Minimum rating</div>
          <div class="track">
            <button
              v-for="option in ratingOptions"
              :key="option.label"
              :class="minRating === option.value ? 'chip-on' : 'chip'"
              class="flex-1 !rounded-[9px] !px-0 !py-[7px] !font-bold hover:!translate-y-0 hover:!scale-105"
              @click="update({ min_rating: option.value || undefined })"
            >
              {{ option.label }}
            </button>
          </div>
        </div>
        <div class="flex flex-col gap-2.5">
          <div class="flex justify-between text-[13px] font-bold">
            <span>Experience</span><span class="text-[#3f8f1c]">{{ experienceDraft ? `${experienceDraft}+ years` : 'Any' }}</span>
          </div>
          <input
            type="range"
            min="0"
            max="15"
            step="1"
            :value="experienceDraft"
            class="w-full"
            aria-label="Minimum experience"
            @input="onExperience(Number(($event.target as HTMLInputElement).value))"
          >
        </div>
        <button class="btn-secondary !rounded-[13px] !p-3 !text-sm !font-bold" @click="reset">Reset filters</button>
      </aside>

      <section class="flex min-h-[calc(100vh-140px)] min-w-0 flex-[3_1_560px] flex-col gap-4 [overflow-anchor:none]">
        <template v-if="status === 'pending'">
          <div v-for="n in skeletonCount" :key="n" class="agent-row" aria-hidden="true">
            <div class="skeleton size-[92px] shrink-0 rounded-full" />
            <div class="flex min-w-0 flex-[1_1_240px] flex-col gap-2">
              <div class="skeleton h-[18px] w-2/5 rounded-md" />
              <div class="skeleton h-3.5 w-3/5 rounded-md" />
              <div class="flex gap-1.5"><div class="skeleton h-[22px] w-14 rounded-lg" /><div class="skeleton h-[22px] w-[70px] rounded-lg" /></div>
            </div>
          </div>
        </template>

        <template v-else>
          <div
            v-for="(agent, index) in agents ?? []"
            :key="agent.id"
            v-tilt="{ x: 4, y: 5, lift: 3, scale: 1, perspective: 1200 }"
            class="agent-row"
            :style="{ animation: `h-row-in .65s cubic-bezier(.25,.8,.25,1) ${index * 60}ms backwards` }"
          >
            <NuxtLink :to="`/agents/${agent.id}`" class="shrink-0">
              <img
                v-if="agent.avatar_url"
                :src="agent.avatar_url"
                :alt="agent.name"
                class="size-[92px] rounded-full border-4 border-white object-cover shadow-[0_0_0_1px_#cfe0c2,0_12px_22px_-10px_rgba(50,90,20,.5)]"
              >
              <span v-else class="grid size-[92px] place-items-center rounded-full bg-mist text-3xl font-bold text-leaf">{{ agent.name.charAt(0) }}</span>
            </NuxtLink>
            <div class="flex min-w-0 flex-[1_1_240px] flex-col gap-1.5">
              <div class="flex flex-wrap items-center gap-2">
                <NuxtLink :to="`/agents/${agent.id}`" class="text-[19px] font-extrabold tracking-[-.01em] text-ink hover:text-[#3f8f1c]">{{ agent.name }}</NuxtLink>
                <span v-if="agent.is_verified" class="verified-pill">✓ Verified</span>
              </div>
              <div class="text-sm text-sage">{{ agent.agency }}<template v-if="agent.areas.length"> · {{ agent.areas.map(a => areaLabels[a]).join(', ') }}</template></div>
              <div class="mt-0.5 flex flex-wrap gap-1.5">
                <span v-for="tag in tagsOf(agent)" :key="tag" class="pill !rounded-lg !px-[9px] !py-1 !shadow-none">{{ tag }}</span>
              </div>
            </div>
            <div class="flex items-center gap-[18px]">
              <div class="text-center"><div class="text-xl font-extrabold">★ {{ agent.rating?.toFixed(1) ?? '—' }}</div><div class="text-xs text-sage">{{ agent.reviews_count }} reviews</div></div>
              <div class="text-center"><div class="text-xl font-extrabold">{{ agent.deals_count }}</div><div class="text-xs text-sage">deals</div></div>
              <div class="text-center"><div class="text-xl font-extrabold">{{ agent.experience_years }}y</div><div class="text-xs text-sage">experience</div></div>
            </div>
            <div class="flex flex-col gap-2">
              <NuxtLink :to="`/agents/${agent.id}#contact`" class="btn-primary !rounded-xl !px-[18px] !py-2.5 !text-sm">Message</NuxtLink>
              <NuxtLink :to="`/agents/${agent.id}`" class="btn-secondary !rounded-xl !px-[18px] !py-2.5 !text-sm">View profile</NuxtLink>
            </div>
          </div>

          <div
            v-if="!agents?.length"
            class="rounded-3xl border border-dashed border-[#cfdcc5] px-5 py-[60px] text-center"
            style="background:linear-gradient(#ffffff,#f5f8f2)"
          >
            <div class="text-[22px] font-extrabold">No agents match these filters</div>
            <div class="mt-1.5 text-[15px] text-sage">Try another area or lower the minimum rating.</div>
          </div>
        </template>
      </section>
    </div>
  </div>
</template>

<style scoped>
.agent-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 20px;
  padding: 18px;
  border-radius: 24px;
  background: linear-gradient(#ffffff, #f5f8f2);
  border: 1px solid #d9e3d1;
  box-shadow: inset 0 1px 0 #fff, 0 1px 0 #cbd8c2, 0 18px 36px -24px rgba(50, 90, 20, .45);
}

.verified-pill {
  white-space: nowrap;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 800;
  color: #1f4a06;
  background: linear-gradient(#dcfca2, #8fd84a);
  border: 1px solid #6ab332;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, .9);
}
</style>
