<script setup lang="ts">
import type { Apartment, Category, FieldErrors, Paginated, PropertyType } from '~/types/api'

const route = useRoute()
const api = useApi()
const auth = useAuthStore()
const favorites = useFavorites()

const { data: apartment, error, refresh } = await useAsyncData(
  `apartment-${route.params.id}`,
  () => api<Apartment>(`/apartments/${route.params.id}`),
)

// 403 (someone else's draft) / 404 -> Nuxt error page
if (error.value || !apartment.value) {
  throw createError({ statusCode: error.value?.statusCode ?? 404, fatal: true })
}

const listing = computed(() => apartment.value!)
useHead({ title: () => `${listing.value.title} — Homely` })

// ---------- Gallery ----------
const photoIndex = ref(0)
const mainImage = ref<HTMLImageElement | null>(null)
const photos = computed(() => listing.value.photos)
// Up to 6 thumbnails; the last one shows "+N" when there are more photos (opens the fullscreen viewer).
const thumbs = computed(() => photos.value.slice(1, 7).map((photo, k, list) => ({
  photo,
  index: k + 1,
  more: k === list.length - 1 && photos.value.length > 7 ? photos.value.length - 7 : 0,
})))

/**
 * Layout per number of thumbnails, so they always fill the space next to the main photo:
 * 1 → one tall · 2 → stacked · 3 → wide + 2 · 4 → 2×2 · 5 → wide + 2×2 · 6 → 3×2 (mockup).
 * Rows have a 90px minimum so they don't collapse when the grid wraps under the main photo (mobile).
 */
const thumbGrid = computed(() => {
  const n = thumbs.value.length
  const layouts: Record<number, { cols: number, rows: number, wideFirst: boolean }> = {
    1: { cols: 1, rows: 1, wideFirst: false },
    2: { cols: 1, rows: 2, wideFirst: false },
    3: { cols: 2, rows: 2, wideFirst: true },
    4: { cols: 2, rows: 2, wideFirst: false },
    5: { cols: 2, rows: 3, wideFirst: true },
    6: { cols: 3, rows: 2, wideFirst: false },
  }
  const { cols, rows, wideFirst } = layouts[n] ?? layouts[6]!
  return {
    wideFirst,
    style: {
      gridTemplateColumns: `repeat(${cols}, minmax(0, 1fr))`,
      gridTemplateRows: `repeat(${rows}, minmax(${n === 1 ? 180 : 90}px, 1fr))`,
    },
  }
})

const lightboxOpen = ref(false)
function openLightbox(index: number) {
  photoIndex.value = index
  lightboxOpen.value = true
}

function go(index: number) {
  const n = photos.value.length
  if (n < 2) return
  const next = (index + n) % n
  const direction = next > photoIndex.value ? 1 : -1
  photoIndex.value = next
  // Same 3D "page turn" as the mockup.
  mainImage.value?.animate?.(
    [{ opacity: 0.2, transform: `perspective(1400px) rotateY(${direction * 14}deg) scale(1.06)` }, { opacity: 1, transform: 'none' }],
    { duration: 700, easing: 'cubic-bezier(.25,.8,.25,1)' },
  )
}

// ---------- Numbers ----------
const areaSqft = computed(() => sqft(listing.value.area))
const pricePerSqft = computed(() => (areaSqft.value ? listing.value.price_cents / 100 / areaSqft.value : 0))
const facts = computed(() => {
  const l = listing.value
  const list: { k: string, v: string }[] = []
  if (l.property_type !== 'commercial') list.push({ k: 'Beds', v: l.rooms === 0 ? 'Studio' : String(l.rooms) })
  if (l.bathrooms !== null) list.push({ k: 'Baths', v: String(l.bathrooms) })
  list.push({ k: 'Area', v: formatArea(l.area, 'ft²') })
  if (l.floor !== null) list.push({ k: 'Floor', v: l.total_floors ? `${l.floor} of ${l.total_floors}` : String(l.floor) })
  if (l.year_built) list.push({ k: 'Built', v: String(l.year_built) })
  return list
})
const paragraphs = computed(() => (listing.value.description ?? '').split(/\n\s*\n/).filter(Boolean))

// ---------- Mortgage calculator (sale listings) ----------
const downPercent = ref(20)
const rate = ref(6.5)
const price = computed(() => listing.value.price_cents / 100)
const loan = computed(() => price.value * (1 - downPercent.value / 100))
const monthly = computed(() => monthlyPayment(loan.value, rate.value))
const dollars = (value: number) => `$${Math.round(value).toLocaleString('en-US')}`

