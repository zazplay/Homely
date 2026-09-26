// Page guard: only realtors and admins. Usage: definePageMeta({ middleware: 'realtor' })
export default defineNuxtRouteMiddleware((to) => {
  const auth = useAuthStore()

  if (!auth.isLoggedIn) {
    return navigateTo({ path: '/login', query: { redirect: to.fullPath } })
  }

  if (!auth.canManageListings) {
    return navigateTo('/')
  }
})
