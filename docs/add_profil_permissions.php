<?php
/**
 * Script pour ajouter les permissions manquantes à la fonctionnalité Profil
 */

require_once __DIR__ . '/../app/config/database.php';

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    echo "🔄 Ajout des permissions pour la fonctionnalité Profil...\n\n";

    // ID de la fonctionnalité Profil (déjà existante)
    $id_profil = 79;

    // Supprimer les anciennes permissions si elles existent
    $pdo->exec("DELETE FROM permissions WHERE id_fonctionnalite = $id_profil");

    // Ajouter les permissions pour tous les groupes
    $sql = "INSERT INTO `permissions` (
        `id_GU`,
        `id_fonctionnalite`,
        `peut_voir`,
        `peut_creer`,
        `peut_modifier`,
        `peut_supprimer`,
        `date_attribution`
    )
    SELECT 
        gu.id_GU,
        :id_profil,
        1,
        0,
        1,
        0,
        NOW()
    FROM groupe_utilisateur gu
    WHERE gu.id_GU IS NOT NULL";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_profil' => $id_profil]);
    $count = $stmt->rowCount();
    echo "✅ Permissions ajoutées pour {$count} groupes d'utilisateurs\n\n";

    // Vérification
    $sql2 = "SELECT 
        gu.id_GU,
        gu.lib_GU AS 'Groupe',
        p.peut_voir AS 'Voir',
        p.peut_modifier AS 'Modifier'
    FROM permissions p
    JOIN groupe_utilisateur gu ON p.id_GU = gu.id_GU
    WHERE p.id_fonctionnalite = :id_profil
    ORDER BY gu.id_GU";

    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute(['id_profil' => $id_profil]);
    $results = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo "📋 Permissions créées:\n";
    echo str_repeat('-', 60) . "\n";
    printf("%-4s %-40s %4s %8s\n", 'ID', 'Groupe', 'Voir', 'Modifier');
    echo str_repeat('-', 60) . "\n";

    foreach ($results as $row) {
        printf(
            "%-4s %-40s %4s %8s\n",
            $row['id_GU'],
            $row['Groupe'],
            $row['Voir'] ? '✓' : '✗',
            $row['Modifier'] ? '✓' : '✗'
        );
    }

    echo str_repeat('-', 60) . "\n";

    $pdo->commit();
    echo "\n✅ Permissions ajoutées avec succès!\n";
    echo "🎯 La page 'Mon Profil' devrait maintenant apparaître dans le menu.\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
