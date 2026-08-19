# Deploying Himam for free

Himam is two deployables plus a database:

| Piece | What it is | Where it can live free |
| --- | --- | --- |
| `HImam-Frontend` | Static files after `npm run build` | Cloudflare Pages, Netlify, Vercel — all genuinely free and generous |
| `Himam-Backend` | Laravel 12 API (PHP 8.2) | The hard part — see below |
| MySQL | 8 tables of content + reader progress | Bundled with some hosts, otherwise separate |

**The frontend is the easy half.** It is a plain static bundle, and every static
host has a real free tier with no card and no expiry.

**Authentication is a bearer token, not a session cookie.** That is why the two
halves can sit on entirely different domains: there is no same-origin
requirement, no CSRF cookie to forward, and no `SANCTUM_STATEFUL_DOMAINS` to get
right. The API only needs the frontend's origin in `FRONTEND_URL` for CORS.

---

## Option A — simplest: one host for API + MySQL

**Alwaysdata free plan.** Native PHP with SSH and MySQL in the same account, so
there is no separate database to wire up, and no credit card.

At the time of writing the free plan is 1 GB disk and 256 MB RAM, which fits
Laravel with `--no-dev` dependencies comfortably. Free-tier terms change — check
what you are actually offered at signup.

1. Create the account, then create a MySQL database from the admin panel.
2. Add an SSH key and clone the backend repo into `~/www`.
3. `composer install --no-dev --optimize-autoloader`
4. Set the site's document root to `public/` — **not** the project root. Serving
   the root would publish `.env` and the entire source tree.
5. Copy `.env.production.example` to `.env`, fill in the database credentials,
   and generate a key: `php artisan key:generate`
6. `php artisan migrate --force && php artisan db:seed --force`
7. `php artisan config:cache && php artisan route:cache`

Then deploy the frontend to Cloudflare Pages (below) with `VITE_API_URL` set to
`https://<your-account>.alwaysdata.net/api`.

## Option B — modern git-push workflow

**Koyeb** for the API (native PHP, deploys from GitHub, does *not* sleep) plus a
separate free MySQL, and **Cloudflare Pages** for the frontend.

Koyeb asks for a card at signup for verification and does not charge free-tier
usage; its free instance is 512 MB RAM with roughly 1 GB outbound per month.
That is ample here because the API only returns JSON — the images and bundle are
served by the static host, not by Laravel.

For MySQL, **TiDB Cloud Serverless** is MySQL-compatible with a genuinely free
tier. Managed providers usually require TLS, so set `MYSQL_ATTR_SSL_CA` to the
CA bundle path they give you.

The repo includes a `Procfile` for buildpack hosts and a `Dockerfile` for
Docker-based ones — use whichever the host asks for.

## Option C — most headroom, most work

**Oracle Cloud Always Free** gives a real VM (generous RAM, no sleeping, no
expiry) where you install PHP, MySQL and a web server yourself. Best long-term
value; a genuine afternoon of sysadmin.

## What will *not* work

Ignore InfinityFree, Byet.host and TinkerHost, which dominate the search results
for "free PHP hosting". They provide PHP and MySQL, but cap script execution at
about 10 seconds, forbid SSH, and cap uploads around 10 MB — Laravel cannot be
installed or run under those limits.

**Render's** free web service sleeps after 15 minutes idle and takes 30–60
seconds to wake, and its free Postgres expires after 30 days. Workable for a
demo you are willing to wait for; frustrating for anything shown to other people.

---

## Deploying the frontend (any of the three options)

Cloudflare Pages, Netlify and Vercel all work the same way:

- **Build command:** `npm run build`
- **Output directory:** `dist`
- **Environment variable:** `VITE_API_URL = https://your-api-host/api`

`VITE_API_URL` must be set **at build time** — Vite inlines `VITE_*` variables
into the bundle, so setting it after the fact does nothing until you rebuild.

SPA routing is already handled: `public/_redirects` covers Cloudflare Pages and
Netlify, `vercel.json` covers Vercel. Without those, refreshing `/progress`
returns 404, because the route only exists in the browser.

## Deploying the API with Docker

```bash
docker build -t himam-api .
docker run -p 8080:8080 --env-file .env.production himam-api
```

The image reads `$PORT` at boot, runs `php artisan migrate --force`, and caches
config and routes before starting Apache. Two switches control the risky parts:

- `RUN_MIGRATIONS` (default `true`) — set `false` if your host runs migrations
  as a separate release step.
- `RUN_SEEDERS` (default `false`) — set `true` for the **first** deploy only, to
  load the programme's books, quizzes and badges. Leaving it on would re-seed on
  every redeploy and duplicate the catalogue.

## Before you go live

- [ ] `APP_KEY` is set (`php artisan key:generate --show`). Without it, sessions
      and encrypted values do not survive a restart.
- [ ] `APP_DEBUG=false`. With it on, any error page publishes your environment
      variables, database credentials included.
- [ ] `FRONTEND_URL` is your real frontend origin — this is the CORS allowlist.
- [ ] `APP_URL` is the API's own public URL; the certificate verification links
      in the QR codes are built from it.
- [ ] **Change the seeded passwords.** Every demo account ships with the
      password `password`, including `admin@himam.test`, which has full access
      to the admin dashboard. Change them, or delete the accounts you do not
      need, before the site is reachable.
