# ❓ FAQ - Questions Fréquentes

Réponses aux questions les plus courantes sur l'utilisation et la configuration de Check Master UFHB.

## Table des Matières

- [Installation et Configuration](#installation-et-configuration)
- [Base de Données](#base-de-données)
- [Authentification et Accès](#authentification-et-accès)
- [Candidatures et Soutenances](#candidatures-et-soutenances)
- [Génération de Documents](#génération-de-documents)
- [Email et Notifications](#email-et-notifications)
- [Docker](#docker)
- [Erreurs Courantes](#erreurs-courantes)

---

## 📦 Installation et Configuration

### Q : Quels sont les prérequis pour installer l'application ?

**R :** Vous avez besoin de :
- Docker (version 20.10+) et Docker Compose (version 2.0+)
- Git
- Node.js (version 18+) pour Tailwind CSS
- Au moins 4 GB de RAM disponible
- 10 GB d'espace disque libre

### Q : Comment installer l'application sans Docker ?

**R :** Installation manuelle :

1. **Installer PHP 8.2 avec extensions** :
   ```bash
   sudo apt install php8.2 php8.2-mysql php8.2-gd php8.2-xml php8.2-mbstring
   ```

2. **Installer MySQL 8.3** :
   ```bash
   sudo apt install mysql-server
   ```

3. **Installer Apache** :
   ```bash
   sudo apt install apache2
   ```

4. **Configurer la base de données** :
   - Modifier `app/config/database.php` : host='localhost'
   - Importer `soutenance_manager.sql`

5. **Installer les dépendances** :
   ```bash
   composer install
   npm install
   ```

### Q : Comment changer le port de l'application ?

**R :** Modifiez `docker-compose.yml` :

```yaml
services:
  web:
    ports:
      - "NOUVEAU_PORT:80"  # Ex: "9090:80"
```

Puis redémarrez : `docker-compose down && docker-compose up -d`

### Q : Les changements CSS ne s'appliquent pas, pourquoi ?

**R :** Tailwind CSS doit être recompilé :

```bash
# Mode watch (développement)
npm run tailwind:dev

# OU compilation unique
npx tailwindcss -i ./src/input.css -o ./public/css/output.css
```

Videz aussi le cache de votre navigateur (Ctrl+F5).

---

## 🗄️ Base de Données

### Q : Comment réinitialiser la base de données ?

**R :** Avec Docker :

```bash
# Arrêter les conteneurs
docker-compose down

# Supprimer le volume de la base de données
docker volume rm check-master-ufhb_db_data

# Redémarrer et réimporter
docker-compose up -d
docker-compose exec db mysql -uroot -ppassword soutenance_manager < soutenance_manager.sql
```

### Q : Comment accéder à la base de données ?

**R :** Plusieurs options :

1. **Via phpMyAdmin** : http://localhost:8081
   - User: `root`
   - Password: `password`

2. **Via ligne de commande** :
   ```bash
   docker-compose exec db mysql -uroot -ppassword soutenance_manager
   ```

3. **Via un client externe** (MySQL Workbench, DBeaver, etc.) :
   - Host: `localhost`
   - Port: `3306`
   - User: `root`
   - Password: `password`
   - Database: `soutenance_manager`

### Q : Comment faire une sauvegarde de la base de données ?

**R :** Sauvegarde complète :

```bash
docker-compose exec db mysqldump -uroot -ppassword soutenance_manager > backup_$(date +%Y%m%d_%H%M%S).sql
```

Sauvegarde automatique quotidienne (cron) :

```bash
# Ajouter dans crontab (crontab -e)
0 2 * * * cd /path/to/project && docker-compose exec -T db mysqldump -uroot -ppassword soutenance_manager > backups/backup_$(date +\%Y\%m\%d).sql
```

### Q : La base de données est trop lente, que faire ?

**R :** Optimisations possibles :

1. **Ajouter des index** sur les colonnes fréquemment utilisées
2. **Augmenter la mémoire MySQL** dans `docker-compose.yml` :
   ```yaml
   db:
     environment:
       MYSQL_INNODB_BUFFER_POOL_SIZE: 256M
   ```
3. **Nettoyer les anciennes données** archivées
4. **Analyser les requêtes lentes** avec `EXPLAIN`

---

## 🔐 Authentification et Accès

### Q : J'ai oublié mon mot de passe, comment le réinitialiser ?

**R :** Méthode 1 - Via l'interface :
1. Cliquez sur "Mot de passe oublié" sur la page de connexion
2. Entrez votre email
3. Suivez le lien reçu par email

Méthode 2 - Via la base de données (pour admin uniquement) :

```sql
-- Générer un hash pour le nouveau mot de passe
-- En PHP : password_hash('nouveau_mdp', PASSWORD_DEFAULT)

-- Mettre à jour dans la base
UPDATE utilisateur 
SET mdp = '$2y$10$...' -- Votre hash généré
WHERE email = 'votre.email@example.com';
```

### Q : Comment créer le premier compte administrateur ?

**R :** Via SQL :

```sql
-- Insérer un utilisateur admin
INSERT INTO utilisateur (nom, prenoms, email, mdp, id_GU) 
VALUES (
  'Admin', 
  'Système', 
  'admin@ufhb.edu.ci', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
  1 -- ID du groupe admin
);
```

Ou créer un script `create_admin.php` :

```php
<?php
require 'app/config/database.php';

$pdo = Database::getConnection();
$email = 'admin@ufhb.edu.ci';
$password = password_hash('VotreMotDePasse', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO utilisateur (nom, prenoms, email, mdp, id_GU) VALUES (?, ?, ?, ?, ?)");
$stmt->execute(['Admin', 'Système', $email, $password, 1]);

echo "Compte admin créé avec succès !";
```

### Q : Comment gérer les permissions d'un utilisateur ?

**R :** Via l'interface admin :
1. Menu **"Gestion des Utilisateurs"**
2. Sélectionnez l'utilisateur
3. Cliquez sur **"Modifier les droits"**
4. Choisissez le groupe d'utilisateur approprié
5. Définissez les actions autorisées par module

Les groupes principaux :
- **Administrateur** : Tous les droits
- **Scolarité** : Gestion des candidatures, notes
- **Enseignant** : Évaluation, consultation
- **Étudiant** : Soumission de candidature, consultation dossier

### Q : La session expire trop rapidement, comment l'allonger ?

**R :** Modifiez `php.ini` :

```ini
session.gc_maxlifetime = 7200  # 2 heures en secondes
session.cookie_lifetime = 0     # Jusqu'à fermeture navigateur
```

Ou dans votre code PHP (au début de session) :

```php
ini_set('session.gc_maxlifetime', 7200);
session_set_cookie_params(7200);
session_start();
```

---

## 📝 Candidatures et Soutenances

### Q : Quels formats de fichiers sont acceptés pour le mémoire ?

**R :** Actuellement, seul le **format PDF** est accepté pour :
- Le mémoire
- Le rapport de stage
- L'attestation d'entreprise

Taille maximale : **100 MB** par fichier (configurable dans `php.ini`).

### Q : Comment modifier une candidature après soumission ?

**R :** Deux cas :

1. **Statut "En attente"** : L'étudiant peut modifier via "Mes Candidatures" → "Modifier"

2. **Statut "Validé" ou supérieur** : 
   - Seul un administrateur peut modifier
   - Ou l'étudiant doit soumettre une demande de modification via "Réclamations"

### Q : Comment annuler une soutenance programmée ?

**R :** Via l'interface admin uniquement :
1. Menu **"Gestion des Soutenances"**
2. Sélectionnez la soutenance
3. Cliquez sur **"Annuler"**
4. Indiquez le motif d'annulation
5. Les notifications sont envoyées automatiquement au jury et à l'étudiant

### Q : Puis-je programmer plusieurs soutenances en même temps ?

**R :** Oui, mais :
- Vérifiez que les **salles sont différentes**
- Vérifiez qu'**aucun membre du jury n'est en conflit**
- Le système affiche un avertissement en cas de conflit

### Q : Comment sont calculées les notes finales ?

**R :** Formule de calcul :

```
Note Finale = (Moyenne M1 × 2 + Moyenne S1 M2 × 3 + Note Mémoire × 3) / 8
```

Où :
- **Moyenne M1** : Moyenne générale Master 1
- **Moyenne S1 M2** : Moyenne générale Semestre 1 Master 2
- **Note Mémoire** : Moyenne des évaluations des membres du jury

Ces calculs sont automatiques et visibles dans l'Annexe 2 du PV.

---

## 📄 Génération de Documents

### Q : Les documents PDF ne se génèrent pas, que faire ?

**R :** Vérifiez :

1. **Extensions PHP installées** :
   ```bash
   docker-compose exec web php -m | grep -E "(gd|mbstring|xml)"
   ```

2. **Permissions d'écriture** :
   ```bash
   chmod -R 777 public/documents/generated
   ```

3. **Logs d'erreur** :
   ```bash
   docker-compose logs web
   ```

4. **Version de la librairie** :
   - Vérifiez que `dompdf` ou `mpdf` est bien installé : `composer show`

### Q : Les caractères accentués n'apparaissent pas dans les PDF

**R :** Problème d'encodage. Solution :

1. **Vérifiez l'encodage des templates** : doivent être en UTF-8

2. **Spécifiez l'encodage dans le PDF** :
   ```php
   $dompdf->loadHtml(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
   ```

3. **Utilisez des polices supportant les caractères accentués** :
   ```php
   // Dans la configuration dompdf
   $dompdf->set_option('defaultFont', 'DejaVu Sans');
   ```

### Q : Comment personnaliser le modèle de PV ?

**R :** Les templates se trouvent dans `ressources/views/documents/` :

1. Modifiez le fichier HTML du template
2. Conservez les variables dynamiques `<?php echo $variable; ?>`
3. Testez en générant un nouveau document
4. Pour les styles, utilisez du CSS inline dans le template

### Q : Peut-on générer plusieurs documents en lot (batch) ?

**R :** Oui, via l'interface admin :
1. Menu **"Génération en Lot"**
2. Sélectionnez le type de document
3. Filtrez par année académique / statut
4. Cliquez sur **"Générer pour toutes les soutenances sélectionnées"**
5. Les documents sont générés et archivés automatiquement

---

## 📧 Email et Notifications

### Q : Les emails ne sont pas envoyés, pourquoi ?

**R :** Causes courantes :

1. **Configuration SMTP incorrecte** :
   - Vérifiez `app/config/email.php`
   - Testez avec `test_email.php`

2. **Authentification Gmail échouée** :
   - Utilisez un "mot de passe d'application"
   - Activez l'authentification à deux facteurs

3. **Port bloqué par le pare-feu** :
   ```bash
   # Tester la connexion au serveur SMTP
   telnet smtp.gmail.com 587
   ```

4. **Limite d'envoi atteinte** :
   - Gmail : 500 emails/jour max
   - Utilisez un service SMTP professionnel (SendGrid, Mailgun)

### Q : Comment désactiver temporairement les emails ?

**R :** Méthode 1 - Désactivation globale :

```php
// Dans app/config/email.php
public static $email_enabled = false;
```

Méthode 2 - Mode simulation :

```php
// Les emails sont logués mais pas envoyés
public static $debug_mode = true;
public static $send_emails = false;
```

### Q : Les emails arrivent en spam, comment corriger ?

**R :** Solutions :

1. **Configurer SPF, DKIM et DMARC** pour votre domaine
2. **Utiliser un domaine d'envoi authentifié** (pas de Gmail)
3. **Éviter les mots "spam"** dans le sujet
4. **Utiliser un service SMTP professionnel**
5. **Ajouter un lien de désinscription** (opt-out)

### Q : Comment personnaliser les templates d'emails ?

**R :** Les templates sont dans `ressources/views/emails/` :

```php
// Exemple de structure
emails/
├── nouvelle_candidature.php
├── validation_candidature.php
├── notification_jury.php
└── rappel_soutenance.php
```

Modifiez le contenu HTML en conservant les variables dynamiques.

---

## 🐳 Docker

### Q : Les conteneurs ne démarrent pas

**R :** Diagnostics :

```bash
# Voir les logs d'erreur
docker-compose logs

# Vérifier si les ports sont déjà utilisés
netstat -tuln | grep -E "8080|3306|8081"

# Arrêter et supprimer les conteneurs
docker-compose down --remove-orphans

# Reconstruire sans cache
docker-compose build --no-cache

# Redémarrer
docker-compose up -d
```

### Q : Comment libérer de l'espace disque Docker ?

**R :** Commandes de nettoyage :

```bash
# Supprimer les conteneurs arrêtés
docker container prune

# Supprimer les images non utilisées
docker image prune -a

# Supprimer les volumes non utilisés (⚠️ Attention aux données)
docker volume prune

# Nettoyage complet (⚠️ Attention)
docker system prune -a --volumes
```

### Q : Comment mettre à jour les images Docker ?

**R :** Mise à jour :

```bash
# Arrêter les conteneurs
docker-compose down

# Télécharger les dernières images
docker-compose pull

# Reconstruire les images personnalisées
docker-compose build

# Redémarrer
docker-compose up -d
```

### Q : Les modifications de code ne sont pas prises en compte

**R :** Vérifications :

1. **Le volume est-il bien monté ?**
   ```bash
   docker-compose ps
   # Vérifier la colonne "Volumes"
   ```

2. **Redémarrer Apache** dans le conteneur :
   ```bash
   docker-compose exec web service apache2 restart
   ```

3. **Vider le cache d'opcache** :
   ```bash
   docker-compose exec web php -r "opcache_reset();"
   ```

### Q : Comment accéder aux logs de l'application ?

**R :** Plusieurs niveaux de logs :

```bash
# Logs Docker Compose
docker-compose logs -f

# Logs Apache (erreurs)
docker-compose exec web tail -f /var/log/apache2/error.log

# Logs Apache (accès)
docker-compose exec web tail -f /var/log/apache2/access.log

# Logs PHP
docker-compose exec web tail -f /var/log/php_errors.log

# Logs MySQL
docker-compose exec db tail -f /var/log/mysql/error.log
```

---

## ⚠️ Erreurs Courantes

### Erreur : "PDOException: SQLSTATE[HY000] [2002] Connection refused"

**Cause** : La base de données n'est pas accessible.

**Solutions** :
1. Vérifier que le conteneur MySQL est démarré : `docker-compose ps`
2. Attendre que MySQL soit complètement démarré : `docker-compose logs db`
3. Vérifier le nom du host dans `database.php` (doit être `db` avec Docker)

### Erreur : "SMTP Error: Could not authenticate"

**Cause** : Échec d'authentification SMTP.

**Solutions** :
1. Vérifier username et password dans `email.php`
2. Pour Gmail : utiliser un mot de passe d'application
3. Vérifier que le port et la sécurité (TLS/SSL) sont corrects

### Erreur : "Fatal error: Maximum execution time exceeded"

**Cause** : Le script PHP dépasse le temps d'exécution maximum.

**Solutions** :
1. Augmenter `max_execution_time` dans `php.ini` :
   ```ini
   max_execution_time = 300
   ```
2. Redémarrer Apache : `docker-compose restart web`
3. Optimiser le code pour réduire le temps d'exécution

### Erreur : "Call to undefined function imagecreate()"

**Cause** : Extension GD non installée.

**Solution** :
```bash
docker-compose exec web apt-get update && apt-get install -y php8.2-gd
docker-compose restart web
```

### Erreur : "File upload size exceeds post_max_size"

**Cause** : Fichier trop volumineux pour les limites PHP.

**Solution** : Modifier `php.ini` :
```ini
upload_max_filesize = 100M
post_max_size = 100M
```

Puis redémarrer : `docker-compose restart web`

### Erreur : "Access denied for user 'root'@'localhost'"

**Cause** : Identifiants de connexion MySQL incorrects.

**Solutions** :
1. Vérifier les identifiants dans `app/config/database.php`
2. Vérifier que les identifiants correspondent à ceux de `docker-compose.yml`
3. Réinitialiser le mot de passe MySQL si nécessaire

---

## 🆘 Obtenir de l'Aide

Si votre problème n'est pas listé ici :

1. **Consultez la [documentation complète](./)**
2. **Recherchez dans les [issues GitHub](https://github.com/Emeric-Soro/Check-Master-UFHB/issues)**
3. **Ouvrez une nouvelle issue** avec :
   - Description détaillée du problème
   - Étapes pour reproduire
   - Messages d'erreur complets
   - Version de Docker, OS utilisé
   - Logs pertinents
4. **Contactez l'équipe de support**

---

**Dernière mise à jour** : Octobre 2025
