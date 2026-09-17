#!/usr/bin/env bash
# Markedge rollback (Phase 11 §50): repoint `current` to the previous release, reload FPM, restart workers.
# Migrations are additive, so the previous release runs against the current schema. Database restoration is
# a separate, deliberate decision (docs/production/backup-and-recovery.md), never part of this script.
set -euo pipefail

DEPLOY_ROOT="${DEPLOY_ROOT:?set DEPLOY_ROOT}"
PHP="${PHP:-php}"
FPM_RELOAD="${FPM_RELOAD:-}"

CURRENT_TARGET="$(readlink -f "$DEPLOY_ROOT/current")"
PREVIOUS="$(ls -1dt "$DEPLOY_ROOT"/releases/* | grep -v "^$CURRENT_TARGET$" | head -n 1 || true)"
[ -n "$PREVIOUS" ] || { echo "No previous release to roll back to"; exit 1; }

echo "[rollback] $CURRENT_TARGET -> $PREVIOUS"
ln -sfn "$PREVIOUS" "$DEPLOY_ROOT/current_tmp" && mv -Tf "$DEPLOY_ROOT/current_tmp" "$DEPLOY_ROOT/current"
[ -n "$FPM_RELOAD" ] && eval "$FPM_RELOAD"
( cd "$DEPLOY_ROOT/current" && $PHP artisan optimize:clear --quiet && $PHP artisan optimize --quiet && $PHP artisan queue:restart --quiet )
echo "[rollback] done; verify /health/ready and run scripts/deploy/smoke-test.sh"
