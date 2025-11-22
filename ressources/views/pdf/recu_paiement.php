<?php
/**
 * Template HTML pour le Reçu de Paiement de Scolarité
 * Variables attendues: $etudiant, $montant, $date_paiement, $methode_paiement, $annee_academique, $niveau
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu de Paiement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            color: #000;
        }
        .receipt-box {
            border: 2px solid #000;
            padding: 20px;
            width: 100%;
            max-width: 750px;
            box-sizing: border-box;
            margin: 10px auto;
            line-height: 1.4;
            position: relative;
        }
        .header {
            margin-bottom: 15px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0 8px;
        }
        .header-table .center-cell {
            text-align: center;
            width: 50%;
        }
        .header-table .side-cell {
            width: 25%;
            text-align: center;
        }
        .header img {
            height: 70px;
            width: auto;
            display: block;
            margin: 0 auto;
        }
        .header h2 {
            margin: 3px 0;
            font-size: 14px;
            line-height: 1.2;
        }
        .header h3 {
            margin: 2px 0;
            font-size: 12px;
            font-weight: normal;
        }
        .header p {
            margin: 2px 0;
            font-size: 11px;
        }
        .receipt-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0;
        }
        .receipt-number {
            font-size: 18px;
            font-weight: bold;
            color: #e74c3c;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 8px 0;
            border-bottom: 1px dashed #d0d0d0;
            vertical-align: top;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 38%;
            max-width: 250px;
        }
        .amount-text {
            font-style: italic;
            margin-top: 5px;
            display: block;
            text-align: left;
            font-size: 12px;
            color: #333;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }
        .footer-table td {
            padding: 6px 0;
        }
        .footer-table td:first-child {
            width: 55%;
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
            padding: 8px 25px;
            font-weight: bold;
        }
        .note {
            text-align: center;
            font-size: 11px;
            margin-top: 25px;
            font-style: italic;
            color: #555;
        }
        .qr-code-container {
            position: absolute;
            right: 25px;
            bottom: 230px;
            width: 90px;
            height: 90px;
        }
        .qr-code-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        @media print {
            body {
                padding: 0;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            .receipt-box {
                border: none;
                width: 100%;
                max-width: none;
                padding: 8mm;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-box">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="side-cell">
                        <?php
                        $logoUfhb = __DIR__ . '/../../../assets/image/logo_ufhb.png';
                        if (file_exists($logoUfhb) && is_readable($logoUfhb)) {
                            $type = mime_content_type($logoUfhb) ?: 'image/png';
                            $data = base64_encode(file_get_contents($logoUfhb));
                            echo '<img src="data:' . $type . ';base64,' . $data . '" alt="Logo UFHB">';
                        }
                        ?>
                    </td>
                    <td class="center-cell">
                        <h2>UNIVERSITÉ FÉLIX HOUPHOUËT-BOIGNY</h2>
                        <h3>FILIÈRES PROFESSIONNALISÉES, UFR MI</h3>
                        <p>22 B.P. 582 Abidjan 22</p>
                        <p>Tél. (Fax): 27 22 41 05 74 / 27 22 48 01 80</p>
                        <p>Cel: 07 07 89 94 26 / 07 07 69 15 04</p>
                    </td>
                    <td class="side-cell">
                        <?php
                        $logoMi = __DIR__ . '/../../../assets/image/logo_mi_sbg.png';
                        if (file_exists($logoMi) && is_readable($logoMi)) {
                            $type = mime_content_type($logoMi) ?: 'image/png';
                            $data = base64_encode(file_get_contents($logoMi));
                            echo '<img src="data:' . $type . ';base64,' . $data . '" alt="Logo MI">';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="receipt-title">
            REÇU <span class="receipt-number">N° <?= htmlspecialchars($numero_recu ?? 'REC-' . date('Ymd') . '-001') ?></span>
        </div>

        <table class="info-table">
            <tr>
                <td>Reçu de M/Mme :</td>
                <td><?= htmlspecialchars(($etudiant['nom'] ?? '') . ' ' . ($etudiant['prenom'] ?? '')) ?></td>
            </tr>
            <tr>
                <td>Numéro Étudiant :</td>
                <td><?= htmlspecialchars($etudiant['num_etu'] ?? '') ?></td>
            </tr>
            <tr>
                <td>La somme de :</td>
                <td>
                    <?php 
                    $montant_num = floatval($montant ?? 0);
                    echo htmlspecialchars(number_format($montant_num, 0, ',', ' ')) . ' FCFA';
                    ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="amount-text">
                    (en toutes lettres : <?= htmlspecialchars($montant_lettres ?? '') ?> FCFA)
                </td>
            </tr>
            <tr>
                <td>En règlement de :</td>
                <td>
                    Frais de Scolarité - Année Académique <?= htmlspecialchars($annee_academique ?? '') ?>
                    - <?= htmlspecialchars($niveau ?? '') ?>
                </td>
            </tr>
            <tr>
                <td>Année d'Études :</td>
                <td><?= htmlspecialchars($niveau ?? '') ?></td>
            </tr>
        </table>

        <table class="footer-table">
            <tr>
                <td>Méthode de paiement :</td>
                <td><?= htmlspecialchars($methode_paiement ?? 'Espèces') ?></td>
            </tr>
            <tr>
                <td>Date de paiement :</td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($date_paiement ?? 'now'))) ?></td>
            </tr>
            <tr>
                <td><strong>Montant total scolarité :</strong></td>
                <td><strong><?= htmlspecialchars(number_format(floatval($montant_total_scolarite ?? 0), 0, ',', ' ')) ?> FCFA</strong></td>
            </tr>
            <tr>
                <td><strong>Montant total payé :</strong></td>
                <td><strong><?= htmlspecialchars(number_format(floatval($montant_total_paye ?? 0), 0, ',', ' ')) ?> FCFA</strong></td>
            </tr>
            <tr>
                <td><strong>Reste à payer :</strong></td>
                <td><strong><?= htmlspecialchars(number_format(floatval($reste_a_payer ?? 0), 0, ',', ' ')) ?> FCFA</strong></td>
            </tr>
        </table>

        <?php
        // QR Code optionnel
        $qrPath = __DIR__ . '/../../../assets/image/Lien_vers_l_acceuil_de_CM-1024.png';
        if (file_exists($qrPath) && is_readable($qrPath)):
        ?>
        <div class="qr-code-container">
            <?php
            $type = mime_content_type($qrPath) ?: 'image/png';
            $data = base64_encode(file_get_contents($qrPath));
            echo '<img src="data:' . $type . ';base64,' . $data . '" alt="QR Code">';
            ?>
        </div>
        <?php endif; ?>

        <div class="signature">
            <p>Signature et cachet</p>
        </div>

        <p class="note">
            N.B.: Aucun remboursement n'est possible après versement.<br>
            Ce reçu doit être conservé précieusement.
        </p>
    </div>
</body>
</html>
