## Points d’entrée HTTP (cible 2026)

Objectif: **une seule entrée applicative** via `public/index.php` (Router), le reste restant **compatible** pendant la migration.

### Public (landing)
- `index.php` (racine): landing UFHB
  - bouton “Plateforme” → `public/indexCM.php`
  - bouton “Connexion” → `public/index.php?_path=/login` (cible)

### Application (cible)
- `public/index.php`: **front controller** (toutes les routes applicatives)
  - `/login` (GET/POST)
  - `/logout` (POST)
  - `/dashboard` (GET)
  - `/app` (GET) : passerelle temporaire vers le legacy

### Legacy (à déprécier progressivement)
- `public/layout.php`: ancienne entrée “app”
  - restera accessible via `/app` le temps de migrer les écrans
- `public/login.php` / `public/logout.php`: handlers legacy
  - doivent déléguer vers `public/index.php` (routes `/login` et `/logout`)
- `public/reset_password.php`: page legacy de reset
  - à migrer vers une route dédiée (ex: `/reset-password`) puis redirections

