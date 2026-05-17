<?php

require_once __DIR__ . '/EmailService.php';

class NotificationService
{
    private EmailService $emailService;
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->emailService = new EmailService();
        $this->pdo = $pdo;
    }

    public function getEmailService(): EmailService
    {
        return $this->emailService;
    }

    /**
     * Envoie un email aux destinataires d'un groupe utilisateur.
     * @param array $groupIds
     * @param string $templateKey
     * @param array $data
     * @return int nombre d'envois
     */
    public function sendToUserGroups(array $groupIds, string $templateKey, array $data): int
    {
        if ($this->pdo === null) {
            $this->pdo = Database::getConnection();
        }
        $count = 0;
        $placeholders = implode(', ', array_fill(0, count($groupIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT
                TRIM(COALESCE(NULLIF(e.mail_enseignant, ''), NULLIF(pa.email_pers_admin, ''), NULLIF(u.login_utilisateur, ''))) AS email,
                TRIM(COALESCE(
                    NULLIF(CONCAT(COALESCE(e.prenom_enseignant, ''), ' ', COALESCE(e.nom_enseignant, '')), ' '),
                    NULLIF(CONCAT(COALESCE(pa.prenom_pers_admin, ''), ' ', COALESCE(pa.nom_pers_admin, '')), ' '),
                    NULLIF(u.nom_utilisateur, '')
                )) AS nom
             FROM utilisateur u
             LEFT JOIN enseignants e ON LOWER(e.mail_enseignant) = LOWER(u.login_utilisateur)
             LEFT JOIN personnel_admin pa ON LOWER(pa.email_pers_admin) = LOWER(u.login_utilisateur)
             WHERE u.statut_utilisateur = 'Actif'
               AND u.id_GU IN ($placeholders)"
        );
        $stmt->execute($groupIds);
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $alreadySent = [];
        foreach ($recipients as $r) {
            $email = strtolower(trim((string)($r['email'] ?? '')));
            if ($email === '' || isset($alreadySent[$email])) {
                continue;
            }
            $d = $data;
            if (!isset($d['nom'])) {
                $d['nom'] = trim((string)($r['nom'] ?? ''));
            }
            if ($this->emailService->sendTemplate($templateKey, $email, $d)) {
                $alreadySent[$email] = true;
                $count++;
            }
        }
        return $count;
    }

    /**
     * Envoie un email avec piece jointe aux destinataires d'un groupe.
     */
    public function sendToUserGroupsWithAttachment(array $groupIds, string $subject, string $body, string $pdfPath, string $pdfName, array $alreadyNotified = []): int
    {
        if ($this->pdo === null) {
            $this->pdo = Database::getConnection();
        }
        $count = 0;
        $placeholders = implode(', ', array_fill(0, count($groupIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT
                TRIM(COALESCE(NULLIF(e.mail_enseignant, ''), NULLIF(pa.email_pers_admin, ''), NULLIF(u.login_utilisateur, ''))) AS email
             FROM utilisateur u
             LEFT JOIN enseignants e ON LOWER(e.mail_enseignant) = LOWER(u.login_utilisateur)
             LEFT JOIN personnel_admin pa ON LOWER(pa.email_pers_admin) = LOWER(u.login_utilisateur)
             WHERE u.statut_utilisateur = 'Actif'
               AND u.id_GU IN ($placeholders)"
        );
        $stmt->execute($groupIds);
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($recipients as $r) {
            $email = strtolower(trim((string)($r['email'] ?? '')));
            if ($email === '' || isset($alreadyNotified[$email])) {
                continue;
            }
            if ($this->emailService->sendEmailWithAttachment($email, $subject, $body, $pdfPath, $pdfName, true)) {
                $alreadyNotified[$email] = true;
                $count++;
            }
        }
        return $count;
    }

    /**
     * Recupere l'email d'un enseignant par son ID.
     */
    public function getEnseignantEmail(string $enseignantId): ?string
    {
        if ($this->pdo === null) {
            $this->pdo = Database::getConnection();
        }
        $stmt = $this->pdo->prepare("SELECT mail_enseignant FROM enseignants WHERE id_enseignant = ?");
        $stmt->execute([$enseignantId]);
        $email = $stmt->fetchColumn();
        return $email ?: null;
    }

    /**
     * Recupere le nom complet d'un enseignant par son ID.
     */
    public function getEnseignantNom(string $enseignantId): string
    {
        if ($this->pdo === null) {
            $this->pdo = Database::getConnection();
        }
        $stmt = $this->pdo->prepare("SELECT CONCAT(prenom_enseignant, ' ', nom_enseignant) FROM enseignants WHERE id_enseignant = ?");
        $stmt->execute([$enseignantId]);
        return (string)$stmt->fetchColumn();
    }

    /**
     * Recupere les encadrants et directeurs d'un rapport.
     */
    public function getEncadrantsForRapport(int $idRapport): array
    {
        if ($this->pdo === null) {
            $this->pdo = Database::getConnection();
        }
        $stmt = $this->pdo->prepare("
            SELECT a.id_enseignant, a.role, CONCAT(e.prenom_enseignant, ' ', e.nom_enseignant) AS nom, e.mail_enseignant AS email
            FROM affecter a
            JOIN enseignants e ON a.id_enseignant = e.id_enseignant
            WHERE a.id_rapport = ?
        ");
        $stmt->execute([$idRapport]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recupere les membres du jury pour une soutenance. 
     */
    public function getJuryMembres(string $numSoutenance): array
    {
        if ($this->pdo === null) {
            $this->pdo = Database::getConnection();
        }
        $stmt = $this->pdo->prepare("
            SELECT ej.id_enseignant, ej.id_qualite_jury, qj.lib_role,
                   CONCAT(e.prenom_enseignant, ' ', e.nom_enseignant) AS nom,
                   e.mail_enseignant AS email
            FROM enseignant_jury ej
            JOIN qualite_jury qj ON ej.id_qualite_jury = qj.id_role_jury
            JOIN enseignants e ON ej.id_enseignant = e.id_enseignant
            WHERE ej.num_soutenance = ?
        ");
        $stmt->execute([$numSoutenance]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Recupere les groupes responsables/admin/scolarite pour les notifications.
     */
    public function getAdminGroupIds(): array
    {
        return [5, 6, 7, 8];
    }
}