// ---------- Contact form ----------
const firstName = computed(() => listing.value.realtor.name.split(' ')[0])
const shortAddress = computed(() => listing.value.address.split(',')[0])
const form = reactive({
  name: '',
  contact: '',
  message: `Hi ${firstName.value}, I'm interested in ${shortAddress.value}. Is it still available?`,
})
const formErrors = ref<FieldErrors>({})
const sending = ref(false)
const sent = ref(false)
const messageField = ref<HTMLTextAreaElement | null>(null)

async function sendInquiry() {
  sending.value = true
  formErrors.value = {}
  try {
    await api('/inquiries', { method: 'POST', body: { ...form, apartment_id: listing.value.id } })
    sent.value = true
  } catch (e) {
    formErrors.value = fieldErrors(e)
    if (!Object.keys(formErrors.value).length) formErrors.value = { message: errorMessage(e) }
  } finally {
    sending.value = false
  }
}

function scheduleTour() {
  sent.value = false
  form.message = `Hi ${firstName.value}, I'd like to schedule a tour of ${shortAddress.value}. When are you available?`
  messageField.value?.focus()
}

// ---------- Owner actions ----------
const isOwner = computed(() => auth.owns(listing.value.realtor.id))
const busy = ref(false)
async function toggleSold() {
  busy.value = true
  try {
    await api(`/apartments/${listing.value.id}`, { method: 'PATCH', body: { is_sold: !listing.value.is_sold } })
    await refresh()
  } finally {
    busy.value = false
  }
}
async function remove() {
  if (!confirm('Delete this listing? Photos will be deleted too.')) return
  busy.value = true
  try {
    await api(`/apartments/${listing.value.id}`, { method: 'DELETE' })
    await navigateTo('/my')
  } finally {
    busy.value = false
  }
}

// ---------- Similar homes ----------
const categoryOf: Record<PropertyType, Category> = {
  apartment: 'apartments', loft: 'apartments', house: 'houses', townhouse: 'houses', commercial: 'commercial',
}
const { data: similarPage } = await useAsyncData(
  `similar-${route.params.id}`,
  () => api<Paginated<Apartment>>('/apartments', {
    query: { deal_type: listing.value.deal_type, category: categoryOf[listing.value.property_type], per_page: 4 },
  }),
  { lazy: true }, // below the fold — don't block the navigation
)
const similar = computed(() => (similarPage.value?.data ?? []).filter(a => a.id !== listing.value.id).slice(0, 3))
</script>

