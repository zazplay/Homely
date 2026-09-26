<script setup lang="ts">
import type { AgentProfile, Apartment, FieldErrors } from '~/types/api'

const route = useRoute()
const api = useApi()

const { data: profile, error } = await useAsyncData(
  `agent-${route.params.id}`,
  () => api<AgentProfile>(`/agents/${route.params.id}`),
)

if (error.value || !profile.value) {
  throw createError({ statusCode: error.value?.statusCode ?? 404, fatal: true })
}

const p = computed(() => profile.value!)
const agent = computed(() => p.value.agent)
const firstName = computed(() => agent.value.name.split(' ')[0])
useHead({ title: () => `${agent.value.name} — Homely` })

const subtitle = computed(() => [
  agent.value.title,
  agent.value.agency,
  agent.value.areas.map(a => areaLabels[a]).join(' & '),
].filter(Boolean).join(' · '))

const stats = computed(() => [
  { v: String(agent.value.deals_count), k: 'Deals closed' },
  { v: agent.value.rating ? `${agent.value.rating.toFixed(1)} ★` : '—', k: `${agent.value.reviews_count} reviews` },
  { v: formatMoneyShort(p.value.sales_volume_cents), k: 'Total sales volume' },
  { v: `${agent.value.experience_years} yrs`, k: 'Experience' },
])

const tags = computed(() => [
  ...agent.value.languages.map(l => languageLabels[l]),
  ...agent.value.specializations.map(s => specializationLabels[s]),
])

// ---------- Listings tabs ----------
type Tab = 'sale' | 'rent' | 'sold'
const groups = computed<Record<Tab, Apartment[]>>(() => ({
  sale: p.value.listings.filter(l => !l.is_sold && l.deal_type === 'sale'),
  rent: p.value.listings.filter(l => !l.is_sold && l.deal_type === 'rent'),
  sold: p.value.listings.filter(l => l.is_sold),
}))
const tabs: { key: Tab, label: string }[] = [
  { key: 'sale', label: 'For sale' },
  { key: 'rent', label: 'For rent' },
  { key: 'sold', label: 'Sold' },
]
const tab = ref<Tab>(tabs.find(t => groups.value[t.key].length)?.key ?? 'sale')
const tagFor = (l: Apartment) => (l.is_sold ? 'Sold' : dealTypeLabel(l.deal_type))

// Keep the grid height when switching to a shorter tab, so the page doesn't jump.
const grid = ref<HTMLElement | null>(null)
const gridMinHeight = ref(0)
function selectTab(key: Tab) {
  if (key === tab.value) return
  gridMinHeight.value = Math.max(gridMinHeight.value, grid.value?.offsetHeight ?? 0)
  tab.value = key
}

// ---------- Avatar follows the mouse (mockup) ----------
const avatar = ref<HTMLElement | null>(null)
function onMouseMove(e: MouseEvent) {
  if (!avatar.value) return
  const x = e.clientX / window.innerWidth - 0.5
  const y = e.clientY / window.innerHeight - 0.5
  avatar.value.style.transform = `rotateY(${x * 24}deg) rotateX(${-y * 16}deg)`
}
onMounted(() => {
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) window.addEventListener('mousemove', onMouseMove)
})
onBeforeUnmount(() => window.removeEventListener('mousemove', onMouseMove))

