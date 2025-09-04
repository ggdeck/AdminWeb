FROM dunglas/frankenphp:latest

WORKDIR /app

# ✅ Install dependencies + intl + zip
RUN apt-get update && apt-get install -y \
    unzip git curl libpng-dev libonig-dev libxml2-dev zip sqlite3 libsqlite3-dev \
    libzip-dev libicu-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd intl zip \
    && rm -rf /var/lib/apt/lists/*

# ✅ Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ✅ Copy project files
COPY ./src /app

# ✅ Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader
RUN composer require laravel/octane --no-interaction
RUN php artisan octane:install --server=frankenphp --no-interaction || true

# ✅ Fix permission
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# ✅ Ports
EXPOSE 80 2019

# ✅ Start octane
CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=80", "--admin-port=2019"]
