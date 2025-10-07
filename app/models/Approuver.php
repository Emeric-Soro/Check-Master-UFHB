<?php
require_once __DIR__ . '/../config/database.php';

class Approuver
{

    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public static function getByRapport($id_rapport)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT a.*, n.lib_approb, p.nom_pers_admin, p.prenom_pers_admin
            FROM approuver a
            JOIN niveau_approbation n ON a.id_approb = n.id_approb
            JOIN personnel_admin p ON a.id_pers_admin = p.id_pers_admin
            WHERE a.id_rapport = ?
            ORDER BY a.date_approv ASC");
        $stmt->execute([$id_rapport]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les rapports ayant au moins un avis dans approuver
     */
    public static function getRapportsAvecAvis()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT DISTINCT r.id_rapport, r.nom_rapport, r.theme_rapport, r.date_rapport, r.etape_validation, e.nom_etu, e.prenom_etu
            FROM rapport_etudiants r
            JOIN etudiants e ON r.num_etu = e.num_etu
            JOIN approuver a ON r.id_rapport = a.id_rapport
            ORDER BY r.date_rapport DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les membres de la commission (personnel_admin)
     */
    public static function getMembresCommission()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id_pers_admin, nom_pers_admin, prenom_pers_admin FROM personnel_admin ORDER BY nom_pers_admin, prenom_pers_admin");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère tous les avis de la table approuver
     */
    public static function getTousAvis()
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id_rapport, id_pers_admin, decision, commentaire_approv FROM approuver");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}