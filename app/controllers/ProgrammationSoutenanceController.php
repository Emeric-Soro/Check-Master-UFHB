<?php

require_once __DIR__ . '/../utils/permissions.php';

class ProgrammationSoutenanceController
{
    /**
     * Récupérer tous les étudiants avec rapport validé (pour affichage et modification)
     */
    public function getEtudiantsForView()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT DISTINCT
                    e.num_etu as id_etudiant,
                    e.nom_etu as nom_etudiant,
                    e.prenom_etu as prenom_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_complet,
                    e.num_etu as matricule_etudiant,
                    e.email_etu as email_etudiant,
                    e.promotion_etu as lib_specialite,
                    -- Récupérer le maître de stage depuis informations_stage
                    ist.encadrant_entreprise as maitre_stage_nom,
                    ist.email_encadrant as maitre_stage_email,
                    -- Récupérer le directeur de mémoire depuis affecter
                    (SELECT CONCAT(ens_dir.prenom_enseignant, ' ', ens_dir.nom_enseignant)
                     FROM affecter af_dir
                     JOIN enseignants ens_dir ON af_dir.id_enseignant = ens_dir.id_enseignant
                     WHERE af_dir.id_rapport = r.id_rapport AND af_dir.role = 'directeur'
                     LIMIT 1) as directeur_nom,
                    (SELECT ens_dir.id_enseignant
                     FROM affecter af_dir
                     JOIN enseignants ens_dir ON af_dir.id_enseignant = ens_dir.id_enseignant
                     WHERE af_dir.id_rapport = r.id_rapport AND af_dir.role = 'directeur'
                     LIMIT 1) as directeur_id,
                    -- Récupérer l'encadreur depuis affecter
                    (SELECT CONCAT(ens_enc.prenom_enseignant, ' ', ens_enc.nom_enseignant)
                     FROM affecter af_enc
                     JOIN enseignants ens_enc ON af_enc.id_enseignant = ens_enc.id_enseignant
                     WHERE af_enc.id_rapport = r.id_rapport AND af_enc.role = 'encadrant'
                     LIMIT 1) as encadreur_nom,
                    (SELECT ens_enc.id_enseignant
                     FROM affecter af_enc
                     JOIN enseignants ens_enc ON af_enc.id_enseignant = ens_enc.id_enseignant
                     WHERE af_enc.id_rapport = r.id_rapport AND af_enc.role = 'encadrant'
                     LIMIT 1) as encadreur_id,
                    -- Statut de programmation
                    CASE 
                        WHEN p.num_etud IS NOT NULL THEN 'programmed'
                        ELSE 'available'
                    END as statut_programmation
                FROM etudiants e
                INNER JOIN rapport_etudiants r ON e.num_etu = r.num_etu
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                LEFT JOIN informations_stage ist ON e.num_etu = ist.num_etu
                LEFT JOIN programmer p ON e.num_etu = p.num_etud
                WHERE r.etape_validation = 'valide' 
                AND v.decision_validation = 'valider'
                ORDER BY e.nom_etu, e.prenom_etu
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getEtudiantsForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les étudiants disponibles pour nouvelle programmation (non encore programmés)
     */
    public function getEtudiantsDisponiblesForView()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT DISTINCT
                    e.num_etu as id_etudiant,
                    e.nom_etu as nom_etudiant,
                    e.prenom_etu as prenom_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_complet,
                    e.num_etu as matricule_etudiant,
                    e.email_etu as email_etudiant,
                    e.promotion_etu as lib_specialite,
                    -- Récupérer le maître de stage depuis informations_stage
                    ist.encadrant_entreprise as maitre_stage_nom,
                    ist.email_encadrant as maitre_stage_email,
                    -- Récupérer le directeur de mémoire depuis affecter
                    (SELECT CONCAT(ens_dir.prenom_enseignant, ' ', ens_dir.nom_enseignant)
                     FROM affecter af_dir
                     JOIN enseignants ens_dir ON af_dir.id_enseignant = ens_dir.id_enseignant
                     WHERE af_dir.id_rapport = r.id_rapport AND af_dir.role = 'directeur'
                     LIMIT 1) as directeur_nom,
                    (SELECT ens_dir.id_enseignant
                     FROM affecter af_dir
                     JOIN enseignants ens_dir ON af_dir.id_enseignant = ens_dir.id_enseignant
                     WHERE af_dir.id_rapport = r.id_rapport AND af_dir.role = 'directeur'
                     LIMIT 1) as directeur_id,
                    -- Récupérer l'encadreur depuis affecter
                    (SELECT CONCAT(ens_enc.prenom_enseignant, ' ', ens_enc.nom_enseignant)
                     FROM affecter af_enc
                     JOIN enseignants ens_enc ON af_enc.id_enseignant = ens_enc.id_enseignant
                     WHERE af_enc.id_rapport = r.id_rapport AND af_enc.role = 'encadrant'
                     LIMIT 1) as encadreur_nom,
                    (SELECT ens_enc.id_enseignant
                     FROM affecter af_enc
                     JOIN enseignants ens_enc ON af_enc.id_enseignant = ens_enc.id_enseignant
                     WHERE af_enc.id_rapport = r.id_rapport AND af_enc.role = 'encadrant'
                     LIMIT 1) as encadreur_id
                FROM etudiants e
                INNER JOIN rapport_etudiants r ON e.num_etu = r.num_etu
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                LEFT JOIN informations_stage ist ON e.num_etu = ist.num_etu
                LEFT JOIN programmer p ON e.num_etu = p.num_etud
                WHERE r.etape_validation = 'valide' 
                AND v.decision_validation = 'valider'
                AND p.num_etud IS NULL
                ORDER BY e.nom_etu, e.prenom_etu
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
     * Récupérer tous les enseignants pour PHP (sans header JSON)
     */
    public function getEnseignantsForView()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    e.id_enseignant,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    CONCAT(e.prenom_enseignant, ' ', e.nom_enseignant) as nom_complet,
                    e.mail_enseignant as email_enseignant,
                    COALESCE(f.lib_fonction, '') as lib_fonction
                FROM enseignants e
                LEFT JOIN occuper o ON e.id_enseignant = o.id_enseignant
                LEFT JOIN fonction f ON o.id_fonction = f.id_fonction
                WHERE 1=1
                ORDER BY e.nom_enseignant, e.prenom_enseignant
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getEnseignantsForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les professeurs titulaires pour PHP (sans header JSON)
     */
    public function getProfesseursTitulairesForView()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    e.id_enseignant,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    CONCAT(e.prenom_enseignant, ' ', e.nom_enseignant) as nom_complet,
                    e.mail_enseignant as email_enseignant,
                    f.lib_fonction
                FROM enseignants e
                INNER JOIN occuper o ON e.id_enseignant = o.id_enseignant
                INNER JOIN fonction f ON o.id_fonction = f.id_fonction
                WHERE f.lib_fonction = 'Professeur titulaire'
                ORDER BY e.nom_enseignant, e.prenom_enseignant
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getProfesseursTitulairesForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer toutes les attributions pour PHP (sans header JSON)
     */
    public function getAttributionsForView()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    p.id_programmation as id_attribution,
                    p.theme_soutenance,
                    p.date_soutenance as date_creation,
                    -- Étudiant
                    e.num_etu as id_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                    e.num_etu as matricule_etudiant,
                    -- Informations du jury
                    p.num_jury,
                    -- Récupérer les membres du jury avec leurs rôles
                    (SELECT ens1.id_enseignant 
                     FROM composer_jury cj1 
                     JOIN enseignants ens1 ON cj1.id_enseignant = ens1.id_enseignant 
                     JOIN roles_jury r1 ON cj1.id_qualite_jury = r1.id_role_jury 
                     WHERE cj1.num_jury = p.num_jury AND r1.lib_role = 'Président du jury' 
                     LIMIT 1) as president_id,
                    (SELECT CONCAT(ens1.prenom_enseignant, ' ', ens1.nom_enseignant) 
                     FROM composer_jury cj1 
                     JOIN enseignants ens1 ON cj1.id_enseignant = ens1.id_enseignant 
                     JOIN roles_jury r1 ON cj1.id_qualite_jury = r1.id_role_jury 
                     WHERE cj1.num_jury = p.num_jury AND r1.lib_role = 'Président du jury' 
                     LIMIT 1) as president_nom,
                    (SELECT ens2.id_enseignant 
                     FROM composer_jury cj2 
                     JOIN enseignants ens2 ON cj2.id_enseignant = ens2.id_enseignant 
                     JOIN roles_jury r2 ON cj2.id_qualite_jury = r2.id_role_jury 
                     WHERE cj2.num_jury = p.num_jury AND r2.lib_role = 'Examinateur' 
                     LIMIT 1) as examinateur_id,
                    (SELECT CONCAT(ens2.prenom_enseignant, ' ', ens2.nom_enseignant) 
                     FROM composer_jury cj2 
                     JOIN enseignants ens2 ON cj2.id_enseignant = ens2.id_enseignant 
                     JOIN roles_jury r2 ON cj2.id_qualite_jury = r2.id_role_jury 
                     WHERE cj2.num_jury = p.num_jury AND r2.lib_role = 'Examinateur' 
                     LIMIT 1) as examinateur_nom,
                    (SELECT ens3.id_enseignant 
                     FROM composer_jury cj3 
                     JOIN enseignants ens3 ON cj3.id_enseignant = ens3.id_enseignant 
                     JOIN roles_jury r3 ON cj3.id_qualite_jury = r3.id_role_jury 
                     WHERE cj3.num_jury = p.num_jury AND r3.lib_role = 'Directeur de mémoire' 
                     LIMIT 1) as directeur_id,
                    (SELECT CONCAT(ens3.prenom_enseignant, ' ', ens3.nom_enseignant) 
                     FROM composer_jury cj3 
                     JOIN enseignants ens3 ON cj3.id_enseignant = ens3.id_enseignant 
                     JOIN roles_jury r3 ON cj3.id_qualite_jury = r3.id_role_jury 
                     WHERE cj3.num_jury = p.num_jury AND r3.lib_role = 'Directeur de mémoire' 
                     LIMIT 1) as directeur_nom,
                    (SELECT ens4.id_enseignant 
                     FROM composer_jury cj4 
                     JOIN enseignants ens4 ON cj4.id_enseignant = ens4.id_enseignant 
                     JOIN roles_jury r4 ON cj4.id_qualite_jury = r4.id_role_jury 
                     WHERE cj4.num_jury = p.num_jury AND r4.lib_role = 'Encadrant' 
                     LIMIT 1) as encadreur_id,
                    (SELECT CONCAT(ens4.prenom_enseignant, ' ', ens4.nom_enseignant) 
                     FROM composer_jury cj4 
                     JOIN enseignants ens4 ON cj4.id_enseignant = ens4.id_enseignant 
                     JOIN roles_jury r4 ON cj4.id_qualite_jury = r4.id_role_jury 
                     WHERE cj4.num_jury = p.num_jury AND r4.lib_role = 'Encadrant' 
                     LIMIT 1) as encadreur_nom,
                    -- Maître de stage récupéré depuis informations_stage
                    NULL as maitre_stage_id,
                    ist.encadrant_entreprise as maitre_stage_nom
                FROM programmer p
                LEFT JOIN etudiants e ON p.num_etud = e.num_etu
                LEFT JOIN informations_stage ist ON e.num_etu = ist.num_etu
                ORDER BY p.date_soutenance DESC, p.heure_soutenance DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getAttributionsForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer tous les étudiants disponibles (avec rapports validés par la commission)
     */
    public function getEtudiants()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT DISTINCT
                    e.num_etu as id_etudiant,
                    e.nom_etu as nom_etudiant,
                    e.prenom_etu as prenom_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_complet,
                    e.num_etu as matricule_etudiant,
                    e.email_etu as email_etudiant,
                    e.promotion_etu as lib_specialite,
                    -- Récupérer le maître de stage depuis informations_stage
                    ist.encadrant_entreprise as maitre_stage_nom,
                    ist.email_encadrant as maitre_stage_email
                FROM etudiants e
                INNER JOIN rapport_etudiants r ON e.num_etu = r.num_etu
                INNER JOIN valider v ON r.id_rapport = v.id_rapport
                LEFT JOIN informations_stage ist ON e.num_etu = ist.num_etu
                WHERE r.etape_validation = 'valide' 
                AND v.decision_validation = 'valider'
                ORDER BY e.nom_etu, e.prenom_etu
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $etudiants
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des étudiants : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer tous les enseignants disponibles
     */
    public function getEnseignants()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    e.id_enseignant,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    CONCAT(e.prenom_enseignant, ' ', e.nom_enseignant) as nom_complet,
                    e.mail_enseignant as email_enseignant,
                    COALESCE(f.lib_fonction, '') as lib_fonction
                FROM enseignants e
                LEFT JOIN occuper o ON e.id_enseignant = o.id_enseignant
                LEFT JOIN fonction f ON o.id_fonction = f.id_fonction
                WHERE 1=1
                ORDER BY e.nom_enseignant, e.prenom_enseignant
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $enseignants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $enseignants
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des enseignants : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer seulement les professeurs titulaires pour le poste de président
     */
    public function getProfesseursTitulaires()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    e.id_enseignant,
                    e.nom_enseignant,
                    e.prenom_enseignant,
                    CONCAT(e.prenom_enseignant, ' ', e.nom_enseignant) as nom_complet,
                    e.mail_enseignant as email_enseignant,
                    f.lib_fonction
                FROM enseignants e
                INNER JOIN occuper o ON e.id_enseignant = o.id_enseignant
                INNER JOIN fonction f ON o.id_fonction = f.id_fonction
                WHERE f.lib_fonction = 'Professeur titulaire'
                ORDER BY e.nom_enseignant, e.prenom_enseignant
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $professeurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $professeurs
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des professeurs titulaires : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Récupérer toutes les attributions de jury (utilise composer_jury)
     */
    public function getAttributions()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    p.id_programmation as id_attribution,
                    p.theme_soutenance,
                    p.date_soutenance as date_creation,
                    -- Étudiant
                    e.num_etu as id_etudiant,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                    e.num_etu as matricule_etudiant,
                    -- Informations du jury
                    p.num_jury,
                    -- Récupérer les membres du jury avec leurs rôles
                    (SELECT ens1.id_enseignant 
                     FROM composer_jury cj1 
                     JOIN enseignants ens1 ON cj1.id_enseignant = ens1.id_enseignant 
                     JOIN roles_jury r1 ON cj1.id_qualite_jury = r1.id_role_jury 
                     WHERE cj1.num_jury = p.num_jury AND r1.lib_role = 'Président du jury' 
                     LIMIT 1) as president_id,
                    (SELECT CONCAT(ens1.prenom_enseignant, ' ', ens1.nom_enseignant) 
                     FROM composer_jury cj1 
                     JOIN enseignants ens1 ON cj1.id_enseignant = ens1.id_enseignant 
                     JOIN roles_jury r1 ON cj1.id_qualite_jury = r1.id_role_jury 
                     WHERE cj1.num_jury = p.num_jury AND r1.lib_role = 'Président du jury' 
                     LIMIT 1) as president_nom,
                    (SELECT ens2.id_enseignant 
                     FROM composer_jury cj2 
                     JOIN enseignants ens2 ON cj2.id_enseignant = ens2.id_enseignant 
                     JOIN roles_jury r2 ON cj2.id_qualite_jury = r2.id_role_jury 
                     WHERE cj2.num_jury = p.num_jury AND r2.lib_role = 'Examinateur' 
                     LIMIT 1) as examinateur_id,
                    (SELECT CONCAT(ens2.prenom_enseignant, ' ', ens2.nom_enseignant) 
                     FROM composer_jury cj2 
                     JOIN enseignants ens2 ON cj2.id_enseignant = ens2.id_enseignant 
                     JOIN roles_jury r2 ON cj2.id_qualite_jury = r2.id_role_jury 
                     WHERE cj2.num_jury = p.num_jury AND r2.lib_role = 'Examinateur' 
                     LIMIT 1) as examinateur_nom,
                    (SELECT ens3.id_enseignant 
                     FROM composer_jury cj3 
                     JOIN enseignants ens3 ON cj3.id_enseignant = ens3.id_enseignant 
                     JOIN roles_jury r3 ON cj3.id_qualite_jury = r3.id_role_jury 
                     WHERE cj3.num_jury = p.num_jury AND r3.lib_role = 'Directeur de mémoire' 
                     LIMIT 1) as directeur_id,
                    (SELECT CONCAT(ens3.prenom_enseignant, ' ', ens3.nom_enseignant) 
                     FROM composer_jury cj3 
                     JOIN enseignants ens3 ON cj3.id_enseignant = ens3.id_enseignant 
                     JOIN roles_jury r3 ON cj3.id_qualite_jury = r3.id_role_jury 
                     WHERE cj3.num_jury = p.num_jury AND r3.lib_role = 'Directeur de mémoire' 
                     LIMIT 1) as directeur_nom,
                    (SELECT ens4.id_enseignant 
                     FROM composer_jury cj4 
                     JOIN enseignants ens4 ON cj4.id_enseignant = ens4.id_enseignant 
                     JOIN roles_jury r4 ON cj4.id_qualite_jury = r4.id_role_jury 
                     WHERE cj4.num_jury = p.num_jury AND r4.lib_role = 'Encadrant' 
                     LIMIT 1) as encadreur_id,
                    (SELECT CONCAT(ens4.prenom_enseignant, ' ', ens4.nom_enseignant) 
                     FROM composer_jury cj4 
                     JOIN enseignants ens4 ON cj4.id_enseignant = ens4.id_enseignant 
                     JOIN roles_jury r4 ON cj4.id_qualite_jury = r4.id_role_jury 
                     WHERE cj4.num_jury = p.num_jury AND r4.lib_role = 'Encadrant' 
                     LIMIT 1) as encadreur_nom,
                    -- Maître de stage récupéré depuis informations_stage (pas besoin de composer_jury)
                    NULL as maitre_stage_id,
                    ist.encadrant_entreprise as maitre_stage_nom
                FROM programmer p
                LEFT JOIN etudiants e ON p.num_etud = e.num_etu
                LEFT JOIN informations_stage ist ON e.num_etu = ist.num_etu
                ORDER BY p.date_soutenance DESC, p.heure_soutenance DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $attributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $attributions
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors du chargement des attributions : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Créer une nouvelle attribution de jury
     */
    public function createAttribution()
    {
        // Vérifier la permission CREATE
        if (!hasPermission('programmation_soutenance', 'CREATE')) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de créer des attributions.'
            ]);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validation des données requises
            if (!isset($input['id_etudiant']) || empty($input['id_etudiant'])) {
                throw new Exception('L\'étudiant est requis');
            }

            if (!isset($input['theme_soutenance']) || empty(trim($input['theme_soutenance']))) {
                throw new Exception('Le thème de soutenance est requis');
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Vérifier si l'étudiant a déjà une programmation
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM programmer WHERE num_etud = ?");
            $checkStmt->execute([$input['id_etudiant']]);
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception('Cet étudiant a déjà une programmation de soutenance');
            }

            // Générer un nouveau numéro de jury
            $juryStmt = $pdo->prepare("SELECT COALESCE(MAX(num_jury), 0) + 1 as next_jury FROM programmer");
            $juryStmt->execute();
            $numJury = $juryStmt->fetch(PDO::FETCH_ASSOC)['next_jury'];

            // Insérer la programmation (sans salle pour l'instant)
            $sql = "
                INSERT INTO programmer (
                    num_etud, num_jury, theme_soutenance, 
                    date_soutenance, heure_soutenance
                ) VALUES (?, ?, ?, CURDATE(), CURTIME())
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['id_etudiant'],
                $numJury,
                trim($input['theme_soutenance'])
            ]);

            // Insérer les membres du jury avec leurs rôles
            $jurySql = "INSERT INTO composer_jury (num_jury, id_enseignant, id_qualite_jury, date_composer_jury) VALUES (?, ?, ?, UNIX_TIMESTAMP())";
            $juryInsertStmt = $pdo->prepare($jurySql);

            // Récupérer les IDs des rôles et insérer les membres du jury
            $roleStmt = $pdo->prepare("SELECT id_role_jury FROM roles_jury WHERE lib_role = ?");

            // Président
            if (!empty($input['president_id'])) {
                $roleStmt->execute(['Président du jury']);
                $roleResult = $roleStmt->fetch(PDO::FETCH_ASSOC);
                if ($roleResult) {
                    $juryInsertStmt->execute([$numJury, $input['president_id'], $roleResult['id_role_jury']]);
                }
            }

            // Examinateur
            if (!empty($input['examinateur_id'])) {
                $roleStmt->execute(['Examinateur']);
                $roleResult = $roleStmt->fetch(PDO::FETCH_ASSOC);
                if ($roleResult) {
                    $juryInsertStmt->execute([$numJury, $input['examinateur_id'], $roleResult['id_role_jury']]);
                }
            }

            // Directeur de mémoire
            if (!empty($input['directeur_id'])) {
                $roleStmt->execute(['Directeur de mémoire']);
                $roleResult = $roleStmt->fetch(PDO::FETCH_ASSOC);
                if ($roleResult) {
                    $juryInsertStmt->execute([$numJury, $input['directeur_id'], $roleResult['id_role_jury']]);
                }
            }

            // Encadreur
            if (!empty($input['encadreur_id'])) {
                $roleStmt->execute(['Encadrant']);
                $roleResult = $roleStmt->fetch(PDO::FETCH_ASSOC);
                if ($roleResult) {
                    $juryInsertStmt->execute([$numJury, $input['encadreur_id'], $roleResult['id_role_jury']]);
                }
            }

            // Maître de stage : automatiquement récupéré depuis informations_stage
            // Pas besoin de l'ajouter au jury manuellement car il n'est pas enseignant

            $attributionId = $pdo->lastInsertId();
            $pdo->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Attribution créée avec succès',
                'data' => ['id' => $attributionId]
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
     * Mettre à jour une attribution de jury
     */
    public function updateAttribution()
    {
        // Vérifier la permission UPDATE
        if (!hasPermission('programmation_soutenance', 'UPDATE')) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de modifier des attributions.'
            ]);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['id']) || empty($input['id'])) {
                throw new Exception('ID de l\'attribution requis');
            }

            if (!isset($input['theme_soutenance']) || empty(trim($input['theme_soutenance']))) {
                throw new Exception('Le thème de soutenance est requis');
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Récupérer le numéro de jury associé à cette programmation
            $juryStmt = $pdo->prepare("SELECT num_jury FROM programmer WHERE id_programmation = ?");
            $juryStmt->execute([$input['id']]);
            $result = $juryStmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                throw new Exception('Programmation non trouvée');
            }

            $numJury = $result['num_jury'];

            // Mettre à jour le thème de soutenance
            $sql = "UPDATE programmer SET theme_soutenance = ? WHERE id_programmation = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([trim($input['theme_soutenance']), $input['id']]);

            // Supprimer les anciens membres du jury
            $deleteJuryStmt = $pdo->prepare("DELETE FROM composer_jury WHERE num_jury = ?");
            $deleteJuryStmt->execute([$numJury]);

            // Réinsérer les nouveaux membres du jury
            $jurySql = "INSERT INTO composer_jury (num_jury, id_enseignant, id_qualite_jury, date_composer_jury) VALUES (?, ?, ?, UNIX_TIMESTAMP())";
            $juryInsertStmt = $pdo->prepare($jurySql);

            // Récupérer les IDs des rôles et insérer les membres du jury
            $roleStmt = $pdo->prepare("SELECT id_role_jury FROM roles_jury WHERE lib_role = ?");

            // Président
            if (!empty($input['president_id'])) {
                $roleStmt->execute(['Président du jury']);
                $roleResult = $roleStmt->fetch(PDO::FETCH_ASSOC);
                if ($roleResult) {
                    $juryInsertStmt->execute([$numJury, $input['president_id'], $roleResult['id_role_jury']]);
                }
            }

            // Examinateur
            if (!empty($input['examinateur_id'])) {
                $roleStmt->execute(['Examinateur']);
                $roleResult = $roleStmt->fetch(PDO::FETCH_ASSOC);
                if ($roleResult) {
                    $juryInsertStmt->execute([$numJury, $input['examinateur_id'], $roleResult['id_role_jury']]);
                }
            }

            // Directeur de mémoire
            if (!empty($input['directeur_id'])) {
                $roleStmt->execute(['Directeur de mémoire']);
                $roleResult = $roleStmt->fetch(PDO::FETCH_ASSOC);
                if ($roleResult) {
                    $juryInsertStmt->execute([$numJury, $input['directeur_id'], $roleResult['id_role_jury']]);
                }
            }

            // Encadreur
            if (!empty($input['encadreur_id'])) {
                $roleStmt->execute(['Encadrant']);
                $roleResult = $roleStmt->fetch(PDO::FETCH_ASSOC);
                if ($roleResult) {
                    $juryInsertStmt->execute([$numJury, $input['encadreur_id'], $roleResult['id_role_jury']]);
                }
            }

            // Maître de stage : automatiquement récupéré depuis informations_stage
            // Pas besoin de l'ajouter au jury manuellement car il n'est pas enseignant

            $pdo->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Attribution mise à jour avec succès'
            ]);
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Supprimer une attribution de jury
     */
    public function deleteAttribution()
    {
        // Vérifier la permission DELETE
        if (!hasPermission('programmation_soutenance', 'DELETE')) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de supprimer des attributions.'
            ]);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (!isset($input['id']) || empty($input['id'])) {
                throw new Exception('ID de l\'attribution requis');
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Récupérer le numéro de jury associé à cette programmation
            $juryStmt = $pdo->prepare("SELECT num_jury FROM programmer WHERE id_programmation = ?");
            $juryStmt->execute([$input['id']]);
            $result = $juryStmt->fetch(PDO::FETCH_ASSOC);

            if ($result) {
                $numJury = $result['num_jury'];
                // Supprimer d'abord les membres du jury
                $deleteJuryStmt = $pdo->prepare("DELETE FROM composer_jury WHERE num_jury = ?");
                $deleteJuryStmt->execute([$numJury]);
            }

            // Supprimer la programmation
            $stmt = $pdo->prepare("DELETE FROM programmer WHERE id_programmation = ?");
            $stmt->execute([$input['id']]);

            $pdo->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Attribution supprimée avec succès'
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
}
?>