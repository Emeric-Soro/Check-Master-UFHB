# 🤝 Guide de Contribution - Check Master UFHB

Merci de votre intérêt pour contribuer à Check Master UFHB ! Ce guide vous aidera à démarrer.

## Table des Matières

1. [Code de Conduite](#code-de-conduite)
2. [Comment Contribuer](#comment-contribuer)
3. [Configuration de l'Environnement](#configuration-de-lenvironnement)
4. [Standards de Code](#standards-de-code)
5. [Workflow Git](#workflow-git)
6. [Tests](#tests)
7. [Documentation](#documentation)
8. [Revue de Code](#revue-de-code)
9. [Signaler des Bugs](#signaler-des-bugs)
10. [Proposer des Fonctionnalités](#proposer-des-fonctionnalités)

---

## 📜 Code de Conduite

### Notre Engagement

Nous nous engageons à faire de la participation à ce projet une expérience exempte de harcèlement pour tous, indépendamment de :
- L'âge
- La taille corporelle
- Le handicap visible ou invisible
- L'ethnicité
- Les caractéristiques sexuelles
- L'identité et l'expression de genre
- Le niveau d'expérience
- L'éducation
- Le statut socio-économique
- La nationalité
- L'apparence personnelle
- La race
- La religion
- L'identité et l'orientation sexuelle

### Comportements Attendus

- Utiliser un langage accueillant et inclusif
- Respecter les points de vue et expériences différents
- Accepter gracieusement les critiques constructives
- Se concentrer sur ce qui est le mieux pour la communauté
- Faire preuve d'empathie envers les autres membres

### Comportements Inacceptables

- L'utilisation de langage ou d'images sexualisés
- Le trolling, les commentaires insultants/désobligeants
- Le harcèlement public ou privé
- La publication d'informations privées d'autrui
- Toute autre conduite inappropriée dans un cadre professionnel

---

## 🚀 Comment Contribuer

### Types de Contributions

Nous acceptons plusieurs types de contributions :

1. **Code** : Corrections de bugs, nouvelles fonctionnalités
2. **Documentation** : Amélioration des guides, tutoriels
3. **Tests** : Ajout de tests unitaires et d'intégration
4. **Design** : Amélioration de l'interface utilisateur
5. **Traductions** : Traduction de l'interface (futur)
6. **Revues** : Revue de code et de pull requests

### Processus de Contribution

1. **Rechercher** les issues existantes ou en créer une nouvelle
2. **Discuter** de votre approche avant de commencer
3. **Fork** le projet
4. **Créer** une branche pour votre fonctionnalité
5. **Développer** avec des commits clairs
6. **Tester** vos changements
7. **Documenter** vos modifications
8. **Soumettre** une Pull Request

---

## 🛠️ Configuration de l'Environnement

### Prérequis

- Git
- Docker et Docker Compose OU PHP 8.2+ et MySQL 8.3+
- Node.js 18+
- Composer
- Un éditeur de code (VS Code recommandé)

### Installation

```bash
# 1. Fork et cloner votre fork
git clone https://github.com/VOTRE_USERNAME/Check-Master-UFHB.git
cd Check-Master-UFHB

# 2. Ajouter le dépôt upstream
git remote add upstream https://github.com/Emeric-Soro/Check-Master-UFHB.git

# 3. Installer les dépendances
composer install
npm install

# 4. Démarrer l'environnement de développement
docker-compose up -d

# 5. Initialiser la base de données
docker-compose exec db mysql -uroot -ppassword soutenance_manager < soutenance_manager.sql

# 6. Compiler les assets
npm run tailwind:dev
```

### Configuration de l'Éditeur (VS Code)

Extensions recommandées :

```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "bradlc.vscode-tailwindcss",
    "esbenp.prettier-vscode",
    "editorconfig.editorconfig"
  ]
}
```

Créer `.vscode/settings.json` :

```json
{
  "editor.formatOnSave": true,
  "editor.tabSize": 4,
  "files.insertFinalNewline": true,
  "files.trimTrailingWhitespace": true,
  "php.validate.executablePath": "/usr/bin/php",
  "[php]": {
    "editor.defaultFormatter": "bmewburn.vscode-intelephense-client"
  },
  "[javascript]": {
    "editor.defaultFormatter": "esbenp.prettier-vscode"
  }
}
```

---

## 📝 Standards de Code

### Conventions PHP

Suivez les standards PSR :
- **PSR-1** : Basic Coding Standard
- **PSR-12** : Extended Coding Style Guide
- **PSR-4** : Autoloading Standard

#### Exemple de Style PHP

```php
<?php

namespace App\Controllers;

use App\Models\Utilisateur;

class UtilisateurController
{
    private $model;

    public function __construct()
    {
        $this->model = new Utilisateur();
    }

    /**
     * Récupère tous les utilisateurs
     *
     * @return array Liste des utilisateurs
     */
    public function getAll(): array
    {
        try {
            $utilisateurs = $this->model->findAll();
            return $utilisateurs;
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des utilisateurs : " . $e->getMessage());
            return [];
        }
    }
}
```

### Conventions JavaScript

Suivez les standards ES6+ :

```javascript
// Utiliser const/let au lieu de var
const API_URL = '/api/utilisateurs';

// Arrow functions
const fetchUtilisateurs = async () => {
    try {
        const response = await fetch(API_URL);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Erreur:', error);
    }
};

// Nommage en camelCase
const userName = 'John Doe';
const isActive = true;
```

### Conventions HTML/CSS

```html
<!-- Utiliser des classes Tailwind -->
<div class="flex items-center justify-between p-4 bg-white rounded-lg shadow">
    <h2 class="text-xl font-semibold text-gray-800">Titre</h2>
    <button class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">
        Action
    </button>
</div>
```

### Conventions de Nommage

- **Classes** : PascalCase (`UtilisateurController`)
- **Méthodes/Fonctions** : camelCase (`getUserById`)
- **Variables** : camelCase (`$userId`)
- **Constantes** : UPPER_SNAKE_CASE (`MAX_UPLOAD_SIZE`)
- **Tables DB** : snake_case (`candidature_soutenance`)
- **Fichiers** : PascalCase pour classes, snake_case pour autres

### Commentaires et Documentation

```php
/**
 * Crée une nouvelle candidature de soutenance
 *
 * @param int $idEtudiant ID de l'étudiant
 * @param array $data Données de la candidature
 * @return int|false ID de la candidature créée ou false en cas d'erreur
 * @throws \InvalidArgumentException Si les données sont invalides
 */
public function createCandidature(int $idEtudiant, array $data)
{
    // Validation des données
    if (empty($data['titre'])) {
        throw new \InvalidArgumentException('Le titre est requis');
    }

    // Logique de création
    // ...
}
```

---

## 🌿 Workflow Git

### Branches

- **`main`** : Branche de production (protégée)
- **`develop`** : Branche de développement
- **`feature/*`** : Nouvelles fonctionnalités
- **`bugfix/*`** : Corrections de bugs
- **`hotfix/*`** : Corrections urgentes en production
- **`docs/*`** : Documentation uniquement

### Créer une Branche

```bash
# Mettre à jour votre fork
git checkout develop
git pull upstream develop

# Créer une nouvelle branche
git checkout -b feature/nom-de-la-fonctionnalite
```

### Commits

Utilisez des messages de commit clairs et descriptifs :

#### Format

```
type(scope): sujet

corps (optionnel)

footer (optionnel)
```

#### Types de Commits

- **feat** : Nouvelle fonctionnalité
- **fix** : Correction de bug
- **docs** : Documentation uniquement
- **style** : Formatage, point-virgules manquants, etc.
- **refactor** : Refactorisation du code
- **test** : Ajout de tests
- **chore** : Maintenance, dépendances, etc.

#### Exemples

```bash
# Nouvelle fonctionnalité
git commit -m "feat(candidature): ajouter validation des documents uploadés"

# Correction de bug
git commit -m "fix(auth): corriger la redirection après connexion"

# Documentation
git commit -m "docs(readme): ajouter instructions d'installation Docker"

# Refactorisation
git commit -m "refactor(models): simplifier la méthode getAll()"
```

### Pull Requests

#### Créer une Pull Request

1. **Poussez** votre branche :
   ```bash
   git push origin feature/nom-de-la-fonctionnalite
   ```

2. **Ouvrez** une PR sur GitHub depuis votre fork vers `Emeric-Soro/Check-Master-UFHB:develop`

3. **Remplissez** le template de PR :

```markdown
## Description
Brève description des changements

## Type de Changement
- [ ] Bug fix
- [ ] Nouvelle fonctionnalité
- [ ] Breaking change
- [ ] Documentation

## Tests Effectués
- [ ] Tests unitaires ajoutés/mis à jour
- [ ] Tests manuels effectués
- [ ] Tests dans différents navigateurs

## Checklist
- [ ] Mon code suit les standards du projet
- [ ] J'ai commenté les parties complexes
- [ ] J'ai mis à jour la documentation
- [ ] Mes changements ne génèrent pas de warnings
- [ ] J'ai ajouté des tests
- [ ] Tous les tests passent
- [ ] J'ai vérifié qu'il n'y a pas de conflits

## Screenshots (si applicable)
```

#### Revue de Code

- Attendez la revue d'au moins un mainteneur
- Répondez aux commentaires de manière constructive
- Effectuez les modifications demandées
- Une fois approuvée, votre PR sera mergée

---

## 🧪 Tests

### Tests Manuels

Avant de soumettre une PR, testez manuellement :

1. **Fonctionnalité principale** : Vérifiez que votre changement fonctionne
2. **Cas limites** : Testez avec des données invalides, vides, extrêmes
3. **Régression** : Vérifiez que vous n'avez rien cassé
4. **Navigateurs** : Testez sur Chrome, Firefox, Safari (si frontend)
5. **Responsive** : Testez sur mobile et desktop (si frontend)

### Tests Unitaires (à venir)

Structure des tests :

```
tests/
├── Unit/
│   ├── Models/
│   ├── Controllers/
│   └── Utils/
└── Integration/
    ├── API/
    └── Database/
```

Exemple de test (PHPUnit) :

```php
<?php

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Utilisateur;

class UtilisateurTest extends TestCase
{
    public function testCreateUtilisateur()
    {
        $utilisateur = new Utilisateur();
        $data = [
            'nom' => 'Doe',
            'prenoms' => 'John',
            'email' => 'john.doe@example.com'
        ];
        
        $id = $utilisateur->create($data);
        
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
    }
}
```

---

## 📚 Documentation

### Documentation du Code

- Commentez les parties complexes
- Utilisez PHPDoc pour les fonctions/méthodes
- Expliquez le "pourquoi", pas le "comment"

### Documentation Utilisateur

Si votre fonctionnalité nécessite une documentation utilisateur :

1. Mettez à jour `docs/GUIDE_UTILISATEUR.md`
2. Ajoutez des captures d'écran si pertinent
3. Créez un tutoriel si c'est une fonctionnalité majeure

### Documentation Technique

Si vous modifiez l'architecture ou ajoutez des dépendances :

1. Mettez à jour le README.md
2. Documentez dans `docs/CONFIGURATION.md`
3. Ajoutez une entrée dans `docs/FAQ.md` si nécessaire

---

## 👀 Revue de Code

### En tant que Contributeur

- Soyez ouvert aux suggestions
- Expliquez vos choix si nécessaire
- Ne prenez pas les commentaires personnellement
- Effectuez les changements demandés rapidement

### En tant que Reviewer

- Soyez respectueux et constructif
- Expliquez le "pourquoi" de vos suggestions
- Approuvez si les standards sont respectés
- Demandez des clarifications si nécessaire

### Checklist de Revue

- [ ] Le code suit les standards du projet
- [ ] Le code est lisible et maintenable
- [ ] Les fonctionnalités complexes sont commentées
- [ ] Il n'y a pas de code dupliqué
- [ ] Les erreurs sont gérées correctement
- [ ] Les tests passent
- [ ] La documentation est à jour
- [ ] Pas de secrets ou credentials en dur

---

## 🐛 Signaler des Bugs

### Avant de Signaler

1. Vérifiez que le bug n'a pas déjà été signalé
2. Assurez-vous que c'est bien un bug et non une fonctionnalité
3. Collectez les informations nécessaires

### Template de Bug Report

Utilisez ce template lors de la création d'une issue :

```markdown
## Description du Bug
Une description claire et concise du bug.

## Étapes pour Reproduire
1. Aller sur '...'
2. Cliquer sur '...'
3. Faire défiler jusqu'à '...'
4. Voir l'erreur

## Comportement Attendu
Ce qui devrait se passer.

## Comportement Observé
Ce qui se passe réellement.

## Screenshots
Si applicable, ajoutez des captures d'écran.

## Environnement
- OS: [ex: Ubuntu 22.04]
- Navigateur: [ex: Chrome 120]
- Version PHP: [ex: 8.2]
- Docker: [Oui/Non]

## Logs d'Erreur
```
Collez les logs pertinents ici
```

## Informations Additionnelles
Tout autre contexte pertinent.
```

---

## 💡 Proposer des Fonctionnalités

### Avant de Proposer

1. Vérifiez que la fonctionnalité n'existe pas déjà
2. Vérifiez qu'elle n'a pas déjà été proposée
3. Assurez-vous qu'elle correspond à la vision du projet

### Template de Feature Request

```markdown
## Problème / Besoin
Décrivez le problème que cette fonctionnalité résoudrait.

## Solution Proposée
Décrivez comment vous imaginez la fonctionnalité.

## Alternatives Considérées
Quelles alternatives avez-vous envisagées ?

## Bénéfices
- Bénéfice 1
- Bénéfice 2

## Impacts Potentiels
- Sur les performances
- Sur la sécurité
- Sur la compatibilité

## Mockups / Wireframes
Si applicable, ajoutez des maquettes.
```

---

## 📬 Contact

### Canaux de Communication

- **GitHub Issues** : Pour les bugs et fonctionnalités
- **GitHub Discussions** : Pour les questions et discussions
- **Email** : contact@ufhb.edu.ci

### Réponse

Nous nous efforçons de répondre dans :
- **Issues** : 2-3 jours ouvrables
- **Pull Requests** : 3-5 jours ouvrables
- **Email** : 5-7 jours ouvrables

---

## 🎉 Reconnaissance

Les contributeurs sont reconnus dans :
- Le fichier CONTRIBUTORS.md (à créer)
- La page "À propos" de l'application
- Les release notes pour les contributions majeures

---

## 📄 Licence

En contribuant à ce projet, vous acceptez que vos contributions soient sous la même licence que le projet principal.

---

## 🙏 Merci !

Merci de contribuer à Check Master UFHB ! Votre aide est précieuse pour améliorer le système de gestion des soutenances.

---

**Dernière mise à jour** : Octobre 2025
