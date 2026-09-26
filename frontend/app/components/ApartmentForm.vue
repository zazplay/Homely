<script setup lang="ts">
import type { Apartment, ApartmentPayload, DealType, Feature, FieldErrors, ListingBadge, PropertyType } from '~/types/api'

const props = withDefaults(defineProps<{
  initial?: Apartment | null
  withPhotos?: boolean
  submitting?: boolean
  submitLabel?: string
  errors?: FieldErrors
}>(), {
  initial: null,
  withPhotos: false,
  submitting: false,
  submitLabel: 'Save',
  errors: () => ({}),
})

const emit = defineEmits<{ submit: [payload: ApartmentPayload] }>()

// The UI works with dollars and sq ft; the API with cents and m².
const form = reactive({
  title: props.initial?.title ?? '',
  description: props.initial?.description ?? '',
  deal_type: (props.initial?.deal_type ?? 'sale') as DealType,
  property_type: (props.initial?.property_type ?? 'apartment') as PropertyType,
  price: props.initial ? props.initial.price_cents / 100 : null as number | null,
  city: props.initial?.city ?? '',
  address: props.initial?.address ?? '',
  rooms: props.initial?.rooms ?? 1,
  bathrooms: props.initial?.bathrooms ?? null as number | null,
  area_sqft: props.initial ? sqft(props.initial.area) : null as number | null,
  floor: props.initial?.floor ?? null as number | null,
  total_floors: props.initial?.total_floors ?? null as number | null,
  year_built: props.initial?.year_built ?? null as number | null,
  features: [...(props.initial?.features ?? [])] as Feature[],
  is_published: props.initial?.is_published ?? true,
  badge: (props.initial?.badge ?? '') as ListingBadge | '',
  is_new_build: props.initial?.is_new_build ?? false,
  photos: [] as string[],
})

const badgeOptions: { value: ListingBadge | '', label: string }[] = [
  { value: '', label: 'None' },
  { value: 'new', label: 'New' },
  { value: 'hot', label: 'Hot' },
  { value: 'price_drop', label: 'Price drop' },
]

function toggleFeature(feature: Feature) {
  form.features = form.features.includes(feature) ? form.features.filter(f => f !== feature) : [...form.features, feature]
}

function toNullableNumber(value: number | string | null): number | null {
  return value === '' || value === null ? null : Number(value)
}

function submit() {
  const payload: ApartmentPayload = {
    title: form.title,
    description: form.description || null,
    deal_type: form.deal_type,
    property_type: form.property_type,
    price_cents: Math.round(Number(form.price ?? 0) * 100),
    city: form.city,
    address: form.address,
    rooms: Number(form.rooms),
    bathrooms: toNullableNumber(form.bathrooms),
    area: sqftToM2(Number(form.area_sqft ?? 0)),
    floor: toNullableNumber(form.floor),
    total_floors: toNullableNumber(form.total_floors),
    year_built: toNullableNumber(form.year_built),
    features: form.features,
    is_published: form.is_published,
    badge: form.badge || null,
    is_new_build: form.is_new_build,
  }

  if (props.withPhotos) {
    payload.photos = form.photos
  }

  emit('submit', payload)
}
</script>

