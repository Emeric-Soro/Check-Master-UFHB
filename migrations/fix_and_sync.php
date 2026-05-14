<?php
/**
 * Script de correction et synchronisation
 * 
 * 1. Migration BD : Ajoute date_operation et id_candidature à rapport_etudiants (sans IF NOT EXISTS)
 * 2. Synchronisation des permissions : Crée les fonctionnalités RAPPORT_ETU et RAPPORT_ADMIN
 *    dans les tables fonctionnalites et permissions
 * 
 * Usage : php migrations/fix_and_sync.php
 */

require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/config/permission_registry.php';
require_once __DIR__ . '/../app/models/Fonctionnalite.php';
require_once __DIR__ . '/../app/models/Categorie.php';
require_once __DIR__ . '/../app/models/Permission.php';

echo "=== Correction BD + Synchronisation des permissions ===\n\n";

$pdo = Database::getConnection();
$fonctionnaliteModel = new Fonctionnalite($pdo);
$categorieModel = new Categorie($pdo);
$permissionModel = new Permission($pdo);

// ============================================================
// PARTIE 1 : Migration BD
// ============================================================
echo "--- PARTIE 1 : Migration BD (rapport_etudiants) ---\n";

try {
    // Vérifier si la colonne date_operation existe
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `rapport_etudiants` LIKE 'date_operation'");
    $stmt->execute();
    $hasDateOperation = (bool) $stmt->fetch();

    if (!$hasDateOperation) {
        $pdo->exec("ALTER TABLE `rapport_etudiants` 
                    ADD COLUMN `date_operation` datetime DEFAULT NULL COMMENT 'Date métier (opération) modifiable par admin' 
                    AFTER `date_redaction_rapport`");
        echo "✓ Colonne `date_operation` ajoutée\n";

        // Initialiser date_operation depuis date_redaction_rapport
        $pdo->exec("UPDATE `rapport_etudiants` SET `date_operation` = `date_redaction_rapport` WHERE `date_operation` IS NULL");
        echo "✓ date_operation initialisée depuis date_redaction_rapport\n";
    } else {
        echo "→ Colonne `date_operation` existe déjà\n";
    }

    // Vérifier si la colonne id_candidature existe
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `rapport_etudiants` LIKE 'id_candidature'");
    $stmt->execute();
    $hasIdCandidature = (bool) $stmt->fetch();

    if (!$hasIdCandidature) {
        $pdo->exec("ALTER TABLE `rapport_etudiants` 
                    ADD COLUMN `id_candidature` int DEFAULT NULL AFTER `num_etu`,
                    ADD KEY `fk_rapport_candidature` (`id_candidature`)");
        echo "✓ Colonne `id_candidature` ajoutée avec index\n";
    } else {
        echo "→ Colonne `id_candidature` existe déjà\n";
    }
} catch (Exception $e) {
    echo "✗ Erreur BD : " . $e->getMessage() . "\n";
}

echo "\n";

// ============================================================
// PARTIE 2 : Synchronisation des permissions
// ============================================================
echo "--- PARTIE 2 : Synchronisation des permissions ---\n";

// Mapping category_code → id_categorie
$categories = [];
$allCategories = $categorieModel->getAllCategories();
foreach ($allCategories as $cat) {
    $categories[$cat->code_categorie] = (int) $cat->id_categorie;
}

echo "Catégories trouvées : " . implode(', ', array_keys($categories)) . "\n\n";

// Les features à synchroniser (depuis permission_registry.php)
$featuresToSync = [
    'telechargement_rapport_etudiant_etu' => [
        'code' => 'RAPPORT_ETU',
        'label' => 'Téléchargement rapport étudiant (vue étudiant)',
        'libelle' => 'Téléchargement rapport étudiant',
        'description' => 'Dépôt du rapport de stage par l\'étudiant',
        'url' => '?page=gestion_rapports&action=telecharger_rapport',
        'category_code' => 'ETUDIANT_ENV',
        'icone' => 'fas fa-file-upload',
        'ordre' => 15,
        'permissions' => [
            5 => ['voir' => 1, 'creer' => 1, 'modifier' => 1, 'supprimer' => 1], // administrateur
            13 => ['voir' => 1, 'creer' => 1, 'modifier' => 1, 'supprimer' => 0], // etudiant
        ],
    ],
    'telechargement_rapport_etudiant_admin' => [
        'code' => 'RAPPORT_ADMIN',
        'label' => 'Téléchargement rapport étudiant (vue administration)',
        'libelle' => 'Import rapports étudiants',
        'description' => 'Import de rapports pour les étudiants + liste des rapports',
        'url' => '?page=gestion_rapports&action=admin_telecharger_rapport',
        'category_code' => 'SCOLARITE',
        'icone' => 'fas fa-file-import',
        'ordre' => 20,
        'permissions' => [
            5 => ['voir' => 1, 'creer' => 1, 'modifier' => 1, 'supprimer' => 1], // administrateur
            6 => ['voir' => 1, 'creer' => 1, 'modifier' => 1, 'supprimer' => 0], // secretaire
            8 => ['voir' => 1, 'creer' => 1, 'modifier' => 1, 'supprimer' => 0], // responsable_scolarite
        ],
    ],
];

$success = true;

foreach ($featuresToSync as $slug => $feature) {
    $code = $feature['code'];
    $label = $feature['label'];
    $libelle = $feature['libelle'];
    $description = $feature['description'];
    $url = $feature['url'];
    $icone = $feature['icone'];
    $ordre = $feature['ordre'];
    $categoryCode = $feature['category_code'];

    // Vérifier la catégorie
    $idCategorie = $categories[$categoryCode] ?? null;
    if ($idCategorie === null) {
        echo "✗ [$code] Catégorie '$categoryCode' introuvable dans la BD. Création...\n";
        try {
            $categorieModel->createCategorie([
                'code_categorie' => $categoryCode,
                'lib_categorie' => $categoryCode === 'ETUDIANT_ENV' ? 'Environnement Étudiant' : 'Gestion de la scolarité',
                'description_categorie' => '',
                'icone_categorie' => $categoryCode === 'ETUDIANT_ENV' ? 'fas fa-user-graduate' : 'fas fa-school',
                'ordre_categorie' => $categoryCode === 'ETUDIANT_ENV' ? 2 : 1,
                'actif' => 1,
            ]);
            // Recharger
            $allCategories = $categorieModel->getAllCategories();
            foreach ($allCategories as $cat) {
                $categories[$cat->code_categorie] = (int) $cat->id_categorie;
            }
            $idCategorie = $categories[$categoryCode] ?? null;
            echo "✓ Catégorie '$categoryCode' créée (id=$idCategorie)\n";
        } catch (Exception $e) {
            echo "✗ Erreur création catégorie : " . $e->getMessage() . "\n";
            $success = false;
            continue;
        }
    }

    // Vérifier si la fonctionnalité existe déjà (par slug_permission puis par code)
    $existing = $fonctionnaliteModel->getFonctionnaliteBySlugPermission($slug);
    if (!$existing) {
        $existing = $fonctionnaliteModel->getFonctionnaliteByCode($code);
    }

    if ($existing) {
        echo "→ [$code] Existe déjà (id={$existing->id_fonctionnalite}). Mise à jour...\n";
        try {
            $fonctionnaliteModel->updateFonctionnalite($existing->id_fonctionnalite, [
                'lib_fonctionnalite' => $libelle,
                'label_fonctionnalite' => $label,
                'description_fonctionnalite' => $description,
                'url_fonctionnalite' => $url,
                'icone_fonctionnalite' => $icone,
                'ordre_fonctionnalite' => $ordre,
                'actif' => 1,
                'slug_permission' => $slug,
            ]);
            $idFonctionnalite = (int) $existing->id_fonctionnalite;
            echo "✓ [$code] Mise à jour effectuée\n";
        } catch (Exception $e) {
            echo "✗ [$code] Erreur mise à jour : " . $e->getMessage() . "\n";
            $success = false;
            continue;
        }
    } else {
        echo "→ [$code] Nouvelle fonctionnalité. Création...\n";
        try {
            $fonctionnaliteModel->createFonctionnalite([
                'id_categorie' => $idCategorie,
                'code_fonctionnalite' => $code,
                'lib_fonctionnalite' => $libelle,
                'label_fonctionnalite' => $label,
                'description_fonctionnalite' => $description,
                'url_fonctionnalite' => $url,
                'icone_fonctionnalite' => $icone,
                'ordre_fonctionnalite' => $ordre,
                'est_sous_page' => 0,
                'page_parente' => '',
                'actif' => 1,
                'slug_permission' => $slug,
            ]);
            // Récupérer l'ID
            $newFeature = $fonctionnaliteModel->getFonctionnaliteBySlugPermission($slug);
            if (!$newFeature) {
                $newFeature = $fonctionnaliteModel->getFonctionnaliteByCode($code);
            }
            $idFonctionnalite = $newFeature ? (int) $newFeature->id_fonctionnalite : null;
            if ($idFonctionnalite) {
                echo "✓ [$code] Créée avec id=$idFonctionnalite\n";
            } else {
                echo "✗ [$code] Créée mais impossible de récupérer l'ID\n";
                $success = false;
                continue;
            }
        } catch (Exception $e) {
            echo "✗ [$code] Erreur création : " . $e->getMessage() . "\n";
            $success = false;
            continue;
        }
    }

    // Attribuer les permissions pour chaque groupe
    // createPermission utilise ON DUPLICATE KEY UPDATE => crée ou met à jour
    $groupPermissions = $feature['permissions'];
    foreach ($groupPermissions as $idGU => $caps) {
        try {
            $permissionModel->createPermission([
                'id_GU' => $idGU,
                'id_fonctionnalite' => $idFonctionnalite,
                'peut_voir' => $caps['voir'],
                'peut_creer' => $caps['creer'],
                'peut_modifier' => $caps['modifier'],
                'peut_supprimer' => $caps['supprimer'],
            ]);
            echo "   ✓ Permission GU#$idGU (voir={$caps['voir']} creer={$caps['creer']} modif={$caps['modifier']} suppr={$caps['supprimer']})\n";
        } catch (Exception $e) {
            echo "   ✗ GU#$idGU Erreur : " . $e->getMessage() . "\n";
            $success = false;
        }
    }

    echo "\n";
}

echo $success ? "✓ Synchronisation terminée avec succès!\n" : "✗ Synchronisation terminée avec des erreurs.\n";
echo "\n=== RÉCAPITULATIF ===\n";
echo "Nouvelles fonctionnalités disponibles dans le système :\n";
echo "  - RAPPORT_ETU  → Téléchargement rapport étudiant (vue étudiant)\n";
echo "  - RAPPORT_ADMIN → Import rapports étudiants (vue administration)\n";
echo "\nPour que les menus apparaissent, allez dans Paramètres → Gestion des menus\n";
echo "et vérifiez que les fonctionnalités sont activées.\n";
