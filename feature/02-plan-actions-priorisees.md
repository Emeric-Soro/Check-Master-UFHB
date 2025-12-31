# Plan d'actions priorisé

## Priorité immédiate (Semaine 0–1)
- **Externaliser les secrets** : remplacer `app/config/email.php` et `app/config/database.php` par des variables d’environnement, ajouter un `.env.example` et supprimer les dumps SQL du dépôt public.
- **CSRF et sessions** : vérifier le token sur `public/login.php` et factoriser un middleware CSRF pour les formulaires; initialiser les sessions avec `cookie_secure`, `httponly`, `samesite=Lax` et rotation d’ID au login/logout.
- **Durcissement de l’accès** : centraliser les vérifications d’autorisation avant de servir `layout.php?page=...`, refuser les pages non mappées et journaliser les refus.
- **Journalisation** : brancher Monolog (déjà en dépendance) pour tracer les connexions, échecs, exports et actions sensibles; définir un format unique (JSON) et un répertoire de logs hors `public/`.

## Court terme (2–4 semaines)
- **Routage unique** : introduire un front-controller (ex. `public/index.php`) avec autoload Composer et PHP-DI pour instancier contrôleurs/services sans `require_once`.
- **Validation entrée/sortie** : utiliser Valitron pour normaliser la validation des formulaires (login, création d’utilisateurs, dossiers) et HTMLPurifier pour tous les contenus riches (rapports, comptes rendus).
- **Build front** : remplacer les CDN par un build Tailwind/FontAwesome versionné, compiler `src/input.css` vers `public/css` dans la CI.
- **Sauvegarde et archivage** : déplacer les exports/dumps hors repo, chiffrer les archives et documenter un plan de rétention.

## Moyen terme (4–8 semaines)
- **Refactor modèles** : encapsuler PDO via un dépôt ou une couche `Repository` avec requêtes préparées et index recommandés; documenter les schémas dans `docs/`.
- **Tests** : ajouter des tests de fumée (authentification, création utilisateur, import Excel) avec PHPUnit + une base SQLite en mémoire; prévoir quelques tests E2E (Playwright/Cypress) pour le parcours d’inscription.
- **Observabilité** : métriques basiques (temps de réponse, erreurs, taille des exports), alertes sur échecs d’envoi d’e-mails et sur quotas disque.
- **Accessibilité** : ajouter navigation clavier, messages d’erreur persistants et alternatives texte sur les images/ICônes.

## Gouvernance et dette
- **CI minimale** : workflow GitHub Actions pour `composer validate`, `composer install --no-dev`, build Tailwind et exécution des tests.
- **Politique de mots de passe** : rendre la complexité configurable et l’appliquer à la création comme à la rotation; enregistrer la dernière mise à jour pour forcer un renouvellement périodique.
- **Gestion des rôles** : formaliser les rôles/groupes en constantes ou `enum`, documenter les privilèges associés et refuser par défaut.
