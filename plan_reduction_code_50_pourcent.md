# Plan structuré — Réduire le code de 50 % sans casser la logique

## 1. Objectif et contrainte principale

Réduire fortement le volume, la duplication et la complexité du code du projet CheckMaster, avec une cible indicative de 50 %, sans modifier les comportements métier attendus.

La réduction ne signifie pas supprimer arbitrairement la moitié des fichiers. La cible sera mesurée sur plusieurs indicateurs :

- lignes de code exécutables ;
- fonctions et méthodes dupliquées ;
- contrôleurs et routes redondants ;
- fichiers legacy devenus inutiles ;
- dépendances réellement utilisées ;
- branches conditionnelles et chemins morts ;
- volume de code de présentation mélangé au traitement métier.

La logique fonctionnelle à préserver comprend notamment :

- authentification, sessions et réinitialisation de mot de passe ;
- CSRF ;
- permissions par groupe et par route ;
- gestion des étudiants, enseignants, inscriptions et soutenances ;
- évaluations et validations ;
- génération, consultation, téléchargement et archivage des documents ;
- imports, exports, e-mails, sauvegardes et journalisation ;
- compatibilité temporaire avec les URLs et formulaires existants.

Aucune suppression ne doit être faite sans preuve d’utilisation, test de non-régression et possibilité de restauration.

---

## 2. État architectural à prendre en compte

Le projet est un monolithe PHP hybride composé de plusieurs générations de code :

- noyau moderne dans `app/Core/` ;
- contrôleurs dans `app/controllers/` ;
- services dans `app/Services/` ;
- sécurité dans `app/Security/` et `app/middlewares/` ;
- routes réparties dans `ressources/routes/` ;
- vues volumineuses dans `ressources/views/` ;
- point d’orchestration très chargé dans `public/layout.php` ;
- routes et endpoints legacy dans `public/` ;
- plusieurs mécanismes d’autoload et de compatibilité ;
- génération documentaire répartie entre PHPWord, TCPDF, mPDF, Dompdf et des chemins historiques ;
- stockage documentaire réparti entre tables BDD, fichiers et cache ;
- identifiants et URLs encore traités de manière hétérogène.

Le conteneur `app/Core/ServiceContainer.php` constitue déjà un point de centralisation, mais il contient de nombreux enregistrements manuels et plusieurs conventions de nommage différentes.

Le fichier `public/layout.php` initialise beaucoup de composants, charge de nombreuses routes, gère encore des comportements legacy et contient une partie importante de la logique de sécurité et de présentation. C’est une cible prioritaire de découpage, pas une cible de suppression brutale.

---

## 3. Principes non négociables

### 3.1 Ne pas rechercher la réduction par suppression directe

Interdire les opérations suivantes sans preuve :

- supprimer un contrôleur simplement parce qu’il paraît ancien ;
- supprimer une route non appelée dans les vues visibles ;
- supprimer une fonction uniquement parce qu’elle n’est pas trouvée par une recherche simple ;
- remplacer plusieurs services par un gros service universel ;
- fusionner des modules métier différents uniquement pour réduire le nombre de fichiers ;
- supprimer les contrôles d’autorisation, CSRF ou validation ;
- remplacer les documents historiques sans stratégie de compatibilité ;
- supprimer les endpoints legacy avant mesure de leur utilisation.

### 3.2 Réduire la duplication, pas la responsabilité métier

Chaque fonction doit avoir une responsabilité claire. La réduction doit venir prioritairement de :

- helpers communs ;
- services réutilisables ;
- DTO et objets de contexte ;
- centralisation des réponses HTTP ;
- centralisation des validations ;
- centralisation des URLs ;
- génération documentaire commune ;
- suppression du code mort prouvé ;
- remplacement des variantes quasi identiques par une configuration déclarative.

### 3.3 Préserver les frontières de sécurité

La simplification ne doit pas supprimer :

- vérification de session ;
- permissions ;
- vérification de propriété ;
- CSRF ;
- validation des entrées ;
- contrôle MIME et chemins ;
- vérification des tokens ;
- journalisation des opérations sensibles.

### 3.4 Mesurer avant et après

Avant chaque lot, produire une référence :

