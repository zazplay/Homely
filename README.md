# Realtor

Real estate listings: realtors publish apartments with photos, clients browse the catalog.

```
rieltor/
├── backend/    Laravel 13 REST API — Sanctum, laravel-data, Actions, Pest, Larastan
└── frontend/   Nuxt 4 — Vue 3, Pinia, Tailwind CSS v4, SSR
```

## Run locally

Requires PHP 8.4, Composer, PostgreSQL, Node 20+.

**Backend** → http://localhost:8000 (Swagger: http://localhost:8000/docs/api)

```bash
cd backend
composer install
cp .env.example .env        # set DB_PASSWORD
php artisan key:generate
php artisan migrate --seed
php artisan storage:link    # serves uploaded photos at /storage/...
php artisan serve
```

**Frontend** → http://localhost:3000

```bash
cd frontend
npm install
npm run dev
```

The API URL is `http://127.0.0.1:8000/api` by default; override with `NUXT_PUBLIC_API_BASE`.

Demo accounts (password `password`): agents from the design mockup — `emma.carter@example.com`,
`liam.brooks@example.com`, `sofia.nguyen@example.com`, … — plus `client@example.com` and `admin@example.com`.
Listings, agent profiles and photos come from the "Homely" mockup (`backend/database/seeders/HomelySeeder.php`).

## Checks

```bash
cd backend && composer check     # Pint + Larastan + Pest
cd frontend && npm run typecheck # vue-tsc
```

## How photos work

The frontend reads selected files as base64 data URIs and sends them in the JSON body.
The API verifies the real file type from the bytes (JPEG / PNG / WebP, ≤ 5 MB), writes the file to
`backend/storage/app/public/apartments/{id}/{uuid}.{ext}` and stores only the path in the database.
Responses contain photo URLs, never base64.

On platforms with an ephemeral filesystem (Render, Heroku) switch the disk to S3 / R2:
`FILESYSTEM_DISK=s3` and `ApartmentPhoto::DISK`.
