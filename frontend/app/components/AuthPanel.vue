<script setup lang="ts">
import type { FieldErrors } from '~/types/api'

/**
 * Sign in / Create account from the Auth mockup. The mode is the route (/login or /register),
 * the role is kept in ?role= so the two pages share it.
 */
const props = defineProps<{ mode: 'login' | 'register' }>()

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const role = ref<'client' | 'realtor'>(route.query.role === 'realtor' ? 'realtor' : 'client')
const isRealtor = computed(() => role.value === 'realtor')
const isRegister = computed(() => props.mode === 'register')

function selectRole(value: 'client' | 'realtor') {
  role.value = value
  done.value = false
  router.replace({ query: { ...route.query, role: value === 'realtor' ? 'realtor' : undefined } })
}

function switchMode(to: 'login' | 'register') {
  navigateTo({ path: to === 'login' ? '/login' : '/register', query: { ...route.query } })
}

const side = computed(() => (isRealtor.value
  ? {
      image: '/images/homely/auth-realtor.jpg',
      tag: 'For realtors',
      title: 'List faster. Get qualified leads.',
      text: 'Verified badge, lead inbox and listing analytics. First 10 listings free.',
    }
  : {
      image: '/images/homely/hero-living-room.jpg',
      tag: 'For home seekers',
      title: 'Save homes, get alerts, message agents.',
      text: 'Your favorites and searches sync across devices. Always free for clients.',
    }))

const title = computed(() => (isRegister.value
  ? (isRealtor.value ? 'Create realtor account' : 'Create your account')
  : (isRealtor.value ? 'Sign in as realtor' : 'Sign in as client')))

const roles = [
  { key: 'client' as const, label: 'Client', letter: 'C', desc: 'Buy or rent a home, save favorites' },
  { key: 'realtor' as const, label: 'Realtor', letter: 'R', desc: 'Post listings and manage leads' },
]

// ---------- Form ----------
const form = reactive({ name: '', email: '', password: '', agency: '', license_number: '', remember: false })
const errors = ref<FieldErrors>({})
const message = ref('')
const submitting = ref(false)
const done = ref(false)
const socialMessage = 'Social sign-in isn\'t connected in the demo — use email and password.'

async function submit() {
  submitting.value = true
  errors.value = {}
  message.value = ''
  try {
    if (isRegister.value) {
      await auth.register({
        name: form.name,
        email: form.email,
        password: form.password,
        role: role.value,
        ...(isRealtor.value ? { agency: form.agency || undefined, license_number: form.license_number || undefined } : {}),
      })
    } else {
      await auth.login(form.email, form.password)
    }

    const redirect = route.query.redirect as string | undefined
    if (redirect) return navigateTo(redirect)
    done.value = true
  } catch (e) {
    errors.value = fieldErrors(e)
    message.value = Object.keys(errors.value).length ? '' : errorMessage(e)
  } finally {
    submitting.value = false
  }
}

const doneState = computed(() => ({
  title: isRegister.value ? (isRealtor.value ? 'Realtor account created' : 'Account created') : 'You are signed in',
  text: isRealtor.value
    ? (isRegister.value ? 'We will verify your license within 24 hours.' : 'Your dashboard and leads are ready.')
    : 'Your saved homes and searches are synced.',
  cta: auth.canManageListings ? 'Post a listing' : 'Browse homes',
  to: auth.canManageListings ? '/my/new' : '/search',
}))
</script>

