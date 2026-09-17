#!/usr/bin/env bash
# Markedge lightweight monitor (Phase 11 §27). Run every 5 minutes from cron on the application host.
# Checks readiness, 5xx and 429 rates in the nginx/app log, failed jobs, scheduler heartbeat, disk, backup age, certificate expiry.
# Alerts are appended to $ALERT_LOG and, when ALERT_MAIL is set, emailed with `mail`. External uptime monitoring is still required.
#   BASE=https://host APP_DIR=/var/www/markedge/current BACKUP_DIR=/var/backups/markedge ./scripts/ops/monitor.sh
set -uo pipefail
BASE="${BASE:?}"; APP_DIR="${APP_DIR:?}"; BACKUP_DIR="${BACKUP_DIR:-}"
ALERT_LOG="${ALERT_LOG:-$APP_DIR/storage/logs/monitor-alerts.log}"
ACCESS_LOG="${ACCESS_LOG:-/var/log/nginx/access.log}"
DISK_LIMIT="${DISK_LIMIT:-85}"; BACKUP_MAX_AGE_HOURS="${BACKUP_MAX_AGE_HOURS:-30}"; CERT_MIN_DAYS="${CERT_MIN_DAYS:-14}"
alerts=()
note() { alerts+=("$1"); }

code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 10 "$BASE/health/ready" 2>/dev/null || true)"; code="${code:-000}"
[ "$code" = "200" ] || note "readiness returned $code"

if [ -r "$ACCESS_LOG" ]; then
  recent="$(tail -n 2000 "$ACCESS_LOG")"
  five="$(printf '%s\n' "$recent" | awk '$9 ~ /^5/' | wc -l)"; toomany="$(printf '%s\n' "$recent" | awk '$9 == 429' | wc -l)"
  [ "$five" -gt 10 ] && note "$five 5xx responses in the last 2000 requests"
  [ "$toomany" -gt 200 ] && note "$toomany 429 responses in the last 2000 requests (rate-limit spike)"
fi

failed="$(cd "$APP_DIR" && php artisan queue:failed --no-ansi 2>/dev/null | grep -cE '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}' || true)"; failed="${failed:-0}"
[ "$failed" -gt 0 ] && note "$failed failed queue job(s)"

heartbeat="$(cd "$APP_DIR" && php artisan schedule:list --no-ansi 2>/dev/null | grep -c 'content:publish-scheduled' || true)"; heartbeat="${heartbeat:-0}"
[ "$heartbeat" -ge 1 ] || note "scheduler entry for content:publish-scheduled missing"
last_run="$(find "$APP_DIR/storage/logs" -name 'laravel-*.log' -mmin -60 | wc -l)"

disk="$(df -P "$APP_DIR" | awk 'NR==2 {gsub("%","",$5); print $5}')"
[ "$disk" -ge "$DISK_LIMIT" ] && note "disk usage ${disk}% on the application volume"

if [ -n "$BACKUP_DIR" ]; then
  newest="$(find "$BACKUP_DIR/db" -type f -mmin -$((BACKUP_MAX_AGE_HOURS * 60)) 2>/dev/null | wc -l | tr -d ' ')"
  [ "$newest" -ge 1 ] || note "no database backup newer than ${BACKUP_MAX_AGE_HOURS}h in $BACKUP_DIR/db"
fi

host="${BASE#https://}"; host="${host%%/*}"
if [[ "$BASE" == https://* ]]; then
  end="$(echo | openssl s_client -servername "$host" -connect "$host:443" 2>/dev/null | openssl x509 -noout -enddate 2>/dev/null | cut -d= -f2)"
  if [ -n "$end" ]; then days=$(( ( $(date -d "$end" +%s) - $(date +%s) ) / 86400 )); [ "$days" -lt "$CERT_MIN_DAYS" ] && note "TLS certificate expires in ${days} days"; else note "could not read TLS certificate"; fi
fi

if [ "${#alerts[@]}" -gt 0 ]; then
  msg="$(date -Is) ALERT $(printf '%s; ' "${alerts[@]}")"
  echo "$msg" | tee -a "$ALERT_LOG"
  [ -n "${ALERT_MAIL:-}" ] && command -v mail >/dev/null && printf '%s\n' "$msg" | mail -s "[Markedge] monitor alert" "$ALERT_MAIL"
  exit 1
fi
echo "$(date -Is) OK readiness=200 failed_jobs=$failed disk=${disk}%"
