# 1) Imagen base con PHP 8.3 (modo CLI)
FROM php:8.3-cli

# 2) Paquetes del sistema + extensiones PHP + ffmpeg
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    ffmpeg \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
  && docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    zip \
    gd \
  && rm -rf /var/lib/apt/lists/*

# 3) Instalar Composer (desde la imagen oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4) Carpeta de trabajo
WORKDIR /app

# 5) Copiar composer.* primero (mejor cache)
COPY composer.json composer.lock ./

# 6) Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 7) Copiar TODO el proyecto
COPY . .

# 8) Enlace simbólico storage (si falla, no rompe build)
RUN php artisan storage:link || true

# 9) Puerto interno
ENV PORT=10000
EXPOSE 10000

# 10) Comando al arrancar
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT}
