# FROM php:8.2-fpm-alpine

# # Dependencias
# RUN apk add --no-cache nginx curl git zip unzip mysql-client bash \
#     && docker-php-ext-install pdo pdo_mysql

# # Composer
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# # Carpeta trabajo
# WORKDIR /var/www/html

# # Copiar proyecto
# COPY . .

# # Permisos necesarios para Laravel
# RUN chown -R www-data:www-data storage bootstrap/cache && \
#     chmod -R 775 storage bootstrap/cache

# # Instalar dependencias de Laravel
# RUN composer install --optimize-autoloader --no-dev

# # Copiar config de nginx
# COPY nginx.conf /etc/nginx/nginx.conf

# # Script de inicio
# COPY init.sh /init.sh
# RUN chmod +x /init.sh

# EXPOSE 8000

# CMD ["/init.sh"]

FROM php:8.2-cli

# Instalar extensiones y utilidades necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Setear el directorio de trabajo
WORKDIR /app

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de Laravel
RUN composer install --optimize-autoloader --no-dev

# Generar APP_KEY si no existe
RUN php -r "file_exists('.env') || copy('.env.example', '.env');"

# Puerto expuesto para Render
EXPOSE 8000

# Comando de inicio
CMD php artisan serve --host=0.0.0.0 --port=8000
