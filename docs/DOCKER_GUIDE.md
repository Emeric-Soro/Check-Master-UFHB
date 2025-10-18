# Guide de Configuration Docker - Check Master UFHB

Ce document explique la nouvelle architecture Docker multi-environnement mise en place pour le projet.

## 📋 Vue d'Ensemble

Le projet utilise une architecture Docker modulaire avec séparation des environnements de développement et de production.

### Fichiers de Configuration

| Fichier | Rôle | Utilisation |
|---------|------|-------------|
| `docker-compose.yml` | Configuration de base commune | Toujours chargé |
| `docker-compose.override.yml` | Surcharge pour développement | Chargé automatiquement avec `docker compose up` |
| `docker-compose.prod.yml` | Surcharge pour production | Doit être spécifié explicitement avec `-f` |
| `docker/php/Dockerfile` | Image PHP multi-stage | Build différent selon le target |
| `.env.example` | Variables d'environnement de développement | Copier en `.env` |
| `.env.prod.example` | Variables d'environnement de production | Copier en `.env` sur le serveur |

## 🚀 Utilisation

### Développement

```bash
# 1. Créer le fichier .env
cp .env.example .env

# 2. Lancer l'environnement de développement
docker compose up -d

# 3. Accéder à l'application
# - Application: http://localhost:8080
# - phpMyAdmin: http://localhost:8081
# - MySQL: localhost:3306
```

**Services en développement:**
- ✅ Web (PHP 8.2 + Apache)
- ✅ MySQL 8.3 (port 3306 exposé)
- ✅ phpMyAdmin (port 8081 exposé)
- ✅ Code monté en volume (hot-reload)
- ✅ Display errors activé pour débogage

### Production

```bash
# 1. Créer le fichier .env de production
cp .env.prod.example .env

# 2. Modifier les secrets
nano .env
# Changer DB_PASSWORD, SMTP_PASSWORD, etc.

# 3. Lancer l'environnement de production
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# 4. Accéder à l'application
# - Application: http://localhost:80 (ou votre domaine)
```

**Services en production:**
- ✅ Web (PHP 8.2 + Apache + OPcache)
- ✅ MySQL 8.3 (port NON exposé - sécurité)
- ❌ phpMyAdmin (désactivé - sécurité)
- ✅ Code copié dans l'image (pas de volume)
- ✅ Display errors désactivé
- ✅ Headers de sécurité activés

## 🏗️ Architecture Multi-Stage du Dockerfile

Le Dockerfile utilise 5 stages pour optimiser le build:

### Stage 1: Base
- Image PHP 8.2 Apache
- Extensions: GD, PDO, MySQL
- Configuration Apache de base

### Stage 2: Development
- Hérite de Base
- Configuration PHP pour le debug
- Display errors activé

### Stage 3: Composer
- Installation des dépendances PHP
- Autoloader optimisé avec classmap

### Stage 4: Node
- Build des assets CSS avec Tailwind
- Minification pour production

### Stage 5: Production
- Hérite de Base
- OPcache activé et configuré
- Sécurité renforcée (no display errors, headers, etc.)
- Copie des artefacts des stages Composer et Node
- Image finale optimisée et légère

## 🔒 Sécurité

### En Production

| Mesure de Sécurité | Status |
|-------------------|--------|
| Port MySQL non exposé | ✅ Activé |
| phpMyAdmin désactivé | ✅ Activé |
| Display errors Off | ✅ Activé |
| Expose PHP Off | ✅ Activé |
| Headers de sécurité | ✅ Activé |
| Listing répertoires désactivé | ✅ Activé |
| Session cookies HTTPOnly | ✅ Activé |
| Secrets dans .env | ✅ Activé |
| .env dans .gitignore | ✅ Activé |

### Configuration Apache

```apache
ServerTokens Prod          # Cache la version Apache
ServerSignature Off        # Désactive la signature
TraceEnable Off           # Désactive TRACE
Options -Indexes          # Désactive le listing
```

### Configuration PHP

```ini
# Production
opcache.enable=1
opcache.validate_timestamps=0  # Pas de rechargement en prod
display_errors=Off
expose_php=Off
session.cookie_httponly=On
session.cookie_secure=On
```

## 📊 Performance

### Optimisations

