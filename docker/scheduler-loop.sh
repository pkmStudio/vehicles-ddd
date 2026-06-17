#!/bin/sh
# Запуск планировщика каждую минуту (аналог cron). Лог — storage/logs/scheduler.log
set -e
cd /var/www
mkdir -p storage/logs
while true; do
  php artisan schedule:run 2>&1 | tee -a storage/logs/scheduler.log
  sleep 60
done
