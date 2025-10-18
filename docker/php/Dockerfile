# ==================================
# Stage 1: Base Image
# ==================================
FROM php:8.2-apache AS base

# Installer les dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    curl \
    git \
    && rm -rf /var/lib/apt/lists/*

# Configurer et installer les extensions PHP
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli

# Activer les modules Apache nécessaires
RUN a2enmod rewrite headers expires

# Configurer le document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# ==================================
# Stage 2: Development Image
# ==================================
FROM base AS development

# Configuration PHP pour le développement
RUN { \
        echo 'display_errors=On'; \
        echo 'error_reporting=E_ALL'; \
        echo 'log_errors=On'; \
    } > /usr/local/etc/php/conf.d/dev.ini

# Le code sera monté via un volume en développement

EXPOSE 80

# ==================================
# Stage 3: Composer Dependencies
# ==================================
FROM base AS composer-stage

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copier uniquement les fichiers de dépendances
COPY composer.json composer.lock ./

# Installer les dépendances (optimisé pour production)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

# Copier le code source et générer l'autoloader optimisé
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

# ==================================
# Stage 4: NPM Assets Build
# ==================================
FROM node:18-alpine AS node-stage

WORKDIR /app

# Copier les fichiers de configuration npm
COPY package*.json ./
COPY tailwind.config.js postcss.config.js ./

# Installer les dépendances npm
RUN npm ci --only=production

# Copier les sources CSS
COPY src ./src
COPY public ./public

# Builder les assets pour production
RUN npm run build || npx tailwindcss -i ./src/input.css -o ./public/css/output.css --minify

# ==================================
# Stage 5: Production Image
# ==================================
FROM base AS production

# Installer OPcache
RUN docker-php-ext-install opcache

# Copier la configuration PHP optimisée pour production
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=60'; \
        echo 'opcache.fast_shutdown=1'; \
        echo 'opcache.enable_cli=0'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Configuration PHP de sécurité pour production
RUN { \
        echo 'expose_php=Off'; \
        echo 'display_errors=Off'; \
        echo 'log_errors=On'; \
        echo 'error_log=/var/log/php_errors.log'; \
        echo 'session.cookie_httponly=On'; \
        echo 'session.cookie_secure=On'; \
        echo 'session.use_strict_mode=On'; \
    } > /usr/local/etc/php/conf.d/security.ini

# Configurer Apache pour la sécurité
RUN { \
        echo 'ServerTokens Prod'; \
        echo 'ServerSignature Off'; \
        echo 'TraceEnable Off'; \
    } >> /etc/apache2/conf-available/security.conf

# Désactiver le listing des répertoires
RUN sed -i 's/Options Indexes FollowSymLinks/Options -Indexes +FollowSymLinks/' /etc/apache2/apache2.conf

# Copier le code de l'application
COPY --chown=www-data:www-data . .

# Copier les dépendances Composer depuis le stage 1
COPY --from=composer-stage --chown=www-data:www-data /app/vendor ./vendor

# Copier les assets buildés depuis le stage 2
COPY --from=node-stage --chown=www-data:www-data /app/public/css/output.css ./public/css/output.css

# Créer les répertoires nécessaires avec les bonnes permissions
RUN mkdir -p public/uploads logs public/documents/generated \
    && chown -R www-data:www-data public/uploads logs public/documents/generated \
    && chmod -R 775 public/uploads logs public/documents/generated

# Exposer le port 80
EXPOSE 80

# Note: Apache s'exécute avec l'utilisateur www-data par défaut
# Le processus principal doit rester root pour lier le port 80

# Healthcheck pour vérifier que l'application fonctionne
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1