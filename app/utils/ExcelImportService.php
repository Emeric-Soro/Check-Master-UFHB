<?php

/**
 * Service for importing Excel/CSV files for student history archives
 * Handles both Excel (.xlsx, .xls) and CSV files
 */
class ExcelImportService
{
    private $db;
    private $errors = [];
    private $successes = [];
    
    // Column mapping (0-indexed)
    const COL_ANNEE_ACAD = 0;
    const COL_MATRICULE = 1;
    const COL_NOM = 2;
    const COL_PRENOMS = 3;
    const COL_THEME = 4;
    const COL_ENTREPRISE = 5;
    const COL_MAITRE_STAGE = 6;
    const COL_ENCADREUR_PEDA = 7;
    const COL_DIRECTEUR_MEMOIRE = 8;
    const COL_DATE_COMMISSION = 9;
    const COL_AVIS_COMMISSION = 10;
    const COL_OBSERVATIONS = 11;
    const COL_DATE_SOUTENANCE = 12;
    const COL_HEURE = 13;
    const COL_SALLE = 14;
    const COL_PRESIDENT_JURY = 15;
    const COL_EXAMINATEUR = 16;
    const COL_NOTE_MEMOIRE = 17;
    const COL_MOYENNE_M1 = 18;
    const COL_MOYENNE_M2_S1 = 19;
    
    public function __construct($db)
    {
        $this->db = $db;
    }
    
    /**
     * Import data from uploaded file
     */
    public function importFile($filePath, $fileType = 'csv')
    {
        $this->errors = [];
        $this->successes = [];
        
        if (!file_exists($filePath)) {
            $this->errors[] = "Fichier non trouvé: $filePath";
            return false;
        }
        
        if ($fileType === 'csv') {
            return $this->importCSV($filePath);
        } else {
            // For Excel files, we'll use a simple parsing approach
            return $this->importExcelAsCSV($filePath);
        }
    }
    
    /**
     * Import CSV file
     */
    private function importCSV($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->errors[] = "Impossible d'ouvrir le fichier";
            return false;
        }
        
        $lineNumber = 0;
        $headers = null;
        
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $lineNumber++;
            
