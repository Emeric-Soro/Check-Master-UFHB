<?php
/**
 * Script de test pour créer une inscription avec premier versement
 * Test de la nouvelle architecture
 */

require_once 'app/config/database.php';
require_once 'app/models/Scolarite.php';

$database = new Database();
$db = $database->getConnection();
$scolarite = new Scolarite($db);

echo "=== TEST D'INSCRIPTION AVEC PREMIER VERSEMENT ===\n\n";

try {
    // Récupérer le premier étudiant disponible
    $etudiants = $scolarite->getEtudiantsNonInscrits();
    if (empty($etudiants)) {
        echo "❌ Aucun étudiant disponible pour l'inscription.\n";
        exit;
    }

    $etudiant = $etudiants[0];
    echo "✓ Étudiant sélectionné: {$etudiant['nom_etu']} {$etudiant['prenom_etu']} (ID: {$etudiant['num_etu']})\n";

    // Récupérer le premier niveau
    $niveaux = $scolarite->getNiveauxEtudes();
    if (empty($niveaux)) {
        echo "❌ Aucun niveau d'études disponible.\n";
        exit;
    }

    $niveau = $niveaux[0];
    echo "✓ Niveau sélectionné: {$niveau['lib_niv_etude']} - " . number_format($niveau['montant_scolarite'], 0, ',', ' ') . " FCFA\n";

    // Récupérer l'année académique active
    $query = "SELECT * FROM annee_academique WHERE CURDATE() BETWEEN date_deb AND date_fin LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $annee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$annee) {
        // Prendre la dernière année si aucune n'est active
        $query = "SELECT * FROM annee_academique ORDER BY id_annee_acad DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $annee = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$annee) {
        echo "❌ Aucune année académique disponible.\n";
        exit;
    }

    $anneeLabel = date('Y', strtotime($annee['date_deb'])) . '-' . date('Y', strtotime($annee['date_fin']));
    echo "✓ Année académique: {$anneeLabel} (ID: {$annee['id_annee_acad']})\n\n";

    // Données pour l'inscription
    $id_etudiant = $etudiant['num_etu'];
    $id_niveau = $niveau['id_niv_etude'];
    $id_annee_acad = $annee['id_annee_acad'];
    $montant_versement = 300000; // Premier versement de 300,000 FCFA
    $methode_paiement = 'Espèce';
    $num_piece = 'TEST-001';

    echo "--- Données de l'inscription ---\n";
    echo "ID Étudiant: {$id_etudiant}\n";
    echo "ID Niveau: {$id_niveau}\n";
    echo "ID Année: {$id_annee_acad}\n";
    echo "Montant versement: " . number_format($montant_versement, 0, ',', ' ') . " FCFA\n";
    echo "Méthode: {$methode_paiement}\n";
    echo "N° Pièce: {$num_piece}\n\n";

    // Créer l'inscription
    echo "🔄 Création de l'inscription...\n";
    $id_inscription = $scolarite->creerInscription(
        $id_etudiant,
        $id_niveau,
        $id_annee_acad,
        $montant_versement,
        $methode_paiement,
        $num_piece
    );

    if ($id_inscription) {
        echo "✅ Inscription créée avec succès ! (ID: {$id_inscription})\n\n";

        // Récupérer les détails de l'inscription
        echo "--- Détails de l'inscription créée ---\n";
        $inscription = $scolarite->getVersementById($id_inscription);

        if ($inscription) {
            echo "Étudiant: {$inscription['nom_etu']} {$inscription['prenom_etu']}\n";
            echo "Niveau: {$inscription['lib_niv_etude']}\n";
            echo "Montant scolarité: " . number_format($inscription['montant_scolarite'], 0, ',', ' ') . " FCFA\n";
            echo "N° Versement: {$inscription['num_versement']}\n";
            echo "Montant versé: " . number_format($inscription['montant_verser'], 0, ',', ' ') . " FCFA\n";
            echo "Montant payé total: " . number_format($inscription['montant_paye'], 0, ',', ' ') . " FCFA\n";
            echo "Solde: " . number_format($inscription['solde'], 0, ',', ' ') . " FCFA\n";
            echo "Méthode: {$inscription['methode_paiement']}\n";
            echo "N° Pièce: {$inscription['num_piece_mp']}\n";
            echo "Statut: {$inscription['statut_inscription']}\n";
            echo "Date: {$inscription['date_versement']}\n";
        }

        // Test: Ajouter un second versement
        echo "\n=== TEST D'AJOUT D'UN SECOND VERSEMENT ===\n";
        $montant_versement_2 = 200000; // Second versement de 200,000 FCFA
        echo "🔄 Ajout du second versement de " . number_format($montant_versement_2, 0, ',', ' ') . " FCFA...\n";

        $id_inscription_2 = $scolarite->creerInscription(
            $id_etudiant,
            $id_niveau,
            $id_annee_acad,
            $montant_versement_2,
            'Virement',
            'VIREMENT-002'
        );

        if ($id_inscription_2) {
            echo "✅ Second versement créé avec succès ! (ID: {$id_inscription_2})\n\n";

            // Vérifier les informations de paiement
            echo "--- Récapitulatif des paiements ---\n";
            $infos = $scolarite->getInfosPaiementEtudiant($id_etudiant, $id_annee_acad);
            if ($infos) {
                echo "Montant scolarité: " . number_format($infos['montant_scolarite'], 0, ',', ' ') . " FCFA\n";
                echo "Nombre de versements: {$infos['nombre_versements']}\n";
                echo "Montant total payé: " . number_format($infos['montant_paye'], 0, ',', ' ') . " FCFA\n";
                echo "Reste à payer: " . number_format($infos['reste_a_payer'], 0, ',', ' ') . " FCFA\n";
            }

            // Afficher tous les versements
            echo "\n--- Liste des versements ---\n";
            $versements = $scolarite->getVersementsEtudiant($id_etudiant, $id_annee_acad);
            foreach ($versements as $v) {
                echo "Versement N°{$v['num_versement']}: " . number_format($v['montant_verser'], 0, ',', ' ') . " FCFA - {$v['methode_paiement']} - Solde: " . number_format($v['solde'], 0, ',', ' ') . " FCFA\n";
            }
        }

    } else {
        echo "❌ Erreur lors de la création de l'inscription.\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST TERMINÉ ===\n";
