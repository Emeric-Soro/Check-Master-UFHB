<?php
/**
 * Script de test pour la nouvelle architecture de scolarité
 * Architecture simplifiée : table inscriptions uniquement
 */

require_once 'app/config/database.php';
require_once 'app/models/Scolarite.php';

$database = new Database();
$db = $database->getConnection();
$scolarite = new Scolarite($db);

echo "=== TEST DE LA NOUVELLE ARCHITECTURE SCOLARITE ===\n\n";

// Test 1: Vérifier la structure actuelle
echo "1. VERIFICATION DE LA STRUCTURE\n";
echo "--------------------------------\n";
$query = "DESCRIBE inscriptions";
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Colonnes de la table inscriptions:\n";
foreach ($columns as $col) {
    echo "  - {$col['Field']} ({$col['Type']})" . ($col['Null'] === 'YES' ? ' NULL' : ' NOT NULL') . "\n";
}
echo "\n";

// Test 2: Récupérer les étudiants non inscrits
echo "2. LISTE DES ETUDIANTS NON INSCRITS\n";
echo "------------------------------------\n";
$etudiants = $scolarite->getEtudiantsNonInscrits();
echo "Nombre d'étudiants non inscrits: " . count($etudiants) . "\n";
if (count($etudiants) > 0) {
    echo "Premiers étudiants:\n";
    for ($i = 0; $i < min(5, count($etudiants)); $i++) {
        echo "  - {$etudiants[$i]['nom_etu']} {$etudiants[$i]['prenom_etu']} (ID: {$etudiants[$i]['num_etu']})\n";
    }
}
echo "\n";

// Test 3: Récupérer les niveaux d'études
echo "3. NIVEAUX D'ETUDES DISPONIBLES\n";
echo "--------------------------------\n";
$niveaux = $scolarite->getNiveauxEtudes();
echo "Nombre de niveaux: " . count($niveaux) . "\n";
foreach ($niveaux as $niveau) {
    echo "  - {$niveau['lib_niv_etude']}: " . number_format($niveau['montant_scolarite'], 0, ',', ' ') . " FCFA\n";
}
echo "\n";

// Test 4: Vérifier les inscriptions actuelles
echo "4. ETUDIANTS INSCRITS\n";
echo "---------------------\n";
$inscrits = $scolarite->getEtudiantsInscrits();
echo "Nombre d'étudiants inscrits: " . count($inscrits) . "\n";
if (count($inscrits) > 0) {
    foreach ($inscrits as $inscrit) {
        echo "  - {$inscrit['nom']} {$inscrit['prenom']} ({$inscrit['nom_niveau']})\n";
        echo "    Montant scolarité: " . number_format($inscrit['montant_scolarite'], 0, ',', ' ') . " FCFA\n";
        echo "    Montant payé: " . number_format($inscrit['montant_paye'], 0, ',', ' ') . " FCFA\n";
        echo "    Reste à payer: " . number_format($inscrit['reste_a_payer'], 0, ',', ' ') . " FCFA\n";
        echo "    Nombre de versements: {$inscrit['nombre_versements']}\n\n";
    }
}
echo "\n";

// Test 5: Récupérer tous les versements
echo "5. LISTE DE TOUS LES VERSEMENTS\n";
echo "--------------------------------\n";
$versements = $scolarite->getAllVersements();
echo "Nombre total de versements: " . count($versements) . "\n";
if (count($versements) > 0) {
    foreach ($versements as $vers) {
        echo "  - {$vers['nom_etu']} {$vers['prenom_etu']}\n";
        echo "    Versement N°{$vers['num_versement']}: " . number_format($vers['montant_verser'], 0, ',', ' ') . " FCFA\n";
        echo "    Date: {$vers['date_versement']}\n";
        echo "    Méthode: {$vers['methode_paiement']}\n";
        echo "    Solde: " . number_format($vers['solde'], 0, ',', ' ') . " FCFA\n\n";
    }
}
echo "\n";

echo "=== TEST TERMINE ===\n";
