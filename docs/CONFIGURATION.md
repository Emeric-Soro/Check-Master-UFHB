# ⚙️ Guide de Configuration - Check Master UFHB

Guide détaillé pour configurer l'environnement de développement et de production.

## Table des Matières

1. [Configuration Docker](#configuration-docker)
2. [Configuration Base de Données](#configuration-base-de-données)
3. [Configuration Email](#configuration-email)
4. [Variables d'Environnement](#variables-denvironnement)
5. [Configuration Apache](#configuration-apache)
6. [Configuration Tailwind CSS](#configuration-tailwind-css)
7. [Configuration de Production](#configuration-de-production)

---

## 🐳 Configuration Docker

### Structure Docker

Le projet utilise Docker Compose avec 3 services :

```yaml
services:
  - web      # PHP 8.2 + Apache
  - db       # MySQL 8.3
  - phpmyadmin  # Interface d'administration DB
```

### Fichier `docker-compose.yml`

```yaml
services:
  web:
    build:
      context: ./docker/php
    ports:
      - "8080:80"
    volumes:
      - .:/var/www/html
    depends_on:
      - db

  db:
    image: mysql:8.3
    environment:
      MYSQL_ROOT_PASSWORD: password
      MYSQL_DATABASE: soutenance_manager
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql

  phpmyadmin:
    image: phpmyadmin/phpmyadmin:latest
    environment:
      PMA_HOST: db
      PMA_USER: root
      PMA_PASSWORD: password
    ports:
      - "8081:80"
    depends_on:
      - db

volumes:
  db_data:
```

### Personnalisation des Ports

Pour changer les ports par défaut, modifiez `docker-compose.yml` :

```yaml
services:
  web:
    ports:
      - "VOTRE_PORT:80"  # Ex: "9090:80"
  
  db:
    ports:
      - "VOTRE_PORT:3306"  # Ex: "3307:3306"
  
  phpmyadmin:
    ports:
      - "VOTRE_PORT:80"  # Ex: "9091:80"
```

### Configuration PHP (php.ini)

Le fichier `docker/php/php.ini` contient les paramètres PHP :

```ini
# Limites de téléchargement
upload_max_filesize = 100M
post_max_size = 100M

# Limites d'exécution
max_execution_time = 300
max_input_time = 300
memory_limit = 512M

# Affichage des erreurs (développement uniquement)
display_errors = On
error_reporting = E_ALL

# Timezone
date.timezone = Africa/Abidjan
```

Pour la production, modifiez :
```ini
display_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
```

### Dockerfile PHP

Le fichier `docker/php/Dockerfile` configure l'environnement PHP :

```dockerfile
FROM php:8.2-apache

# Installation des extensions PHP requises
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install gd pdo pdo_mysql mysqli

# Activation de mod_rewrite
RUN a2enmod rewrite

# Configuration du document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

WORKDIR /var/www/html
```

### Commandes Docker Utiles

```bash
# Démarrer les conteneurs
docker-compose up -d

# Arrêter les conteneurs
docker-compose down

# Voir les logs
docker-compose logs -f

# Voir les logs d'un service spécifique
docker-compose logs -f web

# Reconstruire les images
docker-compose build

# Redémarrer un service
docker-compose restart web

# Accéder au conteneur web
docker-compose exec web bash

# Accéder au conteneur MySQL
docker-compose exec db mysql -uroot -ppassword

# Supprimer les volumes (⚠️ Supprime les données)
docker-compose down -v
```

---

## 🗄️ Configuration Base de Données

### Paramètres de Connexion

Fichier : `app/config/database.php`

```php
<?php
class Database {
    private static $host = 'db';        // Nom du service Docker
    private static $db   = 'soutenance_manager';
    private static $user = 'root';
    private static $pass = 'password';  // À changer en production
    private static $charset = 'utf8';

    public static function getConnection() {
        try {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$db . ";charset=" . self::$charset;
            $pdo = new PDO($dsn, self::$user, self::$pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
}
```

### Configuration pour Environnement de Développement Local (sans Docker)

Si vous n'utilisez pas Docker :

```php
private static $host = 'localhost';  // ou '127.0.0.1'
private static $db   = 'soutenance_manager';
private static $user = 'votre_utilisateur';
private static $pass = 'votre_mot_de_passe';
```

### Initialisation de la Base de Données

#### Option 1 : Ligne de commande

```bash
# Via Docker
docker-compose exec db mysql -uroot -ppassword soutenance_manager < soutenance_manager.sql

# Sans Docker (localhost)
mysql -u root -p soutenance_manager < soutenance_manager.sql
```

#### Option 2 : phpMyAdmin

1. Accéder à http://localhost:8081
2. Connexion : root / password
3. Créer la base `soutenance_manager` si elle n'existe pas
4. Onglet "Importer" → Choisir `soutenance_manager.sql` → Exécuter

### Sauvegarde de la Base de Données

```bash
# Export complet
docker-compose exec db mysqldump -uroot -ppassword soutenance_manager > backup_$(date +%Y%m%d).sql

# Export de la structure uniquement
docker-compose exec db mysqldump -uroot -ppassword --no-data soutenance_manager > structure.sql

# Export des données uniquement
docker-compose exec db mysqldump -uroot -ppassword --no-create-info soutenance_manager > data.sql
```

### Restauration d'une Sauvegarde

```bash
docker-compose exec -T db mysql -uroot -ppassword soutenance_manager < backup_20250118.sql
```

---

## 📧 Configuration Email

### Fichier de Configuration

Fichier : `app/config/email.php`

```php
<?php
class EmailConfig {
    // Configuration SMTP
    public static $smtp_host = 'smtp.gmail.com';
    public static $smtp_port = 587;
    public static $smtp_secure = 'tls';  // 'tls' ou 'ssl'
    
    // Identifiants SMTP
    public static $smtp_username = 'votre.email@gmail.com';
    public static $smtp_password = 'votre_mot_de_passe_application';
    
    // Email expéditeur
    public static $from_email = 'noreply@ufhb.edu.ci';
    public static $from_name = 'Système de Gestion des Soutenances';
    
    // Configuration
    public static $debug = 0;  // 0=off, 1=erreurs, 2=messages, 3=debug complet
    public static $charset = 'UTF-8';
}
```

### Configuration Gmail

Pour utiliser Gmail :

1. **Activer l'authentification à deux facteurs** sur votre compte Google
2. **Générer un mot de passe d'application** :
   - Aller dans Paramètres Google → Sécurité
   - "Mots de passe des applications"
   - Générer un mot de passe pour "Mail"
3. Utiliser ce mot de passe dans la configuration :

```php
public static $smtp_username = 'votre.email@gmail.com';
public static $smtp_password = 'abcd efgh ijkl mnop';  // Mot de passe d'application
```

### Configuration avec Mailgun / SendGrid / Amazon SES

#### Mailgun

```php
public static $smtp_host = 'smtp.mailgun.org';
public static $smtp_port = 587;
public static $smtp_secure = 'tls';
public static $smtp_username = 'postmaster@votre-domaine.mailgun.org';
public static $smtp_password = 'votre_api_key';
```

#### SendGrid

```php
public static $smtp_host = 'smtp.sendgrid.net';
public static $smtp_port = 587;
public static $smtp_secure = 'tls';
public static $smtp_username = 'apikey';
public static $smtp_password = 'votre_api_key_sendgrid';
```

#### Amazon SES

```php
public static $smtp_host = 'email-smtp.us-east-1.amazonaws.com';
public static $smtp_port = 587;
public static $smtp_secure = 'tls';
public static $smtp_username = 'votre_access_key';
public static $smtp_password = 'votre_secret_key';
```

### Test de Configuration Email

Créez un fichier `test_email.php` à la racine :

```php
<?php
require 'vendor/autoload.php';
require 'app/config/email.php';

use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);

try {
    // Configuration SMTP
    $mail->isSMTP();
    $mail->Host = EmailConfig::$smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = EmailConfig::$smtp_username;
    $mail->Password = EmailConfig::$smtp_password;
    $mail->SMTPSecure = EmailConfig::$smtp_secure;
    $mail->Port = EmailConfig::$smtp_port;
    
    // Destinataires
    $mail->setFrom(EmailConfig::$from_email, EmailConfig::$from_name);
    $mail->addAddress('test@example.com', 'Destinataire Test');
    
    // Contenu
    $mail->isHTML(true);
    $mail->Subject = 'Test de configuration email';
    $mail->Body    = 'Si vous recevez cet email, la configuration est correcte !';
    
    $mail->send();
    echo 'Email envoyé avec succès !';
} catch (Exception $e) {
    echo "Erreur : {$mail->ErrorInfo}";
}
```

---

## 🔧 Variables d'Environnement

### Création d'un Fichier .env (Recommandé pour Production)

Créez un fichier `.env` à la racine :

```env
# Base de données
DB_HOST=db
DB_NAME=soutenance_manager
DB_USER=root
DB_PASSWORD=password
DB_CHARSET=utf8

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USERNAME=votre.email@gmail.com
SMTP_PASSWORD=votre_mot_de_passe
FROM_EMAIL=noreply@ufhb.edu.ci
FROM_NAME="Système de Gestion des Soutenances"

# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

# Sécurité
SESSION_LIFETIME=7200
CSRF_TOKEN_EXPIRY=3600
```

### Utilisation des Variables d'Environnement

Installez une librairie pour lire le `.env` :

```bash
composer require vlucas/phpdotenv
```

Puis dans vos fichiers de configuration :

```php
<?php
require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

class Database {
    private static $host = $_ENV['DB_HOST'];
    private static $db   = $_ENV['DB_NAME'];
    private static $user = $_ENV['DB_USER'];
    private static $pass = $_ENV['DB_PASSWORD'];
    // ...
}
```

### Sécurisation du .env

Ajoutez `.env` au `.gitignore` :

```
.env
.env.local
.env.*.local
```

Créez un `.env.example` comme modèle :

```env
DB_HOST=db
DB_NAME=soutenance_manager
DB_USER=root
DB_PASSWORD=changeme
# ...
```

---

## 🌐 Configuration Apache

### Fichier .htaccess

Le fichier `public/.htaccess` configure le routage :

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Rediriger vers index.php si le fichier/dossier n'existe pas
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
</IfModule>

# Sécurité
<FilesMatch "\.(htaccess|htpasswd|ini|log|sh|sql)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Compression gzip
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>

# Cache des fichiers statiques
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### Configuration VirtualHost (Production)

Pour un serveur de production :

```apache
<VirtualHost *:80>
    ServerName soutenance.ufhb.edu.ci
    ServerAdmin admin@ufhb.edu.ci
    
    DocumentRoot /var/www/html/Check-Master-UFHB/public
    
    <Directory /var/www/html/Check-Master-UFHB/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/soutenance_error.log
    CustomLog ${APACHE_LOG_DIR}/soutenance_access.log combined
</VirtualHost>
```

---

## 🎨 Configuration Tailwind CSS

### Fichier tailwind.config.js

```javascript
module.exports = {
  content: [
    "./public/**/*.php",
    "./ressources/**/*.php",
    "./src/**/*.{html,js}"
  ],
  theme: {
    extend: {
      colors: {
        primary: '#1e40af',
        secondary: '#64748b',
      }
    },
  },
  plugins: [],
}
```

### Fichier postcss.config.js

```javascript
module.exports = {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
}
```

### Compiler Tailwind CSS

```bash
# Mode développement (watch)
npm run tailwind:dev

# Mode production (minifié)
npx tailwindcss -i ./src/input.css -o ./public/css/output.css --minify
```

### Fichier Source (src/input.css)

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Vos styles personnalisés */
@layer components {
  .btn-primary {
    @apply bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700;
  }
}
```

---

## 🚀 Configuration de Production

### Checklist de Sécurité

- [ ] Changer les mots de passe par défaut (DB, admin)
- [ ] Désactiver les messages d'erreur PHP (`display_errors = Off`)
- [ ] Utiliser HTTPS avec certificat SSL
- [ ] Configurer un firewall (UFW, iptables)
- [ ] Limiter l'accès à phpMyAdmin
- [ ] Activer les logs d'audit
- [ ] Mettre en place des sauvegardes automatiques
- [ ] Utiliser des variables d'environnement pour les secrets
- [ ] Restreindre les permissions des fichiers
- [ ] Activer la validation CSRF

### Optimisation des Performances

```ini
# php.ini (production)
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
```

### Monitoring et Logs

```bash
# Rotation des logs Apache
sudo nano /etc/logrotate.d/apache2

# Ajouter :
/var/log/apache2/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 640 root adm
}
```

---

## 🔍 Résolution de Problèmes

### Problème : Les conteneurs Docker ne démarrent pas

```bash
# Vérifier les logs
docker-compose logs

# Vérifier les ports utilisés
netstat -tuln | grep LISTEN

# Arrêter et reconstruire
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Problème : Erreur de connexion à la base de données

- Vérifier que le conteneur MySQL est démarré : `docker-compose ps`
- Vérifier les identifiants dans `database.php`
- Vérifier que la base existe : `docker-compose exec db mysql -uroot -ppassword -e "SHOW DATABASES;"`

### Problème : Les emails ne sont pas envoyés

- Vérifier les logs : `docker-compose logs web`
- Tester avec `test_email.php`
- Vérifier les paramètres SMTP
- Vérifier que le port 587 n'est pas bloqué par le pare-feu

---

**Dernière mise à jour** : Octobre 2025
