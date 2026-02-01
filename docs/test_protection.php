<?php
/**
 * Script de test pour vérifier la protection
 */

require_once __DIR__ . '/../app/config/database.php';

try {
    $pdo = Database::getConnection();
    
    echo "🔍 Diagnostic de la protection anti-lockout\n\n";
    
    // 1. Vérifier la session simulée
    echo "📋 SESSION SIMULÉE:\n";
    echo "   - id_GU: 5 (Administrateur)\n";
    echo "   - id_utilisateur: 5\n\n";
    
    // 2. Trouver la fonctionnalité gestion_attribution
    echo "🔎 Recherche de la fonctionnalité 'gestion_attribution'...\n";
    $stmtFonc = $pdo->prepare(
        "SELECT id_fonctionnalite, code_fonctionnalite, lib_fonctionnalite, url_fonctionnalite 
         FROM fonctionnalites 
         WHERE url_fonctionnalite LIKE '%gestion_attribution%' 
         OR code_fonctionnalite = 'PARAM_ATTRIB' 
         LIMIT 1"
    );
    $stmtFonc->execute();
    $currentPageFonc = $stmtFonc->fetch(PDO::FETCH_ASSOC);
    
    if ($currentPageFonc) {
        echo "✅ Fonctionnalité trouvée:\n";
        echo "   - ID: {$currentPageFonc['id_fonctionnalite']}\n";
        echo "   - Code: {$currentPageFonc['code_fonctionnalite']}\n";
        echo "   - Libellé: {$currentPageFonc['lib_fonctionnalite']}\n";
        echo "   - URL: {$currentPageFonc['url_fonctionnalite']}\n\n";
        
        $currentPageId = (int)$currentPageFonc['id_fonctionnalite'];
        
        // 3. Vérifier les permissions actuelles
        echo "📊 Permissions actuelles pour Admin (groupe 5):\n";
        $sql = "SELECT peut_voir, peut_creer, peut_modifier, peut_supprimer 
                FROM permissions 
                WHERE id_GU = 5 AND id_fonctionnalite = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentPageId]);
        $perm = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($perm) {
            echo "   - Voir: " . ($perm['peut_voir'] ? '✓' : '✗') . "\n";
            echo "   - Créer: " . ($perm['peut_creer'] ? '✓' : '✗') . "\n";
            echo "   - Modifier: " . ($perm['peut_modifier'] ? '✓' : '✗') . "\n";
            echo "   - Supprimer: " . ($perm['peut_supprimer'] ? '✓' : '✗') . "\n\n";
        } else {
            echo "   ❌ Aucune permission trouvée!\n\n";
        }
        
        // 4. Simuler le POST sans ces permissions
        echo "🧪 SIMULATION: Si on envoie un POST SANS cocher les cases...\n";
        $permissions = []; // Aucune permission cochée
        
        $groupeId = 5;
        $isModifyingOwnGroup = (int)$groupeId === 5; // Simule $_SESSION['id_GU']
        
        echo "   - isModifyingOwnGroup: " . ($isModifyingOwnGroup ? 'OUI' : 'NON') . "\n";
        
        if ($isModifyingOwnGroup) {
            echo "   - Protection activée!\n";
            echo "   - Forçage de voir = 'on'\n";
            echo "   - Forçage de modifier = 'on'\n";
            
            if (!isset($permissions[$currentPageId]['voir'])) {
                $permissions[$currentPageId]['voir'] = 'on';
            }
            if (!isset($permissions[$currentPageId]['modifier'])) {
                $permissions[$currentPageId]['modifier'] = 'on';
            }
            
            echo "\n   ✅ Permissions après protection:\n";
            echo "      - permissions[$currentPageId]['voir'] = '" . ($permissions[$currentPageId]['voir'] ?? 'non défini') . "'\n";
            echo "      - permissions[$currentPageId]['modifier'] = '" . ($permissions[$currentPageId]['modifier'] ?? 'non défini') . "'\n";
        } else {
            echo "   ❌ Protection NON activée (groupe différent)\n";
        }
        
    } else {
        echo "❌ Fonctionnalité 'gestion_attribution' NON trouvée!\n";
        echo "   La requête SQL ne retourne aucun résultat.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
