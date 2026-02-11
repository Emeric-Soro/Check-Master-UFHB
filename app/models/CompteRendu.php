<?php
require_once __DIR__ . '/../config/database.php';

class CompteRendu
{
    public static function creer($num_etu, $nom_CR, $contenu_CR, $chemin_pdf, $date_CR, $rapports = [])
    {
        $pdo = Database::getConnection();
        // Insertion du compte rendu
        $stmt = $pdo->prepare("INSERT INTO compte_rendu (num_etu, nom_CR, contenu_CR, chemin_fichier_pdf, date_CR) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$num_etu, $nom_CR, $contenu_CR, $chemin_pdf, $date_CR]);
        $id_CR = $pdo->lastInsertId();
        // Liaisons avec les rapports
        if (!empty($rapports)) {
            $stmt2 = $pdo->prepare("INSERT INTO compte_rendu_rapport (id_CR, id_rapport) VALUES (?, ?)");
            foreach ($rapports as $id_rapport) {
                $stmt2->execute([$id_CR, $id_rapport]);
            }
        }
        return $id_CR;
    }

    public static function getAllArchives($limit = null, $offset = 0, $search = null, $year = null)
    {
        $pdo = Database::getConnection();

        $sql = "SELECT cr.*, e.nom_etu, e.prenom_etu, e.email_etu 
                FROM compte_rendu cr 
                JOIN etudiants e ON cr.num_etu = e.num_carte_etud 
                WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (cr.nom_CR LIKE ? OR cr.contenu_CR LIKE ? OR e.nom_etu LIKE ? OR e.prenom_etu LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if ($year) {
            $sql .= " AND YEAR(cr.date_CR) = ?";
            $params[] = $year;
        }

        $sql .= " ORDER BY cr.date_CR DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . intval($limit);
            if ($offset > 0) {
                $sql .= " OFFSET " . intval($offset);
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getArchiveById($id_CR)
    {
        $pdo = Database::getConnection();

        // Récupérer le compte rendu avec les infos de l'étudiant
        $stmt = $pdo->prepare("
            SELECT cr.*, e.nom_etu, e.prenom_etu, e.email_etu 
            FROM compte_rendu cr 
            JOIN etudiants e ON cr.num_etu = e.num_carte_etud 
            WHERE cr.id_CR = ?
        ");
        $stmt->execute([$id_CR]);
        $compteRendu = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($compteRendu) {
            // Récupérer les rapports associés
            $stmt2 = $pdo->prepare("
                SELECT r.*, e.nom_etu, e.prenom_etu, e.email_etu 
                FROM compte_rendu_rapport crr 
                JOIN rapport_etudiants r ON crr.id_rapport = r.id_rapport 
                JOIN etudiants e ON r.num_etu = e.num_carte_etud 
                WHERE crr.id_CR = ?
            ");
            $stmt2->execute([$id_CR]);
            $compteRendu['rapports'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }

        return $compteRendu;
    }

    public static function getStatsArchives()
    {
        $pdo = Database::getConnection();

        $stats = [];

        // Total des comptes rendus
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM compte_rendu");
        $stats['total'] = $stmt->fetchColumn();

        // Comptes rendus par année
        $stmt = $pdo->query("
            SELECT YEAR(date_CR) as annee, COUNT(*) as count 
            FROM compte_rendu 
            GROUP BY YEAR(date_CR) 
            ORDER BY annee DESC
        ");
        $stats['par_annee'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Comptes rendus du mois en cours
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM compte_rendu 
            WHERE MONTH(date_CR) = MONTH(CURRENT_DATE()) 
            AND YEAR(date_CR) = YEAR(CURRENT_DATE())
        ");
        $stats['ce_mois'] = $stmt->fetchColumn();

        // Comptes rendus de la semaine
        $stmt = $pdo->query("
            SELECT COUNT(*) as count 
            FROM compte_rendu 
            WHERE date_CR >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
        ");
        $stats['cette_semaine'] = $stmt->fetchColumn();

        return $stats;
    }

    public static function deleteArchive($id_CR)
    {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            // Supprimer les liaisons avec les rapports
            $stmt = $pdo->prepare("DELETE FROM compte_rendu_rapport WHERE id_CR = ?");
            $stmt->execute([$id_CR]);

            // Supprimer le compte rendu
            $stmt = $pdo->prepare("DELETE FROM compte_rendu WHERE id_CR = ?");
            $stmt->execute([$id_CR]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur lors de la suppression de l'archive: " . $e->getMessage());
            return false;
        }
    }
}