// Page guard: only logged-in users. Usage: definePageMeta({ middleware: 'auth' })
export default defineNuxtRouteMiddleware((to) => {
  if (!useAuthStore().isLoggedIn) {
    return navigateTo({ path: '/login', query: { redirect: to.fullPath } })
  }
})
