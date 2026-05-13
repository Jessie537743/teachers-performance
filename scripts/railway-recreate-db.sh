#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# Recreate ALL databases + tables on Railway for the multi-tenant Laravel app.
# Destructive: wipes the central DB and every tenant DB. Run with care.
#
# Prereq:
#   railway login
#   railway link        # link to the project containing MySQL + app services
#
# Two execution modes:
#   MODE=ssh   (default) — run artisan inside the deployed container via railway ssh
#   MODE=local           — run artisan locally with `railway run`, talking to
#                          Railway's MySQL remotely. Requires PHP + composer
#                          installed locally and `vendor/` populated under src/.
#
# Usage:
#   bash scripts/railway-recreate-db.sh
#   MODE=local bash scripts/railway-recreate-db.sh
# -----------------------------------------------------------------------------
set -euo pipefail

SERVICE="${RAILWAY_APP_SERVICE:-teachers-performance}"
MODE="${MODE:-ssh}"

read -p "This will WIPE the central + all tenant DBs on Railway. Type 'yes' to continue: " confirm
[ "$confirm" = "yes" ] || { echo "Aborted."; exit 1; }

echo
echo "==> Step 1: Ensure databases exist on the MySQL plugin"
echo "    Run this manually in the Railway MySQL shell (railway connect MySQL):"
echo "      CREATE DATABASE IF NOT EXISTS railway CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "      CREATE DATABASE IF NOT EXISTS central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo
read -p "Press <enter> once both databases exist..."

run_artisan() {
  local cmd="$*"
  if [ "$MODE" = "ssh" ]; then
    railway ssh --service "$SERVICE" "cd /var/www && php artisan $cmd"
  else
    ( cd "$(dirname "$0")/../src" && railway run --service "$SERVICE" php artisan $cmd )
  fi
}

echo
echo "==> Step 2: migrate:fresh + seed central"
run_artisan migrate:fresh --database=central --path=database/migrations/central --force
run_artisan db:seed --class=CentralSeeder --force

echo
echo "==> Step 3: migrate:fresh + seed every tenant"
# NOTE: stancl/tenancy's tenants:migrate-fresh does NOT accept --force.
# It auto-passes --force=true to the underlying migrate:fresh internally.
run_artisan tenants:migrate-fresh
run_artisan tenants:seed --force

echo
echo "==> Step 4: (optional) wipe the default 'railway' DB"
run_artisan migrate:fresh --force || true

echo
echo "==> Step 5: verify"
run_artisan migrate:status --database=central --path=database/migrations/central
run_artisan tenants:list

echo
echo "Done."
