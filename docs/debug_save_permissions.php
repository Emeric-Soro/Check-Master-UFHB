<?php
/**
 * Script de débogage: Vérifier pourquoi les permissions ne se sauvegardent pas
 */

require_once __DIR__ . '/../app/config/database.php';

$conn = Database::getConnection();

echo "🔍 ANALYSE DES PERMISSIONS APRÈS SAUVEGARDE\n";
echo "==========================================\n\n";

// 1. Choisir un groupe de test (pas l'admin)
$groupeTest = 8; // Groupe Étudiant ou autre

echo "📋 Groupe testé: ID = $groupeTest\n\n";

// 2. Compter les permissions AVANT modification
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM permissions WHERE id_GU = ?");
$stmt->execute([$groupeTest]);
$countBefore = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "Permissions AVANT: $countBefore\n";

// 3. Afficher quelques permissions actuelles
$stmt = $conn->prepare("
    SELECT p.*, f.lib_fonctionnalite, f.code_fonctionnalite
    FROM permissions p
    JOIN fonctionnalites f ON p.id_fonctionnalite = f.id_fonctionnalite
    WHERE p.id_GU = ?
    ORDER BY f.ordre_fonctionnalite
    LIMIT 10
");
$stmt->execute([$groupeTest]);
$perms = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\n📝 Exemples de permissions actuelles:\n";
foreach ($perms as $p) {
    $v = $p['peut_voir'] ? '✓' : '✗';
    $c = $p['peut_creer'] ? '✓' : '✗';
    $m = $p['peut_modifier'] ? '✓' : '✗';
    $s = $p['peut_supprimer'] ? '✓' : '✗';
    echo sprintf(
        "  • %s (ID %d): V=%s C=%s M=%s S=%s\n",
        $p['lib_fonctionnalite'],
        $p['id_fonctionnalite'],
        $v, $c, $m, $s
    );
}

echo "\n💡 INSTRUCTIONS:\n";
echo "1. Va sur la page de gestion des attributions\n";
echo "2. Sélectionne le groupe ID=$groupeTest\n";
echo "3. Décoche QUELQUES permissions (par exemple, décoche 'Créer' pour 2-3 fonctionnalités)\n";
echo "4. Clique sur 'Enregistrer'\n";
echo "5. Relance ce script: php docs/debug_save_permissions.php\n";
echo "6. Vérifie si les modifications ont été sauvegardées\n\n";

// 4. Vérifier les groupes disponibles
echo "📚 Groupes utilisateurs disponibles:\n";
$stmt = $conn->query("SELECT id_GU, lib_GU FROM groupe_utilisateur ORDER BY id_GU");
$groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($groupes as $g) {
    echo "  • ID {$g['id_GU']}: {$g['lib_GU']}\n";
}

echo "\n✅ Script terminé\n";
