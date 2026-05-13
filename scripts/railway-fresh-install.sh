#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# FRESH INSTALL on Railway — wipes everything, rebuilds from zero.
# Destructive: drops every database on the MySQL service and clears all
# tenant storage. Use this when partial seed data has poisoned the install
# and you want a guaranteed clean slate.
#
# Two phases:
#   Phase A (local) — run in your terminal: drops & recreates databases
#                     via the Railway MySQL shell.
#   Phase B (container) — run inside `railway ssh --service teachers-performance`
#                         from /var/www: clears storage, migrates, seeds.
# -----------------------------------------------------------------------------

cat <<'HEADER'
===============================================================================
FRESH INSTALL — Railway
===============================================================================

This is NOT a shell script you run end-to-end. It's a runbook. Follow it in
two phases, copy-pasting the commands into the right shell each time.

HEADER

cat <<'PHASE_A'
-------------------------------------------------------------------------------
PHASE A — Reset MySQL (run locally)
-------------------------------------------------------------------------------

    railway connect MySQL

then at the MySQL prompt:

    SHOW DATABASES;

    DROP DATABASE IF EXISTS railway;
    DROP DATABASE IF EXISTS central;
    DROP DATABASE IF EXISTS teachers_performance;
    -- if SHOW DATABASES revealed others, drop them too

    CREATE DATABASE railway              CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE central              CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE DATABASE teachers_performance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

    SHOW DATABASES;
    exit;

PHASE_A

cat <<'PHASE_B'
-------------------------------------------------------------------------------
PHASE B — Provision app (run inside the container)
-------------------------------------------------------------------------------

    railway ssh --service teachers-performance

then inside the container:

    cd /var/www

    # 1. clear stale tenant storage
    rm -rf /var/www/storage/tenant*

    # 2. central
    php artisan migrate:fresh \
      --database=central \
      --path=database/migrations/central \
      --force
    php artisan db:seed --class=CentralSeeder --force

    # 3. pre-create the JCD tenant's storage dirs
    mkdir -p /var/www/storage/tenant1/{app,framework/cache/data,framework/sessions,framework/views,logs}
    chown -R www-data:www-data /var/www/storage/tenant1
    chmod -R ug+rwX /var/www/storage/tenant1

    # 4. tenant migrations + seeders
    php artisan tenants:migrate
    php artisan tenants:seed --force

    # 5. verify
    php artisan migrate:status --database=central --path=database/migrations/central
    php artisan tenants:list

    exit

PHASE_B

cat <<'VERIFY'
-------------------------------------------------------------------------------
Sanity-check the data (back at your local terminal)
-------------------------------------------------------------------------------

    railway connect MySQL

    USE teachers_performance;
    SELECT COUNT(*) AS departments FROM departments;
    SELECT COUNT(*) AS users       FROM users;
    SELECT COUNT(*) AS subjects    FROM subjects;
    SELECT COUNT(*) AS criteria    FROM criteria;
    SELECT COUNT(*) AS questions   FROM questions;
    exit;

All counts should be non-zero. Super-admin login is super@platform.test / super123.

VERIFY
