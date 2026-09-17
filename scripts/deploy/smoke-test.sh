#!/usr/bin/env bash
# Markedge production smoke test (Phase 11 §52). Read-only HTTP checks; exits non-zero on the first failure.
#   BASE=https://host ./scripts/deploy/smoke-test.sh [--insecure]
set -uo pipefail
BASE="${BASE:?set BASE, e.g. https://markedge.example}"
CURL="curl -sS --max-time 20 ${1:-}"
fail=0
check() { # check <label> <path> <expected-status> [grep-pattern]
  local out; out="$($CURL -o /tmp/markedge-smoke-body -w '%{http_code}' "$BASE$2")"
  if [ "$out" != "$3" ]; then echo "FAIL $1: $2 -> $out (expected $3)"; fail=1; return; fi
  if [ -n "${4:-}" ] && ! grep -qE -- "$4" /tmp/markedge-smoke-body; then echo "FAIL $1: $2 missing /$4/"; fail=1; return; fi
  echo "ok   $1"
}
header() { # header <label> <path> <header-regex>
  if $CURL -D - -o /dev/null "$BASE$2" | grep -qiE -- "$3"; then echo "ok   $1"; else echo "FAIL $1: $2 lacks /$3/"; fail=1; fi
}
check "homepage" "/" 200 "<h1"
check "services" "/services" 200
check "products" "/products" 200
check "solutions" "/solutions" 200
check "industries" "/industries" 200
check "insights" "/insights" 200
check "search empty" "/search" 200 'name="robots" content="noindex'
check "search query" "/search?q=software" 200
check "sitemap" "/sitemap.xml" 200 "<urlset"
check "robots" "/robots.txt" 200 "Sitemap:"
check "health live" "/health" 200 '"status":"ok"'
check "health ready" "/health/ready" 200 '"status":"ok"'
check "404 page" "/definitely-not-a-page-$RANDOM" 404 "Page not found"
check "admin login" "/admin/login" 200 'name="_token"|wire:snapshot'
check "env not exposed" "/.env" 404
check "git not exposed" "/.git/HEAD" 404
check "composer not exposed" "/composer.json" 404
header "csp header" "/" '^content-security-policy: .*nonce-'
header "nosniff" "/" '^x-content-type-options: nosniff'
header "request id" "/" '^x-request-id:'
header "admin no-store" "/admin/login" '^cache-control: .*no-store'
header "assets immutable" "$(grep -o '/build/assets/app-[^"]*\.css' /tmp/markedge-smoke-body | head -1 || echo /build/manifest.json)" '^cache-control: .*(immutable|max-age)'
[ $fail -eq 0 ] && echo "SMOKE TEST PASSED" || { echo "SMOKE TEST FAILED"; exit 1; }