- nombre de fichiers PHP suivis ;
- lignes PHP hors vendor ;
- nombre de routes ;
- nombre de contrôleurs ;
- nombre de services ;
- nombre de fonctions globales ;
- nombre de duplications détectées ;
- nombre de dépendances Composer utilisées ;
- couverture des scénarios critiques ;
- erreurs HTTP et erreurs PHP de référence.

La cible de 50 % sera déclarée atteinte seulement si le volume baisse sans régression fonctionnelle et si les scénarios critiques restent validés.

---

## 4. Phase 0 — Geler et cartographier l’existant

### 4.1 Créer l’inventaire technique

Établir un inventaire des éléments suivants :

- tous les fichiers PHP hors `vendor/` ;
- tous les points d’entrée publics ;
- toutes les routes et combinaisons `page/action` ;
- tous les contrôleurs et méthodes appelées ;
- tous les services instanciés ;
- toutes les fonctions globales ;
- toutes les inclusions et require ;
- tous les modèles et requêtes SQL ;
- toutes les vues et partials ;
- tous les générateurs de documents ;
- tous les endpoints AJAX ;
- tous les scripts d’administration, audit et diagnostic ;
- tous les fichiers de configuration ;
- toutes les dépendances Composer réellement importées.

### 4.2 Construire la matrice d’utilisation

Pour chaque élément, indiquer :

- chemin ;
- responsabilité ;
- appelants connus ;
- données manipulées ;
- permissions concernées ;
- route associée ;
- statut : actif, legacy, doublon, mort probable ou inconnu ;
- risque de suppression : faible, moyen ou élevé ;
- test nécessaire avant modification.

### 4.3 Identifier les scénarios de référence

Documenter des parcours complets :

- connexion et déconnexion ;
- récupération et changement de mot de passe ;
- consultation du tableau de bord par chaque groupe ;
- création et modification d’un étudiant ;
- dépôt et consultation d’un rapport ;
- validation ou rejet d’un rapport ;
- programmation d’une soutenance ;
- génération d’un planning ;
- génération d’un PV, reçu, bulletin et compte rendu ;
- consultation et téléchargement d’un document ;
- archivage et restauration ;
- import Excel ;
- export Excel/PDF ;
- sauvegarde et restauration ;
- vérification de la piste d’audit.

Ces scénarios deviennent les tests de non-régression obligatoires.

---

## 5. Phase 1 — Réduire le point d’orchestration principal

### 5.1 Découper `public/layout.php`

Ne pas supprimer son rôle immédiatement. Le transformer progressivement en point d’entrée mince qui :

1. charge le bootstrap ;
2. crée le contexte de requête ;
3. délègue l’authentification ;
4. délègue l’autorisation ;
5. délègue le dispatch ;
6. envoie la réponse.

Extraire progressivement :

- initialisation de session ;
- résolution de l’année académique ;
- compatibilité des alias legacy ;
- contrôle CSRF global ;
- contrôle de permission ;
- chargement des routes ;
- choix du layout ;
- construction du menu ;
- gestion des erreurs et réponses AJAX.

Créer des composants ciblés, par exemple :

- `RequestContext` ;
- `AuthenticationGuard` ;
- `AuthorizationGuard` ;
- `CsrfGuard` ;
- `LegacyCompatibilityLayer` ;
- `RouteRegistry` ;
- `ResponseFactory`.

Chaque extraction doit conserver les mêmes variables de session, codes HTTP, redirections et messages.

### 5.2 Centraliser les routes

Remplacer le chargement dispersé et conditionnel par un registre de routes déclaratif, sans supprimer les fichiers immédiatement.

Étapes :

- inventorier les routes existantes ;
- donner un nom unique à chaque route ;
- définir méthode HTTP, permission et contrôleur ;
- charger les modules de routes par domaine ;
- conserver un adaptateur pour les paramètres `page` et `action` ;
- rediriger progressivement les anciennes URLs vers les noms de routes.

La réduction recherchée vient de la suppression des branches répétées, pas de la suppression des fonctionnalités.

---

## 6. Phase 2 — Harmoniser contrôleurs, services et modèles

### 6.1 Contrôleurs