<template>
  <form class="flex flex-col gap-5" @submit.prevent="submit">
    <div class="grid gap-3 sm:grid-cols-2">
      <div>
        <div class="mb-2 text-[13px] font-bold">Deal</div>
        <div class="track">
          <button
            v-for="type in (['sale', 'rent'] as const)"
            :key="type"
            type="button"
            :class="form.deal_type === type ? 'chip-on' : 'chip'"
            class="flex-1 !rounded-[9px] !py-2 !font-bold hover:!translate-y-0"
            @click="form.deal_type = type"
          >
            {{ dealTypeLabel(type) }}
          </button>
        </div>
      </div>
      <div>
        <div class="mb-2 text-[13px] font-bold">Property type</div>
        <div class="flex flex-wrap gap-1.5">
          <button
            v-for="(label, type) in propertyTypeLabels"
            :key="type"
            type="button"
            :class="form.property_type === type ? 'chip-on' : 'chip'"
            @click="form.property_type = type"
          >
            {{ label }}
          </button>
        </div>
        <p v-if="errors.property_type" class="field-error">{{ errors.property_type }}</p>
      </div>
    </div>

    <div>
      <label class="field-box">
        <span class="field-caption">Title</span>
        <input v-model="form.title" class="field-input" required minlength="3" placeholder="Sunny 3-bed with balcony">
      </label>
      <p v-if="errors.title" class="field-error">{{ errors.title }}</p>
    </div>

    <div>
      <label class="field-box">
        <span class="field-caption">Description</span>
        <textarea v-model="form.description" class="field-input min-h-28 resize-y" placeholder="Bright corner unit, renovated in 2024… (blank line = new paragraph)" />
      </label>
      <p v-if="errors.description" class="field-error">{{ errors.description }}</p>
    </div>

    <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(180px,1fr))]">
      <div>
        <label class="field-box">
          <span class="field-caption">Price, ${{ form.deal_type === 'rent' ? ' / month' : '' }}</span>
          <input v-model.number="form.price" type="number" min="1" step="1" class="field-input" required placeholder="785000">
        </label>
        <p v-if="errors.price_cents" class="field-error">{{ errors.price_cents }}</p>
      </div>
      <div>
        <label class="field-box">
          <span class="field-caption">City</span>
          <input v-model="form.city" class="field-input" required placeholder="Brooklyn">
        </label>
        <p v-if="errors.city" class="field-error">{{ errors.city }}</p>
      </div>
      <div>
        <label class="field-box">
          <span class="field-caption">Address</span>
          <input v-model="form.address" class="field-input" required placeholder="214 7th Ave, Park Slope">
        </label>
        <p v-if="errors.address" class="field-error">{{ errors.address }}</p>
      </div>
    </div>

    <div class="grid gap-3 [grid-template-columns:repeat(auto-fit,minmax(120px,1fr))]">
      <div>
        <label class="field-box">
          <span class="field-caption">Bedrooms</span>
          <input v-model.number="form.rooms" type="number" min="0" max="20" class="field-input" required>
        </label>
        <p class="mt-1 text-xs text-muted">0 = studio</p>
        <p v-if="errors.rooms" class="field-error">{{ errors.rooms }}</p>
      </div>
      <div>
        <label class="field-box">
          <span class="field-caption">Baths</span>
          <input v-model.number="form.bathrooms" type="number" min="0" max="20" class="field-input">
        </label>
        <p v-if="errors.bathrooms" class="field-error">{{ errors.bathrooms }}</p>
      </div>
      <div>
        <label class="field-box">
          <span class="field-caption">Area, sq ft</span>
          <input v-model.number="form.area_sqft" type="number" min="60" step="1" class="field-input" required placeholder="1420">
        </label>
        <p v-if="errors.area" class="field-error">{{ errors.area }}</p>
      </div>
      <div>
        <label class="field-box">
          <span class="field-caption">Floor</span>
          <input v-model.number="form.floor" type="number" min="0" class="field-input">
        </label>
        <p v-if="errors.floor" class="field-error">{{ errors.floor }}</p>
      </div>
      <div>
        <label class="field-box">
          <span class="field-caption">Of floors</span>
          <input v-model.number="form.total_floors" type="number" min="1" class="field-input">
        </label>
        <p v-if="errors.total_floors" class="field-error">{{ errors.total_floors }}</p>
      </div>
      <div>
        <label class="field-box">
          <span class="field-caption">Built</span>
          <input v-model.number="form.year_built" type="number" min="1800" max="2100" class="field-input" placeholder="1928">
        </label>
        <p v-if="errors.year_built" class="field-error">{{ errors.year_built }}</p>
      </div>
    </div>

    <div>
      <div class="mb-2 text-[13px] font-bold">Features</div>
      <div class="flex flex-wrap gap-1.5">
        <button
          v-for="(label, feature) in featureLabels"
          :key="feature"
          type="button"
          :class="form.features.includes(feature) ? 'chip-on' : 'chip'"
          @click="toggleFeature(feature)"
        >
          {{ label }}
        </button>
      </div>
      <p v-if="errors.features" class="field-error">{{ errors.features }}</p>
    </div>

    <div>
      <div class="mb-2 text-[13px] font-bold">Badge on the card</div>
      <div class="flex flex-wrap gap-1.5">
        <button
          v-for="option in badgeOptions"
          :key="option.value"
          type="button"
          :class="form.badge === option.value ? 'chip-on' : 'chip'"
          @click="form.badge = option.value"
        >
          {{ option.label }}
        </button>
      </div>
    </div>

    <div v-if="withPhotos">
      <div class="mb-2 text-[13px] font-bold">Photos</div>
      <PhotoPicker v-model="form.photos" :errors="errors" />
    </div>

    <div class="flex flex-col gap-3">
      <button type="button" class="flex cursor-pointer items-center justify-between gap-2.5 border-0 bg-transparent p-0 text-sm font-semibold text-ink" role="switch" :aria-checked="form.is_published" @click="form.is_published = !form.is_published">
        Published (visible in the catalog)
        <HomelySwitch :on="form.is_published" />
      </button>
      <p v-if="errors.is_published" class="field-error !mt-0">{{ errors.is_published }}</p>
      <button type="button" class="flex cursor-pointer items-center justify-between gap-2.5 border-0 bg-transparent p-0 text-sm font-semibold text-ink" role="switch" :aria-checked="form.is_new_build" @click="form.is_new_build = !form.is_new_build">
        New build
        <HomelySwitch :on="form.is_new_build" />
      </button>
    </div>

    <button type="submit" class="btn-lime-lg" :disabled="submitting">
      {{ submitting ? 'Saving…' : submitLabel }}
    </button>
  </form>
</template>
