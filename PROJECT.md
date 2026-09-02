# TruckMarket — Project Documentation

> Single source of truth for how this application works. Read this first in a new session.
> Spec that drove the build: `D:\web\truck\PROMPT.md`. Visual reference (HTML prototype): `D:\web\truck\html\`.

## 1. What this is

A trucking marketplace (Laravel) where users post and order services across 6 listing types:
**Load, Truck, Trailer, Company, Dispatcher, Driver & Owner**. Two locales (EN default, RU).
Free at this stage — no payments anywhere. Visual polish is intentionally rough; a separate
pixel-matching pass against `D:\web\truck\html\` is planned but NOT done yet.

## 2. Environment & how to run

- App root: `D:\web\truck\truckmarket`
- PHP 8.3 (OSPanel), Composer, Node 24. No phpredis extension → **predis** client.
- Services via OSPanel 6.3.5 CLI (`D:\OSPanel\bin\osp.bat`):
  `osp on MySQL-8.0`, `osp on Redis-7.4`, `osp on Mailpit`
- MySQL: host 127.0.0.1, user `root`, empty password, DB `truckmarket`.
  (Windows Git Bash mangles `mysql.exe -e "..."` quoting — use PHP PDO scripts for ad-hoc SQL.)
- **Site is served by OSPanel Nginx+FCGI at http://truckmarket.local** (APP_URL). Also enable
  `osp on Nginx-1.28` and `osp on PHP-8.3-FCGI`. Only `php artisan queue:work` needs a terminal
  (queued mails). `php artisan serve` still works as a fallback but is slower (single process).
- Mailpit UI: http://localhost:8025 (all outgoing mail lands here, incl. verification codes).
- Frontend assets prebuilt; rebuild with `npm run build` (or `npm run dev` while developing).
- Open the site at **http://truckmarket.local** exactly — media URLs are absolute from `APP_URL`.

### Logins (seeded)
- Admin: `admin@truckmarket.test` / `password` → `/admin`
- Demo users (`password`): `company@`, `dispatcher@`, `driver@`, `driver_owner@truckmarket.test`

## 3. Stack

Laravel 13, MySQL 8, Redis (cache + session + queue, predis), Blade + Tailwind CSS 4 + Alpine.js (Vite),
Filament 5 admin (+ `filament/spatie-laravel-media-library-plugin`), Laravel Socialite (Google),
spatie/laravel-permission (roles), spatie/laravel-medialibrary (photos/avatars), Pest tests.

## 4. Localization

- Locales: `en` (default), `ru`. All routes prefixed: `/{locale}/...`; root `/` redirects using session.
- `App\Http\Middleware\SetLocale` sets app locale, `URL::defaults(['locale' => ...])`, stores to session,
  and **calls `$request->route()->forgetParameter('locale')`** — critical: without it the locale string
  is injected positionally into controller signatures and breaks route-model binding.
- All strings in `lang/en/*.php` + `lang/ru/*.php`: `common`, `auth`, `roles`, `listings`, `orders`, `account`.
- Locale switcher component: `resources/views/components/locale-switcher.blade.php` (swaps first URL segment).
- Users carry a `locale` column; queued mails use `->locale($user->locale)`.

## 5. Roles & authentication

Roles via spatie/permission (enum `App\Enums\UserRole`): `company`, `dispatcher`, `driver`,
`driver_owner` (+ `admin`, not registerable). Role describes the user; it does NOT restrict listing types.

### Email registration flow
1. `POST /{locale}/register` (name, email, password, role) → user created unverified, logged in.
2. `App\Services\EmailVerificationService::issue()` — 6-digit code, stored **hashed** in
   `verification_codes` (expires 15 min, max 5 attempts, old codes invalidated), queued `VerificationCodeMail`.
3. `/verify-email` screen → `verify()` checks hash/expiry/attempts → sets `email_verified_at`.
4. Resend rate-limited (5/hour). Login rate-limited (5/min per email+IP).
5. Middleware alias `verified.code` (`App\Http\Middleware\EnsureEmailIsVerified`) guards the whole
   `/account` group — unverified users are bounced to the code screen.

### Company registration & verification
- Choosing the **Company** role on `/register` reveals extra required fields (Alpine toggle):
  company name, **company number (USDOT)**, company phone. Validated `required_if:role,company`.
- **Registry check** at registration via `CompanyVerifier` interface
  (`app/Services/CompanyVerification/`): `FmcsaCompanyVerifier` calls the FMCSA QCMobile API
  (`https://mobile.fmcsa.dot.gov/qc/services/carriers/{dot}?webKey=...`, free key → `FMCSA_WEBKEY` in
  `.env`) and fuzzy-compares legalName/dbaName (≥85% similarity). Invalid number / name mismatch /
  not-allowed-to-operate → registration rejected with an error. No key configured →
  `NullCompanyVerifier`, verification stays **pending** (`company_verified_at` null; admin can set it
  in Filament Users). Valid → `company_verified_at` stamped.
- **SMS phone confirmation**: company users, after email verification, are forced (middleware
  `EnsureEmailIsVerified` → `User::needsPhoneVerification()`) to `/verify-phone` — a 6-digit code is
  sent to `company_phone` via `SmsSender` (`TwilioSmsSender` when `TWILIO_SID/TOKEN/FROM` set in
  `.env`, otherwise `LogSmsSender` writes the code to `storage/logs/laravel.log` for dev). Codes live
  in `verification_codes` with `channel=phone` (email codes use `channel=email`), 10-min TTL,
  attempt/resend rate limits. On success `phone_verified_at` is stamped.

### Google OAuth
- Routes: `/auth/google` → redirect, `/auth/google/callback` (`GoogleAuthController`).
- Existing email → links `google_id`; new user → created verified with `needs_role_selection=true`
  and must pick a role at `/choose-role` before entering the account area.
- **Placeholders only**: `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` in `.env` are empty — flow is
  code-complete but needs real keys to test.

Blocked users (`is_blocked`) cannot log in (checked at login + Google callback).

## 6. Data model (key tables)

- `users` — + `company_name, phone, address, residency, zip, locale, google_id, needs_role_selection,
  is_blocked, notify_by_email`; password nullable (Google-only accounts).
- `listings` — common fields: `user_id, type (enum), title, description, price, zip, latitude, longitude,
  status (pending_moderation|active|inactive|rejected — default active), moderation_note, views`, soft deletes.
  Indexes: `(type,status,created_at)`, `zip`, `(status,price)`.
- Per-type detail tables (1:1 via `listing_id` unique FK):
  - `listing_truck_details` — deal(sell|rent), make_model, cab_type(sleeper|day_cab), year, mileage
  - `listing_trailer_details` — deal, trailer_type(flatbed|reefer|dry_van), year
  - `listing_load_details` — load_type(car_hauler|flatbed|reefer|dry_van), pickup/delivery zip+lat/lng,
    vehicle_type(sedan|suv|truck), weight
  - `listing_company_details` — company_name, services
  - `listing_dispatcher_details` — experience_years, employment_type(full_time|part_time), languages(JSON)
  - `listing_driver_owner_details` — experience_years, cdl_class(a|b), owns_truck
- `orders` — listing_id, customer_id, owner_id, status enum, message, response_note, confirmed_at, completed_at.
- `reviews` — `order_id` UNIQUE (one review per order), author_id, subject_id, score 1-5, is_positive,
  body, reply (one-time), replied_at, is_hidden (admin moderation).
- `blacklists` — user_id + blocked_user_id (unique pair), reason.
- `verification_codes` — user_id, code_hash, expires_at, used_at, attempts.
- `zip_codes` — PK zip, city, state, lat, lng. **Seeded with only ~40 major US cities** (dev subset);
  production needs a full US ZIP import into this same table.
- `media` (spatie) — listing photos (collection `photos`, max 10, conversion `card` 600x400)
  and user avatars (collection `avatar`, conversion `thumb` 200x200). Disk must be **public**.

### Models
`App\Models\{User, Listing, Order, Review, Blacklist, VerificationCode, ZipCode}` +
`App\Models\ListingDetails\{TruckDetail, TrailerDetail, LoadDetail, CompanyDetail, DispatcherDetail, DriverOwnerDetail}`
(each sets `protected $table` explicitly). `Listing::detailRelation()` maps type → relation name;
`Listing::detail()` returns the loaded detail row. Enums in `App\Enums`:
`UserRole, ListingType, ListingStatus, OrderStatus, DealType` — labels come from lang files
(`$enum->label()`), icons via `ListingType::icon()`.

## 7. Services (business logic lives here)

| Service | Responsibility |
|---|---|
| `ListingService` | create/update/delete listing + its detail row (transaction), ZIP→lat/lng, photo attach (respects max 10), cache flush |
| `ListingSearch` | marketplace filtering: per-type filters applied **only when a relevant filter is set** (listings without a detail row still show), price range, ZIP+radius (bounding box + haversine, max 2000 mi), sort (newest/oldest/price asc/desc), pagination `withQueryString` |
| `ListingCache` | Redis: per-type active counts (`listings:counts`) + home preview rows (`listings:preview:{type}`), TTL 600s, `flush()` called on any listing write (site + admin) |
| `OrderService` | full state machine + guards + queued status mails |
| `ReviewService` | review rules + one-time reply + rating cache invalidation |
| `RatingService` | cached aggregate rating per user (`user:{id}:rating`, TTL 1h): average/count/positive/negative |
| `EmailVerificationService` | issue/verify 6-digit codes |
| `ZipResolver` | ZIP → coordinates from `zip_codes`, cached 24h (`zip:{zip}`) |

### Order state machine (`OrderService`)
`pending → confirmed → completed`; `pending → declined` (owner) ; `pending → cancelled` (customer).
Guards on placing: listing must be active; not own listing; owner's blacklist blocks the customer;
no duplicate open (pending/confirmed) order per listing+customer. Every transition queues a localized
`OrderStatusMail` to the other party (skipped if `notify_by_email=false`).
Authorization: `OrderPolicy` — `respond` (owner: confirm/decline/complete), `act` (customer: cancel/review).
Review: only customer of a **completed** order, once (`order_id` unique); reply once by the subject.

**Stripe payments (admin-controlled):** on owner confirm, if payments are enabled and an amount is
resolvable, `orders.payment_status` becomes `pending` and the customer sees a "Pay $X" button on the
order page → Stripe **Checkout** hosted session (`StripeGateway`, raw HTTP, `STRIPE_SECRET` in .env;
no key → `NullGateway`, pay attempt shows "unavailable"). Return URL `?payment=success` re-checks the
session server-side (`OrderPaymentController::settle`) before marking `paid` — the redirect alone is
never trusted. Admin controls: **Settings** resource (`settings` table) → `payments_enabled` toggle +
`default_payment_amount`; per-order override via `payment_amount` on the Filament order form (also
`payment_status`/`paid_at` visible). Amount resolution: per-order value, else default, else no charge.
Production TODO: add a Stripe webhook for settlement instead of relying only on the success redirect.

**Per-order chat:** customer and owner message each other to agree on terms — `order_messages` table,
thread page `account.orders.show` (`/account/orders/{id}`), composer open only while pending/confirmed
(`Order::allowsMessages()`), read-only afterwards. Opening the thread marks the counterpart's messages
read; orders index shows unread badges; recipient gets a queued `OrderMessageMail` only for the first
unread message (no flooding). Access via `OrderPolicy::view` (parties only).

## 8. Routes (all inside `/{locale}` group, names unprefixed)

Public (SEO URL scheme):
- `listings.type` — `/{locale}/{typeSlug}` where typeSlug ∈ trucks|loads|trailers|companies|dispatchers|drivers
  (`ListingType::slug()/fromSlug()`); filters/sort via GET params.
- `listings.show` — `/{locale}/{typeSlug}/{title-slug}-{id}`; `Listing::seoUrl()` builds it, slug column
  auto-generated from title on save (Str::slug, regenerated on title change). Wrong/stale slug or type
  → 301 to canonical. Pages emit `<link rel=canonical>`, meta description, OG tags
  (`marketplace.blade` + `listings/show.blade` `@section('head')`).
- Legacy 301 redirects: `/marketplace?type=x` → type page (`MarketplaceController::legacy`),
  `/listings/{id}` → canonical (`ListingController::legacy`).
- `home`, `profile.show` (public user page). Detail page: gallery lightbox (Alpine), owner card w/ rating,
  Order button, Redis-deduped view counter 1/visitor/hour.
Auth: `login`, `register`, `password.*`, `verification.*`, `auth.google*`, `auth.role.*`, `logout`.
Account (`auth` + `verified.code`): `account.listings.*` (CRUD + photos), `account.orders.*`
(index incoming/outgoing + confirm/decline/complete/cancel), `account.reviews.*` (index/create/store/reply),
`account.blacklist.*`, `account.settings.*` (profile, password, avatar upload; email change un-verifies).

`bootstrap/app.php` registers only the `verified.code` middleware alias; policies auto-discovered.

## 9. Views

Layout `layouts/app.blade.php` (topbar, locale switcher, flash). Components: `listing-card`, `stars`,
`flash`, `locale-switcher`. Home: type sidebar **with live counts** + Alpine-switched per-type filter
forms (`partials/filters/{type}.blade.php`, shared ZIP/radius/price block in `_shared`) + 4-card preview
rows per type. Marketplace: sidebar type list + filters, sort select, paginated grid.
Listing detail: `partials/details/{type}.blade.php` renders type-specific fields.
Account area: `account/_layout` + `_sidebar` (avatar upload, rating, menu), pages under
`account/{listings,orders,reviews,blacklist}/`, `account/settings`.
Listing create/edit form (`account/listings/form.blade.php`): Alpine `x-data type` switches per-type
field groups; hidden groups' inputs are `:disabled` so they don't submit. Type is locked when editing.
Brand color: Tailwind theme vars `--color-brand-*` (orange) in `resources/css/app.css`.

## 10. Admin (Filament 5, `/admin`)

Access: `User::canAccessPanel()` → only role `admin` and not blocked.
Resources in `app/Filament/Resources/`:
- **Users** — role badge/filter, verify action, block/unblock toggle action, listings count.
- **Listings** — photo thumbnails column, type/status filters, **approve/reject actions** (reject asks
  moderation note), edit form with: live `type` select + per-type `Fieldset::relationship('xxxDetail')`
  (visible only for the matching type — switching type swaps the fieldsets), photo upload
  (`SpatieMediaLibraryFileUpload`, disk `public`, max 10, reorderable). On save:
  `EditListing/CreateListing` recompute lat/lng from ZIP, call `ListingResource::pruneStaleDetails()`
  (deletes detail rows of other types) and flush `ListingCache`.
- **Orders** — status badges/filter, force edit.
- **Reviews** — hide/show action (invalidates rating cache), delete.
- Dashboard: `App\Filament\Widgets\StatsOverview` (users, active/pending listings, orders, reviews).

## 11. Caching summary (Redis)

| Key | What | Invalidation |
|---|---|---|
| `listings:counts` | active count per type (home/marketplace sidebar) | `ListingCache::flush()` on listing write |
| `listings:preview:{type}` | latest 4 active ids per type (home rows) | same |
| `user:{id}:rating` | aggregate rating | on new review / hide toggle |
| `zip:{zip}` | ZIP coordinates | TTL 24h |
| `listing:{id}:view:{who}` | view-count dedupe | TTL 1h |

Sessions and queues also live in Redis (`SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`).

## 12. Tests (Pest, 24 tests — all passing)

`php artisan test` — sqlite in-memory (that's why `ListingCache::preview` sorts in PHP, not MySQL `FIELD()`).
- `RegistrationTest` — register+code mail, verify ok/expired, resend invalidates old, account gate.
- `OrderFlowTest` — happy path, self-order/duplicate/blacklist rejections, invalid transitions, owner-only HTTP.
- `ReviewTest` — completed-only, once-only, owner can't review self, one-time reply.
- `PublicPagesTest` — home en/ru, marketplace type filter, inactive hidden, root redirect, profile.
- `AdminListingTypeChangeTest` — Filament: type switch swaps fieldsets + prunes stale detail row;
  photo upload from admin persists to media collection.

Formatting: `./vendor/bin/pint`.

## 13. Gotchas & fixes already learned (do not regress)

1. **`SetLocale` must `forgetParameter('locale')`** — otherwise controllers receive 'en' as the model param.
2. **`Listing::detail()`** uses `$this->{$this->detailRelation()}` (method call, not property).
3. **Filament uploads default to the private `local` disk** — fixed via `FILAMENT_FILESYSTEM_DISK=public`
   in `.env` AND explicit `->disk('public')` on the photo field. If a photo 404s at `/storage/...`,
   check the `media.disk` column first (a one-off script already migrated old rows).
4. **Photo URLs are absolute from `APP_URL`** — site must be opened on the same host (`localhost:8000`).
5. `ListingSearch` applies `whereHas(detail)` only when a type-specific filter is actually set.
6. Composer on this machine is slow/SSL-disabled; long installs may need background runs.
7. Windows: `osp.bat` via `cmd //c`; avoid `mysql.exe -e` from Git Bash (quoting breaks).
8. **Performance (dev):** OPcache was OFF in OSPanel's PHP — enabled in
   `D:\OSPanel\modules\PHP-8.3\PHP\php.ini` (`zend_extension=opcache`, `opcache.enable=On`,
   `opcache.enable_cli=On`, memory 256M, max files 30000). This cut page TTFB from ~1.3s to ~0.4s.
   Restart `php artisan serve` after ini changes. Only `view:cache` + `filament:optimize` are applied —
   **do NOT run `php artisan config:cache` or `route:cache` locally**: cached config overrides
   phpunit.xml env and breaks the test suite with 419 errors (learned the hard way).
   For further gains, add `D:\web\truck` + `D:\OSPanel` to Windows Defender exclusions (user action).
9. **`tests/TestCase.php` has a hard guard**: it aborts the suite (in `setUpTraits`, before
   RefreshDatabase migrates) if the DB connection is not sqlite — a cached config once made tests
   run RefreshDatabase against the real MySQL DB and wiped all development data. Re-seed after
   such an accident with `php artisan db:seed`. Do not remove this guard.
10. **Nginx serving setup (manual, NOT via OSPanel project system):** OSPanel 6.3's project
    auto-registration never picked up home-folder projects here (CLI `osp projects` stays empty),
    so the vhost is written by hand into the nginx TEMPLATE
    `D:\OSPanel\config\Nginx-1.28\default\templates\nginx.conf` (survives `osp init`; the generated
    `modules\Nginx-1.28\conf\nginx.conf` is overwritten on init — never edit it directly).
    The vhost: `truckmarket.local` → root `D:/web/truck/truckmarket/public`, fastcgi_pass
    `127.127.126.21:9333`. **FCGI port is 9333, not 9000** — PhpStorm's Xdebug listener occupies
    0.0.0.0:9000 (changed in `config\PHP-8.3-FCGI\default\settings.ini` start_command AND the
    nginx `virtual_fcgi_host.conf` template). Hosts file has `127.0.0.1 truckmarket.local`.
    A junction `D:\OSPanel\home\truckmarket.local` → app `public\` exists (harmless leftover).
    `config\PHP-8.3-FCGI\default\settings.ini`: PHP_FCGI_CHILDREN=4 (each child has its own
    opcache; warm-up needs a few requests). FCGI php.ini template also has opcache enabled +
    revalidate_freq=10 — after editing PHP code, changes may take up to ~10s to appear.
    `system\program.dat` was edited (backup: `program.dat.bak`) to set default project engines —
    turned out not to matter, but the SSL default is now off.
    **The vhost's static-extension location MUST fall back to `/index.php?$query_string`, not `=404`** —
    Livewire's script is a dynamic `.js` route (`/livewire-*/livewire.js`); with `=404` the admin login
    form silently does nothing (no JS). Guests are redirected via `redirectGuestsTo` in
    `bootstrap/app.php` because bare `route('login')` lacks the `{locale}` parameter on
    Filament/Livewire routes.

## 14. Not done yet / next steps

- **Visual pixel-match pass** against `D:\web\truck\html\` (user explicitly deferred this).
- Real Google OAuth keys in `.env` (+ Google Cloud console redirect `http://localhost:8000/en/auth/google/callback`).
- Full US ZIP dataset import into `zip_codes` (currently ~40 cities).
- Production hardening if deployed: `APP_ENV=production`, `APP_DEBUG=false`, real APP_URL, mail provider,
  `php artisan optimize`, web server vhost pointing to `public/` (standard Laravel `.htaccess` already there).
