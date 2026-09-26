// Shapes returned by the Laravel API (see backend/app/Data).

export type Role = 'client' | 'realtor' | 'admin'
export type DealType = 'sale' | 'rent'
export type PropertyType = 'apartment' | 'house' | 'townhouse' | 'loft' | 'commercial'
export type ListingBadge = 'new' | 'hot' | 'price_drop'
export type Category = 'apartments' | 'houses' | 'commercial'
export type Feature =
  | 'balcony' | 'parking' | 'pet_friendly' | 'elevator' | 'garden' | 'doorman'
  | 'in_unit_laundry' | 'central_ac' | 'dishwasher' | 'gym' | 'bike_storage' | 'south_facing'
export type Specialization = 'buying' | 'selling' | 'rentals' | 'luxury' | 'commercial' | 'new_builds'
export type ServiceArea = 'brooklyn' | 'manhattan' | 'queens' | 'bronx' | 'new_jersey'
export type Language = 'english' | 'spanish' | 'russian' | 'chinese' | 'french'

/** The signed-in user (auth endpoints). */
export interface User {
  id: number
  name: string
  email: string
  role: Role
  avatar_url: string | null
  created_at: string
}

/** Public realtor card. */
export interface Agent {
  id: number
  name: string
  avatar_url: string | null
  title: string | null
  agency: string | null
  is_verified: boolean
  rating: number | null
  reviews_count: number
  deals_count: number
  experience_years: number
  specializations: Specialization[]
  areas: ServiceArea[]
  languages: Language[]
  listings_count: number | null
}

export interface Review {
  id: number
  author_name: string
  deal_label: string | null
  rating: number
  body: string
  created_at: string
}

export interface AgentProfile {
  agent: Agent
  phone: string | null
  bio: string | null
  license_number: string | null
  sales_volume_cents: number
  cover_url: string | null
  reviews: Review[]
  listings: Apartment[]
}

export interface Photo {
  id: number
  url: string
  position: number
}

export interface Apartment {
  id: number
  title: string
  description: string | null
  deal_type: DealType
  property_type: PropertyType
  price_cents: number
  city: string
  address: string
  rooms: number
  bathrooms: number | null
  /** Square meters — shown in the UI as sq ft (see formatArea). */
  area: number
  floor: number | null
  total_floors: number | null
  year_built: number | null
  features: Feature[]
  is_published: boolean
  is_sold: boolean
  badge: ListingBadge | null
  is_new_build: boolean
  realtor: Agent
  photos: Photo[]
  created_at: string
}

export interface Inquiry {
  id: number
  name: string
  contact: string
  message: string
  apartment_id: number | null
  apartment_title: string | null
  read_at: string | null
  created_at: string
}

export interface CatalogStats {
  total: number
  apartments: number
  houses: number
  commercial: number
  new_builds: number
}

export interface Paginated<T> {
  data: T[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export interface AuthResponse {
  user: User
  token: string
}

/** Body for POST /apartments and PATCH /apartments/{id}. */
export interface ApartmentPayload {
  title: string
  description: string | null
  deal_type: DealType
  property_type: PropertyType
  price_cents: number
  city: string
  address: string
  rooms: number
  bathrooms: number | null
  area: number
  floor: number | null
  total_floors: number | null
  year_built: number | null
  features: Feature[]
  is_published: boolean
  badge: ListingBadge | null
  is_new_build: boolean
  is_sold?: boolean
  photos?: string[] // base64 data URIs, create only
}

/** Laravel validation errors: field -> first message. */
export type FieldErrors = Record<string, string>
