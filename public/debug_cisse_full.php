<?php
// Diagnostic complet pour Cissé Kadidja
require_once __DIR__ . '/../app/config/database.php';

try {
    $db = Database::getConnection();

    echo "<h2>Diagnostic Complet: Cissé Kadidja</h2>";

    // 1. Tous les enregistrements de Cissé dans inscriptions
    echo "<h3>1. TOUS les enregistrements d'inscription de Cissé</h3>";
    $sql1 = "SELECT i.*, e.nom_etu, e.prenom_etu FROM inscriptions i
             LEFT JOIN etudiants e ON e.num_carte_etud = i.num_carte_etud
             WHERE i.num_carte_etud = '161213861/CISS'
             ORDER BY i.date_inscription DESC";
    $stmt1 = $db->prepare($sql1);
    $stmt1->execute();
    $inscriptions = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Total d'enregistrements: " . count($inscriptions) . "</p>";

    if ($inscriptions) {
        echo "<table border='1' style='border-collapse:collapse; padding:5px; width:100%;'>";
        echo "<tr><th>ID</th><th>Num Carte</th><th>Montant versé</th><th>Solde</th><th>Date</th><th>Année</th><th>Etudiant trouvé?</th></tr>";
        foreach ($inscriptions as $insc) {
            $etu_found = (!empty($insc['nom_etu']) ? '✓ ' . $insc['nom_etu'] . ' ' . $insc['prenom_etu'] : '✗ NOT FOUND');
            $expected_solde = 1025000 - (int) $insc['montant_verser'];
            $actual_solde = (int) $insc['solde'];
            $solde_ok = ($expected_solde === $actual_solde) ? '✓' : '✗ ERREUR!';

            echo "<tr>";
            echo "<td>" . htmlspecialchars($insc['id_inscription'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($insc['num_carte_etud']) . "</td>";
            echo "<td>" . number_format((int) $insc['montant_verser'], 0, ',', ' ') . "</td>";
            echo "<td>" . number_format($actual_solde, 0, ',', ' ') . " $solde_ok</td>";
            echo "<td>" . htmlspecialchars($insc['date_inscription']) . "</td>";
            echo "<td>" . htmlspecialchars($insc['id_annee_acad']) . "</td>";
            echo "<td>$etu_found</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>Aucun enregistrement trouvé pour '161213861/CISS'</p>";
    }

    // 2. Vérifier si Cissé existe dans etudiants
    echo "<h3>2. Cissé dans table ETUDIANTS</h3>";
    $sql2 = "SELECT * FROM etudiants WHERE num_carte_etud = '161213861/CISS'";
    $stmt2 = $db->prepare($sql2);
    $stmt2->execute();
    $etu = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($etu) {
        echo "<p>✓ Trouvée dans etudiants:</p>";
        echo "<pre>" . htmlspecialchars(print_r($etu, true)) . "</pre>";
    } else {
        echo "<p style='color:red;'>✗ Cissé N'EXISTE PAS dans table etudiants!</p>";
    }

    // 3. Tous les paiements en attente (pour comparaison)
    echo "<h3>3. Comparaison: les 5 plus anciens paiements en attente (année 22625)</h3>";
    $sql3 = "SELECT i.num_carte_etud, e.nom_etu, e.prenom_etu, 
                    i.montant_verser, i.solde, i.date_inscription, i.id_annee_acad
             FROM inscriptions i
             LEFT JOIN etudiants e ON e.num_carte_etud = i.num_carte_etud
             WHERE i.solde > 0 AND i.id_annee_acad = 22625
             ORDER BY i.date_inscription ASC
             LIMIT 5";
    $stmt3 = $db->prepare($sql3);
    $stmt3->execute();
    $paiements = $stmt3->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' style='border-collapse:collapse; padding:5px; width:100%;'>";
    echo "<tr><th>Num Carte</th><th>Nom Etudiant</th><th>Montant versé</th><th>Solde</th><th>Trouvé dans etudiants?</th></tr>";
    foreach ($paiements as $p) {
        $name = (!empty($p['nom_etu']) ? $p['nom_etu'] . ' ' . $p['prenom_etu'] : '✗ NOT FOUND');
        echo "<tr>";
        echo "<td>" . htmlspecialchars($p['num_carte_etud']) . "</td>";
        echo "<td>" . htmlspecialchars($name) . "</td>";
        echo "<td>" . number_format((int) $p['montant_verser'], 0, ',', ' ') . "</td>";
        echo "<td>" . number_format((int) $p['solde'], 0, ',', ' ') . "</td>";
        echo "<td>" . (!empty($p['nom_etu']) ? '✓' : '✗ MISSING') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "Erreur: " . htmlspecialchars($e->getMessage());
}
?>