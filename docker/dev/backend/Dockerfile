FROM php:8.3-fpm-alpine

# Установка системных зависимостей
RUN apk update && apk add --no-cache \
    git \
    curl \
    libzip-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    bash

# Установка PHP расширений
RUN docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        zip \
        intl \
        mbstring \
        xml \
        opcache

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Настройка рабочей директории
WORKDIR /var/www/backend

# Создание пользователя
RUN adduser -D -u 1000 symfony && chown -R symfony:symfony /var/www

USER symfony

EXPOSE 9000
