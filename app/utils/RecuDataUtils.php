<?php

declare(strict_types=1);

namespace App\Utils;

use App\Support\Database;
use PDO;

/**
 * Utilitaire d'extraction des données nécessaires à la génération des reçus.
 *
 * Le schéma de paiement courant repose sur la table `inscriptions` avec clé
 * composite: (num_carte_etud, id_annee_acad, num_versement).
 */
class RecuDataUtils
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Parse un identifiant composite d'inscription.
     *
     * Format attendu: num_carte_etud-id_annee_acad-num_versement
     *
     * @return array{num_carte_etud: string, id_annee_acad: int, num_versement: int}|null
     */
    private function parseCompositeId(string|int $id): ?array
    {
        $raw = trim((string) $id);
        if ($raw === '') {
            return null;
        }

        $parts = explode('-', $raw);
        if (count($parts) < 3) {
            return null;
        }

        $numVersementRaw = array_pop($parts);
        $anneeRaw = array_pop($parts);
        $numCarteEtud = implode('-', $parts);

        if ($numCarteEtud === '' || !is_numeric($anneeRaw) || !is_numeric($numVersementRaw)) {
            return null;
        }

        return [
            'num_carte_etud' => $numCarteEtud,
            'id_annee_acad' => (int) $anneeRaw,
            'num_versement' => (int) $numVersementRaw,
        ];
    }

    /**
     * Récupère un versement (inscription) via son identifiant composite.
     *
     * @return array|null
     */
    public function findVersementById(string|int $id): ?array
    {
        $composite = $this->parseCompositeId($id);
        if ($composite === null) {
            return null;
        }

        $stmt = $this->db->pdo()->prepare(
            "SELECT
                CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) AS id_versement,
                CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) AS id_inscription,
                i.montant_verser AS montant_versement,
                i.date_versement,
                CASE
                    WHEN i.num_versement = 1 THEN 'inscription'
                    ELSE 'scolarite'
                END AS type_versement,
                CASE LOWER(COALESCE(i.methode_paiement, ''))
                    WHEN 'espèce' THEN 'especes'
                    WHEN 'espèces' THEN 'especes'
                    WHEN 'espece' THEN 'especes'
                    WHEN 'especes' THEN 'especes'
                    WHEN 'chèque' THEN 'cheque'
                    WHEN 'cheque' THEN 'cheque'
                    WHEN 'carte bancaire' THEN 'carte'
                    WHEN 'virement' THEN 'virement'
                    ELSE LOWER(COALESCE(i.methode_paiement, ''))
                END AS methode_paiement,
                i.num_carte_etud AS matricule_etudiant,
                '' AS reference_paiement_genere
             FROM inscriptions i
             WHERE i.num_carte_etud = :num_carte_etud
               AND i.id_annee_acad = :id_annee_acad
               AND i.num_versement = :num_versement
             LIMIT 1"
        );
        $stmt->execute($composite);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * Récupère les informations d'inscription via l'identifiant composite.
     *
     * @return array|null
     */
    public function findInscriptionById(string|int $id): ?array
    {
        $composite = $this->parseCompositeId($id);
        if ($composite === null) {
            return null;
        }

        $stmt = $this->db->pdo()->prepare(
            "SELECT
                i.num_carte_etud,
                i.id_annee_acad,
                i.num_versement,
                i.solde AS reste_a_payer,
                (
                    SELECT COALESCE(SUM(i2.montant_verser), 0)
                    FROM inscriptions i2
                    WHERE i2.num_carte_etud = i.num_carte_etud
                      AND i2.id_annee_acad = i.id_annee_acad
                ) AS montant_paye,
                CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) AS libelle_annee,
                COALESCE(n.lib_niv_etude, '') AS code_niveau,
                COALESCE(n.lib_niv_etude, '') AS code_filiere
             FROM inscriptions i
             LEFT JOIN annee_academique aa ON aa.id_annee_acad = i.id_annee_acad
             LEFT JOIN niveau_etude n ON n.id_niv_etude = i.id_niv_etude
             WHERE i.num_carte_etud = :num_carte_etud
               AND i.id_annee_acad = :id_annee_acad
               AND i.num_versement = :num_versement
             LIMIT 1"
        );
        $stmt->execute($composite);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * Récupère un étudiant par son matricule.
     *
     * @return array|null
     */
    public function findEtudiantByMatricule(string $matricule): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT
                e.num_carte_etud,
                e.nom_etu AS nom_etudiant,
                e.prenom_etu AS prenom_etudiant,
                e.email_etu AS email_etudiant
             FROM etudiants e
             WHERE e.num_carte_etud = :matricule
                OR e.num_ident_etud = :matricule
             LIMIT 1'
        );
        $stmt->execute(['matricule' => $matricule]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    /**
     * Marque le reçu comme généré (trace applicative).
     */
    public function marquerRecuGenere(string|int $versementId, string $cheminRecu, string $reference): void
    {
        error_log(sprintf(
            '[RecuDataUtils] Reçu généré pour versement %s: chemin=%s, ref=%s',
            (string) $versementId,
            $cheminRecu,
            $reference
        ));
    }

    /**
     * Génère une référence unique de document au format TYPE-YYYY-NNNNN.
     */
    public function generateReference(string $type): string
    {
        $year = date('Y');
        $seq = (int) (microtime(true) * 100) % 99999;
        $num = str_pad((string) max($seq, 1), 5, '0', STR_PAD_LEFT);

        return strtoupper($type) . '-' . $year . '-' . $num;
    }

    /**
     * Enregistrement factice pour compatibilité.
     *
     * @param array<string, mixed> $data
     */
    public function saveDocumentRecord(array $data): int
    {
        error_log(sprintf(
            '[RecuDataUtils] Document généré (non persisté): ref=%s, type=%s, fichier=%s',
            (string) ($data['reference_document'] ?? '?'),
            (string) ($data['type_document'] ?? '?'),
            (string) ($data['chemin_fichier'] ?? '?')
        ));

        return 0;
    }

    /**
     * Convertit un entier en lettres françaises.
     */
    public static function intToWords(int $number): string
    {
        require_once __DIR__ . '/ReceiptUtils.php';

        return \ReceiptUtils::numberToWords($number);
    }
}
