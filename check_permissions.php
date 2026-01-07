<?php
// Vérifier les permissions pour le Responsable scolarité

$pdo = new PDO('mysql:host=localhost;dbname=ufrmi1802974_2q2mpf', 'root', '');

// Vérifier les permissions pour id_GU = 8
$stmt = $pdo->query("SELECT COUNT(*) as nb FROM permissions WHERE id_GU = 8");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Nombre de permissions pour Responsable scolarité (id_GU=8): " . $result['nb'] . "\n\n";

if ($result['nb'] > 0) {
    echo "Détail des permissions:\n";
    echo str_repeat("-", 100) . "\n";

    $stmt = $pdo->query("
        SELECT p.id_permission, p.id_GU, f.code_fonctionnalite, f.lib_fonctionnalite, 
               p.peut_voir, p.peut_creer, p.peut_modifier, p.peut_supprimer
        FROM permissions p 
        JOIN fonctionnalites f ON p.id_fonctionnalite = f.id_fonctionnalite 
        WHERE p.id_GU = 8
        ORDER BY f.lib_fonctionnalite
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        printf(
            "%-40s | Voir: %d | Créer: %d | Modifier: %d | Supprimer: %d\n",
            $row['lib_fonctionnalite'],
            $row['peut_voir'],
            $row['peut_creer'],
            $row['peut_modifier'],
            $row['peut_supprimer']
        );
    }
}

// Vérifier aussi quelques codes de fonctionnalités
echo "\n\nExemple de codes de fonctionnalités dans la base:\n";
echo str_repeat("-", 100) . "\n";
$stmt = $pdo->query("SELECT code_fonctionnalite, lib_fonctionnalite FROM fonctionnalites LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("%-40s => %s\n", $row['lib_fonctionnalite'], $row['code_fonctionnalite']);
}
