## Base de données de référence (dev/test)

### Problème constaté
Le projet contient plusieurs dumps SQL. Le fichier `ufrmi1802974_2q2mpf.sql` ne contient pas toujours les tables liées aux menus/habilitations (RBAC), alors que le code en dépend.

### Référence retenue
- **Dump de référence**: `ufrmi1802974_2q2mpf (1).sql`
- Raison: il contient les tables:
  - `categories_fonctionnalites` (menus)
  - `fonctionnalites` (écrans + sous-écrans)
  - `permissions` (droits CRUD par groupe)

### Recommandation
- Utiliser ce dump comme base pour l’environnement de développement et de test.
- Éviter de maintenir plusieurs dumps divergents: à terme, générer un **seul dump** cohérent à partir de la DB “source”.

