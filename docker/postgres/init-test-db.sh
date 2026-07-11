#!/bin/sh
set -e

# Отдельная БД для тестов (phpunit.xml: DB_CONNECTION=pgsql, DB_DATABASE=dan_vehicles_test),
# чтобы тесты гоняли реальный Postgres, а не sqlite (см. plan.md §11, п.18).
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    SELECT 'CREATE DATABASE dan_vehicles_test OWNER $POSTGRES_USER'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'dan_vehicles_test')\gexec
EOSQL
