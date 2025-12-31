# Audit technique – Check Master UFHB

## Synthèse rapide
- Application PHP custom (contrôleurs/ modèles faits maison) avec landing pages statiques (`index.php`, `public/indexCM.php`) et absence de framework.
- Dépendances modernes (Twig, HTMLPurifier, PHPMailer, PHP-DI…) mais elles sont peu exploitées : autoload PSR-4 et conteneur non utilisés dans les contrôleurs.
- Aucun socle de tests ou d’intégration continue détecté ; aucune commande `test` définie côté PHP ou Node.

## Risques et problèmes majeurs
- **Secrets en clair dans le dépôt** : identifiants SMTP Google exposés dans `app/config/email.php` et accès MySQL root sans mot de passe dans `app/config/database.php`. Les dumps SQL (`soutenance_manager.sql`, `ufrmi1802974_2q2mpf.sql`) sont également committés.
- **Protection CSRF non appliquée** : le formulaire de connexion inclut un token (`public/page_connexion.php`) mais le contrôleur `public/login.php` ne le vérifie pas. D’autres formulaires reposent sur `$_POST` sans validation centralisée.
- **Sessions non durcies** : aucune configuration de cookies (SameSite, Secure, HttpOnly) ni rotation des identifiants lors du login/logout dans `AuthController`, exposant aux vols de session.
- **Contrôles d’accès dispersés** : la logique de droits est déduite du groupe utilisateur via `MenuController` mais sans middleware central ; chaque page `layout.php?page=...` reste accessible si l’URL est connue.
- **Absence de journalisation structurée** : `AuditLog` existe mais Monolog (dépendance déclarée) n’est pas utilisé ; pas de corrélation ni d’alertes en cas d’échecs répétés.
- **Front-end servi en CDN** : Tailwind et FontAwesome chargés via CDN sur les pages publiques, exposant à des risques de supply chain et empêchant le contrôle de version des assets.

## Dette technique et qualité
- **Couplage fort contrôleur/SQL** : connexions PDO créées à la volée (`Database::getConnection()`), requêtes SQL multi-lignes et duplication des jointures (ex. `EvaluationSoutenanceController::getSoutenancesProgrammeesForView`).
- **Mix présentation/logique** : beaucoup de HTML dans les contrôleurs/handlers (ex. `public/page_connexion.php`), rendant les vues difficiles à maintenir malgré la présence de Twig.
- **Autoload ignoré** : les `require_once` manuels restent omniprésents alors que l’autoload Composer est configuré (`autoload.psr-4`), ce qui complexifie le refactoring.
- **Pas de validation centralisée** : `$_POST` et `$_GET` sont consommés directement dans plusieurs handlers (ex. `public/login.php`), malgré la présence de `vlucas/valitron` pour la validation.
- **Aucune couverture de tests** : ni PHPUnit ni tests end-to-end ; aucune vérification automatisée des workflows critiques (authentification, planification, évaluations).

## Données et performance
- Sous-requêtes multiples pour la composition des jurys et des notes (ex. `EvaluationSoutenanceController`) sans indexation documentée ni mise en cache applicative.
- Génération/lecture de fichiers (PDF/Word/Excel) sans piste de nettoyage ou de quotas, absence de surveillance de l’espace disque.
- Export/archives en clair dans le dépôt (`BaseDonneesHistoriqueComplete.tex.csv`, `docs/sample_archive_data.csv`) qui peuvent contenir des données sensibles.

## Sécurité opérationnelle
- Aucun mécanisme de configuration par environnement (.env) ; les valeurs sensibles sont en dur et impossibles à surcharger proprement en production.
- Pas de politique de mots de passe configurable : la règle est codée en dur dans `AuthController::updatePassword` et ne s’applique pas à la création d’utilisateurs.
- Journalisation des erreurs laissée au serveur web ou à `error_log` sans rétention définie.

## UX & Accessibilité
- Pas de thème sombre ou d’options d’accessibilité (contraste, navigation clavier) alors que l’interface est riche.
- Navigation `layout.php?page=...` dépend de paramètres GET peu robustes ; pas de gestion d’erreur 404/403 dédiée.
