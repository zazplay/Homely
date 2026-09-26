import type { FetchError } from 'ofetch'
import type { FieldErrors } from '~/types/api'

interface LaravelErrorBody {
  message?: string
  errors?: Record<string, string[]>
}

function body(error: unknown): LaravelErrorBody | undefined {
  return (error as FetchError<LaravelErrorBody>)?.data
}

/** 422 response -> { field: first message }. Keys look like "title" or "photos.1". */
export function fieldErrors(error: unknown): FieldErrors {
  const errors = body(error)?.errors ?? {}

  return Object.fromEntries(Object.entries(errors).map(([field, messages]) => [field, messages[0] ?? '']))
}

export function errorMessage(error: unknown): string {
  return body(error)?.message ?? 'Something went wrong. Please try again.'
}
