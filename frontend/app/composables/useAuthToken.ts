/**
 * Sanctum token shared across the app (useState) and persisted in a cookie,
 * so it survives reloads and is available during SSR.
 */
export function useAuthToken() {
  const cookie = useCookie<string | null>('auth_token', {
    maxAge: 60 * 60 * 24 * 30,
    sameSite: 'lax',
  })
  const token = useState<string | null>('auth_token', () => cookie.value ?? null)

  function set(value: string | null) {
    token.value = value
    cookie.value = value
  }

  return { token: readonly(token), set }
}
