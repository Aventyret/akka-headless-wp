# Migration guide: Akka 2.x → 3.0

**Audience:** developers/agents upgrading a consumer site (vi.se, dagens-arbete, etc.) from Akka 2.x to 3.0.

**Scope:** the PHP plugin `aventyret/akka-headless-wp` and the npm package `@aventyret/akka-headless-wp`. Both need to be upgraded together. Each site updates its own `.env` and a small amount of code.

This is a big-bang release. There is no deprecation period for the old env names.

---

## What changes in 3.0

1. **One shared proxy header gates all cms↔www traffic.** Previously, `www→cms` used `AKKA_CMS_API_KEY` with a timing-unsafe `===` check, and `cms→www` used `AKKA_FRONTEND_FLUSH_CACHE_KEY` with `Authorization: Bearer`. Both directions now use `AKKA_WWW_PROXY_HEADER_NAME` + `AKKA_WWW_PROXY_HEADER_SECRET` validated with `hash_equals` (PHP) / `constantTimeEqual` (JS).
2. **All public GET routes are gated.** Previously `Akka\Router::can_get_content()` returned hardcoded `true`. From 3.0 every `/akka/v2/*` route requires the proxy header (exception: `/editor/block` still requires `current_user_can('edit_posts')`).
3. **Draft cookie is HMAC-signed.** The `cms_signed_in` cookie value used to be `'1'`. From 2.6 it is `<userId>.<exp>.<hmac>`, set with `httpOnly + secure + SameSite=Lax`, and the www-side middleware validates the signature.
4. **`AKKA_USE_QUERY_PARAM_PERMALINK` is gone.** Query-param permalinks are the only mode.
5. **Boot validation.** Plugin and Handlers/ContentService throw or surface admin notices when required env is missing or placeholder (`value === name`).
6. **Removed legacy env:** `AKKA_CMS_API_KEY`, `AKKA_FRONTEND_FLUSH_CACHE_KEY`, `AKKA_INTERNAL_API_KEY`, `AKKA_FRONTEND_SHARED_SECRET`, `AKKA_USE_QUERY_PARAM_PERMALINK`.
7. **Removed URL aliases:** `AKKA_FRONTEND_BASE`, `AKKA_FRONTEND_INTERNAL_BASE`, `AKKA_CMS_INTERNAL_BASE`. Use `AKKA_FRONTEND_URL`, `AKKA_FRONTEND_URL_INTERNAL`, `AKKA_CMS_URL_INTERNAL`.
8. **No more `https://example.com` fallback** for `AKKA_FRONTEND_URL` — missing env is caught loudly instead.

Canonical env reference lives at `@aventyret/akka-headless-wp/.env.example`.

---

## Before you start

- You can deploy cms and www at the same time (or close to it). The cookie and header formats are incompatible across versions.
- You have access to your site's secret store (1Password vault, op:// references).
- You can write to the team's deploy env (CI variables or runtime secret mounts).

---

## Step 1 — Generate new secrets

Three new secrets are required. 32 random bytes each:

```bash
openssl rand -base64 32
```

| Secret | Used in | Must match between |
|---|---|---|
| `AKKA_WWW_PROXY_HEADER_SECRET` | cms ↔ www proxy gate | cms and www |
| `AKKA_DRAFT_COOKIE_SECRET` | HMAC for `cms_signed_in` cookie | cms and www |
| (optional) `AKKA_SESSION_ID_SALT`, `AKKA_SHARE_ID_SALT` | session/share-id hashing | www only — rotate if currently placeholders |

Store all in 1Password under the site's vault. Record the `op://...` references.

The proxy header secret supports comma-separated rotation later: `AKKA_WWW_PROXY_HEADER_SECRET="new,old"`. Both are accepted; remove the old value after a deploy cycle.

---

## Step 2 — Update env templates

### Add to **cms/.env.template** and **www/.env.template**

```
AKKA_WWW_PROXY_HEADER_NAME           = X-Akka-Internal
AKKA_WWW_PROXY_HEADER_SECRET         = op://YourVault/yoursite.env/AKKA_WWW_PROXY_HEADER_SECRET
AKKA_DRAFT_COOKIE_SECRET             = op://YourVault/yoursite.env/AKKA_DRAFT_COOKIE_SECRET
```

### Remove from both `.env` files

| Var | Was | Replaced by |
|---|---|---|
| `AKKA_CMS_API_KEY` | www → cms auth (X-WP-API-Key) | `AKKA_WWW_PROXY_HEADER_*` |
| `AKKA_FRONTEND_FLUSH_CACHE_KEY` | cms → www auth (Bearer) | `AKKA_WWW_PROXY_HEADER_*` |
| `AKKA_INTERNAL_API_KEY` | (never read in code) | — |
| `AKKA_FRONTEND_SHARED_SECRET` | (never read in code) | — |
| `AKKA_USE_QUERY_PARAM_PERMALINK` | feature flag | (removed, always on) |
| `NEXT_PUBLIC_AKKA_USE_QUERY_PARAM_PERMALINK` | feature flag (client) | — |
| `AKKA_FRONTEND_BASE` | alias | `AKKA_FRONTEND_URL` |
| `AKKA_FRONTEND_INTERNAL_BASE` | alias | `AKKA_FRONTEND_URL_INTERNAL` |
| `AKKA_CMS_INTERNAL_BASE` | alias | `AKKA_CMS_URL_INTERNAL` |

