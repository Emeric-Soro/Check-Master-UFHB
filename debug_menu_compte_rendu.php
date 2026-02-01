<?php
/**
 * Script de diagnostic pour le menu "COMPTE RENDU"
 * Exécutez ce fichier via le navigateur pour voir l'état des permissions
 */

require_once __DIR__ . '/app/Core/Autoload.php';

use CheckMaster\Core\Session;
use CheckMaster\Core\Bootstrap;

Bootstrap::init();
Session::start();

include __DIR__ . '/app/config/database.php';

if (!isset($_SESSION['id_utilisateur'])) {
    die('Vous devez être connecté pour exécuter ce diagnostic.');
}

$pdo = Database::getConnection();

$idGU = $_SESSION['id_GU'] ?? 0;
$libGU = $_SESSION['lib_GU'] ?? 'Inconnu';

echo "<h1>Diagnostic du menu COMPTE RENDU</h1>";
echo "<h2>Utilisateur connecté</h2>";
echo "<p><strong>ID Groupe :</strong> $idGU</p>";
echo "<p><strong>Libellé Groupe :</strong> $libGU</p>";
echo "<hr>";

// 1. Vérifier les fonctionnalités liées au compte rendu
echo "<h2>1. Fonctionnalités liées au 'compte rendu'</h2>";
$sql = "SELECT f.*, c.lib_categorie, c.actif as categorie_active 
        FROM fonctionnalites f 
        LEFT JOIN categories_fonctionnalites c ON f.id_categorie = c.id_categorie
        WHERE f.url_fonctionnalite LIKE '%redaction_compte_rendu%' 
           OR f.code_fonctionnalite LIKE '%compte_rendu%'
           OR f.lib_fonctionnalite LIKE '%compte%rendu%'";
$stmt = $pdo->query($sql);
$fonctionnalites = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($fonctionnalites)) {
    echo "<p style='color:red'>❌ Aucune fonctionnalité trouvée pour 'compte rendu'. La fonctionnalité n'existe pas dans la table fonctionnalites.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Code</th><th>Libellé</th><th>URL</th><th>Catégorie</th><th>Actif</th><th>Catégorie Active</th></tr>";
    foreach ($fonctionnalites as $f) {
        $actifStyle = $f['actif'] ? 'color:green' : 'color:red';
        $catStyle = $f['categorie_active'] ? 'color:green' : 'color:red';
        echo "<tr>";
        echo "<td>{$f['id_fonctionnalite']}</td>";
        echo "<td>{$f['code_fonctionnalite']}</td>";
        echo "<td>{$f['lib_fonctionnalite']}</td>";
        echo "<td>{$f['url_fonctionnalite']}</td>";
        echo "<td>{$f['lib_categorie']}</td>";
        echo "<td style='$actifStyle'>" . ($f['actif'] ? '✅ Oui' : '❌ Non') . "</td>";
        echo "<td style='$catStyle'>" . ($f['categorie_active'] ? '✅ Oui' : '❌ Non') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "<hr>";

// 2. Vérifier les permissions pour votre groupe
echo "<h2>2. Permissions pour votre groupe (id_GU = $idGU)</h2>";
$sql = "SELECT f.id_fonctionnalite, f.code_fonctionnalite, f.lib_fonctionnalite, 
               p.peut_voir, p.peut_creer, p.peut_modifier, p.peut_supprimer
        FROM fonctionnalites f
        LEFT JOIN permissions p ON f.id_fonctionnalite = p.id_fonctionnalite AND p.id_GU = :id_GU
        WHERE f.url_fonctionnalite LIKE '%redaction_compte_rendu%' 
           OR f.code_fonctionnalite LIKE '%compte_rendu%'
           OR f.lib_fonctionnalite LIKE '%compte%rendu%'";
$stmt = $pdo->prepare($sql);
$stmt->execute(['id_GU' => $idGU]);
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($permissions)) {
    echo "<p style='color:red'>❌ Aucune permission trouvée.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID Fonc.</th><th>Code</th><th>Libellé</th><th>Peut Voir</th><th>Peut Créer</th><th>Peut Modifier</th><th>Peut Supprimer</th></tr>";
    foreach ($permissions as $p) {
        $voirStyle = $p['peut_voir'] ? 'color:green' : 'color:red';
        echo "<tr>";
        echo "<td>{$p['id_fonctionnalite']}</td>";
        echo "<td>{$p['code_fonctionnalite']}</td>";
        echo "<td>{$p['lib_fonctionnalite']}</td>";
        echo "<td style='$voirStyle'>" . ($p['peut_voir'] ? '✅ Oui' : '❌ Non') . "</td>";
        echo "<td>" . ($p['peut_creer'] ? '✅' : '❌') . "</td>";
        echo "<td>" . ($p['peut_modifier'] ? '✅' : '❌') . "</td>";
        echo "<td>" . ($p['peut_supprimer'] ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
echo "<hr>";

// 3. Vérifier toutes les catégories
echo "<h2>3. État des catégories</h2>";
$sql = "SELECT * FROM categories_fonctionnalites ORDER BY ordre_categorie";
$stmt = $pdo->query($sql);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Code</th><th>Libellé</th><th>Icône</th><th>Ordre</th><th>Actif</th></tr>";
foreach ($categories as $cat) {
    $actifStyle = $cat['actif'] ? 'color:green' : 'color:red';
    echo "<tr>";
    echo "<td>{$cat['id_categorie']}</td>";
    echo "<td>{$cat['code_categorie']}</td>";
    echo "<td>{$cat['lib_categorie']}</td>";
    echo "<td>{$cat['icone_categorie']}</td>";
    echo "<td>{$cat['ordre_categorie']}</td>";
    echo "<td style='$actifStyle'>" . ($cat['actif'] ? '✅ Oui' : '❌ Non') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<hr>";

// 4. Vérifier le menu généré pour votre groupe
echo "<h2>4. Menu généré pour votre groupe</h2>";
require_once __DIR__ . '/app/controllers/MenuController.php';
$menuController = new MenuController();
$menuHierarchique = $menuController->genererMenuHierarchique($idGU);

if (empty($menuHierarchique)) {
    echo "<p style='color:red'>❌ Aucun menu généré pour votre groupe.</p>";
} else {
    echo "<ul>";
    foreach ($menuHierarchique as $item) {
        echo "<li><strong>" . htmlspecialchars($item['categorie']->lib_categorie) . "</strong>";
        echo "<ul>";
        foreach ($item['fonctionnalites'] as $fonc) {
            $isCompteRendu = (strpos($fonc->url_fonctionnalite ?? '', 'compte_rendu') !== false);
            $style = $isCompteRendu ? 'background-color:yellow; font-weight:bold;' : '';
            echo "<li style='$style'>" . htmlspecialchars($fonc->label_fonctionnalite ?? $fonc->lib_fonctionnalite) . " (" . htmlspecialchars($fonc->url_fonctionnalite ?? '') . ")</li>";
        }
        echo "</ul>";
        echo "</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h2>Conclusion</h2>";
echo "<p>Si le menu 'COMPTE RENDU' n'apparaît pas, vérifiez que :</p>";
echo "<ol>";
echo "<li>La fonctionnalité existe dans la table <code>fonctionnalites</code> avec <code>actif = TRUE</code></li>";
echo "<li>La catégorie associée est active (<code>actif = TRUE</code>)</li>";
echo "<li>Une permission <code>peut_voir = TRUE</code> existe dans la table <code>permissions</code> pour votre groupe (id_GU = $idGU)</li>";
echo "</ol>";
