import type { Apartment, DealType, Feature, Language, ListingBadge, PropertyType, ServiceArea, Specialization } from '~/types/api'

const SQFT_PER_M2 = 10.7639

/** The API stores money in cents; the UI shows whole dollars: 78500000 -> "$785,000". */
export function formatMoney(cents: number): string {
  return `$${Math.round(cents / 100).toLocaleString('en-US')}`
}

/** Compact money for stats: 9200000000 -> "$92M". */
export function formatMoneyShort(cents: number): string {
  const dollars = cents / 100
  if (dollars >= 1_000_000) return `$${Math.round(dollars / 1_000_000)}M`
  if (dollars >= 1_000) return `$${Math.round(dollars / 1_000)}K`
  return `$${dollars}`
}

export function formatPrice(cents: number, dealType?: DealType): string {
  return formatMoney(cents) + (dealType === 'rent' ? '/mo' : '')
}

/** The API stores m²; the design (US market) shows sq ft. */
export function sqft(m2: number): number {
  return Math.round(m2 * SQFT_PER_M2)
}

export function formatArea(m2: number, unit: 'sq ft' | 'ft²' = 'sq ft'): string {
  return `${sqft(m2).toLocaleString('en-US')} ${unit}`
}

export function sqftToM2(value: number): number {
  return Math.round((value / SQFT_PER_M2) * 10) / 10
}

export function dealTypeLabel(dealType: DealType): string {
  return dealType === 'rent' ? 'For rent' : 'For sale'
}

export const propertyTypeLabels: Record<PropertyType, string> = {
  apartment: 'Apartment',
  house: 'House',
  townhouse: 'Townhouse',
  loft: 'Loft',
  commercial: 'Commercial',
}

export const featureLabels: Record<Feature, string> = {
  balcony: 'Balcony',
  parking: 'Parking',
  pet_friendly: 'Pet friendly',
  elevator: 'Elevator',
  garden: 'Garden',
  doorman: 'Doorman',
  in_unit_laundry: 'In-unit laundry',
  central_ac: 'Central A/C',
  dishwasher: 'Dishwasher',
  gym: 'Gym',
  bike_storage: 'Bike storage',
  south_facing: 'South-facing',
}

/** The six features offered as search filters (Search mockup). */
export const searchFeatures: Feature[] = ['balcony', 'parking', 'pet_friendly', 'elevator', 'garden', 'doorman']

export const specializationLabels: Record<Specialization, string> = {
  buying: 'Buying',
  selling: 'Selling',
  rentals: 'Rentals',
  luxury: 'Luxury',
  commercial: 'Commercial',
  new_builds: 'New builds',
}

export const areaLabels: Record<ServiceArea, string> = {
  brooklyn: 'Brooklyn',
  manhattan: 'Manhattan',
  queens: 'Queens',
  bronx: 'Bronx',
  new_jersey: 'New Jersey',
}

export const languageLabels: Record<Language, string> = {
  english: 'English',
  spanish: 'Spanish',
  russian: 'Russian',
  chinese: 'Chinese',
  french: 'French',
}

const badgeLabels: Record<ListingBadge, string> = {
  new: 'New',
  hot: 'Hot',
  price_drop: 'Price drop',
}

/** Label on the home card photo: the realtor's badge, otherwise the deal type. */
export function listingTag(apartment: Apartment): string {
  if (apartment.badge) return badgeLabels[apartment.badge]
  return apartment.deal_type === 'rent' ? 'Rent' : 'Sale'
}

export function bedsLabel(apartment: Apartment): string {
  if (apartment.property_type === 'commercial') return 'Commercial'
  return apartment.rooms === 0 ? 'Studio' : `${apartment.rooms} bd`
}

/** Short specs for cards: ["3 bd", "2 ba", "1,420 sq ft"]. */
export function listingSpecs(apartment: Apartment): string[] {
  const specs = [bedsLabel(apartment)]
  if (apartment.bathrooms) specs.push(`${apartment.bathrooms} ba`)
  specs.push(formatArea(apartment.area))
  return specs
}

/** Monthly payment of a 30-year fixed mortgage. */
export function monthlyPayment(principal: number, annualRatePercent: number, years = 30): number {
  const r = annualRatePercent / 100 / 12
  const n = years * 12
  return r === 0 ? principal / n : (principal * r) / (1 - (1 + r) ** -n)
}

/** Drops empty values so they don't end up in the URL / API query. */
export function cleanQuery<T extends Record<string, unknown>>(query: T): Partial<T> {
  return Object.fromEntries(
    Object.entries(query).filter(([, value]) => value !== '' && value !== null && value !== undefined),
  ) as Partial<T>
}
