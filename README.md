# 🎓 Check Master UFHB - Système de Gestion des Soutenances

Application web de gestion de la commission de validation des soutenances académiques pour l'Université Félix Houphouët-Boigny.

## 📋 Table des Matières

- [À propos](#à-propos)
- [Stack Technique](#stack-technique)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Structure du Projet](#structure-du-projet)
- [Documentation](#documentation)
- [Contribution](#contribution)
- [Support](#support)

## 🎯 À propos

Check Master UFHB est une application complète pour la gestion des soutenances académiques, incluant :

- ✅ Gestion des candidatures de soutenance
- 📝 Évaluation et notation des soutenances
- 👥 Gestion des jurys et des enseignants
- 📊 Génération de documents officiels (PV, annexes, attestations)
- 📧 Notifications par email
- 🔐 Système d'authentification et gestion des droits d'accès
- 📈 Tableaux de bord pour le suivi des soutenances

## 🛠 Stack Technique

### Backend
- **PHP 8.2** - Langage principal
- **MySQL 8.3** - Base de données
- **Composer** - Gestionnaire de dépendances PHP

### Frontend
- **HTML5 / CSS3**
- **JavaScript**
- **Tailwind CSS 4.x** - Framework CSS
- **Alpine.js** (via Tailwind) - Framework JavaScript léger

### Librairies PHP
- **PHPMailer 6.10** - Envoi d'emails
- **Dompdf 3.1** / **mPDF 8.2** - Génération de PDF
- **smalot/pdfparser 2.12** - Parsing de PDF

### Infrastructure
- **Docker & Docker Compose** - Containerisation
- **Apache** - Serveur web
- **phpMyAdmin** - Administration base de données

## 📦 Prérequis

Assurez-vous d'avoir installé :

- [Docker](https://docs.docker.com/get-docker/) (version 20.10 ou supérieure)
- [Docker Compose](https://docs.docker.com/compose/install/) (version 2.0 ou supérieure)
- [Git](https://git-scm.com/downloads)
- [Node.js](https://nodejs.org/) (version 18 ou supérieure) - Pour Tailwind CSS

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/Emeric-Soro/Check-Master-UFHB.git
cd Check-Master-UFHB
```

### 2. Configuration de l'environnement

Le projet utilise Docker avec des paramètres par défaut. Voir [CONFIGURATION.md](./docs/CONFIGURATION.md) pour personnaliser.

### 3. Installation des dépendances

#### Dépendances PHP (Composer)

```bash
# Si composer est installé localement
composer install

# OU utiliser composer via Docker
docker run --rm -v $(pwd):/app composer install
```

#### Dépendances Frontend (npm)

```bash
npm install
```

### 4. Lancer l'application avec Docker

```bash
docker-compose up -d
```

Cette commande va :
- Construire l'image PHP avec Apache
- Démarrer le conteneur MySQL
- Démarrer phpMyAdmin
- Monter les volumes nécessaires

### 5. Initialiser la base de données

```bash
# Accéder au conteneur MySQL
docker-compose exec db mysql -uroot -ppassword

# Puis importer le schéma
mysql> source /var/lib/mysql/soutenance_manager.sql;
```

**OU** utiliser phpMyAdmin :
1. Accéder à http://localhost:8081
2. Se connecter (user: `root`, password: `password`)
3. Importer le fichier `soutenance_manager.sql`

### 6. Compiler les assets CSS

```bash
# Mode développement avec watch
npm run tailwind:dev

# Mode production (minifié)
npm run build
```

## 🌐 Accès à l'Application

Une fois démarrée, l'application est accessible via :

- **Application principale** : http://localhost:8080
- **phpMyAdmin** : http://localhost:8081
- **Base de données** : localhost:3306

### Identifiants par défaut

Consultez la base de données pour les utilisateurs de test ou créez un nouvel utilisateur via l'interface d'administration.

## ⚙️ Configuration

### Configuration Docker

Le fichier `docker-compose.yml` définit trois services :

```yaml
- web (PHP 8.2 + Apache) : Port 8080
- db (MySQL 8.3) : Port 3306
- phpmyadmin : Port 8081
```

### Configuration Base de Données

Les paramètres de connexion sont définis dans `app/config/database.php` :

```php
- Host: 'db' (nom du service Docker)
- Database: 'soutenance_manager'
- User: 'root'
- Password: 'password'
```

⚠️ **Important** : En production, modifiez ces valeurs pour des raisons de sécurité.

### Configuration Email

La configuration email se trouve dans `app/config/email.php`. Configurez vos paramètres SMTP pour l'envoi d'emails.

Pour plus de détails, consultez [CONFIGURATION.md](./docs/CONFIGURATION.md).

## 📚 Structure du Projet

```
Check-Master-UFHB/
├── app/                      # Code application backend
│   ├── config/              # Fichiers de configuration
│   ├── controllers/         # Contrôleurs MVC
│   ├── models/              # Modèles de données
│   └── utils/               # Utilitaires et helpers
├── public/                   # Point d'entrée public
│   ├── css/                 # Fichiers CSS compilés
│   ├── js/                  # Scripts JavaScript
│   ├── images/              # Images et assets
│   └── layout.php           # Layout principal
├── ressources/              # Ressources application
│   ├── routes/              # Définition des routes
│   └── views/               # Vues (templates)
├── docker/                   # Configuration Docker
│   ├── php/                 # Dockerfile PHP
│   └── mysql/               # Scripts MySQL init
├── docs/                     # Documentation (voir ci-dessous)
├── src/                      # Sources Tailwind CSS
├── vendor/                   # Dépendances Composer
├── node_modules/            # Dépendances npm
├── composer.json            # Dépendances PHP
├── package.json             # Dépendances Node.js
├── docker-compose.yml       # Configuration Docker Compose
└── soutenance_manager.sql   # Schéma base de données
```

## 📖 Documentation

### Guides Utilisateurs
- **[Guide Utilisateur](./docs/GUIDE_UTILISATEUR.md)** - Guide complet pour les utilisateurs
- **[Configuration](./docs/CONFIGURATION.md)** - Configuration détaillée de l'environnement
- **[FAQ](./docs/FAQ.md)** - Questions fréquentes et résolution de problèmes
- **[Déploiement](./docs/DEPLOIEMENT.md)** - Guide de déploiement en production

### Guides Développeurs
- **[Guide de la Charte Graphique](./STYLE_GUIDE.md)** - 🎨 Guide complet de la charte graphique et des styles
- **[Composants Réutilisables](./COMPONENTS.md)** - 📦 Documentation des composants HTML/PHP réutilisables
- **[Guide de Contribution Style](./CONTRIBUTING_STYLE.md)** - 🚀 Guide rapide pour respecter la charte graphique
- **[Contribution Générale](./docs/CONTRIBUTION.md)** - Guide pour les contributeurs

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. Forkez le projet
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Poussez vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

Consultez [CONTRIBUTION.md](./docs/CONTRIBUTION.md) pour plus de détails.

## 🐛 Signaler un Bug

Si vous trouvez un bug, veuillez ouvrir une [issue](https://github.com/Emeric-Soro/Check-Master-UFHB/issues) avec :
- Description détaillée du problème
- Étapes pour reproduire
- Comportement attendu vs comportement observé
- Captures d'écran si applicable
- Environnement (OS, version Docker, navigateur)

## 📝 Licence

Ce projet est développé pour l'Université Félix Houphouët-Boigny.

## 👥 Équipe

Développé par l'équipe de l'UFR MIAGE, Université Félix Houphouët-Boigny.

## 📞 Support

Pour toute question ou assistance :
- Ouvrir une [issue](https://github.com/Emeric-Soro/Check-Master-UFHB/issues)
- Consulter la [FAQ](./docs/FAQ.md)
- Contacter l'équipe de développement

---

**Version** : 1.0.0  
**Dernière mise à jour** : Octobre 2025