Les contrôleurs doivent uniquement :

- lire et normaliser la requête ;
- appeler un cas d’utilisation ;
- transformer le résultat en réponse ou vue ;
- gérer les codes HTTP.

Déplacer hors des contrôleurs :

- requêtes SQL répétées ;
- logique de permission métier ;
- génération de documents ;
- construction de gros tableaux de présentation ;
- validation métier ;
- logique de stockage de fichiers ;
- envoi d’e-mails ;
- génération de redirections complexes.

Identifier les contrôleurs qui mélangent plusieurs domaines et les scinder par cas d’utilisation, sans changer les routes publiques dans un premier temps.

### 6.2 Services

Regrouper les méthodes identiques ou quasi identiques par responsabilité :

- gestion des étudiants ;
- inscriptions et paiements ;
- rapports et mémoires ;
- soutenances et jurys ;
- évaluations et validations ;
- archives et documents ;
- utilisateurs et permissions ;
- imports et exports.

Éviter le service monolithique. Un service ne doit pas devenir un nouveau fichier `layout.php`.

Créer des méthodes communes pour :

- recherche paginée ;
- validation d’identifiants ;
- chargement d’une entité avec contrôle d’existence ;
- traitement des fichiers ;
- génération de références ;
- journalisation métier ;
- réponse de succès ou d’erreur.

### 6.3 Modèles et accès BDD

Conserver PDO et le schéma actuel dans un premier temps.

Réduire le code en :

- centralisant la création des requêtes préparées répétitives ;
- introduisant des repositories ciblés pour les tables majeures ;
- regroupant les requêtes de lecture par agrégat ;
- supprimant les requêtes SQL dupliquées dans les vues ;
- normalisant les noms de paramètres ;
- évitant les appels BDD successifs pour les mêmes données dans une requête.

Ne pas migrer vers Doctrine, Laravel Eloquent ou un autre ORM uniquement pour réduire le nombre de lignes. Une migration ORM complète introduirait une grande surface de régression et ne constitue pas le premier levier.

---

## 7. Phase 3 — Réduire le code des vues

### 7.1 Séparer préparation et affichage

Les vues doivent recevoir des données préparées. Elles ne doivent pas contenir :

- requêtes SQL ;
- décisions d’autorisation ;
- génération documentaire ;
- logique complexe de récupération de fichiers ;
- plusieurs variantes de la même validation.

### 7.2 Standardiser les composants

Étendre les helpers existants, notamment :

- `ComponentHelper.php` ;
- `FormHelper.php` ;
- `TableHelper.php` ;
- `PaginationHelper.php` ;
- `FormattingUtils.php` ;
- `NavigationHelper.php`.

Créer des composants déclaratifs réutilisables pour :

- tableaux paginés ;
- filtres ;
- boutons d’action ;
- formulaires CSRF ;
- messages flash ;
- cartes statistiques ;
- actions de documents ;
- modales de confirmation ;
- états vides et erreurs.

Remplacer les blocs HTML copiés-collés par des composants paramétrés, sans changer le HTML rendu tant que la comparaison visuelle n’est pas validée.

### 7.3 Unifier JavaScript inline

Recenser les scripts inline répétés dans les vues et déplacer les comportements communs vers des modules JS centralisés.

Préserver :

- noms des événements ;
- contrats AJAX ;
- paramètres envoyés ;
- réponses attendues ;
- protection CSRF ;
- comportement mobile et accessibilité.

---

## 8. Phase 4 — Unifier la génération et le stockage documentaire

### 8.1 Créer un contrat commun

Définir une abstraction unique pour les générateurs :

- type de document ;
- données d’entrée ;
- format demandé ;
- utilisateur demandeur ;
- version du modèle ;
- résultat généré ;
- référence publique ;
- statut et journalisation.

Les générateurs existants doivent être adaptés derrière cette abstraction avant toute suppression.

### 8.2 Réduire les générateurs dupliqués

Comparer :

- `PlanningGeneratorService.php` ;
- `PvFinalGeneratorService.php` ;
- `PvCommissionGeneratorService.php` ;
- `RecuGeneratorService.php` ;
- `RapportPdfGeneratorService.php` ;
- `PdfGeneratorService.php`.

