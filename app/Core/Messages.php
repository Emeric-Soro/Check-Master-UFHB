<?php

declare(strict_types=1);

namespace CheckMaster\Core;

/**
 * Messages centralisés de l'application.
 * Remplace tous les strings hardcodés "Erreur...", "Succès...", "Veuillez...".
 * Supporte l'interpolation : Messages::get('auth.failed', ['attempts' => 3])
 */
final class Messages
{
    private static ?array $messages = null;

    /**
     * Récupérer un message par sa clé.
     *
     * @param string $key Notation pointée (ex: 'auth.session_expired')
     * @param array $params Variables d'interpolation {key} => value
     * @return string
     */
    public static function get(string $key, array $params = []): string
    {
        $all = self::all();
        $parts = explode('.', $key);
        $value = $all;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $key; // Fallback : retourner la clé elle-même
            }
            $value = $value[$part];
        }

        if (!is_string($value)) {
            return $key;
        }

        // Interpolation : {name} => value
        foreach ($params as $k => $v) {
            $value = str_replace('{' . $k . '}', (string) $v, $value);
        }

        return $value;
    }

    /**
     * Alias pour compatibilité avec les flash messages.
     */
    public static function flash(string $key, array $params = []): string
    {
        return self::get($key, $params);
    }

    /**
     * Tous les messages.
     */
    public static function all(): array
    {
        if (self::$messages !== null) {
            return self::$messages;
        }

        self::$messages = [
            // ─── Authentification ─────────────────────────
            'auth' => [
                'session_expired'      => 'Session expirée. Veuillez réessayer.',
                'login_failed'         => 'Login ou mot de passe incorrect.',
                'logout_error'         => 'Erreur lors de la déconnexion.',
                'unauthorized'         => 'Vous n\'êtes pas autorisé à accéder à cette ressource.',
                'rate_limited'         => 'Trop de tentatives. Veuillez patienter avant de réessayer.',
                'password_min_length'  => 'Le mot de passe doit contenir au moins {min} caractères.',
                'password_uppercase'   => 'Le mot de passe doit contenir au moins une majuscule.',
                'password_digit'       => 'Le mot de passe doit contenir au moins un chiffre.',
                'password_special'     => 'Le mot de passe doit contenir au moins un caractère spécial.',
                'password_mismatch'    => 'Les mots de passe ne correspondent pas.',
                'password_reset_sent'  => 'Si un compte existe pour cet email, un lien de réinitialisation a été envoyé.',
                'password_reset_ok'    => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.',
                'password_reset_invalid' => 'Lien invalide ou expiré.',
                'user_not_found'       => 'Utilisateur introuvable.',
            ],

            // ─── Erreurs générales ────────────────────────
            'error' => [
                'generic'              => 'Une erreur est survenue. Veuillez réessayer.',
                'database'             => 'Erreur de connexion à la base de données.',
                'not_found'            => 'Élément introuvable.',
                'invalid_input'        => 'Données invalides. Veuillez vérifier votre saisie.',
                'file_not_found'       => 'Fichier introuvable.',
                'file_read'            => 'Impossible de lire le fichier.',
                'file_write'           => 'Impossible d\'écrire le fichier.',
                'file_open'            => 'Impossible d\'ouvrir le fichier.',
                'import_failed'        => 'L\'import a échoué.',
                'export_failed'        => 'L\'export a échoué.',
                'pdf_generation'       => 'Erreur lors de la génération du PDF.',
                'email_send'           => 'Erreur lors de l\'envoi de l\'email.',
                'permission_denied'    => 'Vous n\'avez pas la permission d\'effectuer cette action.',
                'csrf_invalid'         => 'Jeton de sécurité invalide. Veuillez recharger la page.',
                'selection_required'   => 'Veuillez sélectionner un élément.',
                'file_read_csv'        => 'Impossible de lire le fichier CSV.',
                'xlsx_unavailable'     => 'Export XLSX indisponible (PhpSpreadsheet non chargé).',
                'export_payload'       => 'Payload export invalide.',
                'phpspreadsheet'       => 'PhpSpreadsheet indisponible.',
            ],

            // ─── Succès ───────────────────────────────────
            'success' => [
                'created'   => 'Élément créé avec succès.',
                'updated'   => 'Élément mis à jour avec succès.',
                'deleted'   => 'Élément supprimé avec succès.',
                'saved'     => 'Enregistré avec succès.',
                'sent'      => 'Envoyé avec succès.',
                'imported'  => 'Importé avec succès.',
                'exported'  => 'Exporté avec succès.',
                'operation' => 'Opération effectuée avec succès.',
            ],

            // ─── Validation ───────────────────────────────
            'validation' => [
                'required'        => 'Ce champ est obligatoire.',
                'email_invalid'   => 'Adresse email invalide.',
                'min_length'      => 'Ce champ doit contenir au moins {min} caractères.',
                'max_length'      => 'Ce champ ne doit pas dépasser {max} caractères.',
                'numeric'         => 'Ce champ doit être un nombre.',
                'date_invalid'    => 'Date invalide.',
                'file_too_large'  => 'Le fichier est trop volumineux.',
                'file_type'       => 'Type de fichier non autorisé.',
            ],

            // ─── Actions boutons ──────────────────────────
            'button' => [
                'save'      => 'Enregistrer',
                'cancel'    => 'Annuler',
                'delete'    => 'Supprimer',
                'edit'      => 'Modifier',
                'create'    => 'Créer',
                'search'    => 'Rechercher',
                'validate'  => 'Valider',
                'confirm'   => 'Confirmer',
                'reject'    => 'Rejeter',
                'approve'   => 'Approuver',
                'submit'    => 'Soumettre',
                'export'    => 'Exporter',
                'import'    => 'Importer',
                'back'      => 'Retour',
                'close'     => 'Fermer',
                'reset'     => 'Réinitialiser',
                'filter'    => 'Filtrer',
            ],

            // ─── Entités ──────────────────────────────────
            'entity' => [
                'student'    => 'étudiant',
                'teacher'    => 'enseignant',
                'user'       => 'utilisateur',
                'report'     => 'rapport',
                'defense'    => 'soutenance',
                'inscription' => 'inscription',
                'claim'      => 'réclamation',
                'note'       => 'note',
                'room'       => 'salle',
                'jury'       => 'jury',
                'commission' => 'commission',
            ],

            // ─── Opérations métier ────────────────────────
            'business' => [
                'defense_programmed'   => 'Soutenance programmée avec succès.',
                'defense_evaluated'    => 'Évaluation enregistrée.',
                'report_submitted'     => 'Rapport soumis avec succès.',
                'report_validated'     => 'Rapport validé avec succès.',
                'claim_submitted'      => 'Réclamation soumise avec succès.',
                'inscription_complete' => 'Inscription effectuée avec succès.',
                'archive_created'      => 'Archive créée avec succès.',
                'backup_created'       => 'Sauvegarde créée avec succès.',
                'restore_complete'     => 'Restauration terminée avec succès.',
                'purge_complete'       => 'Purge terminée avec succès.',
                'pv_generated'         => 'PV généré avec succès.',
                'receipt_generated'    => 'Reçu généré avec succès.',
                'bulletin_generated'   => 'Bulletin généré avec succès.',
                'candidate_approved'   => 'Candidature approuvée.',
                'candidate_rejected'   => 'Candidature rejetée.',
                'memoire_validated'    => 'Mémoire validé avec succès.',
                'no_active_year'       => 'Aucune année académique active n\'est définie.',
                'write_past_year'      => 'Impossible d\'enregistrer {context} dans une année académique antérieure. Seule l\'année active {year} accepte des écritures.',
                'excel_convert_first'  => 'Pour le moment, veuillez convertir votre fichier Excel en CSV avant l\'importation.',
                'domaine_session_unavailable' => 'Impossible d\'importer la soutenance: domaine ou session indisponible.',
            ],

            // ─── Dashboard ────────────────────────────────
            'dashboard' => [
                'welcome'        => 'Bienvenue, {name}',
                'no_data'        => 'Aucune donnée disponible.',
                'loading'        => 'Chargement...',
                'last_update'    => 'Dernière mise à jour : {date}',
            ],

            // ─── Export/Import ─────────────────────────────
            'export' => [
                'no_data'        => 'Aucune donnée à exporter.',
                'preparing'      => 'Préparation de l\'export...',
                'complete'       => 'Export terminé.',
                'download_ready' => 'Le fichier est prêt au téléchargement.',
            ],

            // ─── Sauvegarde/Restauration ──────────────────
            'backup' => [
                'confirm_backup'   => 'Voulez-vous créer une sauvegarde ?',
                'confirm_restore'  => 'ATTENTION : La restauration écrasera les données actives. Continuer ?',
                'in_progress'      => 'Opération en cours, veuillez patienter...',
                'success'          => 'Opération terminée avec succès.',
                'error'            => 'Erreur lors de l\'opération.',
            ],
        ];

        return self::$messages;
    }

    /**
     * Réinitialiser le cache (utile pour les tests ou le changement de langue).
     */
    public static function reset(): void
    {
        self::$messages = null;
    }
}
