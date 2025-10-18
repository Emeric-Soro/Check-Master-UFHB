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
- 🛡️ Protection CSRF sur tous les formulaires
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

### 4. Configurer l'environnement

```bash
# Copier le fichier d'exemple
cp .env.example .env

# Éditer le fichier .env avec vos paramètres
nano .env
```

### 5. Lancer l'application avec Docker

#### Environnement de Développement

```bash
docker-compose up -d
```

Cette commande va :
- Construire l'image PHP avec Apache (mode développement)
- Démarrer le conteneur MySQL avec le port 3306 exposé
- Démarrer phpMyAdmin sur le port 8081
- Monter les volumes nécessaires pour le hot-reload

#### Environnement de Production

```bash
# Copier et configurer le fichier .env de production
cp .env.prod.example .env
nano .env  # Modifier les valeurs sensibles

# Lancer avec la configuration de production
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

La configuration de production :
- Utilise un build multi-étapes optimisé
- Active OPcache pour de meilleures performances
- N'expose PAS les ports de la base de données ni de phpMyAdmin
- Utilise des volumes nommés pour la persistance des données
- Configure Apache et PHP pour la sécurité

### 6. Initialiser la base de données

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

### 7. Compiler les assets CSS

**Note**: En production, les assets sont automatiquement compilés lors du build Docker. Cette étape est uniquement nécessaire en développement.



```bash
# Mode développement avec watch
npm run tailwind:dev

# Mode production (minifié)
npm run build
```

## 🌐 Accès à l'Application

### Environnement de Développement

Une fois démarrée, l'application est accessible via :

- **Application principale** : http://localhost:8080
- **phpMyAdmin** : http://localhost:8081
- **Base de données** : localhost:3306 (accessible depuis l'hôte)

### Environnement de Production

- **Application principale** : http://localhost:80 (ou votre domaine configuré)
- **phpMyAdmin** : Non disponible (désactivé pour la sécurité)
- **Base de données** : Accessible uniquement via le réseau Docker interne

### Identifiants par défaut

Consultez la base de données pour les utilisateurs de test ou créez un nouvel utilisateur via l'interface d'administration.

## ⚙️ Configuration

### Configuration Docker

Le projet utilise une configuration Docker modulaire :

- **`docker-compose.yml`** : Configuration de base commune
- **`docker-compose.override.yml`** : Surcharge pour le développement (appliqué automatiquement)
- **`docker-compose.prod.yml`** : Surcharge pour la production (doit être spécifié explicitement)

#### Services en Développement

```yaml
- web (PHP 8.2 + Apache) : Port 8080
- db (MySQL 8.3) : Port 3306 (exposé)
- phpmyadmin : Port 8081 (exposé)
```

#### Services en Production

```yaml
- web (PHP 8.2 + Apache + OPcache) : Port 80
- db (MySQL 8.3) : Port NON exposé (sécurisé)
- phpmyadmin : Non inclus (sécurité)
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

## 🔒 Sécurité

### Protection CSRF

L'application intègre un système complet de protection contre les attaques CSRF (Cross-Site Request Forgery) :

#### Fonctionnement

1. **Génération de jeton** : Un jeton CSRF unique est généré pour chaque session utilisateur lors de la connexion
2. **Inclusion dans les formulaires** : Tous les formulaires incluent automatiquement un champ caché contenant le jeton CSRF
3. **Validation côté serveur** : Chaque requête POST est validée pour s'assurer que le jeton CSRF est présent et valide
4. **Régénération** : Le jeton est régénéré après une connexion réussie pour prévenir les attaques de fixation de session

#### Utilisation dans le code

**Dans les vues (formulaires)** :
```php
<form method="post" action="...">
    <?= CSRFProtection::getTokenField() ?>
    <!-- Autres champs du formulaire -->
</form>
```

**Dans les contrôleurs** :
```php
// Valider le jeton CSRF avant de traiter la requête POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRFProtection::verifyRequest();
    // Traitement de la requête...
}
```

#### API de la classe CSRFProtection

- `generateToken()` : Génère un nouveau jeton CSRF
- `getToken()` : Récupère le jeton CSRF actuel (ou en génère un si inexistant)
- `validateToken($token)` : Valide un jeton fourni
- `getTokenField()` : Génère le code HTML du champ caché
- `verifyRequest()` : Vérifie automatiquement le jeton depuis $_POST (lance une erreur 403 si invalide)
- `regenerateToken()` : Régénère le jeton (à utiliser après connexion)

### Bonnes pratiques de sécurité

- ✅ Tous les formulaires POST sont protégés par un jeton CSRF
- ✅ Les mots de passe sont hashés avec `password_hash()` (bcrypt)
- ✅ Validation et échappement des données utilisateur
- ✅ Sessions sécurisées avec cookies httponly
- ⚠️ **En production** : Modifiez les identifiants de base de données par défaut
- ⚠️ **En production** : Configurez HTTPS pour toutes les communications

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
