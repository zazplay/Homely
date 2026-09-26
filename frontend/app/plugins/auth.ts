// Runs once on app start (on the server during SSR, then state is handed to the browser):
// if there is a saved token, load the current user before any page renders.
export default defineNuxtPlugin(async () => {
  await useAuthStore().fetchMe()
})
