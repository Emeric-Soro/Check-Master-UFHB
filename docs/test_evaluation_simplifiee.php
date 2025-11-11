<?php
// Script de test rapide pour l'évaluation simplifiée

echo "<h1>Test de l'évaluation simplifiée</h1>";

// Test simulé avec des données
$criteres_test = [
    ['id_critere' => 1, 'lib_critere' => 'Présentation orale', 'bareme_max' => 20],
    ['id_critere' => 2, 'lib_critere' => 'Qualité du rapport', 'bareme_max' => 20],
    ['id_critere' => 3, 'lib_critere' => 'Maîtrise du sujet', 'bareme_max' => 20]
];

$notes_test = [
    1 => 16,  // Présentation orale: 16/20
    2 => 18,  // Qualité du rapport: 18/20  
    3 => 14   // Maîtrise du sujet: 14/20
];

echo "<h2>Critères avec barèmes (tous sur 20)</h2>";
echo "<ul>";
foreach ($criteres_test as $critere) {
    $note = $notes_test[$critere['id_critere']] ?? 0;
    echo "<li>" . $critere['lib_critere'] . " : " . $note . "/" . $critere['bareme_max'] . "</li>";
}
echo "</ul>";

// Calcul simplifié : somme des notes
$somme = array_sum($notes_test);
echo "<h2>Calcul simplifié</h2>";
echo "<p><strong>Somme des notes : " . $somme . "</strong></p>";
echo "<p>Détail : " . implode(" + ", $notes_test) . " = " . $somme . "</p>";

// Mention basée sur la moyenne (pour information)
$moyenne = $somme / count($notes_test);
echo "<h2>Informations complémentaires</h2>";
echo "<p>Moyenne (pour mention) : " . number_format($moyenne, 2) . "/20</p>";

if ($moyenne >= 16) {
    $mention = 'Très Bien';
} else if ($moyenne >= 14) {
    $mention = 'Bien';
} else if ($moyenne >= 12) {
    $mention = 'Assez Bien';
} else if ($moyenne >= 10) {
    $mention = 'Passable';
} else {
    $mention = 'Insuffisant';
}

echo "<p>Mention : " . $mention . "</p>";

echo "<h2>Avantages du nouveau système :</h2>";
echo "<ul>";
echo "<li>✅ <strong>Simple :</strong> Juste la somme des notes</li>";
echo "<li>✅ <strong>Intuitif :</strong> Plus la note est haute, mieux c'est</li>";
echo "<li>✅ <strong>Flexible :</strong> Chaque critère peut avoir un barème différent selon l'année</li>";
echo "<li>✅ <strong>Validé :</strong> Contrôle automatique que les notes ne dépassent pas le barème</li>";
echo "<li>✅ <strong>Épuré :</strong> Suppression du champ 'note finale' redondant</li>";
echo "</ul>";

?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }

    h1,
    h2 {
        color: #333;
    }

    ul {
        margin-left: 20px;
    }

    li {
        margin: 5px 0;
    }

    p {
        margin: 10px 0;
    }
</style>