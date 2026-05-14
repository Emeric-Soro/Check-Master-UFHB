<?php

/**
* Action unifiée pour gérer à la fois les inscriptions et les versements
* Utilise le paramètre is_new_inscription pour déterminer l'action à effectuer
*
* À ajouter dans GestionScolariteController.php avant le dernier "}
* Et ajouter 'enregistrer_paiement' dans le switch case de la méthode index()
*/
public function enregistrerPaiement()
{
try {
// Vérifier si c'est une nouvelle inscription ou un versement additionnel
$isNewInscription = isset($_POST['is_new_inscription']) && $_POST['is_new_inscription'] === 'true';

if ($isNewInscription) {
// ===== NOUVELLE INSCRIPTION =====

// Validation des données
if (
empty($_POST['etudiant']) || empty($_POST['niveau']) ||
empty($_POST['annee_academique']) || empty($_POST['montant_versement']) ||
empty($_POST['methode_paiement'])
) {
$GLOBALS['messageErreur'] = "Tous les champs obligatoires doivent être remplis.";
return;
}

$id_etudiant = $_POST['etudiant'];
$id_niveau = $_POST['niveau'];
$id_annee_acad = $_POST['annee_academique'];
$montant_premier_versement = floatval($_POST['montant_versement']);
$methode_paiement = $_POST['methode_paiement'];
$num_piece = isset($_POST['num_piece']) ? $_POST['num_piece'] : null;

// Vérifier que l'étudiant n'est pas déjà inscrit
$inscriptionExistante = $this->scolariteModel->getDerniereInscription($id_etudiant);
if ($inscriptionExistante && $inscriptionExistante['id_annee_acad'] == $id_annee_acad) {
$GLOBALS['messageErreur'] = "Cet étudiant est déjà inscrit pour cette année académique.";
return;
}

// Récupérer le montant total de la scolarité
$montant_total = $this->scolariteModel->getMontantScolarite($id_niveau);

// Vérifier que le montant ne dépasse pas le total
if ($montant_premier_versement > $montant_total) {
$GLOBALS['messageErreur'] = "Le montant ne peut pas dépasser le montant total de la scolarité (" .
number_format($montant_total, 0, ',', ' ') . " FCFA).";
return;
}

// Créer l'inscription avec le premier versement
$id_inscription = $this->scolariteModel->creerInscription(
$id_etudiant,
$id_niveau,
$id_annee_acad,
$montant_premier_versement,
$methode_paiement,
$num_piece
);

if ($id_inscription) {
$reste_a_payer = $montant_total - $montant_premier_versement;
$GLOBALS['messageSuccess'] = "✅ Inscription créée avec succès ! Versement de " .
number_format($montant_premier_versement, 0, ',', ' ') . " FCFA enregistré. Reste à payer : " .
number_format($reste_a_payer, 0, ',', ' ') . " FCFA.";
$this->auditLog->logCreation($_SESSION['id_utilisateur'], "inscriptions", 'Succès - Nouvelle inscription');

// Rafraîchir les listes
$GLOBALS['etudiantsInscrits'] = $this->scolariteModel->getEtudiantsInscrits();
$GLOBALS['etudiantsNonInscrits'] = $this->scolariteModel->getEtudiantsNonInscrits();
$GLOBALS['listeAllEtudiant'] = $this->scolariteModel->getAllEtudiants();
} else {
$GLOBALS['messageErreur'] = "❌ Erreur lors de la création de l'inscription.";
$this->auditLog->logCreation($_SESSION['id_utilisateur'], "inscriptions", 'Erreur');
}

} else {
// ===== VERSEMENT ADDITIONNEL =====

// Validation des données
if (
empty($_POST['etudiant']) || empty($_POST['montant_versement']) ||
empty($_POST['methode_paiement'])
) {
$GLOBALS['messageErreur'] = "Tous les champs obligatoires doivent être remplis.";
return;
}

$id_etudiant = $_POST['etudiant'];
$montant = floatval($_POST['montant_versement']);
$methode_paiement = $_POST['methode_paiement'];
$num_piece = isset($_POST['num_piece']) ? $_POST['num_piece'] : null;

// Récupérer la dernière inscription
$derniere_inscription = $this->scolariteModel->getDerniereInscription($id_etudiant);

if (!$derniere_inscription) {
$GLOBALS['messageErreur'] = "⚠️ Aucune inscription trouvée pour cet étudiant.";
return;
}

$id_niveau = $derniere_inscription['id_niveau'];
$id_annee_acad = $derniere_inscription['id_annee_acad'];

// Récupérer les informations de paiement
$infos_paiement = $this->scolariteModel->getInfosPaiementEtudiant($id_etudiant, $id_annee_acad);

if (!$infos_paiement) {
$GLOBALS['messageErreur'] = "⚠️ Impossible de récupérer les informations de paiement.";
return;
}

    // Vérifier si la scolarité est soldée
    if ($infos_paiement['reste_a_payer'] <= 0) {
        $GLOBALS['messageErreur'] = "Cet étudiant a déjà soldé sa scolarité.";
        return;
    }

    // Vérifier que le montant ne dépasse pas le reste à payer
    if ($montant > $infos_paiement['reste_a_payer']) {
        $GLOBALS['messageErreur'] = "Le montant ne peut pas dépasser le reste à payer (" .
            number_format($infos_paiement['reste_a_payer'], 0, ',', ' ') . " FCFA).";
        return;
    }

    // Enregistrer le versement
    $id_inscription = $this->scolariteModel->creerInscription(
    $id_etudiant,
    $id_niveau,
    $id_annee_acad,
    $montant,
    $methode_paiement,
    $num_piece
    );

    if ($id_inscription) {
    $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'inscriptions', 'Succès - Versement');

    $nouveau_reste = $infos_paiement['reste_a_payer'] - $montant;

    $GLOBALS['messageSuccess'] = "Versement de " . number_format($montant, 0, ',', ' ') . " FCFA enregistré ! Reste a payer : " . number_format($nouveau_reste, 0, ',', ' ') . " FCFA.";

    // Rafraîchir les listes
    $GLOBALS['etudiantsInscrits'] = $this->scolariteModel->getEtudiantsInscrits();
    $GLOBALS['listeAllEtudiant'] = $this->scolariteModel->getAllEtudiants();
    } else {
    $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'inscriptions', 'Erreur - Versement');
    $GLOBALS['messageErreur'] = "❌ Erreur lors de l'enregistrement du versement.";
    }
    }

    } catch (Exception $e) {
    error_log("Erreur dans enregistrerPaiement : " . $e->getMessage());
    $GLOBALS['messageErreur'] = "❌ Une erreur est survenue : " . $e->getMessage();
    }
    }