FROM php:8.2-apache

# Installer dépendances et activer GD + autres extensions utiles
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    libreoffice-writer \
    libreoffice-calc \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd pdo pdo_mysql mysqli

# Extensions PHP essentielles
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Activer mod_rewrite pour les URL propres
RUN a2enmod rewrite

# Configurer le document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html