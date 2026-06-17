#!/bin/sh
set -e

# Том storage_data при первом запуске пустой — создаём структуру каталогов (логи, кэш, feedback, ocr)
mkdir -p /var/www/storage/logs /var/www/storage/logs/feedback \
         /var/www/storage/framework/cache/data /var/www/storage/framework/sessions \
         /var/www/storage/framework/views /var/www/storage/app/public
# Владелец www-data: и новые файлы логов (в т.ч. feedback/*.log) можно удалять из приложения / Filament
chown -R www-data:www-data /var/www/storage

# Only run migrations/cache when running the web app (php-fpm), not for horizon/scheduler
if [ "$1" = "php-fpm" ]; then
    # Wait for PostgreSQL (макс. 60 сек, затем — ошибка)
    tries=0
    max_tries=30
    until php -r "
        try {
            \$dsn = 'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE') . ';connect_timeout=2';
            \$pdo = new PDO(\$dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
            exit(0);
        } catch (Throwable \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        tries=$((tries + 1))
        echo "Waiting for database... ($tries/$max_tries)"
        if [ "$tries" -ge "$max_tries" ]; then
            echo "Fatal: database at DB_HOST=${DB_HOST} not reachable. Last error:"
            php -r "
                try {
                    \$dsn = 'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE') . ';connect_timeout=5';
                    new PDO(\$dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
                } catch (Throwable \$e) {
                    echo \$e->getMessage() . PHP_EOL;
                }
            " 2>&1
            exit 1
        fi
        sleep 2
    done

    php artisan migrate --force --no-interaction
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache || true
    php artisan storage:link || true
fi

exec "$@"
