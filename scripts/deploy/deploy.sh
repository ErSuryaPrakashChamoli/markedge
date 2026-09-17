#!/usr/bin/env bash
# Markedge release deploy (Phase 11 §49). Release-directory layout with an atomic symlink switch.
#
#   DEPLOY_ROOT=/var/www/markedge REPO=git@github.com:markedge/website.git ./scripts/deploy/deploy.sh v1.2.0
#
# Requirements on the host: git, composer, node/npm, php 8.5 CLI, the shared .env at $DEPLOY_ROOT/shared/.env,
# shared storage at $DEPLOY_ROOT/shared/storage, and the FPM reload command in FPM_RELOAD (optional).
set -euo pipefail

REF="${1:?usage: deploy.sh <git-tag-or-sha>}"
DEPLOY_ROOT="${DEPLOY_ROOT:?set DEPLOY_ROOT, e.g. /var/www/markedge}"
REPO="${REPO:?set REPO to the git remote}"
PHP="${PHP:-php}"
KEEP_RELEASES="${KEEP_RELEASES:-3}"
FPM_RELOAD="${FPM_RELOAD:-}"            # e.g. "sudo systemctl reload php8.5-fpm"
HEALTH_URL="${HEALTH_URL:-}"            # e.g. https://host/health/ready

STAMP="$(date +%Y%m%d%H%M%S)"
RELEASE="$DEPLOY_ROOT/releases/$STAMP"
SHARED="$DEPLOY_ROOT/shared"
CURRENT="$DEPLOY_ROOT/current"

log() { printf '\n[deploy] %s\n' "$*"; }

log "Preparing release $STAMP from $REF"
mkdir -p "$DEPLOY_ROOT/releases" "$SHARED/storage"
git clone --quiet --depth 1 --branch "$REF" "$REPO" "$RELEASE" 2>/dev/null || {
  git clone --quiet "$REPO" "$RELEASE"; git -C "$RELEASE" checkout --quiet "$REF"; }
echo "$REF $(git -C "$RELEASE" rev-parse HEAD)" > "$RELEASE/RELEASE"

log "Linking shared .env and storage"
[ -f "$SHARED/.env" ] || { echo "Missing $SHARED/.env (copy .env.production.example and fill it)"; exit 1; }
ln -sfn "$SHARED/.env" "$RELEASE/.env"
rm -rf "$RELEASE/storage"
ln -sfn "$SHARED/storage" "$RELEASE/storage"
for dir in app/public framework/cache/data framework/sessions framework/views logs; do mkdir -p "$SHARED/storage/$dir"; done

log "Installing dependencies and building assets"
( cd "$RELEASE" && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet )
( cd "$RELEASE" && npm ci --silent && npm run build --silent )

log "Validating environment"
( cd "$RELEASE" && $PHP artisan markedge:env-check )

log "Caching and migrating (additive migrations only)"
( cd "$RELEASE" && $PHP artisan storage:link --force >/dev/null && $PHP artisan optimize --quiet )
( cd "$RELEASE" && $PHP artisan migrate --force --no-interaction )

log "Switching traffic"
ln -sfn "$RELEASE" "$DEPLOY_ROOT/current_tmp" && mv -Tf "$DEPLOY_ROOT/current_tmp" "$CURRENT"
[ -n "$FPM_RELOAD" ] && eval "$FPM_RELOAD"
( cd "$CURRENT" && $PHP artisan queue:restart --quiet )

if [ -n "$HEALTH_URL" ]; then
  log "Health check $HEALTH_URL"
  curl -fsS --max-time 10 "$HEALTH_URL" >/dev/null || { echo "Health check failed: rolling back"; "$(dirname "$0")/rollback.sh"; exit 1; }
fi

log "Pruning old releases (keeping $KEEP_RELEASES)"
ls -1dt "$DEPLOY_ROOT"/releases/* | tail -n +$((KEEP_RELEASES + 1)) | xargs -r rm -rf

log "Deployed $REF as $RELEASE"