Extraire les fonctions communes :

- préparation des données étudiant ;
- en-têtes et logos ;
- métadonnées ;
- nommage ;
- stockage par type et année ;
- gestion des erreurs ;
- envoi HTTP ;
- archivage ;
- journalisation.

Conserver une stratégie par type pour les différences de mise en page.

### 8.3 PHPWord progressivement

Utiliser PHPWord pour les documents structurés et répétitifs, sans supprimer TCPDF immédiatement.

Pour chaque type :

1. conserver le générateur actuel comme référence ;
2. créer le modèle Word ;
3. générer le nouveau document ;
4. comparer visuellement et fonctionnellement ;
5. enregistrer via `DocumentStorageService` ;
6. activer le nouveau générateur derrière une configuration ;
7. mesurer les erreurs et performances ;
8. retirer l’ancien chemin uniquement après validation.

### 8.4 Unifier le stockage

Faire de `DocumentStorageService` et `DocumentRegistry` les points d’accès uniques.

Interdire progressivement aux contrôleurs et vues de :

- construire directement un chemin physique ;
- diffuser un document sans contrôle ;
- accéder directement aux colonnes historiques sans adaptateur ;
- reconstruire eux-mêmes un nom de fichier.

Cette centralisation réduira beaucoup de code tout en améliorant la sécurité.

---

## 9. Phase 5 — Centraliser les URLs et les identifiants publics

### 9.1 Créer un générateur d’URLs

Toutes les vues, e-mails et redirections doivent utiliser un générateur commun.

Le générateur doit gérer :

- routes nommées ;
- paramètres encodés ;
- base path ;
- URLs legacy ;
- URLs de documents ;
- actions de téléchargement ;
- redirections après erreur.

### 9.2 Références publiques

Pour les documents et ressources sensibles, utiliser une référence publique non séquentielle ou un token signé.

Règles :

- ne pas confondre encodage base64 et chiffrement ;
- ne pas exposer les chemins physiques ;
- vérifier le type, le statut, la permission et la propriété ;
- retourner 404 ou 403 selon une politique cohérente ;
- ne pas révéler si un autre identifiant existe ;
- maintenir les anciennes URLs via un adaptateur temporaire.

### 9.3 Réduction des branches URL

Remplacer les dizaines de constructions manuelles `?page=...&action=...` par des routes configurées.

Avant suppression, journaliser les accès aux anciens formats et conserver un délai de compatibilité défini.

---

## 10. Phase 6 — Nettoyage des dépendances et du code mort

### 10.1 Liste fournie par l’audit

La liste contient 478 entrées compatibles sous PHP 8.4.24 et Apache 2.4.68, mais elle ne représente pas nécessairement les dépendances installées du projet.

Elle contient également :

- doublons de paquets ;
- paquets classés dans plusieurs catégories ;
- placeholders tels que `chrisbalti?` et `srag?` ;
- bibliothèques Laravel, Symfony, CakePHP, Slim et autres frameworks qui ne doivent pas être ajoutées sans besoin ;
- dépendances nécessitant des extensions absentes ou des binaires externes ;
- paquets legacy ou abandonnés.

Ne pas installer cette liste en bloc.

### 10.2 Politique de dépendances

Pour chaque dépendance actuelle :

- vérifier son utilisation réelle dans le code ;
- vérifier si elle est directe ou transitive ;
- vérifier si elle peut être supprimée sans changer un format ;
- vérifier la version réellement installée dans `composer.lock` ;
- vérifier la compatibilité avec PHP 8.4 ;
- documenter les extensions ou binaires nécessaires.

Dépendances à conserver prioritairement selon l’architecture actuelle :

- PHPWord ;
- mPDF, Dompdf ou TCPDF selon le générateur réellement retenu ;
- PhpSpreadsheet ;
- PHPMailer ;
- HTMLPurifier ;
- PDFParser ;
- PSR Log si utilisé par les composants actifs.

Dépendances à envisager seulement si un besoin est démontré :

