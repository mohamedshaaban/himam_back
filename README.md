# Himam — API (Laravel 12)

The backend for the Himam reading programme (منصة همم — جمعية مرتقي العلمية): a
catalogue of books split into sections, a short quiz after each section, and the
points, badges, certificates and honour board that track a reader's progress.

The reader-facing app lives in `../HImam-Frontend`.

## Requirements

- PHP 8.2+
- MySQL / MariaDB
- Composer

## Setup

```bash
composer install
```

Create the database and load the schema and seed content:

```bash
php artisan migrate:fresh --seed
```

Run the API:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

The frontend dev server proxies `/api` to `http://127.0.0.1:8000`, so both apps
sit on one origin in development.

### Demo accounts

Seeded with the password `password`:

| Email | Role |
| --- | --- |
| `admin@himam.test` | administrator |
| `mohammed@himam.test` | reader (10 sections passed) |

Six other readers are seeded with decreasing progress so the honour board has
something to rank.

## Languages

`config/himam.php` is the single source of truth for supported locales — Arabic
(default), English, French and Urdu. Adding one is a single entry there plus a
matching locale file in the frontend.

Content is translatable, not just the interface: `title`, `body`, `name`,
`description`, `caption` and friends are stored as JSON maps of `locale => text`
via `App\Models\Concerns\HasTranslations`. Reads degrade gracefully — requested
locale, then the fallback locale, then whatever translation exists — so a
partially translated record still renders instead of showing a blank.

`App\Http\Middleware\SetLocale` resolves the response language per request, in
this order:

1. `?lang=` — what the frontend sends, pinned to its cache key
2. `X-Locale` header
3. `Accept-Language` header
4. the signed-in reader's saved preference

Anything outside the configured list is ignored rather than trusted. Every
response carries `Content-Language`.

## Layout

- `app/Services/ProgressService.php` — the rules of the programme in one place:
  what a passing quiz is, when points are credited (first pass only), which
  badges unlock, and when a level certificate is issued. `detail()` also builds
  the reader's progress screen: a level → book → section breakdown, the badges
  they are closest to, recent attempts and points per month, resolved from three
  bulk reads rather than a query per section.
- `app/Http/Controllers/Api` — reader endpoints, returning resolved strings.
- `app/Http/Controllers/Admin` — admin CRUD, returning full translation maps so
  the dashboard can offer one input per language.
- `database/seeders` — the catalogue from the design, translated into all four
  locales. Readers are seeded by actually sitting the quizzes through
  `ProgressService`, so attempts, points, badges and certificates all agree.

## Postman

`postman/` holds a collection covering every endpoint, plus a local environment:

- `Himam-API.postman_collection.json`
- `Himam-Local.postman_environment.json`

Import both, run **Auth → Login (reader)** and **Auth → Login (admin)** once —
their test scripts capture the bearer tokens automatically — and the rest is
runnable. Switch the `lang` variable between `ar`, `en`, `fr` and `ur` and
re-send any request to see the same record come back translated.

The collection is designed to be run whole, repeatedly, without disturbing the
seeded data: each admin folder creates its own record, exercises it and deletes
it again; the registration example mints a unique email and is cleaned up at the
end; and the password/preference examples write back their current values so a
full run can't lock you out of the demo account. Verified with
`newman run` — 68 requests green over two consecutive passes with an identical
row-count snapshot before and after.

```bash
npx newman run postman/Himam-API.postman_collection.json -e postman/Himam-Local.postman_environment.json
```

## API

Public: `POST auth/register`, `POST auth/login`, `GET locales`, `GET levels`,
`GET slides/{screen}`, `GET books`, `GET books/{book}`, `GET badges`,
`GET honor-board`, `GET certificates/verify/{code}`.

Reader (Sanctum): `auth/me`, `auth/logout`, `profile`, `profile/password`,
`dashboard`, `progress`, `sections/{section}`, `sections/{section}/quiz` (GET and POST),
`certificates`, `announcements`, `notification-preferences`.

Admin (Sanctum + `admin` middleware, under `/api/admin`): `stats`, `levels`,
`books`, `books/{book}/sections`, `sections/{section}/questions`, `questions`,
`badges` (+ `award` / `revoke`), `announcements` (+ `publish`), `slides`,
`users` (+ `recalculate`), `certificates`.

The quiz answer key never leaves the server on `GET sections/{section}/quiz`;
correct options are returned only in the grading response after an attempt has
been recorded.

## Deploying

See [DEPLOYMENT.md](DEPLOYMENT.md) for free hosting options, the Docker image,
and the pre-launch checklist. Note that every seeded account ships with the
password `password` — change them before the site is publicly reachable.
