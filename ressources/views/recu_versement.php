<?php
require_once __DIR__ . '/../../app/utils/ReceiptUtils.php';
require_once __DIR__ . '/../../app/models/Scolarite.php';
require_once __DIR__ . '/../../app/config/database.php';


$scolarite = new Scolarite(Database::getConnection());


// Récupérer les données du versement
$versement = $scolarite->getVersementById($id_versement);



// Récupérer l'inscription associée au versement
$inscription = $scolarite->getInscriptionById($versement['id_inscription']);



// Générer le numéro de reçu
$numeroRecu = ReceiptUtils::genererNumeroRecu($versement['id_versement'] ?? 0);

// Vérifier que toutes les données nécessaires sont présentes
$nomEtudiant = $versement['nom_etudiant'] ?? '';
$prenomEtudiant = $versement['prenom_etudiant'] ?? '';
$montant = floatval($versement['montant'] ?? 0);
$methodePaiement = $versement['methode_paiement'] ?? '';
$dateVersement = $versement['date_versement'] ?? date('Y-m-d');
$anneeAcademique = $inscription['annee_academique'] ?? '';
$nomNiveau = $inscription['nom_niveau'] ?? '';
$montantScolarite = floatval($inscription['montant_total'] ?? 0);
$montantPaye = floatval($inscription['montant_paye'] ?? 0);
$resteAPayer = floatval($inscription['reste_a_payer'] ?? 0);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de versement</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 20px;
    }

    .receipt-box {
        border: 1px solid #000;
        padding: 20px;
        width: 600px;
        margin: 20px auto;
    }

    .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }

    .header img {
        height: 60px;
        width: auto;
    }

    .header .university-info {
        flex-grow: 1;
        text-align: center;
        margin: 0 20px;
    }

    .header h2,
    .header h3 {
        margin: 0;
        font-size: 1.2em;
    }

    .header p {
        margin: 2px 0;
        font-size: 0.9em;
    }

    .receipt-number {
        font-size: 1.5em;
        font-weight: bold;
        color: #e74c3c;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .info-table td {
        padding: 5px 0;
        border-bottom: 1px dashed #ccc;
    }

    .info-table td:first-child {
        font-weight: bold;
        width: 180px;
    }

    .amount-text {
        font-style: italic;
        margin-top: 5px;
        display: block;
        text-align: left;
    }

    .footer-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30px;
    }

    .footer-table td {
        padding: 5px 0;
    }

    .footer-table td:first-child {
        width: 50%;
    }

    .footer-table td:last-child {
        text-align: right;
    }

    .signature {
        margin-top: 50px;
        text-align: center;
    }

    .signature p {
        margin: 0;
        border-top: 1px solid #000;
        display: inline-block;
        padding: 5px 20px;
    }

    .note {
        text-align: center;
        font-size: 0.8em;
        margin-top: 30px;
    }

    @media print {
        body {
            padding: 0;
        }

        .receipt-box {
            border: none;
        }
    }
    </style>
</head>

<body>
    <div class="receipt-box">
        <div class="header">
            <div>
                <!-- <img src="/images/FHB.png" alt="Logo Université"> -->
            </div>
            <div class="university-info">
                <h2>UNIVERSITE FELIX HOUPHOUET-BOIGNY</h2>
                <p>FILIERES PROFESSIONNALISEES, UFR MI</p>
                <p>22 B.P. 582 Abidjan 22</p>
                <p>Tél. (Fax): 27 22 41 05 74 / 27 22 48 01 80</p>
                <p>Cel: 07 07 89 94 26 / 07 07 69 15 04</p>
            </div>
            <div>
                <!-- <img src="/images/logo_mathInfo_fond_blanc.png" alt="Logo MathInfo"> -->
            </div>
        </div>

        <h3 style="text-align: center; margin-bottom: 20px;">REÇU <span class="receipt-number">Nº
                <?php echo $numeroRecu; ?></span></h3>

        <table class="info-table">
            <tr>
                <td>Reçu de M/Mme :</td>
                <td><?php echo htmlspecialchars($nomEtudiant . ' ' . $prenomEtudiant); ?></td>
            </tr>
            <tr>
                <td>La somme de :</td>
                <td><?php echo htmlspecialchars(number_format($montant, 0, ',', ' ')); ?> FCFA</td>
            </tr>
            <tr>
                <td colspan="2" class="amount-text">(en toutes lettres :
                    <?php echo ReceiptUtils::numberToWords($montant); ?> FCFA)</td>
            </tr>
            <tr>
                <td>En règlement de :</td>
                <td>Scolarité Année Académique <?php echo htmlspecialchars($anneeAcademique); ?> -
                    <?php echo htmlspecialchars($nomNiveau); ?></td>
            </tr>
            <tr>
                <td>Année d'Études :</td>
                <td><?php echo htmlspecialchars($nomNiveau); ?></td>
            </tr>
        </table>

        <table class="footer-table">
            <tr>
                <td>Méthode de paiement: <?php echo htmlspecialchars($methodePaiement); ?></td>
                <td>Date: <?php echo htmlspecialchars(date('d/m/Y', strtotime($dateVersement))); ?></td>
            </tr>
            <tr>
                <td>Reste à payer :</td>
                <td><?php echo htmlspecialchars(number_format($resteAPayer, 0, ',', ' ')); ?> FCFA</td>
            </tr>
            <tr>
                <td>Montant total scolarité :</td>
                <td><?php echo htmlspecialchars(number_format($montantScolarite, 0, ',', ' ')); ?> FCFA
                </td>
            </tr>
            <tr>
                <td>Montant total payé :</td>
                <td><?php echo htmlspecialchars(number_format($montantPaye, 0, ',', ' ')); ?> FCFA</td>
            </tr>
        </table>

        <div class="signature">
            <p>Signature et cachet</p>
        </div>

        <p class="note">N.B.: Aucun remboursement n'est possible après versement</p>
    </div>

    <script>
    // Imprimer automatiquement le reçu
    window.onload = function() {
        window.print();
    }
    </script>
</body>

</html>