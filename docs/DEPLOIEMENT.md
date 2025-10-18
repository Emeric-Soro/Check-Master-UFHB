# 🚀 Guide de Déploiement - Check Master UFHB

Guide complet pour déployer l'application en environnement de production.

## Table des Matières

1. [Prérequis](#prérequis)
2. [Préparation du Serveur](#préparation-du-serveur)
3. [Déploiement avec Docker](#déploiement-avec-docker)
4. [Déploiement Sans Docker](#déploiement-sans-docker)
5. [Configuration HTTPS/SSL](#configuration-httpsssl)
6. [Sécurisation](#sécurisation)
7. [Monitoring et Maintenance](#monitoring-et-maintenance)
8. [Sauvegarde et Restauration](#sauvegarde-et-restauration)
9. [Rollback](#rollback)
10. [Checklist de Déploiement](#checklist-de-déploiement)

---

## 📋 Prérequis

### Serveur Recommandé

- **OS** : Ubuntu 22.04 LTS ou Debian 11+
- **CPU** : 2+ cœurs
- **RAM** : 4 GB minimum, 8 GB recommandé
- **Disque** : 50 GB minimum (SSD recommandé)
- **Bande passante** : 100 Mbps

### Logiciels Requis

Avec Docker :
- Docker Engine 20.10+
- Docker Compose 2.0+
- Git

Sans Docker :
- PHP 8.2+
- MySQL 8.3+
- Apache 2.4+ ou Nginx
- Composer
- Node.js 18+

### Accès Serveur

- Accès SSH avec clé
- Utilisateur avec droits sudo
- Nom de domaine configuré

---

## 🖥️ Préparation du Serveur

### 1. Mise à Jour du Système

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip
```

### 2. Configuration du Pare-feu (UFW)

```bash
# Installer UFW
sudo apt install -y ufw

# Autoriser SSH
sudo ufw allow OpenSSH

# Autoriser HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Activer le pare-feu
sudo ufw enable
```

### 3. Créer un Utilisateur Dédié

```bash
# Créer l'utilisateur
sudo adduser soutenance

# Ajouter aux groupes nécessaires
sudo usermod -aG www-data soutenance
sudo usermod -aG docker soutenance  # Si Docker

# Passer à l'utilisateur
su - soutenance
```

### 4. Configuration SSH (Sécurité)

```bash
# Éditer la configuration SSH
sudo nano /etc/ssh/sshd_config

# Recommandations :
PermitRootLogin no
PasswordAuthentication no
PubkeyAuthentication yes
Port 22  # Ou un port personnalisé

# Redémarrer SSH
sudo systemctl restart sshd
```

---

## 🐳 Déploiement avec Docker

### Architecture Docker

Le projet utilise une **architecture Docker multi-environnement** :

#### Fichiers de Configuration

1. **`docker-compose.yml`** - Configuration de base
   - Définit les services communs (web, db)
   - Utilise des variables d'environnement pour la flexibilité
   - Configuré pour le développement par défaut

2. **`docker-compose.override.yml`** - Développement
   - Appliqué automatiquement avec `docker-compose up`
   - Expose les ports de la base de données et phpMyAdmin
   - Monte le code source en volume pour le hot-reload

3. **`docker-compose.prod.yml`** - Production
   - Doit être spécifié explicitement avec `-f`
   - Build multi-étages optimisé
   - Ports sensibles NON exposés
   - phpMyAdmin désactivé
   - Volumes nommés pour la persistance

#### Dockerfile Multi-Étapes

Le `docker/php/Dockerfile` utilise un build multi-étapes :

- **Stage 1 (base)** : Image de base avec PHP et Apache
- **Stage 2 (development)** : Configuration pour le développement
- **Stage 3 (composer-stage)** : Installation des dépendances Composer
- **Stage 4 (node-stage)** : Build des assets avec npm
- **Stage 5 (production)** : Image finale optimisée avec OPcache

#### Avantages

- ✅ **Sécurité** : Ports sensibles non exposés en production
- ✅ **Performance** : OPcache activé, image optimisée
- ✅ **Maintenabilité** : Séparation claire dev/prod
- ✅ **Build Efficace** : Cache des dépendances, build rapide

### 1. Installation de Docker

```bash
# Installer Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Ajouter l'utilisateur au groupe docker
sudo usermod -aG docker $USER

# Installer Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Vérifier l'installation
docker --version
docker-compose --version
```

### 2. Cloner le Projet

```bash
cd /home/soutenance
git clone https://github.com/Emeric-Soro/Check-Master-UFHB.git
cd Check-Master-UFHB
```

### 3. Configuration de Production

#### a. Créer le fichier `.env`

```bash
cp .env.example .env
nano .env
```

Contenu du `.env` :

```env
# Environnement
APP_ENV=production
APP_DEBUG=false
APP_URL=https://soutenance.ufhb.edu.ci

# Base de données
DB_HOST=db
DB_NAME=soutenance_manager
DB_USER=soutenance_user
DB_PASSWORD=CHANGEZ_CE_MOT_DE_PASSE_FORT
DB_CHARSET=utf8mb4

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_SECURE=tls
SMTP_USERNAME=noreply@ufhb.edu.ci
SMTP_PASSWORD=VOTRE_MOT_DE_PASSE
FROM_EMAIL=noreply@ufhb.edu.ci
FROM_NAME="Système de Gestion des Soutenances - UFHB"

# Sécurité
SESSION_LIFETIME=7200
CSRF_TOKEN_EXPIRY=3600
```

#### b. Utiliser la configuration de production

Le projet utilise désormais une configuration Docker modulaire :

- **`docker-compose.yml`** : Configuration de base
- **`docker-compose.prod.yml`** : Surcharge pour la production

La configuration de production inclut :
- ✅ Build multi-étapes optimisé avec Composer et npm
- ✅ OPcache activé pour PHP
- ✅ Port de base de données NON exposé
- ✅ phpMyAdmin désactivé
- ✅ Apache configuré pour la sécurité
- ✅ Volumes nommés pour la persistance des données

**Note** : Pas besoin de modifier `docker-compose.yml` manuellement, utilisez simplement la commande de déploiement avec `-f docker-compose.prod.yml`.

### 4. Construire et Démarrer

```bash
# Construire et démarrer avec la configuration de production
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Vérifier que les conteneurs sont en cours d'exécution
docker-compose -f docker-compose.yml -f docker-compose.prod.yml ps

# Vérifier les logs
docker-compose -f docker-compose.yml -f docker-compose.prod.yml logs -f web
```

**Note importante** : En production, les dépendances Composer et npm sont automatiquement installées et les assets compilés lors du build Docker. Vous n'avez plus besoin de les installer manuellement sur l'hôte.

### 5. Initialiser la Base de Données

```bash
# Importer le schéma
docker-compose exec db mysql -u${DB_USER} -p${DB_PASSWORD} ${DB_NAME} < soutenance_manager.sql

# Créer le compte administrateur
docker-compose exec web php /var/www/html/scripts/create_admin.php
```

### 6. Configurer les Permissions

```bash
# Permissions des fichiers
sudo chown -R soutenance:www-data /home/soutenance/Check-Master-UFHB
sudo chmod -R 755 /home/soutenance/Check-Master-UFHB

# Permissions d'écriture pour les uploads et logs
sudo chmod -R 775 public/uploads
sudo chmod -R 775 logs
sudo chmod -R 775 public/documents/generated
```

---

## 🔧 Déploiement Sans Docker

### 1. Installation de PHP 8.2

```bash
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt update

sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql \
    php8.2-gd php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip
```

### 2. Installation de MySQL

```bash
sudo apt install -y mysql-server

# Sécuriser MySQL
sudo mysql_secure_installation

# Créer la base et l'utilisateur
sudo mysql -u root -p
```

```sql
CREATE DATABASE soutenance_manager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'soutenance_user'@'localhost' IDENTIFIED BY 'VOTRE_MOT_DE_PASSE';
GRANT ALL PRIVILEGES ON soutenance_manager.* TO 'soutenance_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Installation d'Apache

```bash
sudo apt install -y apache2

# Activer les modules nécessaires
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers

# Redémarrer Apache
sudo systemctl restart apache2
```

### 4. Configuration du VirtualHost

```bash
sudo nano /etc/apache2/sites-available/soutenance.conf
```

```apache
<VirtualHost *:80>
    ServerName soutenance.ufhb.edu.ci
    ServerAdmin admin@ufhb.edu.ci
    
    DocumentRoot /var/www/Check-Master-UFHB/public
    
    <Directory /var/www/Check-Master-UFHB/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/soutenance_error.log
    CustomLog ${APACHE_LOG_DIR}/soutenance_access.log combined
    
    # Sécurité
    <FilesMatch "\.(htaccess|htpasswd|ini|log|sh|sql|md)$">
        Require all denied
    </FilesMatch>
</VirtualHost>
```

```bash
# Activer le site
sudo a2ensite soutenance.conf
sudo a2dissite 000-default.conf

# Redémarrer Apache
sudo systemctl restart apache2
```

### 5. Déployer l'Application

```bash
# Cloner dans /var/www
cd /var/www
sudo git clone https://github.com/Emeric-Soro/Check-Master-UFHB.git
cd Check-Master-UFHB

# Installer les dépendances
composer install --no-dev --optimize-autoloader
npm install --production
npm run build

# Configurer
cp .env.example .env
nano .env  # Modifier les variables

# Importer la base
mysql -u soutenance_user -p soutenance_manager < soutenance_manager.sql

# Permissions
sudo chown -R www-data:www-data /var/www/Check-Master-UFHB
sudo chmod -R 755 /var/www/Check-Master-UFHB
sudo chmod -R 775 public/uploads logs
```

---

## 🔒 Configuration HTTPS/SSL

### Option 1 : Let's Encrypt (Gratuit)

```bash
# Installer Certbot
sudo apt install -y certbot python3-certbot-apache

# Obtenir le certificat
sudo certbot --apache -d soutenance.ufhb.edu.ci

# Renouvellement automatique
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

### Option 2 : Certificat Commercial

```bash
# Générer une CSR
openssl req -new -newkey rsa:2048 -nodes \
    -keyout soutenance.key \
    -out soutenance.csr

# Soumettre la CSR à votre CA
# Recevoir le certificat (.crt) et la chaîne (.ca-bundle)

# Configurer Apache
sudo nano /etc/apache2/sites-available/soutenance-ssl.conf
```

```apache
<VirtualHost *:443>
    ServerName soutenance.ufhb.edu.ci
    
    DocumentRoot /var/www/Check-Master-UFHB/public
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/soutenance.crt
    SSLCertificateKeyFile /etc/ssl/private/soutenance.key
    SSLCertificateChainFile /etc/ssl/certs/soutenance.ca-bundle
    
    # Configuration comme VirtualHost :80
    # ...
</VirtualHost>
```

### Redirection HTTP → HTTPS

```apache
<VirtualHost *:80>
    ServerName soutenance.ufhb.edu.ci
    
    Redirect permanent / https://soutenance.ufhb.edu.ci/
</VirtualHost>
```

---

## 🛡️ Sécurisation

### 1. Configuration PHP pour Production

```bash
sudo nano /etc/php/8.2/apache2/php.ini
```

```ini
# Désactiver l'affichage des erreurs
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

# Limites
max_execution_time = 60
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M

# Sécurité
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off

# Session
session.cookie_httponly = On
session.cookie_secure = On
session.use_strict_mode = On

# Opcache (performance)
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
```

### 2. Sécurité Apache

```bash
sudo nano /etc/apache2/conf-enabled/security.conf
```

```apache
# Masquer la version Apache
ServerTokens Prod
ServerSignature Off

# Headers de sécurité
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'"

# HSTS (si HTTPS)
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

### 3. Sécurité MySQL

```sql
-- Supprimer les utilisateurs anonymes
DELETE FROM mysql.user WHERE User='';

-- Supprimer la base de test
DROP DATABASE IF EXISTS test;

-- Restreindre l'accès root à localhost
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

FLUSH PRIVILEGES;
```

### 4. Fail2Ban (Protection contre les attaques)

```bash
# Installer Fail2Ban
sudo apt install -y fail2ban

# Configuration pour Apache
sudo nano /etc/fail2ban/jail.local
```

```ini
[apache-auth]
enabled = true
port = http,https
logpath = /var/log/apache2/error.log

[apache-noscript]
enabled = true
port = http,https

[apache-overflows]
enabled = true
port = http,https
logpath = /var/log/apache2/error.log
```

```bash
# Redémarrer Fail2Ban
sudo systemctl restart fail2ban
```

### 5. Firewall Applicatif (ModSecurity)

```bash
# Installer ModSecurity
sudo apt install -y libapache2-mod-security2

# Activer
sudo a2enmod security2

# Configuration
sudo cp /etc/modsecurity/modsecurity.conf-recommended /etc/modsecurity/modsecurity.conf
sudo nano /etc/modsecurity/modsecurity.conf

# Changer
SecRuleEngine On

# Redémarrer Apache
sudo systemctl restart apache2
```

---

## 📊 Monitoring et Maintenance

### 1. Monitoring des Services

```bash
# Vérifier l'état des services
sudo systemctl status apache2
sudo systemctl status mysql
sudo systemctl status docker  # Si Docker

# Vérifier les processus
ps aux | grep apache
ps aux | grep mysql
```

### 2. Monitoring des Ressources

```bash
# Installer htop
sudo apt install -y htop

# CPU, RAM, Disque
htop

# Espace disque
df -h

# Utilisation disque par répertoire
du -sh /var/www/Check-Master-UFHB/*
```

### 3. Logs d'Application

```bash
# Logs Apache
sudo tail -f /var/log/apache2/soutenance_error.log
sudo tail -f /var/log/apache2/soutenance_access.log

# Logs PHP
sudo tail -f /var/log/php/error.log

# Logs MySQL
sudo tail -f /var/log/mysql/error.log

# Rotation des logs
sudo nano /etc/logrotate.d/soutenance
```

```
/var/log/apache2/soutenance*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 640 root adm
    sharedscripts
    postrotate
        systemctl reload apache2 > /dev/null
    endscript
}
```

### 4. Alertes Email (Optionnel)

```bash
# Installer postfix
sudo apt install -y postfix mailutils

# Script de monitoring
sudo nano /usr/local/bin/check_services.sh
```

```bash
#!/bin/bash
services=("apache2" "mysql")
for service in "${services[@]}"; do
    if ! systemctl is-active --quiet $service; then
        echo "Service $service is down!" | mail -s "Alert: Service Down" admin@ufhb.edu.ci
    fi
done
```

```bash
# Rendre exécutable
sudo chmod +x /usr/local/bin/check_services.sh

# Ajouter au cron (toutes les 5 minutes)
crontab -e
```

```
*/5 * * * * /usr/local/bin/check_services.sh
```

---

## 💾 Sauvegarde et Restauration

### 1. Script de Sauvegarde Automatique

```bash
sudo nano /usr/local/bin/backup_soutenance.sh
```

```bash
#!/bin/bash

# Configuration
BACKUP_DIR="/backups/soutenance"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="soutenance_manager"
DB_USER="soutenance_user"
DB_PASS="VOTRE_MOT_DE_PASSE"
APP_DIR="/var/www/Check-Master-UFHB"

# Créer le répertoire de sauvegarde
mkdir -p $BACKUP_DIR

# Sauvegarde de la base de données
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Sauvegarde des fichiers uploadés
tar -czf $BACKUP_DIR/uploads_$DATE.tar.gz $APP_DIR/public/uploads

# Sauvegarde de la configuration
cp $APP_DIR/.env $BACKUP_DIR/.env_$DATE

# Supprimer les sauvegardes de plus de 30 jours
find $BACKUP_DIR -type f -mtime +30 -delete

echo "Backup completed: $DATE"
```

```bash
# Rendre exécutable
sudo chmod +x /usr/local/bin/backup_soutenance.sh

# Ajouter au cron (tous les jours à 2h du matin)
sudo crontab -e
```

```
0 2 * * * /usr/local/bin/backup_soutenance.sh >> /var/log/backup.log 2>&1
```

### 2. Restauration depuis une Sauvegarde

```bash
# Restaurer la base de données
gunzip < /backups/soutenance/db_20250118_020000.sql.gz | mysql -u soutenance_user -p soutenance_manager

# Restaurer les fichiers
tar -xzf /backups/soutenance/uploads_20250118_020000.tar.gz -C /

# Restaurer la configuration
cp /backups/soutenance/.env_20250118_020000 /var/www/Check-Master-UFHB/.env
```

---

## ⏮️ Rollback

### Procédure de Rollback

```bash
# 1. Arrêter les services
sudo systemctl stop apache2

# 2. Restaurer le code
cd /var/www/Check-Master-UFHB
git log --oneline  # Voir l'historique
git reset --hard COMMIT_SHA  # Revenir à un commit précédent

# 3. Restaurer la base de données
gunzip < /backups/soutenance/db_AVANT_DEPLOY.sql.gz | mysql -u soutenance_user -p soutenance_manager

# 4. Réinstaller les dépendances
composer install --no-dev
npm install --production
npm run build

# 5. Redémarrer
sudo systemctl start apache2
```

---

## ✅ Checklist de Déploiement

### Avant le Déploiement

- [ ] Tester l'application en environnement de staging
- [ ] Sauvegarder la base de données de production
- [ ] Sauvegarder les fichiers de production
- [ ] Préparer un plan de rollback
- [ ] Informer les utilisateurs de la maintenance
- [ ] Vérifier la disponibilité des ressources serveur

### Configuration

- [ ] Créer le fichier `.env` avec les bonnes valeurs
- [ ] Configurer la base de données
- [ ] Configurer l'envoi d'emails
- [ ] Configurer HTTPS/SSL
- [ ] Configurer le pare-feu
- [ ] Configurer les permissions des fichiers

### Sécurité

- [ ] Changer tous les mots de passe par défaut
- [ ] Désactiver `display_errors` en PHP
- [ ] Configurer les headers de sécurité
- [ ] Installer Fail2Ban
- [ ] Configurer SSL/TLS
- [ ] Restreindre l'accès à phpMyAdmin (si utilisé)

### Performance

- [ ] Activer opcache
- [ ] Activer la compression gzip
- [ ] Configurer le cache navigateur
- [ ] Minifier les CSS/JS
- [ ] Optimiser les images

### Monitoring

- [ ] Configurer les logs
- [ ] Configurer la rotation des logs
- [ ] Configurer les sauvegardes automatiques
- [ ] Configurer les alertes
- [ ] Tester les notifications email

### Après le Déploiement

- [ ] Vérifier que l'application est accessible
- [ ] Tester la connexion utilisateur
- [ ] Tester les fonctionnalités principales
- [ ] Vérifier les logs d'erreur
- [ ] Vérifier les performances
- [ ] Informer les utilisateurs de la fin de maintenance

---

## 🆘 Support et Assistance

En cas de problème pendant le déploiement :

1. Consultez les logs : `/var/log/apache2/`, `/var/log/mysql/`
2. Vérifiez la [FAQ](./FAQ.md)
3. Contactez l'équipe de support
4. Ouvrez une [issue GitHub](https://github.com/Emeric-Soro/Check-Master-UFHB/issues)

---

**Dernière mise à jour** : Octobre 2025
