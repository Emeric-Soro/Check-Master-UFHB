<?php

declare(strict_types=1);

namespace CheckMaster\Services;

use PDO;
use RuntimeException;

/**
 * Référentiel versionné des unités d'enseignement de M2/S1.
 *
 * Les lignes UE sont immuables dès qu'elles sont utilisées par une évaluation.
 * Une nouvelle ligne est donc créée pour chaque changement de crédit ou de libellé.
 */
final class UeReferentielService
{
    public const SEMESTER_CODE = 'M2_S1';

    public function __construct(private readonly PDO $db)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function getSemesters(): array
    {
        $stmt = $this->db->query(
            'SELECT s.code_semestre, s.libelle_semestre,
                    GROUP_CONCAT(a.alias_semestre ORDER BY a.alias_semestre SEPARATOR ", ") AS alias_semestre
             FROM semestre_referentiel s
             LEFT JOIN semestre_referentiel_alias a ON a.code_semestre = s.code_semestre
             WHERE s.actif = 1
             GROUP BY s.code_semestre, s.libelle_semestre
             ORDER BY s.code_semestre'
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function getAllVersions(string $semesterCode = self::SEMESTER_CODE): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.*,
                    CASE WHEN EXISTS (
                        SELECT 1 FROM evaluation_s3 e WHERE e.id_ue = u.id_ue
                    ) THEN 1 ELSE 0 END AS deja_utilisee
             FROM ue u
             WHERE u.semestre_code = :semestre
             ORDER BY u.ordre_ue ASC, u.code_ue ASC, u.date_credit DESC, u.version_ue DESC'
        );
        $stmt->execute([':semestre' => $semesterCode]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, array<string, mixed>> */
    public function getActiveUes(?string $asOfDate = null, string $semesterCode = self::SEMESTER_CODE): array
    {
        $asOfDate = $this->normalizeDate($asOfDate) ?? date('Y-m-d');
        $stmt = $this->db->prepare(
            'SELECT u.*
             FROM ue u
             WHERE u.semestre_code = :semestre
               AND u.actif = 1
               AND u.date_credit <= :date_effet
               AND (u.date_fin IS NULL OR u.date_fin >= :date_effet_fin)
               AND NOT EXISTS (
                   SELECT 1
                   FROM ue u2
                   WHERE u2.semestre_code = u.semestre_code
                     AND u2.code_ue = u.code_ue
                     AND u2.actif = 1
                     AND u2.date_credit <= :date_effet_2
                     AND (u2.date_fin IS NULL OR u2.date_fin >= :date_effet_fin_2)
                     AND (
                         u2.date_credit > u.date_credit
                         OR (u2.date_credit = u.date_credit AND u2.version_ue > u.version_ue)
                     )
               )
             ORDER BY u.ordre_ue ASC, u.code_ue ASC'
        );
        $stmt->execute([
            ':semestre' => $semesterCode,
            ':date_effet' => $asOfDate,
            ':date_effet_fin' => $asOfDate,
            ':date_effet_2' => $asOfDate,
            ':date_effet_fin_2' => $asOfDate,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string, mixed>|null */
    public function findByCode(string $code, ?string $asOfDate = null, string $semesterCode = self::SEMESTER_CODE): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $ues = $this->getActiveUes($asOfDate, $semesterCode);
        foreach ($ues as $ue) {
            if (strcasecmp(trim((string) ($ue['code_ue'] ?? '')), $code) === 0) {
                return $ue;
            }
        }
        return null;
    }

    /** @return array<string, mixed> */
    public function saveVersion(array $payload, int $userId): array
    {
        $code = strtoupper(trim((string) ($payload['code_ue'] ?? '')));
        $label = trim((string) ($payload['libelle_ue'] ?? ''));
        $credit = $this->parseDecimal($payload['credit_ue'] ?? null);
        $dateCredit = $this->normalizeDate((string) ($payload['date_credit'] ?? ''));
        $semester = trim((string) ($payload['semestre_code'] ?? self::SEMESTER_CODE));
        $parcours = trim((string) ($payload['parcours'] ?? ''));
        $niveau = trim((string) ($payload['id_niv_etude'] ?? ''));
        $ordre = max(0, (int) ($payload['ordre_ue'] ?? 0));

        if (!preg_match('/^[A-Z0-9][A-Z0-9._-]{1,49}$/', $code)) {
            throw new RuntimeException('Le code UE est obligatoire et doit être officiel.');
        }
        if ($label === '') {
            throw new RuntimeException('Le libellé UE est obligatoire.');
        }
        if ($credit === null || $credit <= 0 || $credit > 60) {
            throw new RuntimeException('Le crédit UE doit être compris entre 0 et 60.');
        }
        if ($dateCredit === null) {
            throw new RuntimeException('La date d’application du crédit est invalide.');
        }
        if ($semester !== self::SEMESTER_CODE) {
            throw new RuntimeException('Le référentiel actuel accepte uniquement le semestre canonique M2_S1.');
        }

        $exists = $this->db->prepare(
            'SELECT id_ue, credit_ue, libelle_ue, version_ue, date_credit
             FROM ue
             WHERE code_ue = :code AND semestre_code = :semestre
             ORDER BY date_credit DESC, version_ue DESC
             LIMIT 1'
        );
        $exists->execute([':code' => $code, ':semestre' => $semester]);
        $latest = $exists->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($latest !== null
            && (float) $latest['credit_ue'] === $credit
            && (string) $latest['libelle_ue'] === $label
            && (string) $latest['date_credit'] === $dateCredit
        ) {
            return ['id_ue' => (int) $latest['id_ue'], 'created' => false, 'message' => 'Cette version UE existe déjà.'];
        }

        $version = $latest !== null ? ((int) $latest['version_ue'] + 1) : 1;
        $stmt = $this->db->prepare(
            'INSERT INTO ue
                (code_ue, libelle_ue, credit_ue, date_credit, semestre_code, id_niv_etude,
                 parcours, version_ue, ordre_ue, actif, created_by, date_modification)
             VALUES
                (:code, :libelle, :credit, :date_credit, :semestre, :niveau,
                 :parcours, :version, :ordre, 1, :created_by, NOW())'
        );
        $stmt->execute([
            ':code' => $code,
            ':libelle' => $label,
            ':credit' => $credit,
            ':date_credit' => $dateCredit,
            ':semestre' => $semester,
            ':niveau' => $niveau !== '' ? $niveau : null,
            ':parcours' => $parcours !== '' ? $parcours : null,
            ':version' => $version,
            ':ordre' => $ordre,
            ':created_by' => $userId > 0 ? $userId : null,
        ]);

        return [
            'id_ue' => (int) $this->db->lastInsertId(),
            'created' => true,
            'message' => 'La version UE a été enregistrée.',
        ];
    }

    public function normalizeSemester(string $value): ?string
    {
        $value = strtoupper(trim($value));
        if ($value === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT code_semestre FROM semestre_referentiel_alias WHERE UPPER(alias_semestre) = :alias LIMIT 1'
        );
        $stmt->execute([':alias' => $value]);
        $code = $stmt->fetchColumn();
        return $code !== false ? (string) $code : null;
    }

    private function normalizeDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = \DateTimeImmutable::getLastErrors();
            if ($date !== false && (!is_array($errors) || (($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0))) {
                return $date->format('Y-m-d');
            }
        }
        return null;
    }

    private function parseDecimal(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $normalized = str_replace([' ', ','], ['', '.'], trim((string) $value));
        return is_numeric($normalized) ? round((float) $normalized, 2) : null;
    }
}
