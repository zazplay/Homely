<script setup lang="ts">
import type { Apartment, Paginated } from '~/types/api'

definePageMeta({ middleware: 'realtor' })
useHead({ title: 'My listings — Homely' })

const api = useApi()
const auth = useAuthStore()

const { data, status } = await useAsyncData('my-apartments', () => api<Paginated<Apartment>>('/my/apartments'), { lazy: true })

const stateTag = (a: Apartment) => (a.is_sold ? 'Sold' : !a.is_published ? 'Draft' : dealTypeLabel(a.deal_type))
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="m-0 text-[clamp(32px,4vw,46px)] font-extrabold tracking-[-.035em]">My listings</h1>
        <p v-if="data" class="mt-1.5 text-base text-sage">{{ data.meta.total }} total — drafts and sold included</p>
      </div>
      <div class="flex flex-wrap gap-2.5">
        <NuxtLink to="/my/inquiries" class="btn-secondary">Leads inbox</NuxtLink>
        <NuxtLink v-if="auth.user" :to="`/agents/${auth.user.id}`" class="btn-secondary">Public profile</NuxtLink>
        <NuxtLink to="/my/new" class="btn-primary">+ New listing</NuxtLink>
      </div>
    </div>

    <div v-if="status === 'pending' && !data" class="grid gap-5 [grid-template-columns:repeat(auto-fill,minmax(min(100%,260px),1fr))]">
      <ListingTileSkeleton v-for="n in 3" :key="n" />
    </div>

    <div v-else-if="data?.data.length" class="grid gap-5 [grid-template-columns:repeat(auto-fill,minmax(min(100%,260px),1fr))]">
      <div v-for="apartment in data.data" :key="apartment.id" class="flex flex-col gap-2">
        <ListingTile :apartment="apartment" :tag="stateTag(apartment)" show-specs class="flex-1" />
        <NuxtLink :to="`/my/${apartment.id}/edit`" class="btn-secondary w-full">Edit</NuxtLink>
      </div>
    </div>

    <div v-else class="rounded-3xl border border-dashed border-[#cfdcc5] px-5 py-[60px] text-center" style="background:linear-gradient(#ffffff,#f5f8f2)">
      <div class="text-[22px] font-extrabold">No listings yet</div>
      <div class="mt-1.5 text-[15px] text-sage">Your first 10 listings are free.</div>
      <NuxtLink to="/my/new" class="btn-primary mt-5">Create the first one</NuxtLink>
    </div>
  </div>
</template>
