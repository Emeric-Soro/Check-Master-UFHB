<?php
require_once __DIR__ . '/../config/database.php';

class EvaluationSoutenanceController
{
    /**
     * Récupérer toutes les soutenances programmées pour évaluation
     */
    public function getSoutenancesProgrammeesForView()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    p.id_programmation,
                    p.theme_soutenance,
                    p.date_soutenance,
                    p.heure_soutenance,
                    -- Étudiant
                    e.num_etu,
                    CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                    e.num_etu as matricule_etudiant,
                    e.promotion_etu,
                    -- Salle
                    s.lib_salle as nom_salle,
                    -- Jury complet avec rôles
                    (SELECT CONCAT(ens1.prenom_enseignant, ' ', ens1.nom_enseignant) 
                     FROM composer_jury cj1 
                     JOIN enseignants ens1 ON cj1.id_enseignant = ens1.id_enseignant 
                     JOIN roles_jury r1 ON cj1.id_qualite_jury = r1.id_role_jury 
                     WHERE cj1.num_jury = p.num_jury AND r1.lib_role = 'Président du jury' 
                     LIMIT 1) as president_nom,
                    (SELECT CONCAT(ens2.prenom_enseignant, ' ', ens2.nom_enseignant) 
                     FROM composer_jury cj2 
                     JOIN enseignants ens2 ON cj2.id_enseignant = ens2.id_enseignant 
                     JOIN roles_jury r2 ON cj2.id_qualite_jury = r2.id_role_jury 
                     WHERE cj2.num_jury = p.num_jury AND r2.lib_role = 'Examinateur' 
                     LIMIT 1) as examinateur_nom,
                    (SELECT CONCAT(ens3.prenom_enseignant, ' ', ens3.nom_enseignant) 
                     FROM composer_jury cj3 
                     JOIN enseignants ens3 ON cj3.id_enseignant = ens3.id_enseignant 
                     JOIN roles_jury r3 ON cj3.id_qualite_jury = r3.id_role_jury 
                     WHERE cj3.num_jury = p.num_jury AND r3.lib_role = 'Directeur de mémoire' 
                     LIMIT 1) as directeur_nom,
                    (SELECT CONCAT(ens4.prenom_enseignant, ' ', ens4.nom_enseignant) 
                     FROM composer_jury cj4 
                     JOIN enseignants ens4 ON cj4.id_enseignant = ens4.id_enseignant 
                     JOIN roles_jury r4 ON cj4.id_qualite_jury = r4.id_role_jury 
                     WHERE cj4.num_jury = p.num_jury AND r4.lib_role = 'Encadrant' 
                     LIMIT 1) as encadreur_nom,
                    -- Maître de stage
                    ist.encadrant_entreprise as maitre_stage_nom,
                    -- Vérifier si déjà évalué et calculer la note finale (somme des notes)
                    (SELECT COUNT(*) FROM evaluer ev WHERE ev.num_etudiant = e.num_etu) as est_evalue,
                    (SELECT SUM(ev.note) FROM evaluer ev WHERE ev.num_etudiant = e.num_etu) as note_finale
                FROM programmer p
                INNER JOIN etudiants e ON p.num_etud = e.num_etu
                LEFT JOIN salles s ON p.id_salle = s.id_salle
                LEFT JOIN informations_stage ist ON e.num_etu = ist.num_etu
                WHERE p.id_salle IS NOT NULL 
                AND p.date_soutenance IS NOT NULL 
                AND p.heure_soutenance IS NOT NULL
                ORDER BY p.date_soutenance DESC, p.heure_soutenance DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getSoutenancesProgrammeesForView: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer l'année académique courante
     */
    public function getAnneeAcademiqueCourante()
    {
        try {
            $pdo = Database::getConnection();
            $dateActuelle = date('Y-m-d');

            $sql = "
                SELECT 
                    id_annee_acad,
                    date_deb,
                    date_fin
                FROM annee_academique
                WHERE ? BETWEEN date_deb AND date_fin
                ORDER BY date_deb DESC
                LIMIT 1
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$dateActuelle]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si aucune année académique courante, prendre la plus récente
            if (!$result) {
                $sql = "
                    SELECT 
                        id_annee_acad,
                        date_deb,
                        date_fin
                    FROM annee_academique
                    ORDER BY date_deb DESC
                    LIMIT 1
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            return $result;
        } catch (Exception $e) {
            error_log('Erreur getAnneeAcademiqueCourante: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer les critères d'évaluation avec barèmes pour l'année académique courante
     */
    public function getCriteresEvaluation()
    {
        try {
            $pdo = Database::getConnection();
            $anneeAcademique = $this->getAnneeAcademiqueCourante();

            if (!$anneeAcademique) {
                throw new Exception('Aucune année académique trouvée');
            }

            $sql = "
                SELECT DISTINCT
                    c.id_critere,
                    c.lib_critere,
                    cor.bareme as bareme_max
                FROM critere_evaluation c
                INNER JOIN correspondre cor ON c.id_critere = cor.id_critere 
                    AND cor.id_annee_acad = ?
                ORDER BY c.id_critere
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$anneeAcademique['id_annee_acad']]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getCriteresEvaluation: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les années académiques disponibles
     */
    public function getAnneesAcademiques()
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    id_annee_acad,
                    date_deb,
                    date_fin,
                    CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) as lib_annee
                FROM annee_academique
                ORDER BY date_deb DESC
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getAnneesAcademiques: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer une évaluation existante
     */
    public function getEvaluationExistante($numEtu)
    {
        try {
            $pdo = Database::getConnection();

            $sql = "
                SELECT 
                    e.id_critere,
                    e.note,
                    e.date_eval,
                    c.lib_critere
                FROM evaluer e
                JOIN critere_evaluation c ON e.id_critere = c.id_critere
                WHERE e.num_etudiant = ?
                ORDER BY e.date_eval DESC, e.id_critere
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$numEtu]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Erreur getEvaluationExistante: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Enregistrer une évaluation de soutenance
     */
    public function enregistrerEvaluation()
    {
        // Vérifier la permission CREATE pour enregistrer une évaluation
        if (!hasPermission('evaluation_soutenance', 'CREATE')) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission d\'enregistrer des évaluations.'
            ]);
            return;
        }
        
        try {
            // Récupérer les données POST
            $numEtu = $_POST['num_etu'] ?? null;
            $commentaireGeneral = $_POST['commentaire_general'] ?? '';
            $criteres = $_POST['criteres'] ?? [];
            $idAnneeAcad = $_POST['id_annee_acad'] ?? null;

            // Validation
            if (empty($numEtu)) {
                throw new Exception('Numéro étudiant requis');
            }

            if (empty($criteres)) {
                throw new Exception('Au moins un critère doit être évalué');
            }

            $pdo = Database::getConnection();
            $pdo->beginTransaction();

            // Récupérer l'année académique
            if (empty($idAnneeAcad)) {
                $anneeAcademique = $this->getAnneeAcademiqueCourante();
                $idAnneeAcad = $anneeAcademique ? $anneeAcademique['id_annee_acad'] : null;
            }

            if (empty($idAnneeAcad)) {
                throw new Exception('Année académique requise');
            }

            // Valider les notes et calculer la somme
            $noteFinale = $this->calculerSommeNotes($criteres, $idAnneeAcad, $pdo);

            if ($noteFinale < 0) {
                throw new Exception('Erreur dans le calcul de la note finale');
            }

            // Préparer le commentaire avec les détails des critères
            $commentaireComplet = "Évaluation soutenance - " . date('d/m/Y H:i') . "\n";
            $commentaireComplet .= "Année académique : " . $idAnneeAcad . "\n\n";
            $commentaireComplet .= "Détail par critères :\n";

            foreach ($criteres as $idCritere => $note) {
                if (!empty($note)) {
                    // Récupérer le critère et son barème
                    $critereStmt = $pdo->prepare("
                        SELECT c.lib_critere, cor.bareme 
                        FROM critere_evaluation c
                        INNER JOIN correspondre cor ON c.id_critere = cor.id_critere 
                            AND cor.id_annee_acad = ?
                        WHERE c.id_critere = ?
                    ");
                    $critereStmt->execute([$idAnneeAcad, $idCritere]);
                    $critere = $critereStmt->fetch(PDO::FETCH_ASSOC);

                    if ($critere) {
                        $commentaireComplet .= "- " . $critere['lib_critere'] . " : " . $note . "/" . $critere['bareme'] . "\n";
                    }
                }
            }

            $commentaireComplet .= "\nNote finale (somme des notes) : " . $noteFinale . "\n";
            $commentaireComplet .= "\nCommentaire général :\n" . $commentaireGeneral;

            // Récupérer le numéro de jury de l'étudiant
            $juryStmt = $pdo->prepare("SELECT num_jury FROM programmer WHERE num_etud = ?");
            $juryStmt->execute([$numEtu]);
            $juryResult = $juryStmt->fetch(PDO::FETCH_ASSOC);

            if (!$juryResult) {
                throw new Exception('Aucun jury trouvé pour cet étudiant');
            }

            $numJury = $juryResult['num_jury'];
            $dateEval = date('Y-m-d');

            // Supprimer les évaluations existantes pour cet étudiant/jury
            $deleteStmt = $pdo->prepare("DELETE FROM evaluer WHERE num_etudiant = ? AND num_jury = ?");
            $deleteStmt->execute([$numEtu, $numJury]);

            // Insérer les nouvelles évaluations
            $insertStmt = $pdo->prepare("
                INSERT INTO evaluer (num_etudiant, num_jury, id_critere, date_eval, note) 
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($criteres as $idCritere => $note) {
                if (!empty($note) && is_numeric($note)) {
                    $success = $insertStmt->execute([$numEtu, $numJury, $idCritere, $dateEval, $note]);
                    if (!$success) {
                        throw new Exception('Erreur lors de l\'enregistrement du critère ' . $idCritere);
                    }
                }
            }

            $message = 'Évaluation enregistrée avec succès';

            if (!$success) {
                throw new Exception('Erreur lors de l\'enregistrement en base de données');
            }

            $pdo->commit();

            return [
                'success' => true,
                'message' => $message,
                'note_finale' => $noteFinale
            ];
        } catch (Exception $e) {
            if (isset($pdo)) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculer la somme des notes attribuées (simple addition)
     * Valider que chaque note ne dépasse pas son barème
     */
    private function calculerSommeNotes($criteres, $idAnneeAcad, $pdo)
    {
        try {
            $sommeNotes = 0;

            foreach ($criteres as $idCritere => $note) {
                if (!empty($note) && is_numeric($note)) {
                    // Récupérer le barème pour ce critère
                    $baremeStmt = $pdo->prepare("
                        SELECT bareme 
                        FROM correspondre 
                        WHERE id_critere = ? AND id_annee_acad = ?
                    ");
                    $baremeStmt->execute([$idCritere, $idAnneeAcad]);
                    $baremeResult = $baremeStmt->fetch(PDO::FETCH_ASSOC);

                    // Si pas de barème défini pour cette année, ignorer ce critère
                    if (!$baremeResult) {
                        continue;
                    }

                    $baremeMax = $baremeResult['bareme'];

                    // Valider que la note ne dépasse pas le barème
                    if ($note > $baremeMax) {
                        throw new Exception("La note pour le critère $idCritere dépasse le barème autorisé ($baremeMax)");
                    }

                    $sommeNotes += floatval($note);
                }
            }

            return $sommeNotes;
        } catch (Exception $e) {
            error_log('Erreur calculerSommeNotes: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprimer une évaluation
     */
    public function supprimerEvaluation()
    {
        try {
            $numEtu = $_POST['num_etu'] ?? null;

            if (empty($numEtu)) {
                throw new Exception('Numéro étudiant requis');
            }

            $pdo = Database::getConnection();

            $sql = "DELETE FROM evaluer WHERE num_etudiant = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$numEtu]);

            if (!$success) {
                throw new Exception('Erreur lors de la suppression en base de données');
            }

            return [
                'success' => true,
                'message' => 'Évaluation supprimée avec succès'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer les critères d'évaluation pour une année académique spécifique (AJAX)
     */
    public function getCriteresParAnnee()
    {
        try {
            $idAnneeAcad = $_GET['id_annee_acad'] ?? null;

            if (empty($idAnneeAcad)) {
                throw new Exception('ID année académique requis');
            }

            $pdo = Database::getConnection();

            $sql = "
                SELECT DISTINCT
                    c.id_critere,
                    c.lib_critere,
                    cor.bareme as bareme_max
                FROM critere_evaluation c
                INNER JOIN correspondre cor ON c.id_critere = cor.id_critere 
                    AND cor.id_annee_acad = ?
                ORDER BY c.id_critere
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idAnneeAcad]);
            $criteres = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $criteres
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

    /**
     * Imprimer les procès-verbaux (PV) de soutenance en PDF
     */
    public function imprimerPV()
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/../utils/DocumentGeneratorService.php';
        require_once __DIR__ . '/../utils/FormattingUtils.php';

        $numEtu = $_GET['num_etu'] ?? null;

        if (empty($numEtu)) {
            throw new Exception('Numéro étudiant requis');
        }

        $pdo = Database::getConnection();
        
        // Récupérer les informations de l'étudiant et de la soutenance
        $sql = "
            SELECT 
                p.id_programmation,
                p.theme_soutenance,
                p.date_soutenance,
                p.heure_soutenance,
                p.num_jury,
                -- Étudiant
                e.num_etu,
                CONCAT(e.prenom_etu, ' ', e.nom_etu) as nom_etudiant,
                e.promotion_etu,
                -- Niveau (Master 1 / Master 2)
                CASE 
                    WHEN e.promotion_etu LIKE '%M1%' THEN 'Master 1'
                    WHEN e.promotion_etu LIKE '%M2%' THEN 'Master 2'
                    ELSE e.promotion_etu
                END as niveau,
                -- Jury
                (SELECT CONCAT(ens1.prenom_enseignant, ' ', ens1.nom_enseignant) 
                 FROM composer_jury cj1 
                 JOIN enseignants ens1 ON cj1.id_enseignant = ens1.id_enseignant 
                 JOIN roles_jury r1 ON cj1.id_qualite_jury = r1.id_role_jury 
                 WHERE cj1.num_jury = p.num_jury AND r1.lib_role = 'Président du jury' 
                 LIMIT 1) as president,
                (SELECT CONCAT(ens2.prenom_enseignant, ' ', ens2.nom_enseignant) 
                 FROM composer_jury cj2 
                 JOIN enseignants ens2 ON cj2.id_enseignant = ens2.id_enseignant 
                 JOIN roles_jury r2 ON cj2.id_qualite_jury = r2.id_role_jury 
                 WHERE cj2.num_jury = p.num_jury AND r2.lib_role = 'Examinateur' 
                 LIMIT 1) as examinateur,
                (SELECT CONCAT(ens3.prenom_enseignant, ' ', ens3.nom_enseignant) 
                 FROM composer_jury cj3 
                 JOIN enseignants ens3 ON cj3.id_enseignant = ens3.id_enseignant 
                 JOIN roles_jury r3 ON cj3.id_qualite_jury = r3.id_role_jury 
                 WHERE cj3.num_jury = p.num_jury AND r3.lib_role = 'Directeur de mémoire' 
                 LIMIT 1) as directeur,
                (SELECT CONCAT(ens4.prenom_enseignant, ' ', ens4.nom_enseignant) 
                 FROM composer_jury cj4 
                 JOIN enseignants ens4 ON cj4.id_enseignant = ens4.id_enseignant 
                 JOIN roles_jury r4 ON cj4.id_qualite_jury = r4.id_role_jury 
                 WHERE cj4.num_jury = p.num_jury AND r4.lib_role = 'Encadrant' 
                 LIMIT 1) as encadreur,
                -- Maître de stage
                ist.encadrant_entreprise as maitre_stage
            FROM programmer p
            INNER JOIN etudiants e ON p.num_etud = e.num_etu
            LEFT JOIN informations_stage ist ON e.num_etu = ist.num_etu
            WHERE e.num_etu = ?
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$numEtu]);
        $soutenance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$soutenance) {
            throw new Exception('Soutenance non trouvée');
        }

        // Récupérer les évaluations
        $sqlEval = "
            SELECT 
                e.id_critere,
                e.note,
                c.lib_critere,
                cor.bareme
            FROM evaluer e
            JOIN critere_evaluation c ON e.id_critere = c.id_critere
            LEFT JOIN correspondre cor ON c.id_critere = cor.id_critere
            WHERE e.num_etudiant = ?
            ORDER BY e.id_critere
        ";
        $stmtEval = $pdo->prepare($sqlEval);
        $stmtEval->execute([$numEtu]);
        $evaluations = $stmtEval->fetchAll(PDO::FETCH_ASSOC);

        // Calculer la somme des notes et formater pour le template
        $sommeNotes = 0;
        $sommeBaremes = 0;
        $criteresPourTemplate = []; // NOUVEAU : Tableau formaté pour PHPWord

        foreach ($evaluations as $eval) {
            $note = floatval($eval['note']);
            $bareme = floatval($eval['bareme']);
            $sommeNotes += $note;
            $sommeBaremes += $bareme;

            // NOUVEAU : Structurer les données pour le bloc répétitif
            // Note: Using 2 decimals for note and 1 for bareme to match academic standards
            // where scores are precise but max scores are typically whole or half numbers
            $criteresPourTemplate[] = [
                'lib_critere' => FormattingUtils::sanitizeText($eval['lib_critere']),
                'note' => FormattingUtils::formatDecimal($note, 2),
                'bareme' => FormattingUtils::formatDecimal($bareme, 1),
            ];
        }
        
        // Calculer les moyennes
        $moyennes = $this->calculerMoyennesPourAnnexe2($numEtu, $pdo);
        
        // Calculate final note and mention for Annexe 2 (PV)
        $noteFinalePV = (
            $moyennes['moyenne_master1'] * 2 +
            $moyennes['moyenne_s1_master2'] * 3 +
            $sommeNotes * 3
        ) / 8;
        $mentionPV = $this->calculerMention($noteFinalePV);
        
        // Calculate final note and mention for Annexe 3 (Formation Continue)
        $noteFinaleFC = (
            $moyennes['moyenne_master1'] * 1 +
            $sommeNotes * 2
        ) / 3;
        $mentionFC = $this->calculerMention($noteFinaleFC);

        // Préparer les données pour le template
        $templateData = [
            // Annexe 1 - General information and criteria
            'niveau' => $soutenance['niveau'],
            'date_soutenance' => FormattingUtils::formatDate($soutenance['date_soutenance']),
            'promotion' => $soutenance['promotion_etu'],
            'theme' => $soutenance['theme_soutenance'],
            'nom_etudiant' => $soutenance['nom_etudiant'],
            'president' => $soutenance['president'] ?? '',
            'examinateur' => $soutenance['examinateur'] ?? '',
            'directeur' => $soutenance['directeur'] ?? '',
            'encadreur' => $soutenance['encadreur'] ?? '',
            'maitre_stage' => $soutenance['maitre_stage'] ?? '',
            'note_finale' => FormattingUtils::formatDecimal($sommeNotes, 2), // CORRIGÉ : Utiliser la somme calculée
            'total_bareme' => FormattingUtils::formatDecimal($sommeBaremes, 2), // CORRIGÉ : Utiliser la somme calculée

            // CORRECTION : Utiliser le tableau formaté pour le bloc répétitif
            'criteres' => $criteresPourTemplate,
            
            // Annexe 2 - PV Jury (coefficients: M1=2, S1M2=3, Mem=3, total=8)
            'moyenne_master1' => FormattingUtils::formatDecimal($moyennes['moyenne_master1'], 2),
            'moyenne_s1_master2' => FormattingUtils::formatDecimal($moyennes['moyenne_s1_master2'], 2),
            'note_memoire' => FormattingUtils::formatDecimal($sommeNotes, 2),
            'coef_master1' => 2,
            'coef_s1_master2' => 3,
            'coef_memoire' => 3,
            'note_finale_pv' => FormattingUtils::formatDecimal($noteFinalePV, 2),
            'mention' => $mentionPV,
            
            // Annexe 3 - Formation Continue (coefficients: M1=1, Mem=2, total=3)
            'coef_master1_fc' => 1,
            'coef_memoire_fc' => 2,
            'total_coef_fc' => 3,
            'note_finale_fc' => FormattingUtils::formatDecimal($noteFinaleFC, 2),
            'mention_fc' => $mentionFC
        ];

        // Use DocumentGeneratorService
        $documentService = new DocumentGeneratorService();
        
        // Generate PDF from template
        $pdfPath = $documentService->generateFromTemplate('pv_soutenance', $templateData);
        
        // Send PDF to browser
        $pdfFilename = 'PV_Soutenance_' . $numEtu . '_' . date('Y-m-d') . '.pdf';
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $pdfFilename . '"');
        header('Content-Length: ' . filesize($pdfPath));
        readfile($pdfPath);
        
        // Clean up temporary file
        $documentService->cleanupTempFile($pdfPath);
    }

    /**
     * Calculer la mention en fonction de la note finale
     */
    private function calculerMention($noteTotale)
    {
        if ($noteTotale >= 16) {
            return 'Très Bien';
        } elseif ($noteTotale >= 14) {
            return 'Bien';
        } elseif ($noteTotale >= 12) {
            return 'Assez Bien';
        } elseif ($noteTotale >= 10) {
            return 'Passable';
        } else {
            return 'Insuffisant';
        }
    }

    /**
     * Calculer les moyennes nécessaires pour l'Annexe 2
     * - Moyenne Générale Master 1 : depuis resume_candidature (JSON)
     * - Moyenne Générale Semestre 1 Master 2 : depuis la table notes
     */
    private function calculerMoyennesPourAnnexe2($numEtu, $pdo)
    {
        try {
            // 1. Récupérer la Moyenne Générale Master 1 depuis resume_candidature
            // La moyenne est stockée dans resume_json (JSON) sous semestre.moyenne
            $sqlResume = "
                SELECT resume_json
                FROM resume_candidature
                WHERE num_etu = ?
                ORDER BY date_enregistrement DESC
                LIMIT 1
            ";

            $stmtResume = $pdo->prepare($sqlResume);
            $stmtResume->execute([$numEtu]);
            $resume = $stmtResume->fetch(PDO::FETCH_ASSOC);

            $moyenneMaster1 = 0;
            if ($resume && !empty($resume['resume_json'])) {
                $details = json_decode($resume['resume_json'], true);
                if (isset($details['semestre']['moyenne'])) {
                    // Format: "12.63/20" ou "12.63"
                    $moyenneStr = $details['semestre']['moyenne'];
                    $moyenneStr = str_replace('/20', '', $moyenneStr);
                    $moyenneMaster1 = floatval(trim($moyenneStr));
                }
            }

            // 2. Calculer la Moyenne Générale Semestre 1 Master 2
            // Dans votre base, toutes les notes actuelles sont du Master 2 Semestre 1
            // Niveau 10 = Master 2, Semestre 20 = Semestre 9 (premier semestre M2)
            $sqlS1M2 = "
                SELECT 
                    SUM(n.moyenne * u.credit) / SUM(u.credit) as moyenne_s1_master2
                FROM notes n
                INNER JOIN ue u ON n.id_ue = u.id_ue
                WHERE n.num_etu = ? 
                AND u.id_niveau_etude = 10
                AND u.id_semestre = 20
                AND n.moyenne IS NOT NULL
            ";

            $stmtS1M2 = $pdo->prepare($sqlS1M2);
            $stmtS1M2->execute([$numEtu]);
            $resultS1M2 = $stmtS1M2->fetch(PDO::FETCH_ASSOC);
            $moyenneS1Master2 = $resultS1M2['moyenne_s1_master2'] ?? 0;

            return [
                'moyenne_master1' => round($moyenneMaster1, 2),
                'moyenne_s1_master2' => round($moyenneS1Master2, 2)
            ];

        } catch (Exception $e) {
            error_log('Erreur calculerMoyennesPourAnnexe2: ' . $e->getMessage());
            return [
                'moyenne_master1' => 0,
                'moyenne_s1_master2' => 0
            ];
        }
    }
}
?>