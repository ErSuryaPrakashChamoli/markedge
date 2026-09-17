# Markedge — CDN / Reverse-Proxy Readiness (Phase 10 §31/§32)

## What may be cached at the edge

| Path | Edge cache | Reason |
|---|---|---|
| `/build/*` | yes, 1 year, immutable | content-hashed Vite assets |
| `/storage/*` (media, conversions) | yes, 30 days | Media Library file names are stable per upload; new uploads get new paths |
| `/sitemap.xml`, `/robots.txt` | yes, 1 hour (`Cache-Control: max-age=3600, public` set by the app) | invalidated by content version on the app side |
| Public HTML (`/`, `/services/*`, `/insights/*`, …) | **only with cookie bypass** | HTML depends on first-party cookies: `mk_attr` (attribution + campaign CTA targeting) and the session cookie. Configure the CDN to bypass cache when the request carries `mk_attr` or the session cookie, and to strip `Set-Cookie` from cached responses. Without such a rule, keep HTML uncached (the app sends `Cache-Control: no-cache, private`). |
| `/search*` | no | query-specific, noindex |
| `/go/*` | never | click tracking + redirect, `no-store` |
| `/preview/*` | never | signed, authenticated, `no-store`, noindex |
| `/admin/*`, `/livewire/*` | never | authenticated, `no-store`; Livewire POSTs must reach origin |
| `/health*`, `/up` | never | monitoring |
| `/lp/*` | same as public HTML | landing pages carry campaign context |

## Personalisation safety
- The only personalised element on public pages is the campaign CTA (Phase 9). It is derived from the encrypted `mk_attr` cookie, so a cached page for a cookie-less visitor is always the default variant. Never cache a response that was generated with `mk_attr` present.
- `Vary: Cookie` is not used on purpose: it makes edge caching useless while giving a false sense of safety. Use explicit bypass rules.
- Attribution capture depends on the first request reaching the origin to set `mk_attr`; a cached HTML hit for a new visitor still sets no cookie, and the next request (any page) captures attribution. First-touch data is therefore delayed by at most one page view for edge-cached HTML.

## Headers to preserve at the edge
`Content-Security-Policy` (nonce differs per response, so HTML cannot be cached when CSP is enforced unless the CDN keeps the whole response as is), `X-Request-Id`, `Strict-Transport-Security`, `X-Content-Type-Options`.

## Invalidation
- Application caches key on the content version; nothing at the edge needs purging for content changes when HTML is not edge-cached.
- If HTML is edge-cached, purge by URL on publish/unpublish (hook `ContentPublished` / `ContentUnpublished` events) or purge everything on deploy.

## Trusted proxies
Set `TRUSTED_PROXIES="*"` (or the CDN IP ranges) so HTTPS detection, secure cookies, HSTS and rate limiting use the real client IP from `X-Forwarded-*`.

## Object storage (future)
`MEDIA_DISK=s3` with an `s3` disk in `config/filesystems.php` and `MARKEDGE_MEDIA_ORIGINS=https://cdn.example` for the CSP image allow-list. URLs are generated through the disk, never hard-coded, so no code change is required.
