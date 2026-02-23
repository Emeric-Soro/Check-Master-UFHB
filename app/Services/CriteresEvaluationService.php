<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CritereEvaluation.php';

use CritereEvaluation;
use PDO;
use Exception;

class CriteresEvaluationService
{
    private $db;
    private $critereModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->critereModel = new CritereEvaluation($db);
    }

    /**
     * Récupérer toutes les années académiques
     * @return array
     */
    public function getAnneesAcademiques(): array
    {
        $stmt = $this->db->prepare("SELECT id_annee_acad as id, CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) as lib FROM annee_academique ORDER BY id_annee_acad DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupérer tous les critères d'évaluation avec leurs barèmes
     * @return array
     */
    public function getCriteres(): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                ce.id_critere as id,
                ce.lib_critere as libelle,
                c.id_annee_acad as annee_id,
                CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) as annee_lib,
                c.bareme
            FROM critere_evaluation ce
            LEFT JOIN correspondre c ON ce.id_critere = c.id_critere
            LEFT JOIN annee_academique aa ON c.id_annee_acad = aa.id_annee_acad
            ORDER BY ce.id_critere, c.id_annee_acad DESC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Grouper les résultats par critère
        $criteresData = [];
        foreach ($results as $row) {
            $critereId = $row['id'];

            if (!isset($criteresData[$critereId])) {
                $criteresData[$critereId] = [
                    'id' => $critereId,
                    'libelle' => $row['libelle'],
                    'baremes' => []
                ];
            }

            // Ajouter le barème s'il existe
            if ($row['annee_id'] && $row['bareme'] !== null) {
                $criteresData[$critereId]['baremes'][] = [
                    'annee_id' => $row['annee_id'],
                    'annee_lib' => $row['annee_lib'],
                    'bareme' => (int) $row['bareme']
                ];
            }
        }

        return array_values($criteresData);
    }

    /**
     * Créer un nouveau critère d'évaluation avec ses barèmes
     * @param array $input Données du critère (libelle, baremes)
     * @return int ID du critère créé
     * @throws Exception
     */
    public function createCritere(array $input): int
    {
        if (!isset($input['libelle']) || empty(trim($input['libelle']))) {
            throw new Exception('Le libellé du critère est requis');
        }

        if (!isset($input['baremes']) || empty($input['baremes'])) {
            throw new Exception('Au moins un barème est requis');
        }

        // Validation des barèmes - vérifier que le total par année ne dépasse pas 20
        $this->validateBaremesTotaux($input['baremes'], null);

        $this->db->beginTransaction();

        try {
            // Insérer le critère via le modèle
            $code_critere = strtoupper(substr($input['libelle'], 0, 2));
            if (!$this->critereModel->creerCritere($code_critere, trim($input['libelle']))) {
                throw new Exception('Erreur lors de la création du critère');
            }
            $critereId = $this->db->lastInsertId();

            // Insérer les barèmes
            $stmtBareme = $this->db->prepare("INSERT INTO correspondre (id_critere, id_annee_acad, bareme) VALUES (?, ?, ?)");

            foreach ($input['baremes'] as $bareme) {
                if (!empty($bareme['annee_id']) && !empty($bareme['bareme'])) {
                    $stmtBareme->execute([
                        $critereId,
                        $bareme['annee_id'],
                        (int) $bareme['bareme']
                    ]);
                }
            }

            $this->db->commit();
            return (int) $critereId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Mettre à jour un critère d'évaluation avec ses barèmes
     * @param array $input Données du critère (id, libelle, baremes)
     * @throws Exception
     */
    public function updateCritere(array $input): void
    {
        if (!isset($input['id']) || empty($input['id'])) {
            throw new Exception('ID du critère requis');
        }

        if (!isset($input['libelle']) || empty(trim($input['libelle']))) {
            throw new Exception('Le libellé du critère est requis');
        }

        if (!isset($input['baremes']) || empty($input['baremes'])) {
            throw new Exception('Au moins un barème est requis');
        }

        // Validation des barèmes - vérifier que le total par année ne dépasse pas 20 (en excluant le critère actuel)
        $this->validateBaremesTotaux($input['baremes'], $input['id']);

        $this->db->beginTransaction();

        try {
            // Mettre à jour le critère via le modèle
            $code_critere = strtoupper(substr($input['libelle'], 0, 2));
            if (!$this->critereModel->modifierCritere($input['id'], $code_critere, trim($input['libelle']))) {
                throw new Exception('Erreur lors de la modification du critère');
            }

            // Supprimer les anciens barèmes
            $stmtDelete = $this->db->prepare("DELETE FROM correspondre WHERE id_critere = ?");
            $stmtDelete->execute([$input['id']]);

            // Insérer les nouveaux barèmes
            $stmtBareme = $this->db->prepare("INSERT INTO correspondre (id_critere, id_annee_acad, bareme) VALUES (?, ?, ?)");

            foreach ($input['baremes'] as $bareme) {
                if (!empty($bareme['annee_id']) && !empty($bareme['bareme'])) {
                    $stmtBareme->execute([
                        $input['id'],
                        $bareme['annee_id'],
                        (int) $bareme['bareme']
                    ]);
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Supprimer un critère d'évaluation et ses barèmes
     * @param array $input Données contenant l'id du critère
     * @throws Exception
     */
    public function deleteCritere(array $input): void
    {
        if (!isset($input['id']) || empty($input['id'])) {
            throw new Exception('ID du critère requis');
        }

        $this->db->beginTransaction();

        try {
            // Vérifier si le critère existe
            $critere = $this->critereModel->getCritereById($input['id']);
            if (!$critere) {
                throw new Exception('Critère non trouvé');
            }

            // Supprimer les barèmes associés
            $stmtBaremes = $this->db->prepare("DELETE FROM correspondre WHERE id_critere = ?");
            $stmtBaremes->execute([$input['id']]);

            // Supprimer le critère via le modèle
            if (!$this->critereModel->supprimerCritere($input['id'])) {
                throw new Exception('Erreur lors de la suppression du critère');
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Valider que les totaux des barèmes par année ne dépassent pas 20 points
     * @param array $nouveauxBaremes Nouveaux barèmes à valider
     * @param int|null $critereIdExclure ID du critère à exclure (pour les modifications)
     * @throws Exception
     */
    private function validateBaremesTotaux(array $nouveauxBaremes, ?int $critereIdExclure = null): void
    {
        // Récupérer les totaux actuels par année (en excluant le critère en cours de modification si applicable)
        $sqlExclusion = $critereIdExclure ? "AND ce.id_critere != ?" : "";
        $sql = "
            SELECT 
                aa.id_annee_acad,
                CONCAT(aa.date_deb, ' - ', aa.date_fin) as lib_annee,
                COALESCE(SUM(c.bareme), 0) as total_actuel
            FROM annee_academique aa
            LEFT JOIN correspondre c ON aa.id_annee_acad = c.id_annee_acad
            LEFT JOIN critere_evaluation ce ON c.id_critere = ce.id_critere
            WHERE 1=1 $sqlExclusion
            GROUP BY aa.id_annee_acad, aa.date_deb, aa.date_fin
            ORDER BY aa.date_deb DESC
        ";

        $params = $critereIdExclure ? [$critereIdExclure] : [];
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $totauxActuels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Créer un tableau des totaux actuels indexé par année
        $totauxParAnnee = [];
        foreach ($totauxActuels as $total) {
            $totauxParAnnee[$total['id_annee_acad']] = [
                'total' => (float) $total['total_actuel'],
                'lib_annee' => $total['lib_annee']
            ];
        }

        // Calculer les nouveaux totaux avec les barèmes proposés
        foreach ($nouveauxBaremes as $bareme) {
            if (!empty($bareme['annee_id']) && !empty($bareme['bareme'])) {
                $anneeId = $bareme['annee_id'];
                $points = (float) $bareme['bareme'];

                if (!isset($totauxParAnnee[$anneeId])) {
                    // Récupérer les infos de l'année si pas encore présente
                    $stmtAnnee = $this->db->prepare("SELECT CONCAT(date_deb, ' - ', date_fin) as lib_annee FROM annee_academique WHERE id_annee_acad = ?");
                    $stmtAnnee->execute([$anneeId]);
                    $anneeInfo = $stmtAnnee->fetch(PDO::FETCH_ASSOC);

                    $totauxParAnnee[$anneeId] = [
                        'total' => 0,
                        'lib_annee' => $anneeInfo ? $anneeInfo['lib_annee'] : "Année ID $anneeId"
                    ];
                }

                $totauxParAnnee[$anneeId]['total'] += $points;
            }
        }

        // Vérifier les limites
        foreach ($totauxParAnnee as $anneeId => $data) {
            if ($data['total'] > 20) {
                throw new Exception("Le total des points pour l'année {$data['lib_annee']} dépasse 20 points ({$data['total']} points). Veuillez ajuster les barèmes.");
            }
        }
    }
}