<?php

declare(strict_types=1);

namespace App\Utils;

use App\Support\Database;
use PDO;

/**
 * Utilitaire pour l'extraction des données de reçus de paiement.
 * Remplace les anciens repositories non adaptés à la base de données actuelle.
 *
 * NOTE: Les tables `versements` et `inscriptions` existent dans le schéma.
 * Structure réelle :
 *   - versements (id_versement PK, id_inscription, montant, date_versement,
 *                  type_versement ENUM('Premier versement','Tranche'),
 *                  methode_paiement ENUM('Espèce','Carte bancaire','Virement','Chèque'))
 *   - inscriptions (id_inscription PK, id_annee_acad, id_etudiant, id_niveau,
 *                    montant_paye, reste_a_payer, date_inscription, methode_paiement,
 *                    montant_verser, solde)
 */
class RecuDataUtils
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Récupère un versement par son identifiant.
     * Table: versements (id_versement PK)
     *
     * @return array|null
     */
    public function findVersementById(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT i.id_inscription AS id_versement,
                    i.id_inscription,
                    i.montant_verser AS montant_versement,
                    i.date_versement,
                    CASE
                        WHEN i.num_versement = 1 THEN \'inscription\'
                        ELSE \'scolarite\'
                    END AS type_versement,
                    CASE LOWER(i.methode_paiement)
                        WHEN \'espèce\' THEN \'especes\'
                        WHEN \'espece\' THEN \'especes\'
                        WHEN \'chèque\' THEN \'cheque\'
                        WHEN \'cheque\' THEN \'cheque\'
                        WHEN \'carte bancaire\' THEN \'carte\'
                        WHEN \'virement\' THEN \'virement\'
                        ELSE LOWER(i.methode_paiement)
                    END AS methode_paiement,
                    COALESCE(i.num_carte_etud, \'\') AS matricule_etudiant,
                    \'\' AS reference_paiement_genere
             FROM inscriptions i
             WHERE i.num_carte_etud = :num_carte_etud 
               AND i.id_annee_acad = :id_annee_acad
               AND i.num_versement = :num_versement'
        );
        $stmt->execute([
            'num_carte_etud' => $data['num_carte_etud'] ?? '',
            'id_annee_acad' => $data['id_annee_acad'] ?? 0,
            'num_versement' => $data['num_versement'] ?? 1
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Récupère une inscription par son identifiant.
     * Table: inscriptions (id_inscription PK)
     *
     * @return array|null
     */
    public function findInscriptionById(int $id): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT i.num_carte_etud,
                    i.id_annee_acad,
                    i.num_versement,
                    i.solde as reste_a_payer,
                    i.montant_verser as montant_paye,
                    CONCAT(aa.date_deb, \' / \', aa.date_fin) AS libelle_annee,
                    COALESCE(n.lib_niv_etude, \'\') AS code_niveau,
                    COALESCE(n.lib_niv_etude, \'\') AS code_filiere
             FROM inscriptions i
             LEFT JOIN annee_academique aa ON aa.id_annee_acad = i.id_annee_acad
             LEFT JOIN niveau_etude n ON n.id_niv_etude = i.id_niv_etude
             WHERE i.num_carte_etud = :num_carte_etud
               AND i.id_annee_acad = :id_annee_acad
               AND i.num_versement = :num_versement'
        );
        $stmt->execute([
            'num_carte_etud' => $data['num_carte_etud'] ?? '',
            'id_annee_acad' => $data['id_annee_acad'] ?? 0,
            'num_versement' => $data['num_versement'] ?? 1
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Récupère un étudiant par son matricule.
     * Table: etudiants (num_carte_etud PK) — cette table EXISTE dans base.txt.
     *
     * @return array|null
     */
    public function findEtudiantByMatricule(string $matricule): ?array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT e.num_carte_etud, e.nom_etu AS nom_etudiant,
                    e.prenom_etu AS prenom_etudiant,
                    e.email_etu AS email_etudiant,
                    i.id_niv_etude, i.id_annee_acad
             FROM etudiants e
             LEFT JOIN inscriptions i ON (i.num_carte_etud, i.id_annee_acad, i.num_versement) = (
                 SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement FROM inscriptions i2 
                 WHERE i2.num_carte_etud = e.num_carte_etud 
                 ORDER BY i2.date_inscription DESC, i2.id_annee_acad DESC, i2.num_versement DESC LIMIT 1
             )
             WHERE e.num_carte_etud = :matricule'
        );
        $stmt->execute(['matricule' => $matricule]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : null;
    }

    /**
     * Met à jour le versement avec le chemin du reçu et la référence.
     * Table: versements
     */
    public function marquerRecuGenere(int $versementId, string $cheminRecu, string $reference): void
    {
        // La table versements ne possède pas les colonnes recu_genere, chemin_recu,
        // reference_paiement_genere. On logue simplement l'opération.
        error_log(sprintf(
            '[RecuDataUtils] Reçu marqué généré (versement #%d): chemin=%s, ref=%s',
            $versementId,
            $cheminRecu,
            $reference
        ));
    }

    /**
     * Génère une référence unique de document au format TYPE-YYYY-NNNNN.
     * Puisqu'il n'y a PAS de table `document_genere` dans la base,
     * on génère un identifiant basé sur le timestamp et un numéro aléatoire.
     */
    public function generateReference(string $type): string
    {
        $year = date('Y');
        $seq = (int) (microtime(true) * 100) % 99999;
        $num = str_pad((string) max($seq, 1), 5, '0', STR_PAD_LEFT);

        return strtoupper($type) . '-' . $year . '-' . $num;
    }

    /**
     * Enregistrement factice d'un document généré.
     * La table `document_genere` N'EXISTE PAS dans le schéma actuel (base.txt).
     * Cette méthode fait un no-op mais retourne un ID fictif pour maintenir la compatibilité.
     *
     * @param array<string, mixed> $data Données du document
     * @return int ID fictif du document (0)
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
     * Convertit un nombre entier en lettres en français.
     * Encapsule ReceiptUtils::numberToWords() pour éviter une dépendance globale.
     */
    public static function intToWords(int $number): string
    {
        // Utilise la classe ReceiptUtils existante (sans namespace)
        require_once __DIR__ . '/ReceiptUtils.php';

        return \ReceiptUtils::numberToWords($number);
    }
}
