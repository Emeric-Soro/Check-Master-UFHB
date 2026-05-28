<?php

class Note
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer les notes d'un étudiant
     */
    public function getByStudent($studentId, $anneeAcadId = null)
    {
        try {
            $query = "SELECT n.*, a.date_deb, a.date_fin
                     FROM notes n 
                     LEFT JOIN annee_academique a ON n.id_annee_acad = a.id_annee_acad
                     WHERE n.num_etu = ?";

            $params = [$studentId];

            if ($anneeAcadId) {
                $query .= " AND n.id_annee_acad = ?";
                $params[] = $anneeAcadId;
            }

            $query .= " ORDER BY n.date_creation DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des notes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Créer ou mettre à jour les notes d'un étudiant pour une année académique
     */
    public function saveNotes($numEtu, $moyenneM1, $moyenneM2, $anneeAcadId)
    {
        try {
            // Vérifier si une note existe déjà pour cet étudiant et cette année académique
            $existing = $this->getByStudentAndYear($numEtu, $anneeAcadId);

            if ($existing) {
                // Mise à jour
                return $this->updateNote($numEtu, $moyenneM1, $moyenneM2, $anneeAcadId);
            }

            $existingForStudent = $this->getLatestNote($numEtu);
            if ($existingForStudent) {
                return $this->updateNote($numEtu, $moyenneM1, $moyenneM2, $anneeAcadId);
            }

            
                // Création
                return $this->createNote($numEtu, $moyenneM1, $moyenneM2, $anneeAcadId);
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'enregistrement des notes: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les notes d'un étudiant pour une année académique spécifique
     */
    public function getByStudentAndYear($studentId, $anneeAcadId)
    {
        try {
            $query = "SELECT n.*, a.date_deb, a.date_fin
                     FROM notes n 
                     LEFT JOIN annee_academique a ON n.id_annee_acad = a.id_annee_acad
                     WHERE n.num_etu = ? AND n.id_annee_acad = ?
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$studentId, $anneeAcadId]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des notes: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Créer une nouvelle entrée de notes
     */
    private function createNote($numEtu, $moyenneM1, $moyenneM2, $anneeAcadId)
    {
        try {
            $query = "INSERT INTO notes (num_etu, moyenne_M1, moyenne_M2, id_annee_acad) 
                     VALUES (?, ?, ?, ?)";

            $stmt = $this->db->prepare($query);
            return $stmt->execute([$numEtu, $moyenneM1, $moyenneM2, $anneeAcadId]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de la note: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour une note existante
     */
    private function updateNote($numEtu, $moyenneM1, $moyenneM2, $anneeAcadId = null)
    {
        try {
            $query = "UPDATE notes 
                     SET moyenne_M1 = ?, moyenne_M2 = ?";

            $params = [$moyenneM1, $moyenneM2];

            if ($anneeAcadId !== null) {
                $query .= ", id_annee_acad = ?";
                $params[] = $anneeAcadId;
            }

            $query .= " WHERE num_etu = ?";
            $params[] = $numEtu;

            $stmt = $this->db->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la note: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une note
     */
    public function deleteNote($numEtu)
    {
        try {
            $query = "DELETE FROM notes WHERE num_etu = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$numEtu]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la note: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un étudiant a validé un semestre donné pour un niveau donné.
     * Un semestre est considéré validé si la moyenne du niveau >= 10.
     *
     * @param string $numEtu Numéro étudiant
     * @param string $codeSemestre Code du semestre (S1, S2)
     * @param string $libNiveau Libellé du niveau (Master 1, Master 2)
     * @return bool
     */
    public function estSemestreValide(string $numEtu, string $codeSemestre, string $libNiveau): bool
    {
        try {
            $note = $this->getLatestNote($numEtu);
            if (!$note) {
                return false;
            }

            // Pour le S1 du Master 2, on vérifie que la moyenne M2 >= 10
            if ($codeSemestre === 'S1' && $libNiveau === 'Master 2') {
                return ($note->moyenne_M2 ?? 0) >= 10;
            }

            // Pour le S2 du Master 1, on vérifie que la moyenne M1 >= 10
            if ($codeSemestre === 'S2' && $libNiveau === 'Master 1') {
                return ($note->moyenne_M1 ?? 0) >= 10;
            }

            // Par défaut, on accepte si la note de ce niveau existe
            if ($libNiveau === 'Master 2') {
                return ($note->moyenne_M2 ?? 0) >= 10;
            }

            return ($note->moyenne_M1 ?? 0) >= 10;
        } catch (Exception $e) {
            error_log("Erreur estSemestreValide: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer la moyenne générale d'un étudiant pour une année académique
     */
    public function getMoyenneGenerale($studentId, $anneeAcadId = null)
    {
        try {
            $note = $anneeAcadId
                ? $this->getByStudentAndYear($studentId, $anneeAcadId)
                : $this->getLatestNote($studentId);

            if (!$note) {
                return null;
            }

            // Moyenne générale = (M1 + M2) / 2
            $moyenneGenerale = ($note->moyenne_M1 + $note->moyenne_M2) / 2;

            return (object) [
                'moyenne_M1' => $note->moyenne_M1,
                'moyenne_M2' => $note->moyenne_M2,
                'moyenne_generale' => round($moyenneGenerale, 2)
            ];
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul de la moyenne: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer la note la plus récente d'un étudiant
     */
    public function getLatestNote($studentId)
    {
        try {
            $query = "SELECT n.*, a.date_deb, a.date_fin
                     FROM notes n 
                     LEFT JOIN annee_academique a ON n.id_annee_acad = a.id_annee_acad
                     WHERE n.num_etu = ?
                     ORDER BY n.date_creation DESC
                     LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$studentId]);
            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la dernière note: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupérer toutes les notes d'un niveau pour une année académique
     */
    public function getNotesByNiveauAndYear($niveauId, $anneeAcadId)
    {
        try {
            $query = "SELECT e.num_carte_etud,
                            MAX(n.num_etu) AS num_etu,
                            MAX(n.id_annee_acad) AS id_annee_acad,
                            MAX(n.moyenne_M1) AS moyenne_M1,
                            MAX(n.moyenne_M2) AS moyenne_M2,
                            MAX(n.date_creation) AS date_creation,
                            MAX(n.date_modification) AS date_modification,
                            e.nom_etu, e.prenom_etu,
                            MAX(a.date_deb) AS date_deb,
                            MAX(a.date_fin) AS date_fin
                     FROM notes n
                     INNER JOIN etudiants e ON (n.num_etu = e.num_carte_etud OR n.num_etu = e.num_ident_etud)
                     LEFT JOIN annee_academique a ON n.id_annee_acad = a.id_annee_acad
                     LEFT JOIN (
                        SELECT i1.num_carte_etud, i1.id_niv_etude
                        FROM inscriptions i1
                        INNER JOIN (
                            SELECT num_carte_etud, MAX(num_versement) AS max_v
                            FROM inscriptions
                            GROUP BY num_carte_etud
                        ) latest ON latest.num_carte_etud = i1.num_carte_etud
                               AND latest.max_v = i1.num_versement
                        ) ins ON ins.num_carte_etud = e.num_carte_etud
                     WHERE ins.id_niv_etude = ?
                     GROUP BY e.num_carte_etud, e.nom_etu, e.prenom_etu
                     ORDER BY e.nom_etu, e.prenom_etu";

            $params = [$niveauId];
            if ($anneeAcadId !== null && $anneeAcadId !== '' && (int) $anneeAcadId > 0) {
                $query = str_replace(
                    'WHERE ins.id_niv_etude = ?',
                    'WHERE n.id_annee_acad = ? AND ins.id_niv_etude = ?',
                    $query
                );
                $params = [(int) $anneeAcadId, $niveauId];
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des notes par niveau: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer toutes les notes (filtrables par année académique)
     */
    public function getNotesByYear($anneeAcadId = null)
    {
        try {
            $query = "SELECT e.num_carte_etud,
                            MAX(n.num_etu) AS num_etu,
                            MAX(n.id_annee_acad) AS id_annee_acad,
                            MAX(n.moyenne_M1) AS moyenne_M1,
                            MAX(n.moyenne_M2) AS moyenne_M2,
                            MAX(n.date_creation) AS date_creation,
                            MAX(n.date_modification) AS date_modification,
                            e.nom_etu, e.prenom_etu,
                            MAX(a.date_deb) AS date_deb,
                            MAX(a.date_fin) AS date_fin
                     FROM notes n
                     INNER JOIN etudiants e ON (n.num_etu = e.num_carte_etud OR n.num_etu = e.num_ident_etud)
                     LEFT JOIN annee_academique a ON n.id_annee_acad = a.id_annee_acad";

            $params = [];
            if ($anneeAcadId !== null && $anneeAcadId !== '' && (int) $anneeAcadId > 0) {
                $query .= " WHERE n.id_annee_acad = ?";
                $params[] = (int) $anneeAcadId;
            }

            $query .= " GROUP BY e.num_carte_etud, e.nom_etu, e.prenom_etu ORDER BY e.nom_etu, e.prenom_etu";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des notes par année: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer le classement d'un étudiant dans son niveau
     */
    public function getClassementStudent($studentId)
    {
        try {
            // 1. Trouver le niveau et l'année de l'étudiant
            $latestNote = $this->getLatestNote($studentId);
            if (!$latestNote) return (object)['classement' => null, 'total' => 0];

            $anneeAcadId = $latestNote->id_annee_acad;

            // 2. Trouver le niveau via l'inscription
            $queryNiveau = "SELECT id_niv_etude FROM inscriptions WHERE num_carte_etud = ? ORDER BY num_versement DESC LIMIT 1";
            $stmtNiv = $this->db->prepare($queryNiveau);
            $stmtNiv->execute([$studentId]);
            $niveau = $stmtNiv->fetch(PDO::FETCH_OBJ);
            if (!$niveau) return (object)['classement' => null, 'total' => 0];

            $niveauId = $niveau->id_niv_etude;

            // 3. Récupérer toutes les moyennes du même niveau et même année
            // On calcule la moyenne (M1+M2)/2 pour le classement
            $queryAll = "SELECT n.num_etu, (n.moyenne_M1 + n.moyenne_M2) / 2 as moyenne_gen
                        FROM notes n
                        INNER JOIN inscriptions i ON n.num_etu = i.num_carte_etud
                        WHERE i.id_niv_etude = ? AND n.id_annee_acad = ?
                        ORDER BY moyenne_gen DESC";
            
            $stmtAll = $this->db->prepare($queryAll);
            $stmtAll->execute([$niveauId, $anneeAcadId]);
            $allNotes = $stmtAll->fetchAll(PDO::FETCH_OBJ);

            $total = count($allNotes);
            $classement = null;

            foreach ($allNotes as $index => $row) {
                if ($row->num_etu == $studentId) {
                    $classement = $index + 1;
                    break;
                }
            }

            return (object)['classement' => $classement, 'total' => $total];
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du classement: " . $e->getMessage());
            return (object)['classement' => null, 'total' => 0];
        }
    }

    /**
     * Récupérer les semestres rattachés au niveau d'un étudiant
     */
    public function getSemestreByEtudiant($studentId)
    {
        try {
            $query = "SELECT s.* 
                     FROM semestre s
                     INNER JOIN inscriptions i ON s.id_niv_etude = i.id_niv_etude
                     WHERE i.num_carte_etud = ?
                     ORDER BY i.num_versement DESC, s.id_semestre ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$studentId]);
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des semestres: " . $e->getMessage());
            return [];
        }
    }
}