<template>
  <div class="mx-auto flex max-w-[1100px] flex-wrap items-stretch gap-7 pt-2">
    <section class="relative min-h-[560px] min-w-0 flex-[1_1_380px] overflow-hidden rounded-[30px] border-[6px] border-white shadow-[0_1px_0_#cfdcc5,0_40px_70px_-34px_rgba(50,90,20,.55)]">
      <Transition name="side" mode="out-in">
        <img :key="side.image" :src="side.image" alt="" class="absolute inset-0 size-full object-cover">
      </Transition>
      <div class="absolute inset-0" style="background:linear-gradient(rgba(255,255,255,.35),rgba(255,255,255,0) 35%,rgba(20,40,10,.55))" />
      <div class="absolute inset-x-6 bottom-6 text-white">
        <div class="tag inline-flex !rounded-[9px] !px-[11px] !py-[5px] !text-xs !shadow-[inset_0_1px_0_rgba(255,255,255,.9)]">{{ side.tag }}</div>
        <div class="mt-3 text-[30px] font-extrabold leading-[1.1] tracking-[-.03em] [text-shadow:0_2px_12px_rgba(0,0,0,.3)] [text-wrap:balance]">{{ side.title }}</div>
        <div class="mt-2 text-[15px] leading-normal opacity-95 [text-shadow:0_1px_8px_rgba(0,0,0,.35)]">{{ side.text }}</div>
      </div>
    </section>

    <section
      class="flex min-w-0 flex-[1_1_420px] flex-col gap-5 rounded-[30px] border border-[#d7e2cf] p-7"
      style="background:linear-gradient(#ffffff,#eef4e9);box-shadow:inset 0 1px 0 #fff,0 1px 0 #cad7c1,0 36px 60px -34px rgba(50,90,20,.5)"
    >
      <div>
        <h1 class="m-0 text-[32px] font-extrabold tracking-[-.03em]">{{ title }}</h1>
        <p class="mb-0 mt-1.5 text-[15px] text-sage">{{ isRegister ? 'Takes less than a minute.' : 'Welcome back to Homely.' }}</p>
      </div>

      <div class="grid grid-cols-2 gap-3" style="perspective:800px">
        <button
          v-for="r in roles"
          :key="r.key"
          type="button"
          class="role-card"
          :class="{ 'role-card-on': role === r.key }"
          :aria-pressed="role === r.key"
          @click="selectRole(r.key)"
        >
          <span class="flex items-center justify-between">
            <span class="role-letter">{{ r.letter }}</span>
            <span class="role-dot" :class="{ 'role-dot-on': role === r.key }" />
          </span>
          <span class="mt-1.5 text-base font-extrabold">{{ r.label }}</span>
          <span class="text-[13px] leading-[1.4] text-sage">{{ r.desc }}</span>
        </button>
      </div>

      <div class="flex gap-1 rounded-[14px] bg-[#e6ede0] p-[5px] shadow-[inset_0_2px_4px_rgba(40,70,20,.12),0_1px_0_#fff]">
        <button
          v-for="m in ([{ key: 'login', label: 'Sign in' }, { key: 'register', label: 'Create account' }] as const)"
          :key="m.key"
          type="button"
          class="flex-1 cursor-pointer whitespace-nowrap rounded-[10px] border p-2.5 text-sm font-bold transition duration-500 hover:scale-[1.02]"
          :class="mode === m.key ? 'border-[#d3ddcb] text-ink shadow-[inset_0_1px_0_#fff,0_3px_6px_-2px_rgba(40,70,20,.2)]' : 'border-transparent bg-transparent text-[#5a6c52]'"
          :style="mode === m.key ? 'background:linear-gradient(#fff,#f1f6ec)' : ''"
          @click="switchMode(m.key)"
        >
          {{ m.label }}
        </button>
      </div>

      <div
        v-if="done"
        class="rounded-[22px] border border-[#9fd66a] p-[26px] text-center [animation:h-pop_.7s_cubic-bezier(.25,.8,.25,1)]"
        style="background:linear-gradient(#e9fbcf,#c4ef8e);box-shadow:inset 0 2px 0 rgba(255,255,255,.9),0 14px 26px -14px rgba(80,150,30,.55)"
      >
        <div class="text-[22px] font-extrabold text-[#183d05]">{{ doneState.title }}</div>
        <div class="mt-1.5 text-[15px] text-[#2f5518]">{{ doneState.text }}</div>
        <div class="mt-[18px] flex flex-wrap justify-center gap-2.5">
          <NuxtLink :to="doneState.to" class="btn-dark !rounded-[13px] !px-5 !py-3">{{ doneState.cta }}</NuxtLink>
          <NuxtLink to="/" class="btn-secondary !rounded-[13px] !px-5 !py-3 !font-bold !text-leaf-dark">Home</NuxtLink>
        </div>
      </div>

      <form v-else class="flex flex-col gap-2.5" @submit.prevent="submit">
        <label v-if="isRegister" class="field-box">
          <span class="field-caption">Full name</span>
          <input v-model="form.name" placeholder="Emma Carter" class="field-input" required autocomplete="name">
        </label>
        <p v-if="errors.name" class="field-error !mt-0">{{ errors.name }}</p>

        <label class="field-box">
          <span class="field-caption">Email</span>
          <input v-model="form.email" type="email" placeholder="you@example.com" class="field-input" required autocomplete="email">
        </label>
        <p v-if="errors.email" class="field-error !mt-0">{{ errors.email }}</p>

        <label class="field-box">
          <span class="field-caption">Password</span>
          <input
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            class="field-input"
            required
            :minlength="isRegister ? 8 : undefined"
            :autocomplete="isRegister ? 'new-password' : 'current-password'"
          >
        </label>
        <p v-if="errors.password" class="field-error !mt-0">{{ errors.password }}</p>

        <template v-if="isRegister && isRealtor">
          <div class="grid gap-2.5 [grid-template-columns:repeat(auto-fit,minmax(160px,1fr))]">
            <label class="field-box">
              <span class="field-caption">Agency</span>
              <input v-model="form.agency" placeholder="Greenleaf Realty" class="field-input" autocomplete="organization">
            </label>
            <label class="field-box">
              <span class="field-caption">License #</span>
              <input v-model="form.license_number" placeholder="10401234567" class="field-input">
            </label>
          </div>
          <div class="text-[13px] text-sage">We verify your license within 24 hours. Until then your listings are saved as drafts.</div>
        </template>

        <div v-if="!isRegister" class="flex items-center justify-between text-sm">
          <label class="flex items-center gap-2 text-[#3d4d37]">
            <input v-model="form.remember" type="checkbox" class="size-4 accent-[#6fc22f]"> Remember me
          </label>
          <a href="#" class="font-semibold" @click.prevent="message = 'Password reset isn\'t available in the demo yet.'">Forgot password?</a>
        </div>

        <p v-if="message" class="field-error !mt-0">{{ message }}</p>

        <button type="submit" class="btn-lime-lg" :disabled="submitting">
          {{ submitting ? 'Please wait…' : isRegister ? 'Create account' : (isRealtor ? 'Sign in as realtor' : 'Sign in as client') }}
        </button>

        <div class="my-1 flex items-center gap-3 text-[13px] text-[#8a9a82]">
          <span class="h-px flex-1 bg-line" />or<span class="h-px flex-1 bg-line" />
        </div>
        <div class="flex flex-wrap gap-2.5">
          <button type="button" class="btn-secondary flex-[1_1_150px] !rounded-[13px] !p-3 !text-sm !font-bold" @click="message = socialMessage">Continue with Google</button>
          <button type="button" class="btn-secondary flex-[1_1_150px] !rounded-[13px] !p-3 !text-sm !font-bold" @click="message = socialMessage">Continue with Apple</button>
        </div>

        <div class="mt-1 text-center text-sm text-sage">
          {{ isRegister ? 'Already have an account?' : 'New to Homely?' }}
          <button type="button" class="cursor-pointer border-0 bg-transparent p-0 font-bold text-[#3f8f1c]" @click="switchMode(isRegister ? 'login' : 'register')">
            {{ isRegister ? 'Sign in' : 'Create account' }}
          </button>
        </div>

      </form>
    </section>

    <p v-if="!isRegister" class="m-0 w-full text-center text-xs text-sage">
      Demo accounts: <code>emma.carter@example.com</code> (realtor) or <code>client@example.com</code> — password <code>password</code>
    </p>
  </div>
