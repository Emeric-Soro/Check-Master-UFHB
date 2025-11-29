<?php
// Script de test pour vérifier les critères par année académique
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/controllers/EvaluationSoutenanceController.php';

echo "<h2>Test des critères d'évaluation par année académique</h2>";

$controller = new EvaluationSoutenanceController();

// 1. Vérifier l'année académique courante
$anneeAcademique = $controller->getAnneeAcademiqueCourante();
echo "<h3>1. Année académique courante :</h3>";
if ($anneeAcademique) {
    echo "<p>ID: " . $anneeAcademique['id_annee_acad'] . "</p>";
    echo "<p>Période: " . $anneeAcademique['date_deb'] . " au " . $anneeAcademique['date_fin'] . "</p>";
} else {
    echo "<p style='color: red;'>Aucune année académique trouvée !</p>";
}

// 2. Vérifier tous les critères disponibles
echo "<h3>2. Tous les critères (table critere_evaluation) :</h3>";
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query("SELECT id_critere, lib_critere FROM critere_evaluation ORDER BY id_critere");
    $tousCriteres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<ul>";
    foreach ($tousCriteres as $critere) {
        echo "<li>ID: " . $critere['id_critere'] . " - " . htmlspecialchars($critere['lib_critere']) . "</li>";
    }
    echo "</ul>";
    echo "<p>Total: " . count($tousCriteres) . " critères</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}

// 3. Vérifier les barèmes définis (table correspondre)
echo "<h3>3. Barèmes définis (table correspondre) :</h3>";
try {
    $stmt = $pdo->query("
        SELECT 
            cor.id_annee_acad, 
            cor.id_critere, 
            cor.bareme,
            c.lib_critere,
            aa.date_deb,
            aa.date_fin
        FROM correspondre cor
        JOIN critere_evaluation c ON cor.id_critere = c.id_critere
        JOIN annee_academique aa ON cor.id_annee_acad = aa.id_annee_acad
        ORDER BY cor.id_annee_acad, cor.id_critere
    ");
    $baremes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($baremes)) {
        echo "<p style='color: orange;'>Aucun barème défini dans la table correspondre !</p>";
        echo "<p>Il faut créer des enregistrements dans cette table pour lier les critères aux années académiques.</p>";
    } else {
        $parAnnee = [];
        foreach ($baremes as $bareme) {
            $anneeLabel = $bareme['date_deb'] . " - " . $bareme['date_fin'];
            if (!isset($parAnnee[$anneeLabel])) {
                $parAnnee[$anneeLabel] = [];
            }
            $parAnnee[$anneeLabel][] = $bareme;
        }

        foreach ($parAnnee as $annee => $baremes) {
            echo "<h4>Année " . $annee . " :</h4>";
            echo "<ul>";
            foreach ($baremes as $bareme) {
                echo "<li>" . htmlspecialchars($bareme['lib_critere']) . " : " . $bareme['bareme'] . " points max</li>";
            }
            echo "</ul>";
        }
        echo "<p>Total: " . count($baremes) . " barèmes définis</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}

// 4. Tester la méthode getCriteresEvaluation()
echo "<h3>4. Critères retournés par getCriteresEvaluation() :</h3>";
$criteres = $controller->getCriteresEvaluation();

if (empty($criteres)) {
    echo "<p style='color: red;'>Aucun critère retourné ! Cela signifie qu'aucun barème n'est défini pour l'année académique courante.</p>";
} else {
    echo "<ul>";
    foreach ($criteres as $critere) {
        echo "<li>ID: " . $critere['id_critere'] . " - " . htmlspecialchars($critere['lib_critere']) . " (Note max: " . $critere['bareme_max'] . ")</li>";
    }
    echo "</ul>";
    echo "<p>Total: " . count($criteres) . " critères avec barèmes pour l'année courante</p>";
}

// 5. Recommandations
echo "<h3>5. Recommandations :</h3>";
if (empty($baremes)) {
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px;'>";
    echo "<h4>Action requise :</h4>";
    echo "<p>Il faut insérer des données dans la table <code>correspondre</code> pour définir les barèmes de chaque critère pour chaque année académique.</p>";
    echo "<p>Exemple de requête SQL :</p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
    echo "INSERT INTO correspondre (id_annee_acad, id_critere, bareme) VALUES\n";
    echo "(1, 1, 15),  -- Critère 1 avec 15 points max pour l'année 1\n";
    echo "(1, 2, 20),  -- Critère 2 avec 20 points max pour l'année 1\n";
    echo "...";
    echo "</pre>";
    echo "</div>";
}
?>