<template>
  <div>
    <!-- Breadcrumbs -->
    <div class="mb-[18px] flex flex-wrap items-center gap-2 text-sm text-muted">
      <NuxtLink to="/" class="text-sage">Home</NuxtLink><span>›</span>
      <NuxtLink :to="{ path: '/search', query: { location: listing.city } }" class="text-sage">{{ listing.city }}</NuxtLink><span>›</span>
      <span class="font-semibold text-ink">{{ shortAddress }}</span>
    </div>

    <!-- Gallery -->
    <section class="flex flex-wrap gap-3.5" style="perspective:1600px">
      <div
        class="relative aspect-[16/10] min-w-0 flex-[2.2_1_520px] overflow-hidden rounded-[28px] border-[6px] border-white bg-[#e4efd9]"
        style="box-shadow:0 1px 0 #cfdcc5,0 40px 70px -34px rgba(50,90,20,.55)"
      >
        <img
          v-if="photos[photoIndex]"
          ref="mainImage"
          :src="photos[photoIndex]!.url"
          :alt="listing.title"
          class="absolute inset-0 size-full cursor-zoom-in object-cover"
          @click="lightboxOpen = true"
        >
        <div class="pointer-events-none absolute inset-x-0 top-0 h-[35%] bg-gradient-to-b from-white/40 to-transparent" />
        <button v-if="photos.length" class="gallery-arrow absolute right-3.5 top-3.5 !text-lg" aria-label="Open fullscreen" title="Fullscreen" @click="lightboxOpen = true">⤢</button>
        <div class="absolute left-3.5 top-3.5 flex gap-2">
          <span v-if="listing.is_sold" class="tag !rounded-[9px] !px-[11px] !py-[5px] !text-xs">Sold</span>
          <span v-else-if="listing.badge" class="tag !rounded-[9px] !px-[11px] !py-[5px] !text-xs">{{ listingTag(listing) }}</span>
          <span v-if="listing.realtor.is_verified" class="rounded-[9px] bg-[rgba(30,45,25,.6)] px-[11px] py-[5px] text-xs font-bold text-white backdrop-blur-md">Verified listing</span>
          <span v-if="!listing.is_published" class="rounded-[9px] bg-amber-100 px-[11px] py-[5px] text-xs font-bold text-amber-800">Draft — visible only to you</span>
        </div>
        <div v-if="photos.length > 1" class="absolute bottom-3.5 right-3.5 flex gap-2">
          <button class="gallery-arrow" aria-label="Previous photo" @click="go(photoIndex - 1)">‹</button>
          <button class="gallery-arrow" aria-label="Next photo" @click="go(photoIndex + 1)">›</button>
        </div>
        <div v-if="photos.length" class="absolute bottom-3.5 left-3.5 rounded-[9px] bg-[rgba(30,45,25,.6)] px-[11px] py-1.5 text-[13px] font-semibold text-white backdrop-blur-md">
          {{ photoIndex + 1 }} / {{ photos.length }}
        </div>
      </div>

      <!-- The thumbnail grid adapts to the number of photos and always fills the height next to the main photo. -->
      <div
        v-if="thumbs.length"
        class="grid min-w-0 flex-[1_1_260px] gap-2.5"
        :style="thumbGrid.style"
      >
        <button
          v-for="(thumb, i) in thumbs"
          :key="thumb.photo.id"
          class="thumb relative cursor-pointer overflow-hidden rounded-2xl border-[3px] bg-[#e4efd9] p-0"
          :class="[
            photoIndex === thumb.index ? 'border-[#7ccc38] opacity-100' : 'border-white opacity-85',
            i === 0 && thumbGrid.wideFirst ? 'col-span-full' : '',
          ]"
          :aria-label="`Photo ${thumb.index + 1}`"
          @click="thumb.more ? openLightbox(thumb.index) : go(thumb.index)"
        >
          <img :src="thumb.photo.url" alt="" loading="lazy" class="absolute inset-0 size-full object-cover" draggable="false">
          <span v-if="thumb.more" class="absolute inset-0 grid place-items-center bg-[rgba(20,35,15,.55)] text-lg font-extrabold text-white">+{{ thumb.more }}</span>
        </button>
      </div>
    </section>

    <section class="mt-8 flex flex-wrap items-start gap-8">
      <div class="flex min-w-0 flex-[1_1_520px] flex-col gap-6">
        <div v-reveal>
          <div class="flex flex-wrap items-baseline gap-4">
            <div class="text-[clamp(36px,4.5vw,52px)] font-extrabold tracking-[-.035em]">
              {{ formatMoney(listing.price_cents) }}<span v-if="listing.deal_type === 'rent'" class="text-2xl font-semibold text-muted">/mo</span>
            </div>
            <div class="text-[15px] text-sage">
              <template v-if="listing.deal_type === 'sale'">{{ dollars(pricePerSqft) }} / sq ft · est. {{ dollars(monthly) }}/mo</template>
              <template v-else>{{ dealTypeLabel(listing.deal_type) }} · {{ propertyTypeLabels[listing.property_type] }}</template>
            </div>
          </div>
          <h1 class="my-1.5 text-[28px] font-bold tracking-[-.02em]">{{ listing.title }}</h1>
          <div class="text-base text-sage">{{ listing.address }}, {{ listing.city }}</div>

          <div class="mt-5 grid gap-2.5 [grid-template-columns:repeat(auto-fit,minmax(120px,1fr))]">
            <div
              v-for="fact in facts"
              :key="fact.k"
              v-tilt="{ x: 9, y: 7, lift: 4, scale: 1 }"
              class="rounded-[18px] border border-[#d7e2cf] px-4 py-3.5"
              style="background:linear-gradient(#ffffff,#eef4e9);box-shadow:inset 0 1px 0 #fff,0 1px 0 #cad7c1,0 12px 22px -16px rgba(50,90,20,.4)"
            >
              <div class="text-xs font-semibold uppercase tracking-[.06em] text-muted">{{ fact.k }}</div>
              <div class="mt-0.5 text-xl font-extrabold">{{ fact.v }}</div>
            </div>
          </div>
        </div>

        <div v-reveal class="content-card">
          <h2 class="mb-3 mt-0 text-[22px] font-extrabold tracking-[-.02em]">About this home</h2>
          <p v-for="(paragraph, i) in paragraphs" :key="i" class="mb-3 mt-0 text-base leading-[1.65] text-[#3d4d37] [text-wrap:pretty] last:mb-0">{{ paragraph }}</p>
          <p v-if="!paragraphs.length" class="m-0 text-sage">The realtor hasn't added a description yet.</p>
          <template v-if="listing.features.length">
            <h3 class="mb-3 mt-[22px] text-base font-bold">Features</h3>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="feature in listing.features"
                :key="feature"
                class="flex items-center gap-2 rounded-[11px] border border-[#dbe6d2] bg-mist px-3 py-2 text-sm font-semibold text-[#2f4a26] shadow-[inset_0_1px_0_#fff]"
              >
                <span class="size-2 rounded-full" style="background:radial-gradient(circle at 35% 30%,#e8ffc4,#6fc22f 60%,#4a9418)" />
                {{ featureLabels[feature] }}
              </span>
            </div>
          </template>
        </div>

        <div v-if="listing.deal_type === 'sale'" v-reveal class="content-card">
          <div class="flex flex-wrap items-baseline justify-between gap-3">
            <h2 class="m-0 text-[22px] font-extrabold tracking-[-.02em]">Mortgage calculator</h2>
            <div class="text-sm text-sage">30-yr fixed · {{ rate.toFixed(1) }}%</div>
          </div>
          <div class="mt-[18px] grid items-center gap-6 [grid-template-columns:repeat(auto-fit,minmax(240px,1fr))]">
            <div class="flex flex-col gap-[18px]">
              <label class="flex flex-col gap-2">
                <span class="flex justify-between text-sm font-semibold"><span>Down payment</span><span>{{ downPercent }}% · {{ dollars(price * downPercent / 100) }}</span></span>
                <input v-model.number="downPercent" type="range" min="5" max="50" step="1" class="w-full">
              </label>
              <label class="flex flex-col gap-2">
                <span class="flex justify-between text-sm font-semibold"><span>Interest rate</span><span>{{ rate.toFixed(1) }}%</span></span>
                <input v-model.number="rate" type="range" min="3" max="9" step="0.1" class="w-full">
              </label>
            </div>
            <div
              class="rounded-[20px] border border-[#9fd66a] p-[22px] text-center"
              style="background:linear-gradient(#e9fbcf,#c4ef8e);box-shadow:inset 0 2px 0 rgba(255,255,255,.9),0 14px 26px -14px rgba(80,150,30,.55)"
            >
              <div class="text-[13px] font-bold uppercase tracking-[.06em] text-[#2f5518]">Estimated monthly</div>
              <div class="mt-1 text-[40px] font-extrabold tracking-[-.03em] text-[#183d05]">{{ dollars(monthly) }}</div>
              <div class="text-[13px] text-[#2f5518]">Loan {{ dollars(loan) }}</div>
            </div>
          </div>
        </div>

        <div
          v-reveal
          class="relative flex aspect-[16/7] items-center justify-center overflow-hidden rounded-3xl border-[6px] border-white font-mono text-[13px] text-[#6f8a60]"
          style="background:repeating-linear-gradient(135deg,#e6f0dc 0 11px,#dce8cf 11px 22px);box-shadow:0 1px 0 #cfdcc5,0 24px 40px -26px rgba(50,90,20,.5)"
        >
          map · {{ listing.address.split(',').at(-1)?.trim() }}, {{ listing.city }}
          <div
            class="absolute left-1/2 top-1/2 size-7 -translate-x-1/2 -translate-y-[110%] rotate-[-45deg] rounded-[50%_50%_50%_0] border-[3px] border-white shadow-[0_8px_16px_-4px_rgba(50,90,20,.6)]"
            style="background:radial-gradient(circle at 35% 30%,#e8ffc4,#6fc22f 60%,#4a9418)"
          />
        </div>
      </div>

      <!-- Sticky agent card -->
      <aside class="top-[110px] flex max-w-[420px] flex-[1_1_300px] flex-col gap-3.5 md:sticky max-md:max-w-none">
        <div class="panel rounded-[26px] p-[22px] shadow-[inset_0_1px_0_#fff,0_1px_0_#cad7c1,0_30px_50px_-28px_rgba(50,90,20,.5)]">
          <NuxtLink :to="`/agents/${listing.realtor.id}`" class="flex items-center gap-3.5 text-ink hover:text-ink">
            <img
              v-if="listing.realtor.avatar_url"
              :src="listing.realtor.avatar_url"
              :alt="listing.realtor.name"
              class="size-[60px] rounded-full border-[3px] border-white object-cover shadow-[0_0_0_1px_#cfe0c2,0_8px_16px_-6px_rgba(50,90,20,.45)]"
            >
            <span v-else class="grid size-[60px] place-items-center rounded-full bg-mist text-xl font-bold text-leaf">{{ listing.realtor.name.charAt(0) }}</span>
            <div>
              <div class="text-[17px] font-extrabold">{{ listing.realtor.name }}</div>
              <div class="text-[13px] text-sage">{{ listing.realtor.agency }}</div>
              <div v-if="listing.realtor.rating" class="mt-0.5 text-xs font-bold text-[#3f8f1c]">★ {{ listing.realtor.rating.toFixed(1) }} · {{ listing.realtor.deals_count }} deals</div>
            </div>
          </NuxtLink>

          <form class="mt-[18px] flex flex-col gap-2" @submit.prevent="sendInquiry">
            <input v-model="form.name" placeholder="Your name" class="input !py-3" required minlength="2" autocomplete="name">
            <p v-if="formErrors.name" class="field-error !mt-0">{{ formErrors.name }}</p>
            <input v-model="form.contact" placeholder="Phone or email" class="input !py-3" required minlength="5" autocomplete="email">
            <p v-if="formErrors.contact" class="field-error !mt-0">{{ formErrors.contact }}</p>
            <textarea ref="messageField" v-model="form.message" rows="3" class="input resize-y !py-3" required minlength="5" />
            <p v-if="formErrors.message || formErrors.apartment_id" class="field-error !mt-0">{{ formErrors.message || formErrors.apartment_id }}</p>
            <button type="submit" class="btn-lime-lg" :disabled="sending || sent">
              {{ sent ? '✓ Message sent' : sending ? 'Sending…' : 'Contact agent' }}
            </button>
            <button type="button" class="btn-secondary !rounded-[14px] !p-[13px] !font-bold" @click="scheduleTour">Schedule a tour</button>
          </form>
        </div>

        <button
          class="btn-secondary !rounded-[14px] !p-[13px] !font-bold"
          :class="favorites.isFavorite(listing.id) ? '!text-[#3f8f1c]' : ''"
          @click="favorites.toggle(listing.id)"
        >
          {{ favorites.isFavorite(listing.id) ? '♥ Saved' : '♡ Save listing' }}
        </button>

        <div v-if="isOwner" class="flex flex-wrap gap-2">
          <NuxtLink :to="`/my/${listing.id}/edit`" class="btn-secondary flex-1">Edit</NuxtLink>
          <button class="btn-secondary flex-1" :disabled="busy" @click="toggleSold">{{ listing.is_sold ? 'Back on market' : 'Mark as sold' }}</button>
          <button class="btn-danger flex-1" :disabled="busy" @click="remove">Delete</button>
        </div>
      </aside>
    </section>

    <PhotoLightbox v-model:open="lightboxOpen" v-model:index="photoIndex" :photos="photos" :title="listing.title" />

    <section v-if="similar.length" v-reveal class="mt-14">
      <h2 class="mb-5 mt-0 text-[32px] font-extrabold tracking-[-.03em]">Similar homes nearby</h2>
      <div class="grid gap-5 [grid-template-columns:repeat(auto-fill,minmax(min(100%,260px),1fr))]">
        <ListingTile v-for="item in similar" :key="item.id" :apartment="item" />
      </div>
    </section>
  </div>
