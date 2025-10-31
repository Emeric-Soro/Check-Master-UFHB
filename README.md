# Check Master UFHB

Application de gestion des soutenances pour l'Université Félix Houphouët-Boigny.

## Prérequis

- Docker
- Docker Compose

## Installation

1. Cloner le repository
```bash
git clone https://github.com/Emeric-Soro/Check-Master-UFHB.git
cd Check-Master-UFHB
```

2. Configurer les variables d'environnement
```bash
cp .env.example .env
# Éditer .env et configurer les valeurs de production
```

3. Construire et démarrer les conteneurs Docker
```bash
docker-compose up --build -d
```

4. L'application sera accessible sur:
   - Application web: http://localhost:8080
   - phpMyAdmin: http://localhost:8081

## Configuration de Production

Pour déployer en production, définissez la variable d'environnement `APP_ENV=production` dans votre docker-compose.yml ou .env:

```yaml
services:
  web:
    build:
      context: .
      dockerfile: docker/php/Dockerfile
      args:
        APP_ENV: production
```

Cela activera:
- Configuration PHP sécurisée (display_errors=Off, log_errors=On)
- OPcache pour les performances
- En-têtes de sécurité HTTP

## Mise à jour après modification du code

Si vous avez modifié le Dockerfile ou les dépendances, vous devez reconstruire l'image:

```bash
docker-compose down
docker-compose up --build -d
```

## Extensions PHP requises

L'application nécessite les extensions PHP suivantes:
- `pdo` - Connexion à la base de données
- `pdo_mysql` - Driver MySQL
- `mysqli` - Interface MySQL améliorée
- `curl` - Requêtes HTTP
- `gd` - Manipulation d'images
- `zip` - Manipulation de fichiers ZIP (requis pour PHPWord)
- `fileinfo` - Information sur les fichiers
- `opcache` - Cache d'opcode pour les performances

## Fonctionnalités principales

- Gestion des soutenances
- Évaluation des dossiers
- Génération de documents PDF (PV de soutenance)
- Gestion des utilisateurs et permissions
- Suivi et archivage

## Technologies utilisées

- PHP 8.2
- MySQL 8.3
- Apache 2.4
- PHPWord - Génération de documents Word
- Gotenberg - Conversion de documents en PDF
- Docker & Docker Compose

## Sécurité

### En-têtes de sécurité HTTP

L'application implémente les en-têtes de sécurité suivants:
- `Content-Security-Policy` - Prévention des attaques XSS
- `X-Content-Type-Options: nosniff` - Prévention du MIME sniffing
- `X-Frame-Options: DENY` - Protection contre le clickjacking
- `Strict-Transport-Security` - Force HTTPS
- `Referrer-Policy` - Contrôle des informations de référence

### Rate Limiting

L'authentification inclut une limitation de taux:
- Maximum 5 tentatives de connexion
- Verrouillage de 5 minutes après dépassement

### Variables d'environnement sensibles

**IMPORTANT**: Ne jamais commiter de secrets dans le code source. Utilisez toujours des variables d'environnement pour:
- Mots de passe de base de données
- Credentials SMTP
- Clés API
- Tokens de sécurité

Consultez `.env.example` pour la liste complète des variables requises.

### Audits de sécurité

Le projet a été audité pour:
- ✅ Vulnérabilités des dépendances (composer audit, npm audit)
- ✅ Protection XSS dans les vues
- ✅ Validation des entrées utilisateur
- ✅ Optimisation des requêtes N+1
- ✅ Suppression des logs de débogage

## Optimisation des performances

### CSS de production

Pour générer le CSS minifié pour la production:

```bash
npm run build
```

### OPcache

OPcache est automatiquement activé en production pour améliorer les performances PHP.

## Résolution de problèmes

### Erreur "Class ZipArchive not found"

Si vous rencontrez cette erreur, cela signifie que l'extension PHP `zip` n'est pas installée. Assurez-vous de reconstruire l'image Docker:

```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Vérifier les extensions PHP installées

Pour vérifier les extensions PHP installées dans le conteneur:

```bash
docker-compose exec web php -m
```

Vous devriez voir `zip` et `opcache` dans la liste des modules.

## Support

Pour toute question ou problème, veuillez ouvrir une issue sur GitHub.