1. **OPcache Activé**
   - Cache le bytecode PHP compilé
   - Réduit le temps de traitement de 70%
   - Configuration optimale pour production

2. **Multi-Stage Build**
   - Image finale plus petite
   - Pas de dépendances de build inutiles
   - Build plus rapide grâce au cache

3. **Autoloader Optimisé**
   - Classmap authoritative
   - Chargement plus rapide des classes

4. **Assets Minifiés**
   - CSS compilé et minifié
   - Taille réduite

## 🛠️ Commandes Utiles

### Développement

```bash
# Démarrer les services
docker compose up -d

# Voir les logs
docker compose logs -f web

# Arrêter les services
docker compose down

# Rebuilder l'image
docker compose build --no-cache

# Accéder au shell du conteneur web
docker compose exec web bash

# Accéder à MySQL
docker compose exec db mysql -uroot -ppassword soutenance_manager
```

### Production

```bash
# Démarrer les services
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Voir les logs
docker compose -f docker-compose.yml -f docker-compose.prod.yml logs -f

# Arrêter les services
docker compose -f docker-compose.yml -f docker-compose.prod.yml down

# Rebuilder (après mise à jour du code)
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build

# Vérifier le status
docker compose -f docker-compose.yml -f docker-compose.prod.yml ps
```

### Maintenance

```bash
# Voir la configuration mergée
docker compose config

# Voir la configuration de production
docker compose -f docker-compose.yml -f docker-compose.prod.yml config

# Nettoyer les volumes inutilisés
docker volume prune

# Nettoyer les images inutilisées
docker image prune -a
```

## 🧪 Vérification

Un script de vérification est disponible:

```bash
./verify-docker-config.sh
```

Ce script vérifie:
- ✅ Configuration des services (dev vs prod)
- ✅ Exposition des ports
- ✅ Présence de phpMyAdmin
- ✅ Configuration OPcache
- ✅ Sécurité (display_errors, listing, etc.)
- ✅ Présence des fichiers .env

## 📝 Variables d'Environnement

### Variables Importantes

| Variable | Développement | Production | Description |
|----------|---------------|------------|-------------|
| `APP_ENV` | development | production | Environnement |
| `APP_DEBUG` | true | false | Mode debug |
| `DB_HOST` | db | db | Hôte MySQL |
| `DB_PASSWORD` | password | **CHANGEZ-MOI** | Mot de passe DB |
| `DOCKER_WEB_PORT` | 8080 | 80 | Port web |
| `DOCKER_DB_PORT` | 3306 | - | Port MySQL (dev uniquement) |
| `DOCKER_PHPMYADMIN_PORT` | 8081 | - | Port phpMyAdmin (dev uniquement) |

### Secrets en Production

⚠️ **IMPORTANT**: En production, changez TOUS les mots de passe:
- `DB_PASSWORD`
- `SMTP_PASSWORD`
- Tout autre secret

Ne commitez JAMAIS le fichier `.env` avec des secrets réels!

## 🔄 Migration depuis l'Ancienne Configuration

Si vous utilisez l'ancienne configuration Docker:

1. Sauvegardez vos données
2. Arrêtez les anciens conteneurs: `docker compose down`
3. Créez le fichier `.env` depuis `.env.example`
4. Relancez avec la nouvelle configuration: `docker compose up -d`
5. Restaurez vos données si nécessaire

## 📚 Ressources

- [Documentation Docker Compose](https://docs.docker.com/compose/)
- [Multi-Stage Builds](https://docs.docker.com/build/building/multi-stage/)
- [PHP OPcache](https://www.php.net/manual/fr/book.opcache.php)
- [Apache Security](https://httpd.apache.org/docs/2.4/misc/security_tips.html)

## 🆘 Dépannage

### Problème: Port déjà utilisé

```bash
# Trouver le processus qui utilise le port
sudo lsof -i :8080

# Changer le port dans .env
DOCKER_WEB_PORT=8090
```

### Problème: Erreur de build

```bash
# Nettoyer et rebuilder
docker compose down
docker compose build --no-cache
docker compose up -d
```

### Problème: Volumes corrompus

```bash
# Supprimer les volumes et recommencer
docker compose down -v
docker compose up -d
```

---

**Version**: 1.0.0  
**Dernière mise à jour**: Octobre 2025
