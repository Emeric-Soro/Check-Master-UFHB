<?php
class DossierAcademique {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Récupérer le résumé académique d'un étudiant depuis resume_candidature
     */
    public function getByNumEtu($num_etu) {
        $stmt = $this->pdo->prepare('SELECT * FROM resume_candidature WHERE num_etu = ? ORDER BY date_enregistrement DESC LIMIT 1');
        $stmt->execute([$num_etu]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Créer ou mettre à jour le résumé académique d'un étudiant
     * @param $data Données comprenant: num_etu, resume_json, decision
     */
    public function saveOrUpdate($data) {
        
        $num_etu = $data['num_etu'] ?? '';
        $resume_json = $data['resume_json'] ?? '{}';
        $decision = $data['decision'] ?? 'En attente';

        // Vérifier si un résumé existe déjà pour cet étudiant
        $stmt = $this->pdo->prepare('SELECT id FROM resume_candidature WHERE num_etu = ? LIMIT 1');
        $stmt->execute([$num_etu]);
        if ($stmt->fetch()) {
            // Update
            $sql = 'UPDATE resume_candidature SET resume_json = :resume_json, decision = :decision, date_enregistrement = NOW() WHERE num_etu = :num_etu';
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':resume_json' => $resume_json,
                ':decision' => $decision,
                ':num_etu' => $num_etu
            ]);
        } else {
            // Insert - récupérer id_candidature si disponible
            $id_candidature = $data['id_candidature'] ?? null;
            $sql = 'INSERT INTO resume_candidature (num_etu, id_candidature, resume_json, decision, date_enregistrement) VALUES (:num_etu, :id_candidature, :resume_json, :decision, NOW())';
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':num_etu' => $num_etu,
                ':id_candidature' => $id_candidature,
                ':resume_json' => $resume_json,
                ':decision' => $decision
            ]);
        }
    }

    /**
     * Supprimer le résumé académique d'un étudiant
     */
    public function deleteByNumEtu($num_etu) {
        $stmt = $this->pdo->prepare('DELETE FROM resume_candidature WHERE num_etu = ?');
        return $stmt->execute([$num_etu]);
    }
} 