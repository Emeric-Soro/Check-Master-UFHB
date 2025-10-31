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

2. Construire et démarrer les conteneurs Docker
```bash
docker-compose up --build -d
```

3. L'application sera accessible sur:
   - Application web: http://localhost:8080
   - phpMyAdmin: http://localhost:8081

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

Vous devriez voir `zip` dans la liste des modules.

## Support

Pour toute question ou problème, veuillez ouvrir une issue sur GitHub.
