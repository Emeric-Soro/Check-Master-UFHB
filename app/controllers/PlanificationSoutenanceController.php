<?php

class PlanificationSoutenanceController
{
    /**
     * Vérifier si un étudiant a déjà une planification complète
     */
    private function etudiantDejaPlannifie($numEtu, $excludeId = null)
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT COUNT(*) as count
                FROM programmer 
                WHERE num_etud = ?
                AND id_salle IS NOT NULL 
                AND date_soutenance IS NOT NULL 
                AND heure_soutenance IS NOT NULL
            ";

            $params = [$numEtu];

            if ($excludeId) {
                $sql .= " AND id_programmation != ?";
                $params[] = $excludeId;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        } catch (Exception $e) {
            error_log('Erreur etudiantDejaPlannifie: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer tous les étudiants qui ont une attribution de jury
     */
    public function getEtudiantsAvecJuryForView()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT DISTINCT
                    p.id_programmation,
                    p.num_etud as id_etudiant,
                    e.nom_etu as nom_etudiant,
                    e.prenom_etu as prenom_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_complet,
                    e.num_carte_etud as matricule_etudiant,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle,
                    s.lib_salle as nom_salle,
                    CASE 
                        WHEN p.id_salle IS NOT NULL AND p.date_soutenance IS NOT NULL AND p.heure_soutenance IS NOT NULL THEN 'complete'
                        WHEN p.num_jury IS NOT NULL THEN 'partial'
                        ELSE 'none'
                    END as statut_planification
                FROM programmer p
                INNER JOIN etudiants e ON p.num_etud = e.num_carte_etud
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                WHERE p.num_jury IS NOT NULL
                ORDER BY e.nom_etu ASC, e.prenom_etu ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getEtudiantsAvecJuryForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les étudiants disponibles pour nouvelle planification (non encore planifiés)
     */
    public function getEtudiantsDisponiblesForView()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT DISTINCT
                    p.id_programmation,
                    p.num_etud as id_etudiant,
                    e.nom_etu as nom_etudiant,
                    e.prenom_etu as prenom_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_complet,
                    e.num_carte_etud as matricule_etudiant,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle,
                    s.lib_salle as nom_salle,
                    CASE 
                        WHEN p.id_salle IS NOT NULL AND p.date_soutenance IS NOT NULL AND p.heure_soutenance IS NOT NULL THEN 'complete'
                        WHEN p.num_jury IS NOT NULL THEN 'partial'
                        ELSE 'none'
                    END as statut_planification
                FROM programmer p
                INNER JOIN etudiants e ON p.num_etud = e.num_carte_etud
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                WHERE p.num_jury IS NOT NULL
                AND (p.id_salle IS NULL OR p.date_soutenance IS NULL OR p.heure_soutenance IS NULL)
                ORDER BY e.nom_etu ASC, e.prenom_etu ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getEtudiantsDisponiblesForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer toutes les salles disponibles
     */
    public function getSallesForView()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    id_salle,
                    lib_salle
                FROM salles
                ORDER BY lib_salle
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getSallesForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer toutes les planifications pour affichage
     */
    public function getPlanificationsForView()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    p.id_programmation,
                    p.num_etud as id_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                    e.num_carte_etud as matricule_etudiant,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle,
                    s.lib_salle as nom_salle
                FROM programmer p
                INNER JOIN etudiants e ON p.num_etud = e.num_carte_etud
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                WHERE p.id_salle IS NOT NULL 
                AND p.date_soutenance IS NOT NULL 
                AND p.heure_soutenance IS NOT NULL
                ORDER BY p.date_soutenance ASC, p.heure_soutenance ASC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getPlanificationsForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Planifier une soutenance (mettre à jour salle, date, heure) - Version POST
     */
    public function planifierSoutenance()
    {
        try {
            // Récupérer les données depuis $_POST
            $idProgrammation = $_POST['id_programmation'] ?? null;
            $idSalle = $_POST['id_salle'] ?? null;
            $dateSoutenance = $_POST['date_soutenance'] ?? null;
            $heureSoutenance = $_POST['heure_soutenance'] ?? null;
            $editId = $_POST['edit_id'] ?? null; // Pour détecter le mode édition

            // Validation des données requises
            if (empty($idProgrammation)) {
                throw new Exception('ID de programmation requis');
            }

            if (empty($idSalle)) {
                throw new Exception('Salle requise');
            }

            if (empty($dateSoutenance)) {
                throw new Exception('Date de soutenance requise');
            }

            if (empty($heureSoutenance)) {
                throw new Exception('Heure de soutenance requise');
            }

            // Validation de la date (ne doit pas être dans le passé)
            $selectedDateTime = new DateTime($dateSoutenance . ' ' . $heureSoutenance);
            $now = new DateTime();

            if ($selectedDateTime <= $now) {
                throw new Exception('La date et l\'heure de soutenance doivent être dans le futur');
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Déterminer l'ID à utiliser pour la vérification des conflits
            $conflictCheckId = $editId ? $editId : $idProgrammation;

            // Récupérer le numéro d'étudiant pour les vérifications
            $etudiantStmt = $pdo->prepare("SELECT num_etud FROM programmer WHERE id_programmation = ?");
            $etudiantStmt->execute([$conflictCheckId]);
            $etudiantData = $etudiantStmt->fetch(PDO::FETCH_ASSOC);

            if (!$etudiantData) {
                throw new Exception('Programmation non trouvée');
            }

            // Vérifier si l'étudiant n'a pas déjà une planification complète (sauf en mode édition)
            if ($this->etudiantDejaPlannifie($etudiantData['num_etud'], $editId)) {
                throw new Exception('Cet étudiant a déjà une soutenance complètement planifiée');
            }

            // Vérifier les conflits de salle
            $conflictStmt = $pdo->prepare("
                SELECT COUNT(*) as conflicts
                FROM programmer 
                WHERE id_salle = ? 
                AND date_soutenance = ? 
                AND heure_soutenance = ?
                AND id_programmation != ?
            ");
            $conflictStmt->execute([
                $idSalle,
                $dateSoutenance,
                $heureSoutenance,
                $conflictCheckId
            ]);

            if ($conflictStmt->fetch(PDO::FETCH_ASSOC)['conflicts'] > 0) {
                throw new Exception('Conflit : Cette salle est déjà occupée à cette date et heure');
            }

            // Déterminer l'ID à utiliser pour la mise à jour
            $updateId = $editId ? $editId : $idProgrammation;

            // Mettre à jour la programmation
            $sql = "
                UPDATE programmer 
                SET id_salle = ?, 
                    date_soutenance = ?, 
                    heure_soutenance = ?
                WHERE id_programmation = ?
            ";

            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $idSalle,
                $dateSoutenance,
                $heureSoutenance,
                $updateId
            ]);

            if (!$success) {
                throw new Exception('Erreur lors de la mise à jour en base de données');
            }

            $pdo->commit();

            $message = $editId ? 'Planification modifiée avec succès' : 'Soutenance planifiée avec succès';

            return [
                'success' => true,
                'message' => $message
            ];
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Erreur lors de la planification : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Supprimer une planification (remettre salle, date, heure à NULL) - Version POST
     */
    public function supprimerPlanification()
    {
        try {
            $id = $_POST['id_programmation'] ?? null;

            if (empty($id)) {
                throw new Exception('ID de programmation requis');
            }

            $pdo = Database::getConnection();

            // Remettre à NULL la salle, date et heure (garder l'attribution du jury)
            $sql = "
                UPDATE programmer 
                SET id_salle = NULL, 
                    date_soutenance = NULL, 
                    heure_soutenance = NULL
                WHERE id_programmation = ?
            ";

            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$id]);

            if (!$success) {
                throw new Exception('Erreur lors de la suppression en base de données');
            }

            return [
                'success' => true,
                'message' => 'Planification supprimée avec succès'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer une planification par ID pour modification
     */
    public function getPlanification()
    {
        try {
            $id = $_GET['id'] ?? null;

            if (!$id) {
                throw new Exception('ID requis');
            }

            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    p.id_programmation,
                    p.num_etud as id_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    p.id_salle
                FROM programmer p
                INNER JOIN etudiants e ON p.num_etud = e.num_carte_etud
                WHERE p.id_programmation = ?
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $planification = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$planification) {
                throw new Exception('Planification non trouvée');
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $planification
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ]);
        }
    }
}
?>