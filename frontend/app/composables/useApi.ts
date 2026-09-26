/**
 * $fetch preconfigured for the Laravel API: base URL, JSON, and the Bearer token if logged in.
 * Usage: const api = useApi(); await api<Apartment>('/apartments/1')
 */
export function useApi() {
  const config = useRuntimeConfig()
  const { token } = useAuthToken()

  return $fetch.create({
    baseURL: config.public.apiBase,
    headers: { Accept: 'application/json' },
    onRequest({ options }) {
      if (token.value) {
        options.headers.set('Authorization', `Bearer ${token.value}`)
      }
    },
  })
}
