FROM php:8.1-apache

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    default-mysql-client \
    cron \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql mysqli zip mbstring exif pcntl bcmath gd

# Включение mod_rewrite для Apache
RUN a2enmod rewrite

# Настройка прав на будущие монтируемые папки (на случай если проект собирается без томов)
RUN mkdir -p /var/www/html/uploads/images && \
    chown -R www-data:www-data /var/www/html/uploads && \
    chmod -R 775 /var/www/html/uploads

# Установка рабочей директории
WORKDIR /var/www/html

# Настройка cron задач
COPY docker/crontab /etc/cron.d/autorewrite-cron
RUN chmod 0644 /etc/cron.d/autorewrite-cron \
    && crontab /etc/cron.d/autorewrite-cron

# Настройка Apache
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf

# Экспозиция порта
EXPOSE 80

# Запуск Apache и cron
CMD service cron start && apache2-foreground
