<?php
require_once __DIR__ . '/../app/config/database.php';

$pdo = Database::getConnection();

echo "🔍 Vérification des permissions Admin après sauvegarde\n\n";

$sql = "SELECT 
    f.id_fonctionnalite,
    f.code_fonctionnalite,
    f.lib_fonctionnalite,
    p.peut_voir,
    p.peut_creer,
    p.peut_modifier,
    p.peut_supprimer
FROM permissions p
JOIN fonctionnalites f ON p.id_fonctionnalite = f.id_fonctionnalite
WHERE p.id_GU = 5
ORDER BY f.id_fonctionnalite ASC
LIMIT 10";

$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📊 10 premières permissions du groupe Administrateur:\n";
echo str_repeat('-', 90) . "\n";
printf("%-4s %-20s %-30s %4s %5s %8s %9s\n", 'ID', 'Code', 'Libellé', 'Voir', 'Créer', 'Modifier', 'Suppr');
echo str_repeat('-', 90) . "\n";

foreach ($results as $r) {
    printf("%-4s %-20s %-30s %4s %5s %8s %9s\n",
        $r['id_fonctionnalite'],
        substr($r['code_fonctionnalite'], 0, 20),
        substr($r['lib_fonctionnalite'], 0, 30),
        $r['peut_voir'] ? '✓' : '✗',
        $r['peut_creer'] ? '✓' : '✗',
        $r['peut_modifier'] ? '✓' : '✗',
        $r['peut_supprimer'] ? '✓' : '✗'
    );
}

// Compter le total
$total = $pdo->query("SELECT COUNT(*) FROM permissions WHERE id_GU = 5")->fetchColumn();
echo str_repeat('-', 90) . "\n";
echo "\nTotal permissions: $total\n";
