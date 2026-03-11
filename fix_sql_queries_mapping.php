<?php
/**
 * Script de correction automatique des requêtes SQL
 * Adapte toutes les requêtes à la nouvelle structure de la base de données
 * où num_etu a été remplacé par num_carte_etud dans la table etudiants
 */

// Liste des fichiers à corriger et leurs remplacements
$corrections = [
    // Format: 'fichier' => [['ancien' => '...', 'nouveau' => '...']]
    'app/models/Scolarite.php' => [
        ['ancien' => 'SELECT num_etu, nom_etu, prenom_etu FROM etudiants WHERE num_etu NOT IN', 'nouveau' => 'SELECT num_carte_etud as num_etu, nom_etu, prenom_etu FROM etudiants WHERE num_carte_etud NOT IN'],
        ['ancien' => 'SELECT num_etu, nom_etu, prenom_etu FROM etudiants WHERE num_etu = ?', 'nouveau' => 'SELECT num_carte_etud as num_etu, nom_etu, prenom_etu FROM etudiants WHERE num_carte_etud = ?'],
        ['ancien' => 'JOIN etudiants e ON i.id_etudiant = e.num_etu', 'nouveau' => 'JOIN etudiants e ON i.id_etudiant = e.num_carte_etud'],
    ],
    '

app/models/RapportEtudiant.php' => [
        ['ancien' => 'JOIN etudiants e ON r.num_etu = e.num_etu', 'nouveau' => 'JOIN etudiants e ON r.num_etu = e.num_carte_etud'],
        ['ancien' => 'LEFT JOIN rapport_etudiants r ON e.num_etu = r.num_etu', 'nouveau' => 'LEFT JOIN rapport_etudiants r ON e.num_carte_etud = r.num_etu'],
        ['ancien' => 'SELECT COUNT(*) FROM etudiants WHERE num_etu = ?', 'nouveau' => 'SELECT COUNT(*) FROM etudiants WHERE num_carte_etud = ?'],
    ],
    'app/models/CompteRendu.php' => [
        ['ancien' => 'JOIN etudiants e ON cr.num_etu = e.num_etu', 'nouveau' => 'JOIN etudiants e ON cr.num_etu = e.num_carte_etud'],
        ['ancien' => 'JOIN etudiants e ON r.num_etu = e.num_etu', 'nouveau' => 'JOIN etudiants e ON r.num_etu = e.num_carte_etud'],
    ],
    'app/models/Archive.php' => [
        ['ancien' => 'LEFT JOIN rapport_etudiants r ON e.num_etu = r.num_etu', 'nouveau' => 'LEFT JOIN rapport_etudiants r ON e.num_carte_etud = r.num_etu'],
        ['ancien' => 'INNER JOIN etudiants e ON p.num_etud = e.num_etu', 'nouveau' => 'INNER JOIN etudiants e ON p.num_etud = e.num_carte_etud'],
        ['ancien' => 'LEFT JOIN etudiants e ON e.num_etu = i.id_etudiant', 'nouveau' => 'LEFT JOIN etudiants e ON e.num_carte_etud = i.id_etudiant'],
        ['ancien' => 'UPDATE etudiants SET', 'nouveau' => 'UPDATE etudiants SET', 'condition' => 'WHERE num_etu', 'condition_nouveau' => 'WHERE num_carte_etud'],
    ],
    'app/models/Reclamation.php' => [
        ['ancien' => 'LEFT JOIN etudiants e ON r.num_etu = e.num_etu', 'nouveau' => 'LEFT JOIN etudiants e ON r.num_etu = e.num_carte_etud'],
        ['ancien' => 'JOIN etudiants e ON r.num_etu = e.num_etu', 'nouveau' => 'JOIN etudiants e ON r.num_etu = e.num_carte_etud'],
    ],
    'app/models/Valider.php' => [
        ['ancien' => 'JOIN etudiants e ON r.num_etu = e.num_etu', 'nouveau' => 'JOIN etudiants e ON r.num_etu = e.num_carte_etud'],
    ],
    'app/models/Approuver.php' => [
        ['ancien' => 'JOIN etudiants e ON r.num_etu = e.num_etu', 'nouveau' => 'JOIN etudiants e ON r.num_etu = e.num_carte_etud'],
    ],
    'app/models/EvaluationRapport.php' => [
        ['ancien' => 'JOIN etudiants e ON r.num_etu = e.num_etu', 'nouveau' => 'JOIN etudiants e ON r.num_etu = e.num_carte_etud'],
    ],
    'app/models/Utilisateur.php' => [
        ['ancien' => 'SELECT * FROM etudiants WHERE num_etu = :id', 'nouveau' => 'SELECT * FROM etudiants WHERE num_carte_etud = :id'],
    ],
];

echo "=== LISTE DES CORRECTIONS À APPLIQUER ===\n\n";
foreach ($corrections as $fichier => $replacements) {
    echo "$fichier :\n";
    foreach ($replacements as $i => $repl) {
        echo "  " . ($i + 1) . ". " . substr($repl['ancien'], 0, 60) . "...\n";
    }
    echo "\n";
}

echo "\nNote: Appliquez ces corrections manuellement avec votre éditeur.\n";
echo "Les patterns de recherche/remplacement sont documentés ci-dessus.\n";
