<?php
require_once __DIR__ . '/../config/database.php';

class CriteresEvaluationController
{

    public function __construct()
    {
        // La classe Database utilise des méthodes statiques
    }

    /**
     * Afficher la page principale (pas d'action spécifique)
     */
    public function index()
    {
        // La vue sera incluse par le layout principal
        // Pas de logique particulière nécessaire ici
    }

    /**
     * Récupérer toutes les années académiques
     */
    public function getAnneesAcademiques()
    {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT id_annee_acad as id, CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) as lib FROM annee_academique ORDER BY id_annee_acad DESC");
            $stmt->execute();
            $annees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $annees
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des années académiques : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer tous les critères d'évaluation avec leurs barèmes
     */
    public function getCriteres()
    {
        try {
            $pdo = Database::getConnection();

            // Récupérer les critères avec leurs barèmes
            $stmt = $pdo->prepare("
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

            // Convertir en array indexé
            $criteresArray = array_values($criteresData);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $criteresArray
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des critères : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Créer un nouveau critère d'évaluation
     */
    public function createCritere()
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Debug : log des données reçues
            error_log("Données reçues pour création critère: " . print_r($input, true));

            if (!isset($input['libelle']) || empty(trim($input['libelle']))) {
                throw new Exception('Le libellé du critère est requis');
            }

            if (!isset($input['baremes']) || empty($input['baremes'])) {
                throw new Exception('Au moins un barème est requis');
            }

            $pdo = Database::getConnection();

            // Validation des barèmes - vérifier que le total par année ne dépasse pas 20
            $this->validateBaremesTotaux($pdo, $input['baremes'], null);

            $pdo->beginTransaction();

            // Insérer le critère
            $stmt = $pdo->prepare("INSERT INTO critere_evaluation (lib_critere) VALUES (?)");
            $stmt->execute([trim($input['libelle'])]);
            $critereId = $pdo->lastInsertId();

            // Insérer les barèmes
            $stmtBareme = $pdo->prepare("INSERT INTO correspondre (id_critere, id_annee_acad, bareme) VALUES (?, ?, ?)");

            foreach ($input['baremes'] as $bareme) {
                if (!empty($bareme['annee_id']) && !empty($bareme['bareme'])) {
                    $stmtBareme->execute([
                        $critereId,
                        $bareme['annee_id'],
                        (int) $bareme['bareme']
                    ]);
                }
            }

            $pdo->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Critère créé avec succès',
                'data' => ['id' => $critereId]
            ]);
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la création : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Mettre à jour un critère d'évaluation
     */
    public function updateCritere()
    {
        try {
            // Lire les données depuis POST ou JSON selon le Content-Type
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $input = json_decode(file_get_contents('php://input'), true);
            } else {
                $input = $_POST;
            }

            if (!isset($input['id']) || empty($input['id'])) {
                throw new Exception('ID du critère requis');
            }

            if (!isset($input['libelle']) || empty(trim($input['libelle']))) {
                throw new Exception('Le libellé du critère est requis');
            }

            if (!isset($input['baremes']) || empty($input['baremes'])) {
                throw new Exception('Au moins un barème est requis');
            }

            $pdo = Database::getConnection();

            // Validation des barèmes - vérifier que le total par année ne dépasse pas 20 (en excluant le critère actuel)
            $this->validateBaremesTotaux($pdo, $input['baremes'], $input['id']);

            $pdo->beginTransaction();

            // Mettre à jour le critère
            $stmt = $pdo->prepare("UPDATE critere_evaluation SET lib_critere = ? WHERE id_critere = ?");
            $stmt->execute([trim($input['libelle']), $input['id']]);

            // Supprimer les anciens barèmes
            $stmtDelete = $pdo->prepare("DELETE FROM correspondre WHERE id_critere = ?");
            $stmtDelete->execute([$input['id']]);

            // Insérer les nouveaux barèmes
            $stmtBareme = $pdo->prepare("INSERT INTO correspondre (id_critere, id_annee_acad, bareme) VALUES (?, ?, ?)");

            foreach ($input['baremes'] as $bareme) {
                if (!empty($bareme['annee_id']) && !empty($bareme['bareme'])) {
                    $stmtBareme->execute([
                        $input['id'],
                        $bareme['annee_id'],
                        (int) $bareme['bareme']
                    ]);
                }
            }

            $pdo->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Critère modifié avec succès'
            ]);
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la modification : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Supprimer un critère d'évaluation
     */
    public function deleteCritere()
    {
        try {
            // Lire les données depuis POST ou JSON selon le Content-Type
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') !== false) {
                $input = json_decode(file_get_contents('php://input'), true);
            } else {
                $input = $_POST;
            }

            if (!isset($input['id']) || empty($input['id'])) {
                throw new Exception('ID du critère requis');
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Vérifier si le critère existe
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM critere_evaluation WHERE id_critere = ?");
            $stmt->execute([$input['id']]);
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Critère non trouvé');
            }

            // Supprimer les barèmes associés
            $stmtBaremes = $pdo->prepare("DELETE FROM correspondre WHERE id_critere = ?");
            $stmtBaremes->execute([$input['id']]);

            // Supprimer le critère
            $stmtCritere = $pdo->prepare("DELETE FROM critere_evaluation WHERE id_critere = ?");
            $stmtCritere->execute([$input['id']]);

            $pdo->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Critère supprimé avec succès'
            ]);
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Valider que les totaux des barèmes par année ne dépassent pas 20 points
     * @param PDO $pdo Connexion à la base de données
     * @param array $nouveauxBaremes Nouveaux barèmes à valider
     * @param int|null $critereIdExclure ID du critère à exclure (pour les modifications)
     */
    private function validateBaremesTotaux($pdo, $nouveauxBaremes, $critereIdExclure = null)
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
        $stmt = $pdo->prepare($sql);
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
                    $stmtAnnee = $pdo->prepare("SELECT CONCAT(date_deb, ' - ', date_fin) as lib_annee FROM annee_academique WHERE id_annee_acad = ?");
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
?>