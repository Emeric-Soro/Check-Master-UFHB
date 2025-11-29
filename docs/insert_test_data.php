<?php
// Script pour insérer des données de test dans la table evaluer
require_once __DIR__ . '/app/config/database.php';

try {
    $pdo = Database::getConnection();

    echo "<h2>Insertion de données de test dans la table evaluer</h2>";

    // Données de test
    $testData = [
        ['num_etudiant' => 1, 'num_jury' => 1, 'id_critere' => 1, 'note' => 15],
        ['num_etudiant' => 1, 'num_jury' => 1, 'id_critere' => 2, 'note' => 17],
        ['num_etudiant' => 1, 'num_jury' => 1, 'id_critere' => 3, 'note' => 14],
        ['num_etudiant' => 2, 'num_jury' => 2, 'id_critere' => 1, 'note' => 16],
        ['num_etudiant' => 2, 'num_jury' => 2, 'id_critere' => 2, 'note' => 18],
    ];

    // Supprimer les données existantes
    $pdo->exec("DELETE FROM evaluer");
    echo "<p>✅ Données existantes supprimées</p>";

    // Insérer les nouvelles données
    $stmt = $pdo->prepare("INSERT INTO evaluer (num_etudiant, num_jury, id_critere, date_eval, note) VALUES (?, ?, ?, CURDATE(), ?)");

    foreach ($testData as $data) {
        $success = $stmt->execute([$data['num_etudiant'], $data['num_jury'], $data['id_critere'], $data['note']]);
        if ($success) {
            echo "<p>✅ Évaluation ajoutée: Étudiant {$data['num_etudiant']}, Critère {$data['id_critere']}, Note {$data['note']}</p>";
        } else {
            echo "<p>❌ Erreur pour l'étudiant {$data['num_etudiant']}</p>";
        }
    }

    echo "<h3>Vérification des données insérées:</h3>";
    $stmt = $pdo->query("SELECT * FROM evaluer ORDER BY num_etudiant, id_critere");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>num_etudiant</th><th>num_jury</th><th>id_critere</th><th>date_eval</th><th>note</th></tr>";
    foreach ($results as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td style='padding: 5px;'>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";

    echo "<h3>Test de la fonction getEvaluationExistante:</h3>";

    // Test de la fonction
    require_once __DIR__ . '/app/controllers/EvaluationSoutenanceController.php';
    $controller = new EvaluationSoutenanceController();

    $evaluation1 = $controller->getEvaluationExistante(1);
    echo "<h4>Évaluation pour l'étudiant 1:</h4>";
    echo "<pre>" . json_encode($evaluation1, JSON_PRETTY_PRINT) . "</pre>";

    $evaluation2 = $controller->getEvaluationExistante(2);
    echo "<h4>Évaluation pour l'étudiant 2:</h4>";
    echo "<pre>" . json_encode($evaluation2, JSON_PRETTY_PRINT) . "</pre>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    table {
        border-collapse: collapse;
    }

    th,
    td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
    }

    pre {
        background: #f5f5f5;
        padding: 10px;
        border-radius: 4px;
    }
</style>