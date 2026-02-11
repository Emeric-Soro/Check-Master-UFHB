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
            $this->errors[] = ['line' => 'Système', 'message' => "Fichier non trouvé: $filePath"];
            return false;
        }

        if ($fileType === 'csv') {
            return $this->importCSV($filePath);
        } else {
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
            $this->errors[] = ['line' => 'Système', 'message' => "Impossible d'ouvrir le fichier"];
            return false;
        }

        // Détection du séparateur
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';

        $lineNumber = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNumber++;

            // Skip header row
            if ($lineNumber === 1)
                continue;

            // Skip empty rows
            if (empty(array_filter($row)))
                continue;

            // CORRECTION 3 : Remplissage des colonnes manquantes
            // Si la ligne a moins de 20 colonnes, on ajoute des vides à la fin
            while (count($row) < 20) {
                $row[] = '';
            }

            try {
                $this->processRow($row, $lineNumber);
            } catch (Exception $e) {
                $this->errors[] = [
                    'line' => $lineNumber,
                    'message' => $e->getMessage()
                ];
            }
        }

        fclose($handle);

        return count($this->errors) === 0;
    }

    private function importExcelAsCSV($filePath)
    {
        $this->errors[] = ['line' => 'Système', 'message' => "Pour le moment, veuillez convertir votre fichier Excel en CSV avant l'importation."];
        return false;
    }

    private function processRow($row, $lineNumber)
    {
        // Validate required fields
        if (empty($row[self::COL_ANNEE_ACAD]))
            throw new Exception("Année académique manquante");
        if (empty($row[self::COL_MATRICULE]))
            throw new Exception("Matricule manquant");
        if (empty($row[self::COL_NOM]))
            throw new Exception("Nom manquant");

        // Start transaction
        $this->db->beginTransaction();

        try {
            $idAnneeAcad = $this->getOrCreateAcademicYear($row[self::COL_ANNEE_ACAD]);
            $numEtu = $this->getOrCreateStudent($row);
            $this->ensureInscription($numEtu, $row[self::COL_ANNEE_ACAD], $idAnneeAcad);
            $idEntreprise = $this->getOrCreateEnterprise($row[self::COL_ENTREPRISE]);

            $this->createStageInfo($numEtu, $idEntreprise, $row);
            $idRapport = $this->createRapport($numEtu, $row);

            if (!empty($row[self::COL_ENCADREUR_PEDA])) {
                $idEncadreur = $this->getOrCreateEnseignant($row[self::COL_ENCADREUR_PEDA]);
                $this->assignEncadrant($idEncadreur, $idRapport, 'encadrant');
            }

            if (!empty($row[self::COL_DIRECTEUR_MEMOIRE])) {
                $idDirecteur = $this->getOrCreateEnseignant($row[self::COL_DIRECTEUR_MEMOIRE]);
                $this->assignEncadrant($idDirecteur, $idRapport, 'directeur');
            }

            $idValidateur = isset($idDirecteur) ? $idDirecteur : (isset($idEncadreur) ? $idEncadreur : null);
            if ($idValidateur) {
                $this->createValidation($idValidateur, $idRapport, $row);
            }

            if (!empty($row[self::COL_DATE_SOUTENANCE])) {
                $this->createSoutenanceData($numEtu, $idRapport, $row);
            }

            $this->db->commit();
            $this->successes[] = "Ligne $lineNumber: Import réussi pour " . $row[self::COL_NOM];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function getOrCreateAcademicYear($anneeAcad)
    {
        [$startYear, $endYear] = $this->parseAcademicYear($anneeAcad);

        // Generer l'ID unique (ex: 21413 pour 2013-2014)
        $id = substr($endYear, 0, 1) . substr($endYear, 2, 2) . substr($startYear, 2, 2);

        // CORRECTION 2 : Verifier par ID d'abord pour eviter les doublons de cle primaire
        $stmt = $this->db->prepare("SELECT id_annee_acad FROM annee_academique WHERE id_annee_acad = :id");
        $stmt->execute(['id' => $id]);
        if ($stmt->fetch())
            return $id;

        $dateDebut = $startYear . '-09-01';
        $dateFin = $endYear . '-08-31';

        $stmt = $this->db->prepare("INSERT INTO annee_academique (id_annee_acad, date_deb, date_fin) VALUES (:id, :date_deb, :date_fin)");
        $stmt->execute(['id' => $id, 'date_deb' => $dateDebut, 'date_fin' => $dateFin]);
        return $id;
    }

    /**
     * Ensure an inscription exists so academic year filters work on history listings.
     */
    private function ensureInscription($numEtu, $anneeAcad, $idAnneeAcad)
    {
        [$startYear,] = $this->parseAcademicYear($anneeAcad);

        $stmt = $this->db->prepare("SELECT id_inscription FROM inscriptions WHERE id_etudiant = :num AND id_annee_acad = :annee");
        $stmt->execute(['num' => $numEtu, 'annee' => $idAnneeAcad]);
        if ($stmt->fetch()) {
            return;
        }

        $dateInscription = $startYear . '-09-01 00:00:00';
        $insert = $this->db->prepare("INSERT INTO inscriptions (id_etudiant, id_niveau, id_annee_acad, date_inscription, statut_inscription, nombre_tranche, reste_a_payer, montant_paye) VALUES (:etudiant, NULL, :annee, :date_inscription, :statut, :tranches, :reste, :paye)");
        $insert->execute([
            'etudiant' => $numEtu,
            'annee' => $idAnneeAcad,
            'date_inscription' => $dateInscription,
            'statut' => 'En cours',
            'tranches' => 1,
            'reste' => 0,
            'paye' => 0
        ]);
    }

    /**
     * Normalize/validate an academic year string.
     */
    private function parseAcademicYear($anneeAcad)
    {
        $parts = explode('-', $anneeAcad);
        if (count($parts) !== 2) {
            $parts = explode('/', $anneeAcad);
        }
        if (count($parts) !== 2) {
            throw new Exception("Format d'annee academique invalide: $anneeAcad");
        }

        $startYear = trim($parts[0]);
        $endYear = trim($parts[1]);

        if (!is_numeric($startYear) || !is_numeric($endYear)) {
            throw new Exception("Format d'annee academique invalide: $anneeAcad");
        }

        return [$startYear, $endYear];
    }

    private function getOrCreateStudent($row)
    {
        $matricule = trim($row[self::COL_MATRICULE]);
        $stmt = $this->db->prepare("SELECT num_carte_etud FROM etudiants WHERE num_carte_etud = :num_etu");
        $stmt->execute(['num_etu' => $matricule]);
        if ($stmt->fetch())
            return $matricule;

        $stmt = $this->db->prepare("INSERT INTO etudiants (num_carte_etud, nom_etu, prenom_etu, email_etu, date_naiss_etu, genre_etu, promotion_etu) VALUES (:num_etu, :nom, :prenom, :email, :date_naiss, :genre, :promotion)");

        [$startYear,] = $this->parseAcademicYear($row[self::COL_ANNEE_ACAD]);
        $prenomClean = $this->sanitizeForEmail($row[self::COL_PRENOMS]);
        $nomClean = $this->sanitizeForEmail($row[self::COL_NOM]);
        $email = strtolower($prenomClean) . '.' . strtolower($nomClean) . '@student.ufhb.edu.ci';

        $stmt->execute([
            'num_etu' => $matricule,
            'nom' => $row[self::COL_NOM],
            'prenom' => $row[self::COL_PRENOMS],
            'email' => $email,
            'date_naiss' => '2000-01-01',
            'genre' => 3,
            'promotion' => $startYear
        ]);
        return $matricule;
    }

    private function sanitizeForEmail($str)
    {
        $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
        $str = preg_replace('/[^a-zA-Z0-9]/', '', $str);
        return $str;
    }

    private function getOrCreateEnterprise($nomEntreprise)
    {
        $nom = !empty($nomEntreprise) ? trim($nomEntreprise) : 'Non spécifiée';
        $stmt = $this->db->prepare("SELECT id_entreprise FROM entreprises WHERE lib_entreprise = :lib");
        $stmt->execute(['lib' => $nom]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result)
            return $result['id_entreprise'];

        $stmt = $this->db->prepare("INSERT INTO entreprises (lib_entreprise) VALUES (:lib)");
        $stmt->execute(['lib' => $nom]);
        return $this->db->lastInsertId();
    }

    private function createStageInfo($numEtu, $idEntreprise, $row)
    {
        $this->db->prepare("DELETE FROM informations_stage WHERE num_etu = ?")->execute([$numEtu]);
        $stmt = $this->db->prepare("INSERT INTO informations_stage (num_etu, id_entreprise, date_debut_stage, date_fin_stage, sujet_stage, description_stage, encadrant_entreprise, email_encadrant, telephone_encadrant) VALUES (:num_etu, :id_entreprise, :date_debut, :date_fin, :sujet, :description, :encadrant, :email, :tel)");

        $parts = explode('-', $row[self::COL_ANNEE_ACAD]);
        $dateDebut = trim($parts[0]) . '-01-01';
        $dateFin = trim($parts[0]) . '-06-30';

        $stmt->execute([
            'num_etu' => $numEtu,
            'id_entreprise' => $idEntreprise,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'sujet' => $row[self::COL_THEME] ?? 'Stage',
            'description' => 'Stage importé depuis archives',
            'encadrant' => $row[self::COL_MAITRE_STAGE] ?? 'Non spécifié',
            'email' => '',
            'tel' => ''
        ]);
    }

    private function createRapport($numEtu, $row)
    {
        $stmt = $this->db->prepare("SELECT id_rapport FROM rapport_etudiants WHERE num_etu = ?");
        $stmt->execute([$numEtu]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing)
            return $existing['id_rapport'];

        $stmt = $this->db->prepare("INSERT INTO rapport_etudiants (num_etu, nom_rapport, date_rapport, theme_rapport, statut_rapport, etape_validation) VALUES (:num_etu, :nom, :date, :theme, :statut, :etape)");

        $avis = $row[self::COL_AVIS_COMMISSION] ?? '';
        $statut = (stripos($avis, 'valid') !== false || stripos($avis, 'approuv') !== false) ? 'valider' : 'en_cours';
        $etape = ($statut === 'valider') ? 'approuve_commission' : 'en_attente_commission';
        $dateCommission = !empty($row[self::COL_DATE_COMMISSION]) ? $row[self::COL_DATE_COMMISSION] : date('Y-m-d');

        $stmt->execute([
            'num_etu' => $numEtu,
            'nom' => 'Rapport ' . $row[self::COL_NOM],
            'date' => $dateCommission . ' 00:00:00',
            'theme' => $row[self::COL_THEME],
            'statut' => $statut,
            'etape' => $etape
        ]);
        return $this->db->lastInsertId();
    }

    // CORRECTION 1 : Gestion de la spécialité par défaut
    private function getOrCreateDefaultSpecialite()
    {
        // Chercher n'importe quelle spécialité existante
        $stmt = $this->db->query("SELECT id_specialite FROM specialite LIMIT 1");
        $id = $stmt->fetchColumn();

        if ($id)
            return $id;

        // Si aucune n'existe, en créer une "Général"
        $this->db->exec("INSERT INTO specialite (lib_specialite) VALUES ('Général')");
        return $this->db->lastInsertId();
    }

    private function getOrCreateEnseignant($nomComplet)
    {
        $nomComplet = trim($nomComplet);
        if (empty($nomComplet) || $nomComplet === 'N/A')
            return null;

        $cleanName = preg_replace('/^(M\.|Mme|Dr|Pr|Prof\.)\s+/i', '', $nomComplet);
        $parts = explode(' ', $cleanName, 2);
        $nom = $parts[0];
        $prenom = $parts[1] ?? 'Enseignant';

        $stmt = $this->db->prepare("SELECT id_enseignant FROM enseignants WHERE nom_enseignant LIKE :nom");
        $stmt->execute(['nom' => "%$nom%"]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result)
            return $result['id_enseignant'];

        // Récupérer un ID de spécialité valide
        $idSpecialite = $this->getOrCreateDefaultSpecialite();

        $prenomClean = $this->sanitizeForEmail($prenom);
        $nomClean = $this->sanitizeForEmail($nom);
        $email = strtolower($prenomClean) . '.' . strtolower($nomClean) . '@ufhb.edu.ci';

        $stmt = $this->db->prepare("INSERT INTO enseignants (nom_enseignant, prenom_enseignant, mail_enseignant, id_specialite, type_enseignant) VALUES (:nom, :prenom, :email, :specialite, 'Simple')");
        $stmt->execute([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'specialite' => $idSpecialite
        ]);
        return $this->db->lastInsertId();
    }

    private function assignEncadrant($idEnseignant, $idRapport, $role)
    {
        if (!$idEnseignant)
            return;
        $stmt = $this->db->prepare("SELECT * FROM affecter WHERE id_enseignant = ? AND id_rapport = ? AND role = ?");
        $stmt->execute([$idEnseignant, $idRapport, $role]);
        if ($stmt->fetch())
            return;

        $stmt = $this->db->prepare("INSERT INTO affecter (id_enseignant, role, id_rapport) VALUES (?, ?, ?)");
        $stmt->execute([$idEnseignant, $role, $idRapport]);
    }

    private function createValidation($idEnseignant, $idRapport, $row)
    {
        $stmt = $this->db->prepare("SELECT * FROM valider WHERE id_rapport = ?");
        $stmt->execute([$idRapport]);
        if ($stmt->fetch())
            return;

        $dateCommission = !empty($row[self::COL_DATE_COMMISSION]) ? $row[self::COL_DATE_COMMISSION] : date('Y-m-d');
        $avis = $row[self::COL_AVIS_COMMISSION] ?? 'Validé';
        $observations = $row[self::COL_OBSERVATIONS] ?? 'Importé depuis archives';
        $decision = (stripos($avis, 'valid') !== false || stripos($avis, 'approuv') !== false) ? 'valider' : 'rejeter';

        $stmt = $this->db->prepare("INSERT INTO valider (id_enseignant, id_rapport, date_validation, commentaire_validation, decision_validation) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$idEnseignant, $idRapport, $dateCommission . ' 00:00:00', $observations, $decision]);
    }

    private function createSoutenanceData($numEtu, $idRapport, $row)
    {
        $dateSoutenance = $row[self::COL_DATE_SOUTENANCE];
        if (empty($dateSoutenance) || $dateSoutenance === 'N/A')
            return;

        $idSalle = null;
        if (!empty($row[self::COL_SALLE]) && $row[self::COL_SALLE] !== 'N/A') {
            $stmt = $this->db->prepare("SELECT id_salle FROM salles WHERE lib_salle = ?");
            $stmt->execute([$row[self::COL_SALLE]]);
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res) {
                $idSalle = $res['id_salle'];
            } else {
                $this->db->prepare("INSERT INTO salles (lib_salle) VALUES (?)")->execute([$row[self::COL_SALLE]]);
                $idSalle = $this->db->lastInsertId();
            }
        }

        $numJury = random_int(1000, 9999);
        if (!empty($row[self::COL_PRESIDENT_JURY])) {
            $idPres = $this->getOrCreateEnseignant($row[self::COL_PRESIDENT_JURY]);
            if ($idPres)
                $this->addJuryMember($numJury, $idPres, 1);
        }
        if (!empty($row[self::COL_EXAMINATEUR])) {
            $idExam = $this->getOrCreateEnseignant($row[self::COL_EXAMINATEUR]);
            if ($idExam)
                $this->addJuryMember($numJury, $idExam, 2);
        }

        $stmt = $this->db->prepare("SELECT id_programmation FROM programmer WHERE num_etud = ?");
        $stmt->execute([$numEtu]);
        if (!$stmt->fetch()) {
            $stmt = $this->db->prepare("INSERT INTO programmer (num_etud, num_jury, id_salle, date_soutenance, heure_soutenance, theme_soutenance) VALUES (?, ?, ?, ?, ?, ?)");
            $heure = !empty($row[self::COL_HEURE]) ? $row[self::COL_HEURE] : '08:00';
            $stmt->execute([$numEtu, $numJury, $idSalle, $dateSoutenance, $heure, $row[self::COL_THEME]]);
        }

        if (!empty($row[self::COL_NOTE_MEMOIRE]) && is_numeric($row[self::COL_NOTE_MEMOIRE])) {
            $this->db->prepare("DELETE FROM evaluer WHERE num_etudiant = ?")->execute([$numEtu]);
            $stmt = $this->db->prepare("INSERT INTO evaluer (num_etudiant, num_jury, id_critere, date_eval, note) VALUES (?, ?, 1, ?, ?)");
            $stmt->execute([$numEtu, $numJury, $dateSoutenance, floatval($row[self::COL_NOTE_MEMOIRE])]);
        }
    }

    private function addJuryMember($numJury, $idEnseignant, $idQualite)
    {
        $stmt = $this->db->prepare("INSERT INTO composer_jury (num_jury, id_enseignant, id_qualite_jury, date_composer_jury) VALUES (?, ?, ?, ?)");
        $stmt->execute([$numJury, $idEnseignant, $idQualite, time()]);
    }

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