            // Skip header row
            if ($lineNumber === 1) {
                $headers = $row;
                continue;
            }
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            try {
                $this->processRow($row, $lineNumber);
            } catch (Exception $e) {
                $this->errors[] = "Ligne $lineNumber: " . $e->getMessage();
            }
        }
        
        fclose($handle);
        
        return count($this->errors) === 0;
    }
    
    /**
     * Convert Excel to CSV and import
     * For simplicity, we expect users to export to CSV from Excel
     */
    private function importExcelAsCSV($filePath)
    {
        // This would require PhpSpreadsheet or similar library
        // For now, we'll suggest CSV format
        $this->errors[] = "Pour le moment, veuillez convertir votre fichier Excel en CSV avant l'importation.";
        return false;
    }
    
    /**
     * Process a single row of data
     */
    private function processRow($row, $lineNumber)
    {
        // Validate required fields
        if (empty($row[self::COL_ANNEE_ACAD])) {
            throw new Exception("Année académique manquante");
        }
        if (empty($row[self::COL_MATRICULE])) {
            throw new Exception("Matricule manquant");
        }
        if (empty($row[self::COL_NOM]) || empty($row[self::COL_PRENOMS])) {
            throw new Exception("Nom ou prénoms manquants");
        }
        
        // Start transaction
        $this->db->beginTransaction();
        
        try {
            // 1. Create or get academic year
            $idAnneeAcad = $this->getOrCreateAcademicYear($row[self::COL_ANNEE_ACAD]);
            
            // 2. Create or get student
            $numEtu = $this->getOrCreateStudent($row);
            
            // 3. Create or get enterprise
            $idEntreprise = $this->getOrCreateEnterprise($row[self::COL_ENTREPRISE]);
            
            // 4. Create stage information
            $idInfoStage = $this->createStageInfo($numEtu, $idEntreprise, $row);
            
            // 5. Create rapport
            $idRapport = $this->createRapport($numEtu, $row);
            
            // 6. Assign encadrant and directeur
            $idEncadreur = $this->getOrCreateEnseignant($row[self::COL_ENCADREUR_PEDA]);
            $this->assignEncadrant($idEncadreur, $idRapport, 'encadrant');
            
            if (!empty($row[self::COL_DIRECTEUR_MEMOIRE])) {
                $idDirecteur = $this->getOrCreateEnseignant($row[self::COL_DIRECTEUR_MEMOIRE]);
                $this->assignEncadrant($idDirecteur, $idRapport, 'directeur');
            }
            
            // 7. Create validation record
            $this->createValidation($idEncadreur, $idRapport, $row);
            
            // 8. If soutenance data exists, create programming and jury
            if (!empty($row[self::COL_DATE_SOUTENANCE])) {
                $this->createSoutenanceData($numEtu, $idRapport, $row);
            }
            
            $this->db->commit();
            $this->successes[] = "Ligne $lineNumber: Import réussi pour " . $row[self::COL_NOM] . " " . $row[self::COL_PRENOMS];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get or create academic year
     */
    private function getOrCreateAcademicYear($anneeAcad)
    {
        // Format: 2010-2011
        $parts = explode('-', $anneeAcad);
        if (count($parts) !== 2) {
            throw new Exception("Format d'année académique invalide: $anneeAcad");
        }
        
        $dateDebut = $parts[0] . '-09-01';
        $dateFin = $parts[1] . '-08-31';
        
        // Check if exists
        $stmt = $this->db->prepare("SELECT id_annee_acad FROM annee_academique WHERE date_deb = :date_deb AND date_fin = :date_fin");
        $stmt->execute(['date_deb' => $dateDebut, 'date_fin' => $dateFin]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['id_annee_acad'];
        }
        
        // Create new
        $stmt = $this->db->prepare("INSERT INTO annee_academique (date_deb, date_fin) VALUES (:date_deb, :date_fin)");
        $stmt->execute(['date_deb' => $dateDebut, 'date_fin' => $dateFin]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Get or create student
     */
    private function getOrCreateStudent($row)
    {
        $matricule = intval($row[self::COL_MATRICULE]);
        
        // Check if exists
        $stmt = $this->db->prepare("SELECT num_etu FROM etudiants WHERE num_etu = :num_etu");
        $stmt->execute(['num_etu' => $matricule]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['num_etu'];
        }
        
        // Create new student
        $stmt = $this->db->prepare("
            INSERT INTO etudiants (num_etu, nom_etu, prenom_etu, email_etu, date_naiss_etu, genre_etu, promotion_etu) 
            VALUES (:num_etu, :nom, :prenom, :email, :date_naiss, :genre, :promotion)
        ");
        
        $email = strtolower($row[self::COL_PRENOMS]) . '.' . strtolower($row[self::COL_NOM]) . '@example.com';
        
        $stmt->execute([
            'num_etu' => $matricule,
            'nom' => $row[self::COL_NOM],
            'prenom' => $row[self::COL_PRENOMS],
            'email' => $email,
            'date_naiss' => '2000-01-01', // Default date
            'genre' => 'Neutre',
            'promotion' => explode('-', $row[self::COL_ANNEE_ACAD])[0]
        ]);
        
        return $matricule;
    }
    
    /**
     * Get or create enterprise
     */
    private function getOrCreateEnterprise($nomEntreprise)
    {
        if (empty($nomEntreprise)) {
            throw new Exception("Nom d'entreprise manquant");
        }
        
        // Check if exists
        $stmt = $this->db->prepare("SELECT id_entreprise FROM entreprises WHERE lib_entreprise = :lib");
        $stmt->execute(['lib' => $nomEntreprise]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['id_entreprise'];
        }
        
        // Create new
        $stmt = $this->db->prepare("INSERT INTO entreprises (lib_entreprise) VALUES (:lib)");
        $stmt->execute(['lib' => $nomEntreprise]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Create stage information
     */
    private function createStageInfo($numEtu, $idEntreprise, $row)
    {
        $stmt = $this->db->prepare("
            INSERT INTO informations_stage 
            (num_etu, id_entreprise, date_debut_stage, date_fin_stage, sujet_stage, description_stage, encadrant_entreprise, email_encadrant, telephone_encadrant)
            VALUES (:num_etu, :id_entreprise, :date_debut, :date_fin, :sujet, :description, :encadrant, :email, :tel)
        ");
        
        $parts = explode('-', $row[self::COL_ANNEE_ACAD]);
        $dateDebut = $parts[0] . '-01-01';
        $dateFin = $parts[0] . '-06-30';
        
        $stmt->execute([
            'num_etu' => $numEtu,
            'id_entreprise' => $idEntreprise,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'sujet' => $row[self::COL_THEME] ?? 'Stage',
            'description' => 'Stage importé depuis archives',
            'encadrant' => $row[self::COL_MAITRE_STAGE] ?? 'Non spécifié',
            'email' => 'contact@example.com',
            'tel' => '0000000000'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Create rapport
     */
    private function createRapport($numEtu, $row)
    {
        $stmt = $this->db->prepare("
            INSERT INTO rapport_etudiants 
            (num_etu, nom_rapport, date_rapport, theme_rapport, statut_rapport, etape_validation)
            VALUES (:num_etu, :nom, :date, :theme, :statut, :etape)
        ");
        
        $avis = $row[self::COL_AVIS_COMMISSION] ?? '';
        $statut = (stripos($avis, 'validé') !== false || stripos($avis, 'approuvé') !== false) ? 'valider' : 'en_cours';
        $etape = ($statut === 'valider') ? 'approuve_commission' : 'en_attente_commission';
        
        $dateCommission = !empty($row[self::COL_DATE_COMMISSION]) ? $row[self::COL_DATE_COMMISSION] : date('Y-m-d');
        
        $stmt->execute([
            'num_etu' => $numEtu,
            'nom' => 'Rapport ' . $row[self::COL_NOM] . ' ' . $row[self::COL_PRENOMS],
            'date' => $dateCommission . ' 00:00:00',
            'theme' => $row[self::COL_THEME],
            'statut' => $statut,
            'etape' => $etape
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get or create enseignant
     */
    private function getOrCreateEnseignant($nomComplet)
    {
        if (empty($nomComplet)) {
            throw new Exception("Nom d'enseignant manquant");
        }
        
        // Parse name (assuming "NOM Prenom" format)
        $parts = explode(' ', trim($nomComplet), 2);
        $nom = $parts[0];
        $prenom = $parts[1] ?? '';
        
        // Check if exists
        $stmt = $this->db->prepare("SELECT id_enseignant FROM enseignants WHERE nom_enseignant = :nom AND prenom_enseignant = :prenom");
        $stmt->execute(['nom' => $nom, 'prenom' => $prenom]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['id_enseignant'];
        }
        
        // Create new enseignant
        $email = strtolower($prenom) . '.' . strtolower($nom) . '@ufhb.edu.ci';
        
        $stmt = $this->db->prepare("
            INSERT INTO enseignants (nom_enseignant, prenom_enseignant, mail_enseignant, id_specialite, type_enseignant)
            VALUES (:nom, :prenom, :email, :specialite, :type)
        ");
        
        $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'specialite' => 1, // Default specialite
            'type' => 'Simple'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Assign encadrant or directeur to rapport
     */
    private function assignEncadrant($idEnseignant, $idRapport, $role)
    {
        $stmt = $this->db->prepare("
            INSERT INTO affecter (id_enseignant, role, id_rapport)
            VALUES (:id_enseignant, :role, :id_rapport)
        ");
        
        $stmt->execute([
            'id_enseignant' => $idEnseignant,
            'role' => $role,
            'id_rapport' => $idRapport
        ]);
    }
    
    /**
     * Create validation record
     */
    private function createValidation($idEnseignant, $idRapport, $row)
    {
        $dateCommission = !empty($row[self::COL_DATE_COMMISSION]) ? $row[self::COL_DATE_COMMISSION] : date('Y-m-d');
        $avis = $row[self::COL_AVIS_COMMISSION] ?? 'Validé';
        $observations = $row[self::COL_OBSERVATIONS] ?? 'Importé depuis archives';
        
        $decision = (stripos($avis, 'validé') !== false || stripos($avis, 'approuvé') !== false) ? 'valider' : 'rejeter';
        
        $stmt = $this->db->prepare("
            INSERT INTO valider (id_enseignant, id_rapport, date_validation, commentaire_validation, decision_validation)
            VALUES (:id_enseignant, :id_rapport, :date, :commentaire, :decision)
        ");
        
        $stmt->execute([
            'id_enseignant' => $idEnseignant,
            'id_rapport' => $idRapport,
            'date' => $dateCommission . ' 00:00:00',
            'commentaire' => $observations,
            'decision' => $decision
        ]);
    }
    
    /**
     * Create soutenance programming and jury
     */
    private function createSoutenanceData($numEtu, $idRapport, $row)
    {
        // Create or get salle
        $idSalle = null;
        if (!empty($row[self::COL_SALLE])) {
            $idSalle = $this->getOrCreateSalle($row[self::COL_SALLE]);
        }
        
        // Create jury
        $numJury = $this->createJury($row);
        
        // Create programming
        $dateSoutenance = !empty($row[self::COL_DATE_SOUTENANCE]) ? $row[self::COL_DATE_SOUTENANCE] : null;
        $heureSoutenance = !empty($row[self::COL_HEURE]) ? $row[self::COL_HEURE] : null;
        
        if ($dateSoutenance) {
            $stmt = $this->db->prepare("
                INSERT INTO programmer (num_etud, num_jury, id_salle, date_soutenance, heure_soutenance, theme_soutenance)
                VALUES (:num_etud, :num_jury, :id_salle, :date, :heure, :theme)
            ");
            
            $stmt->execute([
                'num_etud' => $numEtu,
                'num_jury' => $numJury,
                'id_salle' => $idSalle,
                'date' => $dateSoutenance,
                'heure' => $heureSoutenance,
                'theme' => $row[self::COL_THEME]
            ]);
        }
        
        // Create evaluation if note exists
        if (!empty($row[self::COL_NOTE_MEMOIRE])) {
            $this->createEvaluation($numEtu, $numJury, $row);
        }
    }
    
    /**
     * Get or create salle
     */
    private function getOrCreateSalle($nomSalle)
    {
        // Check if exists
        $stmt = $this->db->prepare("SELECT id_salle FROM salles WHERE lib_salle = :lib");
        $stmt->execute(['lib' => $nomSalle]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['id_salle'];
        }
        
        // Create new
        $stmt = $this->db->prepare("INSERT INTO salles (lib_salle) VALUES (:lib)");
        $stmt->execute(['lib' => $nomSalle]);
        return $this->db->lastInsertId();
    }
    
    /**
     * Create jury with members
     */
    private function createJury($row)
    {
        // For archived data, we create a simple jury entry
        // The actual jury number would be auto-generated
        // We'll use a simple counter or max+1 approach
        
        $stmt = $this->db->prepare("SELECT MAX(num_jury) as max_jury FROM composer_jury");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $numJury = ($result['max_jury'] ?? 0) + 1;
        
        // Add president
        if (!empty($row[self::COL_PRESIDENT_JURY])) {
            $idPresident = $this->getOrCreateEnseignant($row[self::COL_PRESIDENT_JURY]);
            $this->addJuryMember($numJury, $idPresident, 1); // 1 = President
        }
        
        // Add examinateur
        if (!empty($row[self::COL_EXAMINATEUR])) {
            $idExaminateur = $this->getOrCreateEnseignant($row[self::COL_EXAMINATEUR]);
            $this->addJuryMember($numJury, $idExaminateur, 2); // 2 = Examinateur
        }
        
        // Add encadreur
        if (!empty($row[self::COL_ENCADREUR_PEDA])) {
            $idEncadreur = $this->getOrCreateEnseignant($row[self::COL_ENCADREUR_PEDA]);
            $this->addJuryMember($numJury, $idEncadreur, 3); // 3 = Encadreur
        }
        
        // Add directeur if exists
        if (!empty($row[self::COL_DIRECTEUR_MEMOIRE])) {
            $idDirecteur = $this->getOrCreateEnseignant($row[self::COL_DIRECTEUR_MEMOIRE]);
            $this->addJuryMember($numJury, $idDirecteur, 4); // 4 = Directeur
        }
        
        return $numJury;
    }
    
    /**
     * Add jury member
     */
    private function addJuryMember($numJury, $idEnseignant, $idQualite)
    {
        $stmt = $this->db->prepare("
            INSERT INTO composer_jury (num_jury, id_enseignant, id_qualite_jury, date_composer_jury)
            VALUES (:num_jury, :id_enseignant, :id_qualite, :date)
        ");
        
        $stmt->execute([
            'num_jury' => $numJury,
            'id_enseignant' => $idEnseignant,
            'id_qualite' => $idQualite,
            'date' => time()
        ]);
    }
    
    /**
     * Create evaluation record
     */
    private function createEvaluation($numEtu, $numJury, $row)
    {
        if (!empty($row[self::COL_NOTE_MEMOIRE])) {
            $dateSoutenance = !empty($row[self::COL_DATE_SOUTENANCE]) ? $row[self::COL_DATE_SOUTENANCE] : date('Y-m-d');
            
            // We'll use a default criterium ID (adjust based on actual data)
            $stmt = $this->db->prepare("
                INSERT INTO evaluer (num_etudiant, num_jury, id_critere, date_eval, note)
                VALUES (:num_etu, :num_jury, :critere, :date, :note)
            ");
            
            $stmt->execute([
                'num_etu' => $numEtu,
                'num_jury' => $numJury,
                'critere' => 1, // Default critere
                'date' => $dateSoutenance,
                'note' => floatval($row[self::COL_NOTE_MEMOIRE])
            ]);
        }
    }
    
    /**
     * Get errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Get successes
     */
    public function getSuccesses()
    {
        return $this->successes;
    }
    
    /**
     * Get import summary
     */
    public function getSummary()
    {
        return [
            'total_success' => count($this->successes),
            'total_errors' => count($this->errors),
            'errors' => $this->errors,
            'successes' => $this->successes
        ];
    }
}
