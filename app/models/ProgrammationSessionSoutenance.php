<?php

class ProgrammationSessionSoutenance
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function tableExists(): bool
    {
        $stmt = $this->db->prepare('SHOW TABLES LIKE ?');
        $stmt->execute(['programmation_sessions_soutenance']);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getByYear(int $anneeId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id_annee_acad, num_session, date_debut, date_fin
             FROM programmation_sessions_soutenance
             WHERE id_annee_acad = ?
             ORDER BY num_session ASC'
        );
        $stmt->execute([$anneeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id_annee_acad, num_session, date_debut, date_fin
             FROM programmation_sessions_soutenance
             ORDER BY id_annee_acad DESC, num_session ASC'
        );
        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    public function upsertSession(int $anneeId, int $numSession, ?string $dateDebut, ?string $dateFin): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO programmation_sessions_soutenance (id_annee_acad, num_session, date_debut, date_fin)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                date_debut = VALUES(date_debut),
                date_fin = VALUES(date_fin)'
        );
        return $stmt->execute([$anneeId, $numSession, $dateDebut, $dateFin]);
    }

    public function deleteByYearSession(int $anneeId, int $numSession): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM programmation_sessions_soutenance
             WHERE id_annee_acad = ? AND num_session = ?'
        );
        return $stmt->execute([$anneeId, $numSession]);
    }
}