- `symfony/uid` ou `ramsey/uuid` pour les références publiques ;
- `monolog/monolog` pour une journalisation unifiée ;
- `phpunit/phpunit` pour les tests ;
- `symfony/rate-limiter` ou un mécanisme local pour les endpoints sensibles.

### 10.3 Détection du code mort

Un élément sera déclaré supprimable seulement si :

- aucun appel statique ou dynamique connu n’existe ;
- aucune route ne l’expose ;
- aucun formulaire, JavaScript, cron ou e-mail ne l’utilise ;
- aucune compatibilité historique ne le nécessite ;
- les tests de non-régression passent sans lui ;
- une restauration simple est possible.

---

## 11. Phase 7 — Standardiser les erreurs, logs et réponses

### 11.1 Réponses HTTP

Créer une fabrique commune pour :

- réponse HTML ;
- réponse JSON ;
- redirection ;
- 400 ;
- 401 ;
- 403 ;
- 404 ;
- 422 ;
- 429 ;
- 500.

Cela réduira les constructions répétées de `header()`, `http_response_code()` et `json_encode()`.

### 11.2 Exceptions

Définir quelques exceptions métier et techniques communes au lieu de messages dispersés.

Ne pas exposer les détails SQL, chemins physiques ou exceptions internes en production.

### 11.3 Journalisation

Centraliser les logs dans un service unique avec contexte :

- utilisateur ;
- route ;
- action ;
- entité ;
- résultat ;
- adresse IP selon la politique applicable ;
- identifiant de corrélation.

Supprimer les logs de debug spécifiques seulement après avoir vérifié qu’ils ne sont plus nécessaires et qu’un remplacement centralisé existe.

---

## 12. Phase 8 — Tests et protection contre les régressions

### 12.1 Tests minimums

Mettre en place progressivement :

- tests unitaires des helpers de sécurité ;
- tests des tokens et URLs ;
- tests du stockage documentaire ;
- tests des permissions ;
- tests du routeur et des alias legacy ;
- tests des générateurs de documents ;
- tests des réponses HTTP ;
- tests d’intégration des scénarios métier critiques.

### 12.2 Tests de comparaison

Pour les vues et documents :

- comparer les codes HTTP ;
- comparer les redirections ;
- comparer les droits ;
- comparer les données affichées ;
- comparer les fichiers générés ;
- comparer les métadonnées ;
- comparer les traces d’audit ;
- comparer les temps et la mémoire lorsque nécessaire.

### 12.3 Déploiement par lots

Chaque lot doit être :

1. isolé ;
2. mesuré ;
3. testé ;
4. déployable séparément ;
5. réversible ;
6. documenté.

Interdire les gros commits mélangeant nettoyage, refonte métier, migration de BDD et changement d’URL.

---

## 13. Stratégie de réduction mesurable vers 50 %

### Lot A — Code mort prouvé

Objectif : réduction initiale à faible risque.

- supprimer fichiers de diagnostic publics inutiles après sécurisation ;
- supprimer fonctions réellement non appelées ;
- supprimer routes obsolètes après mesure ;
- supprimer doublons manifestes ;
- supprimer anciens adaptateurs déjà inutilisés.

### Lot B — Helpers et réponses communes

- centraliser réponses HTTP ;
- centraliser validations ;
- centraliser redirections ;
- centraliser affichage des erreurs ;
- centraliser pagination et tableaux ;
- centraliser génération des liens.

### Lot C — Services documentaires

- unifier stockage, références, MIME et diffusion ;
- extraire la génération commune ;
- réduire les variantes de nommage ;
- migrer progressivement les documents vers PHPWord.

### Lot D — Contrôleurs et routes

- alléger les contrôleurs ;
- centraliser les routes ;
- transformer les branches `page/action` en configuration ;
- conserver les alias legacy dans une couche dédiée.

### Lot E — Vues et JavaScript

- remplacer les blocs répétés par composants ;
- déplacer les scripts communs ;
- séparer les requêtes et la logique métier des vues.

### Lot F — Dépendances et compatibilité

- supprimer uniquement les dépendances inutilisées ;
- éviter les frameworks concurrents ;
- uniformiser l’autoload ;
- réduire les fichiers de compatibilité après mesure.

La réduction globale doit être calculée après chaque lot. Une cible réaliste peut être :

