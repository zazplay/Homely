<script setup lang="ts">
import type { Inquiry, Paginated } from '~/types/api'

definePageMeta({ middleware: 'realtor' })
useHead({ title: 'Leads inbox — Homely' })

const api = useApi()
const { data, status, refresh } = await useAsyncData('my-inquiries', () => api<Paginated<Inquiry>>('/my/inquiries'), { lazy: true })

const unread = computed(() => data.value?.data.filter(i => !i.read_at).length ?? 0)

async function markRead(inquiry: Inquiry) {
  await api(`/my/inquiries/${inquiry.id}/read`, { method: 'POST' })
  await refresh()
}

function formatDate(value: string) {
  return new Date(value).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })
}

const isEmail = (contact: string) => contact.includes('@')
</script>

<template>
  <div class="mx-auto flex max-w-[860px] flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="m-0 text-[clamp(32px,4vw,46px)] font-extrabold tracking-[-.035em]">Leads inbox</h1>
        <p v-if="data" class="mt-1.5 text-base text-sage">{{ data.meta.total }} messages · {{ unread }} unread</p>
      </div>
      <NuxtLink to="/my" class="btn-secondary">← My listings</NuxtLink>
    </div>

    <div v-if="status === 'pending' && !data" class="flex flex-col gap-3.5">
      <div v-for="n in 3" :key="n" class="skeleton h-28 rounded-[22px]" />
    </div>

    <template v-else-if="data?.data.length">
      <article
        v-for="inquiry in data.data"
        :key="inquiry.id"
        class="rounded-[22px] border p-[22px]"
        :class="inquiry.read_at ? 'border-[#d9e3d1]' : 'border-[#86c94d]'"
        :style="inquiry.read_at
          ? 'background:linear-gradient(#ffffff,#f5f8f2);box-shadow:inset 0 1px 0 #fff,0 14px 28px -22px rgba(50,90,20,.4)'
          : 'background:linear-gradient(#fbfff5,#eefbe0);box-shadow:inset 0 1px 0 #fff,0 16px 30px -20px rgba(80,150,30,.55)'"
      >
        <div class="flex flex-wrap items-baseline justify-between gap-3">
          <div class="font-bold">
            {{ inquiry.name }}
            <a :href="isEmail(inquiry.contact) ? `mailto:${inquiry.contact}` : `tel:${inquiry.contact.replace(/[^\d+]/g, '')}`" class="ml-1 font-semibold">{{ inquiry.contact }}</a>
          </div>
          <div class="text-[13px] text-sage">{{ formatDate(inquiry.created_at) }}</div>
        </div>
        <div v-if="inquiry.apartment_id" class="mt-1 text-[13px] text-sage">
          about <NuxtLink :to="`/apartments/${inquiry.apartment_id}`">{{ inquiry.apartment_title }}</NuxtLink>
        </div>
        <p class="mb-0 mt-2.5 whitespace-pre-line text-[15px] leading-[1.6] text-[#3d4d37]">{{ inquiry.message }}</p>
        <button v-if="!inquiry.read_at" class="btn-soft mt-3" @click="markRead(inquiry)">Mark as read</button>
      </article>
    </template>

    <div v-else class="rounded-3xl border border-dashed border-[#cfdcc5] px-5 py-[60px] text-center" style="background:linear-gradient(#ffffff,#f5f8f2)">
      <div class="text-[22px] font-extrabold">No leads yet</div>
      <div class="mt-1.5 text-[15px] text-sage">Messages from the "Contact agent" forms land here.</div>
    </div>
  </div>
</template>