</template>

<style scoped>
.gallery-arrow {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 20px;
  font-weight: 700;
  color: #1d2a1a;
  background: linear-gradient(#fff, #eef3ea);
  border: 1px solid #d3ddcb;
  box-shadow: inset 0 1px 0 #fff, 0 6px 14px -4px rgba(0, 0, 0, .3);
  transition: transform .6s cubic-bezier(.25, .8, .25, 1);
}

.gallery-arrow:hover { transform: scale(1.08); }

/* Thumbnails: a small lift + highlighted border on hover — no scale, it made them jitter. */
.thumb {
  box-shadow: 0 10px 20px -14px rgba(50, 90, 20, .5);
  transition: transform .35s cubic-bezier(.25, .8, .25, 1), opacity .3s, border-color .3s, box-shadow .3s;
  backface-visibility: hidden;
}

.thumb:hover {
  opacity: 1;
  transform: translateY(-2px);
  border-color: #b4ea7c;
  box-shadow: 0 16px 26px -14px rgba(50, 90, 20, .55);
}

.content-card {
  padding: 26px;
  border-radius: 24px;
  background: linear-gradient(#ffffff, #f5f8f2);
  border: 1px solid #d9e3d1;
  box-shadow: inset 0 1px 0 #fff, 0 1px 0 #cbd8c2, 0 18px 36px -24px rgba(50, 90, 20, .4);
}
</style>