If `AKKA_SESSION_ID_SALT` or `AKKA_SHARE_ID_SALT` are placeholders (value equals name), rotate to real random values now. The 3.0 plugin will flag them as placeholders in admin notices.

### Update CI / deploy env

Whatever overlays your `.env` (GitHub Actions secrets, Docker compose, k8s configmap) needs the same add/remove.

---

## Step 3 — Bump package versions

### cms (`composer.json`)

```json
"require": {
  "aventyret/akka-headless-wp": "^3.0.0"
}
```

If your site uses `akka-headless-wp-formkit`, also bump to `^0.2.0`.

```bash
composer update aventyret/akka-headless-wp aventyret/akka-headless-wp-formkit
```

### www (`package.json`)

Bump `@aventyret/akka-headless-wp` to the new major (check the package's CHANGELOG for the exact version number).

```bash
pnpm update @aventyret/akka-headless-wp
```

---

## Step 4 — Update www code

### Handlers initialization

Typically in `app/api/services/headless-wp/[...args]/route.js` (or wherever you mount the cms cache-flush endpoint).

**Before:**
```js
const handlers = Handlers({
  cache,
  AKKA_FRONTEND_FLUSH_CACHE_KEY: process.env.AKKA_FRONTEND_FLUSH_CACHE_KEY
});
```

**After:**
```js
const handlers = Handlers({
  cache,
  AKKA_WWW_PROXY_HEADER_NAME: process.env.AKKA_WWW_PROXY_HEADER_NAME,
  AKKA_WWW_PROXY_HEADER_SECRET: process.env.AKKA_WWW_PROXY_HEADER_SECRET
});
```

`Handlers()` throws at init if either env is missing or a placeholder.

### ContentService initialization

Typically in `src/services/headless-wp-service.js` or similar.

**Before:**
```js
const contentService = ContentService({
  cache,
  language,
  AKKA_LANG: process.env.AKKA_LANG,
  AKKA_CMS_URL_INTERNAL: process.env.AKKA_CMS_URL_INTERNAL,
  AKKA_CMS_URL: process.env.AKKA_CMS_URL,
  AKKA_FRONTEND_URL: process.env.AKKA_FRONTEND_URL
});
```

**After:**
```js
const contentService = ContentService({
  cache,
  language,
  AKKA_LANG: process.env.AKKA_LANG,
  AKKA_CMS_URL_INTERNAL: process.env.AKKA_CMS_URL_INTERNAL,
  AKKA_CMS_URL: process.env.AKKA_CMS_URL,
  AKKA_FRONTEND_URL: process.env.AKKA_FRONTEND_URL,
  AKKA_WWW_PROXY_HEADER_NAME: process.env.AKKA_WWW_PROXY_HEADER_NAME,
  AKKA_WWW_PROXY_HEADER_SECRET: process.env.AKKA_WWW_PROXY_HEADER_SECRET
});
```

### Draft middleware

Typically in `middleware.js` (Next.js root middleware).

**Before:**
```js
import { akka_mw_wp_draft_middleware } from '@aventyret/akka-headless-wp/middleware';

const draftMw = akka_mw_wp_draft_middleware({
  AKKA_CMS_COOKIE_NAME: process.env.AKKA_CMS_COOKIE_NAME
});
```

**After:**
```js
const draftMw = akka_mw_wp_draft_middleware({
  AKKA_CMS_COOKIE_NAME: process.env.AKKA_CMS_COOKIE_NAME,
  AKKA_DRAFT_COOKIE_SECRET: process.env.AKKA_DRAFT_COOKIE_SECRET
});
```

### AdminBar (client component)

If your `<AdminBar>` usage passes `isQueryParamPermalink`, remove the prop — it no longer exists.

**Before:**
```jsx
<AdminBar isQueryParamPermalink cookieName={...} adminUrl={...} />
```

**After:**
```jsx
<AdminBar cookieName={...} adminUrl={...} />
```

### Optional: add a boot-level env check

To fail fast on app boot instead of relying on Handlers/ContentService to throw later, call `requireEnv` once at startup:

```js
import { requireEnv } from '@aventyret/akka-headless-wp/server';

requireEnv(process.env, [
  'AKKA_FRONTEND_URL',
  'AKKA_FRONTEND_URL_INTERNAL',
  'AKKA_CMS_URL',
  'AKKA_CMS_URL_INTERNAL',
  'AKKA_CMS_COOKIE_NAME',
  'AKKA_CMS_COOKIE_PATH',
  'AKKA_WWW_PROXY_HEADER_NAME',
  'AKKA_WWW_PROXY_HEADER_SECRET',
  'AKKA_DRAFT_COOKIE_SECRET'
]);
```

---

## Step 5 — Update cms theme (if needed)

If your site's theme registers its own REST routes against `Akka\Router::can_post_content` or `Akka\Router::can_get_content`, the methods still exist as thin aliases — your routes keep working **and** are now actually protected (instead of returning hardcoded `true` or using a timing-unsafe `===`). Optional cleanup: rename the callbacks to the canonical name:

```php
// Before
'permission_callback' => 'Akka\Router::can_post_content',

// After (preferred, but the alias is fine in 3.0)
'permission_callback' => 'Akka\Router::require_proxy_header',
```

---

## Step 6 — Deploy

**Recommended:** deploy cms and www together, in the same maintenance window. The cookie and header formats are mutually incompatible across versions.

**If you must stage them:**
1. Push new env to both secret stores.
2. Deploy cms first. Verify the admin loads with no Akka admin notices.
3. Deploy www. The 403 errors that appear in the gap between (2) and (3) are expected and self-resolve.

**Active sessions:** every cms user must log out and log back in once. Old unsigned cookies are rejected by the new middleware.

---

## Step 7 — Verify

| What | How | Expected |
|---|---|---|
| Admin loads | Open `/wp/wp-admin` | No "Akka env validation failed" notice |
| www reads cms | Open the front page | Loads, no console errors |
| Draft requires real login | Set `cms_signed_in=1` in devtools, visit `/draft/<slug>` | 401 |
| Draft works after real login | Log in to cms, click "Preview" on a draft | 200, draft renders |
| Cache flush works | Save a post in cms | Front-end cache clears within seconds |
| Editor block preview | Insert an Akka block in Gutenberg | Renders in editor |
| Sitemap | Open `/sitemap.xml` | Renders, contains content |

---

## Step 8 — Roll back (if needed)

The new env vars are ignored by 2.x, so the safest rollback is:
1. Put the OLD env vars back in your secret store (`AKKA_CMS_API_KEY`, `AKKA_FRONTEND_FLUSH_CACHE_KEY`, etc.). New ones can stay.
2. Redeploy the prior cms + www versions.

You don't need to "remove" the new env vars from anywhere — they just stop being read.

---

## Common errors and fixes

### "Akka env validation failed" admin notice

The validator lists which env vars are missing or placeholder. Add them to your env and redeploy cms.

### `403 Permission denied` on cms→www flush

The flush handler doesn't accept your header. Verify:
- `AKKA_WWW_PROXY_HEADER_NAME` is identical in cms and www
- `AKKA_WWW_PROXY_HEADER_SECRET` is identical in cms and www
- No leading/trailing whitespace
- If you rotated, both sides currently have the same value

### `404 Not found` on www→cms reads

Old behavior was `200` with hardcoded `can_get_content() => true`. Now routes reject unauthenticated traffic with `404` (the `permission_callback` returning `false` becomes `403`/`404` depending on WP REST configuration). Verify:
- `AKKA_WWW_PROXY_HEADER_*` are set on the cms side
- www's `ContentService` is configured with the same values
- Browser-direct requests to `/wp-json/akka/v2/post?...` will be denied — that's intentional, fetches must go through the BFF

### `401 Unauthorized` on `/draft` after a real login

Middleware rejected the cookie HMAC. Verify:
- `AKKA_DRAFT_COOKIE_SECRET` is set in cms (so the cookie is signed when set)
- `AKKA_DRAFT_COOKIE_SECRET` is set in www (so middleware can validate)
- The two values are identical
- User has logged out and logged in again after the upgrade

### `Handlers: AKKA_WWW_PROXY_HEADER_NAME and AKKA_WWW_PROXY_HEADER_SECRET are required`

www-side route handler isn't passing them. Update the `Handlers({...})` call (see Step 4).

### `ContentService: AKKA_WWW_PROXY_HEADER_NAME and AKKA_WWW_PROXY_HEADER_SECRET are required`

Same as above for `ContentService({...})`. See Step 4.

### Cache flush from cms silently does nothing

The plugin returns early when proxy env is unset on the cms side. Check the cms env — `AKKA_WWW_PROXY_HEADER_NAME` and `AKKA_WWW_PROXY_HEADER_SECRET` both set.

### "value equals variable name (placeholder)" notice

A template line like `AKKA_X='AKKA_X'` was copied without filling in. Generate a real value and put it in your secret store.

---

## Secret rotation (post-upgrade)

To rotate `AKKA_WWW_PROXY_HEADER_SECRET` without downtime:

1. Set `AKKA_WWW_PROXY_HEADER_SECRET="<new>,<old>"` in cms and www.
2. Deploy. Both values are accepted; outgoing requests use the first (`<new>`).
3. Verify nothing fails.
4. Set `AKKA_WWW_PROXY_HEADER_SECRET="<new>"` (drop the old value).
5. Deploy again.

Same applies to `AKKA_DRAFT_COOKIE_SECRET` — but rotating it invalidates every active draft preview cookie until users log in again.