// ---------- Contact form ----------
const form = reactive({ name: '', contact: '', message: '' })
const formErrors = ref<FieldErrors>({})
const sending = ref(false)
const sent = ref(false)
async function send() {
  sending.value = true
  formErrors.value = {}
  try {
    await api('/inquiries', { method: 'POST', body: { ...form, agent_id: agent.value.id } })
    sent.value = true
  } catch (e) {
    formErrors.value = fieldErrors(e)
    if (!Object.keys(formErrors.value).length) formErrors.value = { message: errorMessage(e) }
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div>
    <!-- Profile hero -->
    <section
      class="relative overflow-hidden rounded-[32px] border border-[#d7e2cf]"
      style="background:linear-gradient(#ffffff,#eef4e9);box-shadow:inset 0 1px 0 #fff,0 1px 0 #cad7c1,0 36px 60px -34px rgba(50,90,20,.5)"
    >
      <div class="relative h-[220px] overflow-hidden">
        <img :src="p.cover_url ?? '/images/homely/category-apartments.jpg'" alt="" class="absolute inset-0 size-full object-cover">
        <div class="absolute inset-0" style="background:linear-gradient(rgba(233,251,207,.25),rgba(233,251,207,0) 40%,rgba(255,255,255,.85))" />
      </div>
      <div class="relative -mt-[70px] flex flex-wrap items-end gap-7 px-8 pb-7">
        <div style="perspective:600px">
          <img
            v-if="agent.avatar_url"
            ref="avatar"
            :src="agent.avatar_url"
            :alt="agent.name"
            class="size-[148px] rounded-full border-[6px] border-white object-cover shadow-[0_0_0_1px_#cfe0c2,0_24px_40px_-14px_rgba(50,90,20,.6)] transition-transform duration-[600ms] ease-[cubic-bezier(.25,.8,.25,1)]"
          >
          <span v-else class="grid size-[148px] place-items-center rounded-full border-[6px] border-white bg-mist text-5xl font-bold text-leaf">{{ agent.name.charAt(0) }}</span>
        </div>
        <div class="min-w-0 flex-[1_1_320px]">
          <div v-if="agent.is_verified" class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-[#6ab332] px-2.5 py-1 text-xs font-extrabold text-leaf-dark shadow-[inset_0_1px_0_rgba(255,255,255,.9)]" style="background:linear-gradient(#dcfca2,#8fd84a)">
            ✓ Licensed & verified
          </div>
          <h1 class="mb-1 mt-2 text-[clamp(34px,4vw,48px)] font-extrabold tracking-[-.035em]">{{ agent.name }}</h1>
          <div class="text-base text-sage">{{ subtitle }}</div>
        </div>
        <div class="flex max-w-full flex-none flex-wrap gap-2.5">
          <a href="#contact" class="btn-primary !rounded-[14px] !px-[22px] !py-3.5 !text-base !font-extrabold">Message {{ firstName }}</a>
          <a v-if="p.phone" :href="`tel:${p.phone.replace(/[^\d+]/g, '')}`" class="btn-secondary !rounded-[14px] !px-[22px] !py-3.5 !text-base !font-bold">{{ p.phone }}</a>
        </div>
      </div>
      <div class="grid gap-3 px-8 pb-8 [grid-template-columns:repeat(auto-fit,minmax(150px,1fr))]">
        <div
          v-for="stat in stats"
          :key="stat.k"
          v-tilt="{ x: 9, y: 7, lift: 4, scale: 1 }"
          class="rounded-[18px] border border-[#dde6d6] bg-white px-[18px] py-4 shadow-[inset_0_1px_0_#fff,0_12px_22px_-16px_rgba(50,90,20,.4)]"
        >
          <div class="text-[26px] font-extrabold tracking-[-.02em]">{{ stat.v }}</div>
          <div class="text-[13px] font-medium text-sage">{{ stat.k }}</div>
        </div>
      </div>
    </section>

    <section class="mt-10 flex flex-wrap items-start gap-8">
      <div class="flex min-w-0 flex-[1_1_520px] flex-col gap-10">
        <div v-reveal>
          <div class="mb-5 flex flex-wrap items-end justify-between gap-4">
            <h2 class="m-0 text-[32px] font-extrabold tracking-[-.03em]">{{ firstName }}'s listings</h2>
            <div class="segment" role="tablist">
              <button
                v-for="t in tabs"
                :key="t.key"
                role="tab"
                :aria-selected="tab === t.key"
                :class="tab === t.key ? 'segment-item-active' : 'segment-item'"
                @click="selectTab(t.key)"
              >
                {{ t.label }} · {{ groups[t.key].length }}
              </button>
            </div>
          </div>
          <div
            ref="grid"
            class="grid content-start gap-[18px] [grid-template-columns:repeat(auto-fill,minmax(min(100%,250px),1fr))] [overflow-anchor:none]"
            :style="{ minHeight: gridMinHeight ? `${gridMinHeight}px` : undefined }"
          >
            <div
              v-for="(listing, index) in groups[tab]"
              :key="`${tab}-${listing.id}`"
              :style="{ animation: `h-card-in .75s cubic-bezier(.25,.8,.25,1) ${index * 70}ms backwards` }"
            >
              <ListingTile :apartment="listing" :tag="tagFor(listing)" class="h-full" />
            </div>
            <p v-if="!groups[tab].length" class="col-span-full m-0 rounded-3xl border border-dashed border-[#cfdcc5] px-5 py-10 text-center text-sage">
              Nothing here yet.
            </p>
          </div>
        </div>

        <div v-if="p.reviews.length" v-reveal>
          <h2 class="mb-5 mt-0 text-[32px] font-extrabold tracking-[-.03em]">Client reviews</h2>
          <div class="flex flex-col gap-3.5">
            <div
              v-for="review in p.reviews"
              :key="review.id"
              class="rounded-[22px] border border-[#d9e3d1] p-[22px]"
              style="background:linear-gradient(#ffffff,#f5f8f2);box-shadow:inset 0 1px 0 #fff,0 1px 0 #cbd8c2,0 14px 28px -22px rgba(50,90,20,.4)"
            >
              <div class="flex flex-wrap justify-between gap-3">
                <div class="font-bold">{{ review.author_name }} <span v-if="review.deal_label" class="font-medium text-sage">· {{ review.deal_label }}</span></div>
                <div class="font-extrabold tracking-[2px] text-leaf" :aria-label="`${review.rating} out of 5`">{{ '★'.repeat(review.rating) }}</div>
              </div>
              <p class="mb-0 mt-2.5 text-[15px] leading-[1.6] text-[#3d4d37] [text-wrap:pretty]">{{ review.body }}</p>
            </div>
          </div>
        </div>
      </div>

      <aside id="contact" class="top-[110px] flex max-w-[400px] flex-[1_1_280px] scroll-mt-[110px] flex-col gap-3.5 md:sticky max-md:max-w-none">
        <div class="panel rounded-[26px] p-6 shadow-[inset_0_1px_0_#fff,0_1px_0_#cad7c1,0_30px_50px_-28px_rgba(50,90,20,.5)]">
          <h3 class="mb-1.5 mt-0 text-xl font-extrabold">About {{ firstName }}</h3>
          <p v-if="p.bio" class="mb-4 mt-0 text-[15px] leading-[1.6] text-[#3d4d37]">{{ p.bio }}</p>
          <div v-if="tags.length" class="mb-[18px] flex flex-wrap gap-1.5">
            <span v-for="tag in tags" :key="tag" class="pill !px-2.5 !py-1.5 !shadow-none">{{ tag }}</span>
          </div>
          <form class="flex flex-col gap-2" @submit.prevent="send">
            <input v-model="form.name" placeholder="Your name" class="input !py-3" required minlength="2" autocomplete="name">
            <p v-if="formErrors.name" class="field-error !mt-0">{{ formErrors.name }}</p>
            <input v-model="form.contact" placeholder="Phone or email" class="input !py-3" required minlength="5" autocomplete="email">
            <p v-if="formErrors.contact" class="field-error !mt-0">{{ formErrors.contact }}</p>
            <textarea v-model="form.message" rows="3" placeholder="I'm looking for…" class="input resize-y !py-3" required minlength="5" />
            <p v-if="formErrors.message || formErrors.agent_id" class="field-error !mt-0">{{ formErrors.message || formErrors.agent_id }}</p>
            <button type="submit" class="btn-lime-lg" :disabled="sending || sent">
              {{ sent ? '✓ Message sent' : sending ? 'Sending…' : `Message ${firstName}` }}
            </button>
          </form>
          <div class="mt-4 text-[13px] text-sage">
            <template v-if="p.license_number">License #{{ p.license_number }} · </template>Replies in ~15 min
          </div>
        </div>
      </aside>
    </section>
  </div>
</template>
