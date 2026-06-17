# Используем официальный образ PHP с FPM
FROM php:8.4-fpm

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    libssl-dev \
    pkg-config \
    zip \
    unzip

# Установка пакетов для сжатия изображений
RUN apt-get install -y jpegoptim optipng pngquant

# Настройка GD расширения
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Установка PHP расширений
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    sockets \
    xml

# Установка Redis расширения
RUN pecl install redis && docker-php-ext-enable redis

# Настройка Git safe.directory
RUN git config --global --add safe.directory /var/www

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php && \
    mv composer.phar /usr/local/bin/composer

# Установка Node.js и npm (LTS)
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm@latest

# Копируем только composer файлы сначала
COPY composer.json composer.lock /var/www/

# UID/GID
ARG USER_ID=1000
ARG GROUP_ID=1000
ARG USERNAME=user

# Создаем пользователя
RUN groupadd -g ${GROUP_ID} ${USERNAME} || true && \
    useradd -u ${USER_ID} -g ${GROUP_ID} -m ${USERNAME} || true

WORKDIR /var/www

# Копируем файлы на пользователя
COPY --chown=${USER_ID}:${GROUP_ID} . .

# Права
RUN chown -R ${USER_ID}:${GROUP_ID} /var/www && \
    chmod -R 775 storage bootstrap/cache

# Генерируем автозагрузчик
#RUN composer dump-autoload --optimize

# Переключаемся на пользователя
USER ${USER_ID}

# Открываем порт PHP-FPM
EXPOSE 9000

# Запускаем PHP-FPM
CMD ["php-fpm"]
