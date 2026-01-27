<?php
/**
 * Script d'urgence pour restaurer les permissions administrateur
 */

require_once __DIR__ . '/../app/config/database.php';

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    echo "🚨 Restauration des permissions Administrateur...\n\n";

    $id_admin = 5; // Groupe Administrateur

    // Trouver la fonctionnalité gestion_attribution
    $sql = "SELECT id_fonctionnalite, code_fonctionnalite, lib_fonctionnalite 
            FROM fonctionnalites 
            WHERE url_fonctionnalite LIKE '%gestion_attribution%' 
            OR code_fonctionnalite LIKE '%ATTRIBUTION%'
            OR lib_fonctionnalite LIKE '%attribution%'";
    
    $stmt = $pdo->query($sql);
    $fonctionnalites = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📋 Fonctionnalités trouvées liées aux attributions:\n";
    foreach ($fonctionnalites as $f) {
        echo "   - ID: {$f['id_fonctionnalite']} | Code: {$f['code_fonctionnalite']} | Libellé: {$f['lib_fonctionnalite']}\n";
    }
    echo "\n";

    // Donner TOUS les droits à l'administrateur sur TOUTES les fonctionnalités
    echo "🔓 Attribution de TOUS les droits à l'Administrateur sur TOUTES les fonctionnalités...\n";
    
    // D'abord supprimer les permissions existantes
    $pdo->exec("DELETE FROM permissions WHERE id_GU = $id_admin");
    
    // Puis ajouter toutes les permissions avec tous les droits
    $sql = "INSERT INTO permissions (id_GU, id_fonctionnalite, peut_voir, peut_creer, peut_modifier, peut_supprimer, date_attribution)
            SELECT 
                $id_admin,
                id_fonctionnalite,
                1,
                1,
                1,
                1,
                NOW()
            FROM fonctionnalites
            WHERE actif = 1";
    
    $count = $pdo->exec($sql);
    echo "✅ {$count} permissions ajoutées avec droits complets\n\n";

    // Vérification spécifique pour gestion_attribution
    $sql_check = "SELECT 
        f.code_fonctionnalite,
        f.lib_fonctionnalite,
        p.peut_voir,
        p.peut_creer,
        p.peut_modifier,
        p.peut_supprimer
    FROM permissions p
    JOIN fonctionnalites f ON p.id_fonctionnalite = f.id_fonctionnalite
    WHERE p.id_GU = $id_admin 
    AND (f.url_fonctionnalite LIKE '%attribution%' OR f.lib_fonctionnalite LIKE '%Attribution%')";
    
    $stmt = $pdo->query($sql_check);
    $perms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "🔍 Permissions pour les attributions:\n";
    echo str_repeat('-', 80) . "\n";
    foreach ($perms as $p) {
        printf("%-30s | Voir:%s Créer:%s Modifier:%s Supprimer:%s\n",
            $p['lib_fonctionnalite'],
            $p['peut_voir'] ? '✓' : '✗',
            $p['peut_creer'] ? '✓' : '✗',
            $p['peut_modifier'] ? '✓' : '✗',
            $p['peut_supprimer'] ? '✓' : '✗'
        );
    }
    echo str_repeat('-', 80) . "\n";

    $pdo->commit();
    echo "\n✅ Permissions administrateur restaurées!\n";
    echo "🎯 Tu peux maintenant accéder à toutes les pages.\n";
    echo "⚠️  Reconnecte-toi pour que les changements prennent effet.\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