- 15 à 20 % par suppression du code mort et des doublons prouvés ;
- 10 à 15 % par centralisation des helpers et réponses ;
- 10 à 15 % par simplification des contrôleurs, routes et documents ;
- 5 à 10 % par nettoyage des vues, JavaScript et dépendances.

Ces pourcentages sont des objectifs de mesure, pas une autorisation de supprimer du code sans preuve.

---

## 14. Architecture cible recommandée

```text
public/
  index.php                 Point d'entrée mince
  assets/                   Ressources publiques

app/
  Core/                     Bootstrap, requête, réponse, conteneur
  Domain/                   Règles métier et contrats
  Application/              Cas d'utilisation
  Infrastructure/           PDO, fichiers, documents, e-mails, logs
  Presentation/             Contrôleurs, routeurs, réponses, vues
  Security/                 Authentification, autorisation, CSRF, tokens
  Shared/                   DTO, exceptions, validation, helpers

ressources/
  routes/                   Déclarations par domaine
  views/                    Vues et composants
  templates/documents/      Modèles PHPWord non publics

storage/
  documents/                Documents privés
  cache/                    Cache temporaire
  logs/                     Logs protégés

tests/
  Unit/
  Integration/
  Fixtures/
```

Cette architecture doit être atteinte progressivement par déplacement et adaptateurs, pas par réécriture complète.

---

## 15. Séquence de mise en œuvre sans casse

1. Mesurer l’état initial et créer une sauvegarde complète.
2. Protéger ou retirer `server_audit.php` de l’accès public.
3. Ajouter les tests des scénarios critiques existants.
4. Centraliser les réponses HTTP, erreurs et redirections.
5. Extraire les guards de `public/layout.php` sans modifier les règles métier.
6. Centraliser le registre de routes et conserver les alias legacy.
7. Introduire les contrats documentaires et unifier le stockage.
8. Migrer un seul type de document vers PHPWord comme pilote.
9. Répéter la migration document par document.
10. Remplacer progressivement les URLs par des routes nommées et références publiques.
11. Réduire les contrôleurs et vues par lots.
12. Nettoyer les dépendances après analyse du lockfile et des imports.
13. Supprimer le code mort prouvé uniquement après observation des logs et tests.
14. Mesurer la réduction finale et publier une matrice de compatibilité.

---

## 16. Critères d’acceptation finale

Le chantier sera considéré comme réussi si :

- le volume de code mesuré a baissé d’environ 50 % selon la métrique choisie ;
- les principales fonctionnalités restent disponibles ;
- les permissions sont inchangées ou renforcées ;
- les tests critiques passent ;
- les documents sont toujours générés, consultables et téléchargeables ;
- les anciennes URLs disposent d’une stratégie de compatibilité ;
- aucune donnée sensible n’est exposée dans les URLs ;
- les erreurs de production ne révèlent pas d’informations internes ;
- les dépendances ajoutées sont réellement utilisées ;
- les fichiers publics de diagnostic sont supprimés ou protégés ;
- chaque suppression est traçable et réversible ;
- la documentation de l’architecture cible est à jour.

---

## 17. Livrables à produire avant le code

- inventaire complet des fichiers et routes ;
- matrice des fonctionnalités et dépendances ;
- métriques initiales ;
- matrice des scénarios critiques ;
- carte des duplications ;
- matrice des documents et générateurs ;
- matrice des URLs anciennes et cibles ;
- plan de migration par lots ;
- stratégie de rollback ;
- conventions de code et de nommage ;
- décision sur les bibliothèques réellement retenues ;
- critères de validation de la réduction de 50 %.

## Conclusion

La stratégie recommandée est une simplification progressive du monolithe existant, fondée sur la centralisation et les adaptateurs. Il ne faut pas introduire Laravel, Symfony, Doctrine ou plusieurs nouvelles bibliothèques simplement parce qu’elles apparaissent compatibles dans l’audit. La réduction de 50 % doit venir de la suppression du code mort prouvé, de la réduction des duplications et de l’extraction des responsabilités, tout en conservant les routes legacy, les permissions et les générateurs existants jusqu’à validation complète.
