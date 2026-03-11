<?php

class MaitreDeStage
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer tous les maîtres de stage pour l'autocomplétion
     */
    public function getAllMaitresDeStage()
    {
        try {
            $query = "SELECT 
                        ms.id_maitre_stage,
                        ms.Nom,
                        ms.prenom,
                        ms.email,
                        ms.telephone,
                        ms.id_entreprise,
                        e.lib_long_entreprise,
                        e.lib_court_en,
                        ms.id_fonction,
                        f.lib_fonction
                      FROM maitre_de_stage ms
                      LEFT JOIN entreprises e ON ms.id_entreprise = e.id_entreprise
                      LEFT JOIN fonction f ON ms.id_fonction = f.id_fonction
                      ORDER BY ms.Nom, ms.prenom";

            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des maîtres de stage: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Rechercher un maître de stage par nom, prénom et email
     */
    public function findByNameAndEmail($nom, $prenom, $email)
    {
        try {
            $query = "SELECT * FROM maitre_de_stage 
                      WHERE LOWER(Nom) = LOWER(:nom) 
                      AND LOWER(prenom) = LOWER(:prenom) 
                      AND LOWER(email) = LOWER(:email)
                      LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                ':nom' => trim($nom),
                ':prenom' => trim($prenom),
                ':email' => trim($email)
            ]);

            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche du maître de stage: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Générer un nouvel ID pour un maître de stage
     * Format: MS-{id_entreprise}-{XXX}
     */
    private function generateId($id_entreprise)
    {
        try {
            // Compter le nombre de maîtres de stage dans cette entreprise
            $query = "SELECT COUNT(*) as total FROM maitre_de_stage 
                      WHERE id_maitre_stage LIKE :pattern";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':pattern' => "MS-{$id_entreprise}-%"]);
            $result = $stmt->fetch(PDO::FETCH_OBJ);

            $numero = str_pad(($result->total + 1), 3, '0', STR_PAD_LEFT);
            return "MS-{$id_entreprise}-{$numero}";
        } catch (PDOException $e) {
            error_log("Erreur lors de la génération de l'ID maître de stage: " . $e->getMessage());
            // Fallback en cas d'erreur
            return "MS-{$id_entreprise}-" . uniqid();
        }
    }

    /**
     * Créer un nouveau maître de stage
     */
    public function create($data)
    {
        try {
            $id = $this->generateId($data['id_entreprise']);

            $query = "INSERT INTO maitre_de_stage 
                      (id_maitre_stage, Nom, prenom, email, telephone, id_entreprise, id_fonction)
                      VALUES 
                      (:id, :nom, :prenom, :email, :telephone, :id_entreprise, :id_fonction)";

            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                ':id' => $id,
                ':nom' => trim($data['nom']),
                ':prenom' => trim($data['prenom']),
                ':email' => trim($data['email']),
                ':telephone' => trim($data['telephone']),
                ':id_entreprise' => $data['id_entreprise'],
                ':id_fonction' => $data['id_fonction'] ?? 'AU' // Fonction "Autre" par défaut
            ]);

            if ($result) {
                return $id;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création du maître de stage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer un maître de stage par ID
     */
    public function getById($id)
    {
        try {
            $query = "SELECT 
                        ms.*,
                        e.lib_long_entreprise,
                        e.lib_court_en,
                        f.lib_fonction
                      FROM maitre_de_stage ms
                      LEFT JOIN entreprises e ON ms.id_entreprise = e.id_entreprise
                      LEFT JOIN fonction f ON ms.id_fonction = f.id_fonction
                      WHERE ms.id_maitre_stage = :id";

            $stmt = $this->db->prepare($query);
            $stmt->execute([':id' => $id]);

            return $stmt->fetch(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du maître de stage: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Créer ou récupérer un maître de stage
     * Retourne l'ID du maître de stage
     */
    public function findOrCreate($data)
    {
        // Extraire nom et prénom du champ encadrant si besoin
        $fullName = trim($data['encadrant']);
        $nameParts = explode(' ', $fullName, 2);
        $nom = $nameParts[0] ?? '';
        $prenom = $nameParts[1] ?? '';

        // Chercher si le maître de stage existe déjà
        $existing = $this->findByNameAndEmail($nom, $prenom, $data['email_encadrant']);

        if ($existing) {
            return $existing->id_maitre_stage;
        }

        // Sinon, créer un nouveau maître de stage
        $newData = [
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $data['email_encadrant'],
            'telephone' => $data['telephone_encadrant'],
            'id_entreprise' => $data['id_entreprise'],
            'id_fonction' => 'AU' // Fonction "Autre" par défaut
        ];

        return $this->create($newData);
    }
}