</template>

<style scoped>
.role-card {
  cursor: pointer;
  text-align: left;
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 16px;
  border-radius: 20px;
  color: #1d2a1a;
  font: inherit;
  background: linear-gradient(#fff, #f5f8f2);
  border: 2px solid #dde6d6;
  box-shadow: inset 0 1px 0 #fff, 0 6px 14px -10px rgba(40, 70, 20, .3);
  transition: transform .6s cubic-bezier(.25, .8, .25, 1), box-shadow .5s, border-color .4s;
}

.role-card:hover { transform: translateY(-3px) scale(1.02); }

.role-card-on {
  background: linear-gradient(#f6ffe8, #e2f7c6);
  border-color: #7ccc38;
  box-shadow: inset 0 1px 0 #fff, 0 16px 28px -14px rgba(80, 150, 30, .6);
  transform: translateY(-2px);
}

.role-letter {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 800;
  color: #fff;
  background: linear-gradient(160deg, #d8fb9c, #8fd84a 45%, #5aa825);
  border: 1px solid #4a9418;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, .9), inset 0 -3px 6px rgba(40, 90, 10, .3), 0 6px 12px -4px rgba(80, 150, 30, .55);
  text-shadow: 0 1px 0 #3f8a14;
}

.role-dot {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  border: 2px solid #cfdac7;
  background: #fff;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, .15);
  transition: background .3s;
}

.role-dot-on {
  border-color: #fff;
  background: radial-gradient(circle at 35% 30%, #e8ffc4, #6fc22f 60%, #4a9418);
}

.side-enter-active, .side-leave-active { transition: opacity .35s ease; }
.side-enter-from, .side-leave-to { opacity: 0; }
</style>
