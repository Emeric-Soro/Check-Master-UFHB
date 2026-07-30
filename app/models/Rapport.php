<?php

use CheckMaster\Models\BaseModel;

class Rapport extends BaseModel
{
    protected const TABLE = 'rapport_etudiants';
    protected const PRIMARY_KEY = 'id_rapport';

    /**
     * Centre de documents archivés (rapports, CR, fiches inscription).
     *
     * @param int $id_annee_acad
     * @param array<string, mixed> $filters
     * @param int $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public function getByAnneeWithFiles($id_annee_acad, array $filters = [], $limit = 500, $offset = 0)
    {
        $idAnnee = (int) $id_annee_acad;
        if ($idAnnee <= 0) {
            return [];
        }

        $params = [
            ':id_annee_rapport' => $idAnnee,
            ':id_annee_cr' => $idAnnee,
            ':id_annee_fiche' => $idAnnee,
        ];

        $sql = "
            SELECT *
            FROM (
                SELECT
                    'rapport' AS type_doc,
                    CAST(re.id_rapport AS CHAR) AS id_doc,
                    re.chemin_fichier AS chemin,
                    COALESCE(re.theme_rapport, CONCAT('Rapport #', re.id_rapport)) AS titre,
                    re.date_redaction_rapport AS date_depot,
                    re.taille_fichier AS taille,
                    CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant,
                    COALESCE(e.num_ident_etud, e.num_carte_etud) AS num_carte_etud
                FROM rapport_etudiants re
                INNER JOIN etudiants e ON (e.num_carte_etud = re.num_etu OR e.num_ident_etud = re.num_etu)
                INNER JOIN inscriptions i1 ON i1.num_carte_etud = e.num_carte_etud
                WHERE i1.id_annee_acad = :id_annee_rapport
                  AND (
                        (re.chemin_fichier IS NOT NULL AND re.chemin_fichier <> '')
                        OR EXISTS (
                            SELECT 1
                            FROM documents d
                            WHERE d.entite_type = 'rapport_etudiants'
                              AND d.entite_id = CAST(re.id_rapport AS CHAR)
                              AND d.statut = 'actif'
                              AND d.type_document IN ('rapport', 'html_doc')
                        )
                      )

                UNION ALL

                SELECT
                    'compte_rendu' AS type_doc,
                    CAST(cr.id_CR AS CHAR) AS id_doc,
                    cr.chemin_fichier_pdf AS chemin,
                    COALESCE(cr.nom_CR, CONCAT('Compte rendu #', cr.id_CR)) AS titre,
                    cr.date_CR AS date_depot,
                    NULL AS taille,
                    CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant,
                    COALESCE(e.num_ident_etud, e.num_carte_etud) AS num_carte_etud
                FROM compte_rendu cr
                INNER JOIN etudiants e ON (e.num_carte_etud = cr.num_etu OR e.num_ident_etud = cr.num_etu)
                INNER JOIN inscriptions i2 ON i2.num_carte_etud = e.num_carte_etud
                WHERE i2.id_annee_acad = :id_annee_cr
                  AND (
                        (cr.chemin_fichier_pdf IS NOT NULL AND cr.chemin_fichier_pdf <> '')
                        OR EXISTS (
                            SELECT 1
                            FROM documents d
                            WHERE d.entite_type = 'compte_rendu'
                              AND d.entite_id = CAST(cr.id_CR AS CHAR)
                              AND d.statut = 'actif'
                              AND d.type_document = 'compte_rendu'
                        )
                      )

                UNION ALL

                SELECT
                    'fiche_inscription' AS type_doc,
                    CONCAT(i3.num_carte_etud, '-', i3.id_annee_acad, '-', i3.num_versement) AS id_doc,
                    i3.fiche_inscription AS chemin,
                    CONCAT('Fiche inscription ', COALESCE(e.num_ident_etud, e.num_carte_etud)) AS titre,
                    i3.date_inscription AS date_depot,
                    NULL AS taille,
                    CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant,
                    COALESCE(e.num_ident_etud, e.num_carte_etud) AS num_carte_etud
                FROM inscriptions i3
                INNER JOIN etudiants e ON e.num_carte_etud = i3.num_carte_etud
                WHERE i3.id_annee_acad = :id_annee_fiche
                  AND (
                        (i3.fiche_inscription IS NOT NULL AND i3.fiche_inscription <> '')
                        OR EXISTS (
                            SELECT 1
                            FROM documents d
                            WHERE d.entite_type = 'inscriptions'
                              AND d.entite_id = CONCAT(i3.num_carte_etud, '-', i3.id_annee_acad, '-', i3.num_versement)
                              AND d.statut = 'actif'
                              AND d.type_document = 'fiche_inscription'
                        )
                      )
            ) AS docs
            WHERE 1 = 1
        ";

        if (!empty($filters['type_doc'])) {
            $sql .= " AND docs.type_doc = :f_type_doc";
            $params[':f_type_doc'] = (string) $filters['type_doc'];
        }
        if (!empty($filters['matricule'])) {
            $sql .= " AND docs.num_carte_etud = :f_matricule";
            $params[':f_matricule'] = (string) $filters['matricule'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (
                docs.titre LIKE :f_search
                OR docs.etudiant LIKE :f_search
                OR docs.num_carte_etud LIKE :f_search
            )";
            $params[':f_search'] = '%' . trim((string) $filters['search']) . '%';
        }

        $sql .= " ORDER BY docs.date_depot DESC LIMIT :cm_limit OFFSET :cm_offset";

        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':cm_limit', max(1, (int) $limit), PDO::PARAM_INT);
            $stmt->bindValue(':cm_offset', max(0, (int) $offset), PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log("Erreur Rapport::getByAnneeWithFiles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retourne les métadonnées et le chemin d'un document.
     *
     * @param string $type
     * @param string|int $id
     * @return array<string, mixed>|null
     */
    public function findDocumentByTypeAndId($type, $id)
    {
        $type = trim((string) $type);
        if ($type === '') {
            return null;
        }

        try {
            if ($type === 'rapport') {
                $stmt = $this->pdo->prepare("
                    SELECT
                        'rapport' AS type_doc,
                        CAST(re.id_rapport AS CHAR) AS id_doc,
                        re.chemin_fichier AS chemin,
                        COALESCE(re.theme_rapport, CONCAT('Rapport #', re.id_rapport)) AS titre,
                        re.date_redaction_rapport AS date_depot,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant,
                        COALESCE(e.num_ident_etud, e.num_carte_etud) AS num_carte_etud
                    FROM rapport_etudiants re
                    INNER JOIN etudiants e ON (e.num_carte_etud = re.num_etu OR e.num_ident_etud = re.num_etu)
                    WHERE re.id_rapport = :id
                    LIMIT 1
                ");
                $stmt->execute([':id' => (int) $id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ?: null;
            }

            if ($type === 'compte_rendu') {
                $stmt = $this->pdo->prepare("
                    SELECT
                        'compte_rendu' AS type_doc,
                        CAST(cr.id_CR AS CHAR) AS id_doc,
                        cr.chemin_fichier_pdf AS chemin,
                        COALESCE(cr.nom_CR, CONCAT('Compte rendu #', cr.id_CR)) AS titre,
                        cr.date_CR AS date_depot,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant,
                        COALESCE(e.num_ident_etud, e.num_carte_etud) AS num_carte_etud
                    FROM compte_rendu cr
                    INNER JOIN etudiants e ON (e.num_carte_etud = cr.num_etu OR e.num_ident_etud = cr.num_etu)
                    WHERE cr.id_CR = :id
                    LIMIT 1
                ");
                $stmt->execute([':id' => (int) $id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ?: null;
            }

            if ($type === 'fiche_inscription') {
                // $id doit être au format composé 'num_carte_etud|id_annee_acad|num_versement'
                $parts = preg_split('/[-|]/', (string) $id);
                if (!is_array($parts) || count($parts) < 3) {
                    return null;
                }
                $versement = array_pop($parts);
                $annee = array_pop($parts);
                $numCarteEtud = implode('-', $parts);
                if ($numCarteEtud === '' || $annee === '' || $versement === '') {
                    return null;
                }
                $stmt = $this->pdo->prepare("
                    SELECT
                        'fiche_inscription' AS type_doc,
                        CONCAT(i.num_carte_etud, '-', i.id_annee_acad, '-', i.num_versement) AS id_doc,
                        i.fiche_inscription AS chemin,
                        CONCAT('Fiche inscription ', e.num_carte_etud) AS titre,
                        i.date_inscription AS date_depot,
                        CONCAT(e.nom_etu, ' ', e.prenom_etu) AS etudiant,
                        e.num_carte_etud
                    FROM inscriptions i
                    INNER JOIN etudiants e ON e.num_carte_etud = i.num_carte_etud
                    WHERE i.num_carte_etud = :num_etu
                      AND i.id_annee_acad = :annee
                      AND i.num_versement = :versement
                    LIMIT 1
                ");
                $stmt->execute([
                    ':num_etu' => $numCarteEtud,
                    ':annee' => (int) $annee,
                    ':versement' => (int) $versement,
                ]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ?: null;
            }

            return null;
        } catch (PDOException $e) {
            error_log("Erreur Rapport::findDocumentByTypeAndId: " . $e->getMessage());
            return null;
        }
    }
}
