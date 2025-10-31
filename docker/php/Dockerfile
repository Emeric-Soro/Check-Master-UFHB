FROM php:8.2.27-apache-bookworm

# Installer les dépendances système nécessaires et nettoyer ensuite
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    curl \
    libcurl4-openssl-dev \
    libzip-dev \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Configurer et installer les extensions PHP requises
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli curl zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Appliquer la configuration PHP personnalisée
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Configurer Apache
RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de dépendances et les installer
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-scripts --no-dev --no-autoloader

# Copier le reste de l'application
COPY . .

# Finaliser l'installation et définir les permissions
RUN composer dump-autoload --optimize \
    && mkdir -p /var/www/html/ressources/uploads \
    && mkdir -p /var/www/html/ressources/templates \
    && chown -R www-data:www-data /var/www/html/ressources/uploads \
    && chown -R www-data:www-data /var/www/html/ressources/templates