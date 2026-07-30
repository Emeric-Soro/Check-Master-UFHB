<?php

require_once __DIR__ . '/../config/database.php';

/** Gestion de la liste des votants de la commission de validation. */
class CommissionValidationMembre
{
    private PDO $pdo;

    private const GROUP_ADMINISTRATEUR = 5;
    private const GROUP_PRESIDENT = 14;
    private const GROUP_COMMISSION = 11;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?: Database::getConnection();
    }

    public function estAdministrateur(int $idUtilisateur): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_GU FROM utilisateur WHERE id_utilisateur = ? AND statut_utilisateur = \'Actif\' LIMIT 1'
        );
        $stmt->execute([$idUtilisateur]);
        return (int) $stmt->fetchColumn() === self::GROUP_ADMINISTRATEUR;
    }

    public function peutGerer(int $idUtilisateur): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id_GU FROM utilisateur WHERE id_utilisateur = ? AND statut_utilisateur = \'Actif\' LIMIT 1'
        );
        $stmt->execute([$idUtilisateur]);
        return in_array((int) $stmt->fetchColumn(), [self::GROUP_ADMINISTRATEUR, self::GROUP_PRESIDENT], true);
    }

    public function getMembres(): array
    {
        $stmt = $this->pdo->query(
            "SELECT u.id_utilisateur, u.nom_utilisateur, u.login_utilisateur, gu.lib_GU,
                    COALESCE(cvm.actif_votant, 0) AS actif_votant,
                    cvm.date_selection, cvm.date_desactivation,
                    CASE WHEN u.id_GU = " . self::GROUP_PRESIDENT . " THEN 1 ELSE 0 END AS est_president
             FROM utilisateur u
             INNER JOIN groupe_utilisateur gu ON gu.id_GU = u.id_GU
             LEFT JOIN commission_validation_membres cvm ON cvm.id_utilisateur = u.id_utilisateur
             WHERE u.statut_utilisateur = 'Actif'
               AND u.id_GU IN (" . self::GROUP_COMMISSION . ', ' . self::GROUP_PRESIDENT . ")
             ORDER BY est_president DESC, u.nom_utilisateur ASC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNombreActifs(): int
    {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM commission_validation_membres cvm
             INNER JOIN utilisateur u ON u.id_utilisateur = cvm.id_utilisateur
             WHERE cvm.actif_votant = 1 AND u.statut_utilisateur = 'Actif'"
        );
        return (int) $stmt->fetchColumn();
    }

    public function estVotantActif(int $idUtilisateur): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM commission_validation_membres cvm
             INNER JOIN utilisateur u ON u.id_utilisateur = cvm.id_utilisateur
             WHERE cvm.id_utilisateur = ? AND cvm.actif_votant = 1
               AND u.statut_utilisateur = 'Actif'"
        );
        $stmt->execute([$idUtilisateur]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function synchroniserSelection(array $idsActifs, int $selectionneur): void
    {
        $idsActifs = array_values(array_unique(array_filter(array_map('intval', $idsActifs), static fn (int $id): bool => $id > 0)));

        // Le président est déterminé par son groupe. S'il a été retiré par
        // l'administrateur, il ne peut pas se réactiver lui-même.
        $groupStmt = $this->pdo->prepare('SELECT id_GU FROM utilisateur WHERE id_utilisateur = ? LIMIT 1');
        $groupStmt->execute([$selectionneur]);
        $groupeSelectionneur = (int) $groupStmt->fetchColumn();
        if ($groupeSelectionneur === self::GROUP_PRESIDENT && $this->estVotantActif($selectionneur)) {
            $idsActifs[] = $selectionneur;
            $idsActifs = array_values(array_unique($idsActifs));
        }

        $this->pdo->beginTransaction();
        try {
            $eligible = $this->pdo->query(
                "SELECT id_utilisateur FROM utilisateur
                 WHERE statut_utilisateur = 'Actif' AND id_GU IN (" . self::GROUP_COMMISSION . ', ' . self::GROUP_PRESIDENT . ')'
            )->fetchAll(PDO::FETCH_COLUMN);
            $eligible = array_map('intval', $eligible);
            $idsActifs = array_values(array_intersect($idsActifs, $eligible));

            $upsert = $this->pdo->prepare(
                "INSERT INTO commission_validation_membres
                    (id_utilisateur, actif_votant, selectionne_par, date_selection, date_desactivation)
                 VALUES (?, 1, ?, NOW(), NULL)
                 ON DUPLICATE KEY UPDATE
                    actif_votant = 1, selectionne_par = ?, date_selection = NOW(), date_desactivation = NULL"
            );
            foreach ($idsActifs as $id) {
                $upsert->execute([$id, $selectionneur, $selectionneur]);
            }

            $deactivate = $this->pdo->prepare(
                "UPDATE commission_validation_membres
                 SET actif_votant = 0, date_desactivation = NOW()
                 WHERE id_utilisateur IN (" . implode(',', $eligible ?: [0]) . ")"
            );
            $deactivate->execute();

            // Réactiver après la désactivation afin de conserver l'historique.
            foreach ($idsActifs as $id) {
                $upsert->execute([$id, $selectionneur, $selectionneur]);
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
