# 1) Imagen base con PHP 8.2 (modo CLI)
FROM php:8.2-cli

# 2) Instalar paquetes del sistema + extensiones PHP + ffmpeg
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    ffmpeg \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
 && docker-php-ext-install pdo pdo_mysql zip gd \
 && rm -rf /var/lib/apt/lists/*

# 3) Instalar Composer (copiado desde la imagen oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 4) Carpeta de trabajo dentro del contenedor
WORKDIR /app

# 5) Copiar archivos base de Composer primero (para cachear mejor)
COPY composer.json composer.lock ./

# 6) Instalar dependencias PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# 7) Copiar TODO el proyecto Laravel al contenedor
COPY . .

# 8) Crear enlace simbólico de storage (si falla, no rompe el build)
RUN php artisan storage:link || true

# 9) Puerto donde escuchará Laravel dentro del contenedor
ENV PORT=10000
EXPOSE 10000

# 🔚 10) Comando al arrancar el contenedor:
#    - Ejecuta migraciones
#    - Levanta php artisan serve en 0.0.0.0:PORT
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT}
