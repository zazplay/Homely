const STORAGE_KEY = 'homely:favorites'

/**
 * Saved listings (the ♡ on cards). Kept in localStorage — no account needed.
 */
export function useFavorites() {
  const ids = useState<number[]>('favorites', () => [])
  const loaded = useState('favorites-loaded', () => false)

  // Read after mount, not during setup: SSR has no localStorage, and reading it during
  // hydration would render a different heart than the server did (hydration mismatch).
  onMounted(() => {
    if (loaded.value) return
    loaded.value = true
    try {
      ids.value = JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]')
    } catch {
      ids.value = []
    }
  })

  function toggle(id: number) {
    ids.value = ids.value.includes(id) ? ids.value.filter(x => x !== id) : [...ids.value, id]
    localStorage.setItem(STORAGE_KEY, JSON.stringify(ids.value))
  }

  return {
    isFavorite: (id: number) => ids.value.includes(id),
    toggle,
  }
}
