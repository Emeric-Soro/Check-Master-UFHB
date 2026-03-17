<?php
// Diagnostic des paiements en attente
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/utils/AcademicYear.php';

try {
    session_start();
    $db = Database::getConnection();
    $selectedYearId = \AcademicYear::getSelectedIdFromSession();

    echo "<h2>Diagnostic Paiements en Attente</h2>";
    echo "<p><strong>Année académique sélectionnée: " . ($selectedYearId ?? 'AUCUNE') . "</strong></p>";
    echo "<p style='background:yellow;padding:10px;margin:10px 0;'>⚠️ Si AUCUNE: il n'y a pas d'année sélectionnée. Le dashboard filtrera les paiements par l'année sélectionnée.</p>";
    // Test 1: Sans filtre d'année
    echo "<h3>1. Sans filtre d'année (solde > 0)</h3>";
    $sql1 = "SELECT i.num_carte_etud, e.nom_etu, e.prenom_etu, i.id_niv_etude, 
                    i.montant_verser, i.solde, i.date_inscription, i.id_annee_acad
             FROM inscriptions i
             JOIN etudiants e ON e.num_carte_etud = i.num_carte_etud
             WHERE i.solde > 0
             ORDER BY i.date_inscription ASC
             LIMIT 10";
    $stmt1 = $db->prepare($sql1);
    $stmt1->execute();
    $results1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Total: " . count($results1) . " enregistrements</p>";
    echo "<table border='1' style='border-collapse:collapse; padding:5px;'>";
    echo "<tr><th>Num Etudiant</th><th>Nom</th><th>Montant versé</th><th>Solde</th><th>Date</th><th>ID Année</th></tr>";
    foreach ($results1 as $row) {
        echo "<tr><td>" . htmlspecialchars($row['num_carte_etud']) . "</td>";
        echo "<td>" . htmlspecialchars($row['nom_etu'] . ' ' . $row['prenom_etu']) . "</td>";
        echo "<td>" . $row['montant_verser'] . "</td>";
        echo "<td>" . $row['solde'] . "</td>";
        echo "<td>" . $row['date_inscription'] . "</td>";
        echo "<td>" . $row['id_annee_acad'] . "</td></tr>";
    }
    echo "</table>";

    // Test 1b: Afficher quels ID années ont des paiements
    echo "<h3>1b. Récapitulatif par année académique</h3>";
    $sqlRecap = "SELECT i.id_annee_acad, COUNT(*) as count, GROUP_CONCAT(DISTINCT CONCAT(e.nom_etu, ' ', e.prenom_etu) SEPARATOR ', ') as etudiants
                 FROM inscriptions i
                 JOIN etudiants e ON e.num_carte_etud = i.num_carte_etud
                 WHERE i.solde > 0
                 GROUP BY i.id_annee_acad
                 ORDER BY i.id_annee_acad";
    $stmtRecap = $db->prepare($sqlRecap);
    $stmtRecap->execute();
    $recaps = $stmtRecap->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Paiements en attente par année académique:</p>";
    foreach ($recaps as $recap) {
        echo "ID Année <strong>" . $recap['id_annee_acad'] . "</strong>: " . $recap['count'] . " paiement(s) (Étudiants: " . htmlspecialchars($recap['etudiants']) . ")<br>";
    }

    // Test 2: Avec filtre d'année (comme dans le service)
    if ($selectedYearId) {
        echo "<h3>2. Avec filtre d'année (id_annee_acad = " . $selectedYearId . ")</h3>";
        $sql2 = "SELECT i.num_carte_etud, e.nom_etu, e.prenom_etu, i.id_niv_etude, 
                        i.montant_verser, i.solde, i.date_inscription, i.id_annee_acad
                 FROM inscriptions i
                 JOIN etudiants e ON e.num_carte_etud = i.num_carte_etud
                 WHERE i.solde > 0 AND i.id_annee_acad = :annee
                 ORDER BY i.date_inscription ASC
                 LIMIT 10";
        $stmt2 = $db->prepare($sql2);
        $stmt2->execute([':annee' => $selectedYearId]);
        $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Total: " . count($results2) . " enregistrements</p>";
        echo "<table border='1' style='border-collapse:collapse; padding:5px;'>";
        echo "<tr><th>Num Etudiant</th><th>Nom</th><th>Montant versé</th><th>Solde</th><th>Date</th><th>ID Année</th></tr>";
        foreach ($results2 as $row) {
            echo "<tr><td>" . htmlspecialchars($row['num_carte_etud']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nom_etu'] . ' ' . $row['prenom_etu']) . "</td>";
            echo "<td>" . $row['montant_verser'] . "</td>";
            echo "<td>" . $row['solde'] . "</td>";
            echo "<td>" . $row['date_inscription'] . "</td>";
            echo "<td>" . $row['id_annee_acad'] . "</td></tr>";
        }
        echo "</table>";
    }

} catch (Exception $e) {
    echo "Erreur: " . htmlspecialchars($e->getMessage());
}
?>