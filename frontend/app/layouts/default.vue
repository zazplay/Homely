<script setup lang="ts">
const auth = useAuthStore()
const route = useRoute()

const nav = [
  { label: 'Buy', to: { path: '/search', query: { deal_type: 'sale' } } },
  { label: 'Rent', to: { path: '/search', query: { deal_type: 'rent' } } },
  { label: 'New builds', to: { path: '/search', query: { new_build: '1' } } },
  { label: 'Agents', to: '/agents' },
]

// The header bar "presses in" slightly once the page is scrolled (mockup behaviour).
const scrolled = ref(false)
const onScroll = () => (scrolled.value = window.scrollY > 20)

// User menu (replaces "Sign in" when logged in, same footprint).
const menuOpen = ref(false)
const menu = ref<HTMLElement | null>(null)
function onDocumentClick(e: MouseEvent) {
  if (menu.value && !menu.value.contains(e.target as Node)) menuOpen.value = false
}
watch(() => route.fullPath, () => (menuOpen.value = false))

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
  document.addEventListener('click', onDocumentClick)
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  document.removeEventListener('click', onDocumentClick)
})

async function logout() {
  menuOpen.value = false
  await auth.logout()
  await navigateTo('/')
}
</script>

<template>
  <div class="min-h-screen" style="background:radial-gradient(1200px 500px at 70% -100px,#e3f7c9 0%,rgba(227,247,201,0) 70%),linear-gradient(#f7faf4,#eef3ea)">
    <HomelyPreloader />

    <header class="sticky top-0 z-20 px-6 py-3.5">
      <div
        class="mx-auto flex max-w-[1240px] origin-top items-center gap-4 rounded-[20px] border border-line py-2.5 pl-[18px] pr-3 [animation:h-drop_.9s_cubic-bezier(.2,.9,.25,1.1)_backwards]"
        :style="{
          background: 'linear-gradient(#ffffff,#f1f5ee)',
          transition: 'transform .5s cubic-bezier(.2,.8,.2,1), box-shadow .5s ease',
          transform: scrolled ? 'scale(.985)' : 'none',
          boxShadow: scrolled
            ? 'inset 0 1px 0 #fff,0 1px 0 #cfdac7,0 18px 40px -14px rgba(60,100,30,.4)'
            : 'inset 0 1px 0 #fff,0 1px 0 #cfdac7,0 10px 30px -12px rgba(60,100,30,.25)',
        }"
      >
        <NuxtLink to="/" class="flex items-center gap-2.5 text-ink hover:text-ink">
          <HomelyLogo />
          <!-- On phones the full header (as in the mockup) is wider than the screen: keep only the icon. -->
          <span class="text-xl font-extrabold tracking-[-.02em] max-[480px]:hidden">Homely<span class="text-[#5aa825]">.</span></span>
        </NuxtLink>

        <!-- overflow:hidden clips links on narrow screens; the padding/negative margin
             keeps room for the hover lift + shadow so their top edge isn't cut off. -->
        <nav class="-mx-1 -my-1.5 flex min-w-0 flex-auto gap-0.5 overflow-hidden whitespace-nowrap px-1 py-1.5 max-[480px]:hidden">
          <NuxtLink
            v-for="(item, i) in nav"
            :key="item.label"
            :to="item.to"
            class="nav-link"
            :style="{ animation: `h-in .7s cubic-bezier(.25,.8,.25,1) ${0.25 + i * 0.08}s backwards` }"
          >
            {{ item.label }}
          </NuxtLink>
        </nav>

        <div class="ml-auto flex shrink-0 items-center gap-2.5">
          <div v-if="auth.isLoggedIn" ref="menu" class="relative">
            <button class="btn-secondary !py-[7px] !pl-[7px]" :aria-expanded="menuOpen" @click="menuOpen = !menuOpen">
              <img v-if="auth.user?.avatar_url" :src="auth.user.avatar_url" alt="" class="size-7 rounded-full object-cover">
              <span v-else class="grid size-7 place-items-center rounded-full bg-mist text-xs font-bold text-leaf">{{ auth.user?.name.charAt(0) }}</span>
              <span class="max-w-[110px] truncate">{{ auth.user?.name.split(' ')[0] }}</span>
            </button>
            <div
              v-if="menuOpen"
              class="absolute right-0 top-[calc(100%+8px)] z-30 flex min-w-[190px] flex-col rounded-2xl border border-line bg-white p-1.5 shadow-[0_18px_40px_-14px_rgba(60,100,30,.45)] [animation:h-pop-soft_.35s_cubic-bezier(.25,.8,.25,1)]"
            >
              <template v-if="auth.canManageListings">
                <NuxtLink to="/my" class="menu-item">My listings</NuxtLink>
                <NuxtLink to="/my/inquiries" class="menu-item">Leads inbox</NuxtLink>
                <NuxtLink v-if="auth.user" :to="`/agents/${auth.user.id}`" class="menu-item">Public profile</NuxtLink>
              </template>
              <NuxtLink to="/search" class="menu-item">Browse homes</NuxtLink>
              <button class="menu-item text-left" @click="logout">Log out</button>
            </div>
          </div>
          <NuxtLink v-else to="/login" class="btn-secondary">Sign in</NuxtLink>

          <NuxtLink to="/my/new" class="btn-primary !px-[18px]" aria-label="Post a listing"><span class="max-[379px]:hidden">+ Post a listing</span><span class="min-[380px]:hidden">+ Post</span></NuxtLink>
        </div>
      </div>
    </header>

    <!-- Pages with definePageMeta({ fullWidth: true }) lay out their own sections. -->
    <main :class="route.meta.fullWidth ? '' : 'mx-auto max-w-[1240px] px-6 pb-[60px] pt-4'">
      <slot />
    </main>

    <footer class="border-t border-line" style="background:linear-gradient(#f1f5ee,#e9efe4)">
      <div class="mx-auto flex max-w-[1240px] flex-wrap items-center justify-between gap-6 px-6 py-7 text-sm text-sage">
        <div class="font-extrabold text-ink">
          Homely<span class="text-[#5aa825]">.</span> <span class="font-normal text-sage">© 2026</span>
        </div>
        <div class="flex flex-wrap gap-5">
          <a href="#" class="text-sage hover:text-leaf">About</a>
          <NuxtLink :to="{ path: '/register', query: { role: 'realtor' } }" class="text-sage hover:text-leaf">For agents</NuxtLink>
          <a href="#" class="text-sage hover:text-leaf">Help</a>
          <a href="#" class="text-sage hover:text-leaf">Privacy</a>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.nav-link {
  padding: 8px 14px;
  border-radius: 10px;
  color: #2a3a26;
  font-weight: 500;
  font-size: 15px;
  border: 1px solid transparent;
  background-color: rgba(255, 255, 255, 0);
  transition: transform .6s cubic-bezier(.25, .8, .25, 1), background-color .35s ease, box-shadow .45s ease, border-color .35s ease, color .3s;
}

.nav-link:hover {
  color: #1f4a06;
  transform: translateY(-1px) scale(1.03);
  background-color: #eef8e2;
  border-color: #cfe6b8;
  box-shadow: inset 0 1px 0 #fff, inset 0 8px 10px -6px rgba(255, 255, 255, 1), 0 8px 14px -8px rgba(80, 150, 30, .45);
}

.nav-link:active { transform: scale(.97); }

.menu-item {
  cursor: pointer;
  border-radius: 10px;
  padding: 9px 12px;
  font-size: 14px;
  font-weight: 600;
  color: #2a3a26;
}

.menu-item:hover { background: #eef8e2; color: #1f4a06; }
</style>
