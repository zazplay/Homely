import type { Directive } from 'vue'

/**
 * Motion effects from the Homely mockup, as directives:
 *   v-tilt   — 3D tilt of a card following the mouse; v-tilt="{ x: 9, y: 7, lift: 4 }" to tune
 *   v-reveal — section slides in when scrolled into view
 * Both are no-ops on the server and for users with "reduce motion" enabled.
 */

interface TiltOptions {
  /** Max rotation around Y (deg) at the card edge. */
  x?: number
  /** Max rotation around X (deg). */
  y?: number
  /** Lift in px while hovered. */
  lift?: number
  scale?: number
  perspective?: number
}

const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches

type TiltEl = HTMLElement & { _tilt?: TiltOptions, _tiltMove?: (e: MouseEvent) => void, _tiltLeave?: () => void }

const tilt: Directive<TiltEl, TiltOptions | undefined> = {
  mounted(el, binding) {
    if (reducedMotion()) return

    el._tilt = binding.value ?? {}
    el.style.transition = 'transform .35s cubic-bezier(.2,.8,.2,1), box-shadow .3s'
    el.style.willChange = 'transform'

    el._tiltMove = (e) => {
      const { x = 11, y = 9, lift = 6, scale = 1.015, perspective = 900 } = el._tilt ?? {}
      const r = el.getBoundingClientRect()
      const px = (e.clientX - r.left) / r.width - 0.5
      const py = (e.clientY - r.top) / r.height - 0.5
      el.style.transform = `perspective(${perspective}px) rotateX(${-py * y}deg) rotateY(${px * x}deg) translateY(-${lift}px) scale(${scale})`
    }
    el._tiltLeave = () => {
      el.style.transform = ''
    }

    el.addEventListener('mousemove', el._tiltMove)
    el.addEventListener('mouseleave', el._tiltLeave)
  },
  updated(el, binding) {
    el._tilt = binding.value ?? {}
  },
  unmounted(el) {
    if (el._tiltMove) el.removeEventListener('mousemove', el._tiltMove)
    if (el._tiltLeave) el.removeEventListener('mouseleave', el._tiltLeave)
  },
  getSSRProps: () => ({}),
}

type RevealEl = HTMLElement & { _observer?: IntersectionObserver }

const reveal: Directive<RevealEl> = {
  mounted(el) {
    if (reducedMotion()) return

    // Already on screen (e.g. short page): don't hide it at all.
    const rect = el.getBoundingClientRect()
    if (rect.top < window.innerHeight * 0.88) return

    el.style.opacity = '0'
    el.style.transform = 'perspective(1200px) rotateX(10deg) translateY(50px)'
    el.style.transformOrigin = '50% 0'
    el.style.transition = 'opacity .9s ease, transform 1s cubic-bezier(.2,.8,.2,1)'

    el._observer = new IntersectionObserver((entries) => {
      if (entries.some(entry => entry.isIntersecting)) {
        el.style.opacity = '1'
        el.style.transform = 'none'
        el._observer?.disconnect()
      }
    }, { threshold: 0.12 })
    el._observer.observe(el)
  },
  unmounted(el) {
    el._observer?.disconnect()
  },
  getSSRProps: () => ({}),
}

export default defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.directive('tilt', tilt)
  nuxtApp.vueApp.directive('reveal', reveal)
})
