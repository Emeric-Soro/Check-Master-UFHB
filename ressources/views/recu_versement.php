<?php

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Scolarite.php';
require_once __DIR__ . '/../../app/utils/ReceiptUtils.php';

// Instancier de façon sûre : si les classes ne sont pas disponibles, afficher un message lisible
$scolarite = null;
if (class_exists('Scolarite') && class_exists('Database')) {
    try {
        $scolarite = new Scolarite(Database::getConnection());
    } catch (Exception $e) {
        echo '<div style="padding:20px;max-width:720px;margin:20px auto;background:#fff3cd;border:1px solid #ffeeba;color:#856404;">';
        echo '<strong>Erreur :</strong> impossible de se connecter à la base de données pour générer le reçu.';
        echo '</div>';
        return;
    }
} else {
    echo '<div style="padding:20px;max-width:720px;margin:20px auto;background:#fff3cd;border:1px solid #ffeeba;color:#856404;">';
    echo '<strong>Configuration manquante :</strong> composant requis indisponible pour générer le reçu.';
    echo '</div>';
    return;
}


// Récupérer les données du versement
// Récupérer le versement. Parfois le contrôleur peut fournir un id d'inscription
$versement = null;
if (isset($id_versement)) {
    $versement = $scolarite->getVersementById($id_versement);
}

// Si on n'a rien obtenu, essayer d'interpréter l'id fourni comme un id d'inscription
if (empty($versement) || $versement === false) {
    $maybeId = $id_versement ?? ($_GET['id'] ?? null);
    if ($maybeId) {
        // Si on a reçu un id d'inscription, récupérer le dernier versement réel lié à cette inscription
        $lastVersement = $scolarite->getLastVersementByInscription($maybeId);
        if (!empty($lastVersement) && is_array($lastVersement)) {
            $versement = $lastVersement;
        } else {
            // sinon, tenter de récupérer l'inscription afin d'avoir des infos d'étudiant pour message d'erreur
            $maybeIns = $scolarite->getInscriptionById($maybeId);
            if (!empty($maybeIns) && is_array($maybeIns)) {
                // Construire un versement minimum à partir des données d'inscription
                $versement = [
                    'id_versement' => $maybeIns['id_inscription'],
                    'id_inscription' => $maybeIns['id_inscription'],
                    'montant' => $maybeIns['montant_premier_versement'] ?? ($maybeIns['montant_paye'] ?? 0),
                    'date_versement' => $maybeIns['date_inscription'] ?? date('Y-m-d'),
                    'methode_paiement' => $maybeIns['methode_paiement'] ?? '',
                    'nom_etudiant' => $maybeIns['nom_etudiant'] ?? '',
                    'prenom_etudiant' => $maybeIns['prenom_etudiant'] ?? ''
                ];
            }
        }
    }
}

// Si toujours rien, afficher un message clair et arrêter l'affichage pour éviter warnings
if (empty($versement) || $versement === false) {
    echo '<div style="padding:20px;max-width:720px;margin:20px auto;background:#fff3cd;border:1px solid #ffeeba;color:#856404;">';
    echo '<strong>Reçu introuvable :</strong> le versement demandé est introuvable ou l\'identifiant fourni est invalide.';
    echo '</div>';
    return;
}

// Récupérer l'inscription associée au versement
$inscription = $scolarite->getInscriptionById($versement['id_inscription']);

// Calcul historique : montants au moment du versement (pour que chaque reçu soit un historique)
$dateVersement = $versement['date_versement'] ?? date('Y-m-d');
$montantsAsOf = $scolarite->getMontantsAsOf($versement['id_inscription'], $dateVersement);

// Générer le numéro de reçu (fallback lisible si utilitaire manquant)
$numeroRecu = null;
if (class_exists('ReceiptUtils') && method_exists('ReceiptUtils', 'genererNumeroRecu')) {
    $numeroRecu = ReceiptUtils::genererNumeroRecu($versement['id_versement'] ?? 0);
} else {
    $numeroRecu = 'REC-' . ($versement['id_versement'] ?? '0');
}

// Vérifier que toutes les données nécessaires sont présentes
$nomEtudiant = $versement['nom_etudiant'] ?? '';
$prenomEtudiant = $versement['prenom_etudiant'] ?? '';
$montant = floatval($versement['montant'] ?? 0);
$methodePaiement = $versement['methode_paiement'] ?? '';
$anneeAcademique = $inscription['annee_academique'] ?? '';
$nomNiveau = $inscription['nom_niveau'] ?? '';
$montantScolarite = floatval($montantsAsOf['montant_total'] ?? ($inscription['montant_total'] ?? 0));
$montantPaye = floatval($montantsAsOf['montant_paye'] ?? ($inscription['montant_paye'] ?? 0));
$resteAPayer = floatval($montantsAsOf['reste_a_payer'] ?? ($inscription['reste_a_payer'] ?? 0));
?>
<?php
// Construire l'URL de base (scheme + host) pour garantir que les assets sont accessibles
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
?>
<?php
// Préparer des data-URI pour les logos (fallback vers URL absolue si non trouvés)
$logo1Path = __DIR__ . '/../../public/image/logo_ufhb.png';
$logo2Path = __DIR__ . '/../../public/image/logo_mi_sbg.png';
$logo1Data = null;
$logo2Data = null;
if (file_exists($logo1Path) && is_readable($logo1Path)) {
    $type = mime_content_type($logo1Path) ?: 'image/png';
    $data = base64_encode(file_get_contents($logo1Path));
    $logo1Data = 'data:' . $type . ';base64,' . $data;
}
if (file_exists($logo2Path) && is_readable($logo2Path)) {
    $type = mime_content_type($logo2Path) ?: 'image/png';
    $data = base64_encode(file_get_contents($logo2Path));
    $logo2Data = 'data:' . $type . ';base64,' . $data;
}
// QR code image (fallback to data URI if available)
$qrPath = __DIR__ . '/../../public/image/Lien_vers_l_acceuil_de_CM-1024.png';
$qrData = null;
if (file_exists($qrPath) && is_readable($qrPath)) {
    $type = mime_content_type($qrPath) ?: 'image/png';
    $data = base64_encode(file_get_contents($qrPath));
    $qrData = 'data:' . $type . ';base64,' . $data;
}
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
            padding: 14px;
            /* largeur fluide adaptée à A4 paysage */
            width: 100%;
            max-width: 720px;
            box-sizing: border-box;
            margin: 10px auto;
            line-height: 1.35;
            font-size: 13px;
            position: relative;
        }

        /* QR code bottom-right */
        .qr-container {
            position: relative;
        }

        .qr-bottom-right {
            position: absolute;
            right: 12px;
            bottom: 12px;
            width: 90px;
            height: 90px;
        }

        @media print {
            .qr-bottom-right {
                right: 8mm;
                bottom: 8mm;
                width: 30mm;
                height: 30mm;
            }
        }

        /* Use table-based header for better compatibility with Dompdf (flexbox support is limited) */
        .header {
            margin-bottom: 12px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .header-table td {
            vertical-align: middle;
            padding: 0 6px;
        }

        .header-table .center-cell {
            text-align: center;
            width: 52%;
        }

        .header-table .side-cell {
            width: 24%;
            text-align: center;
        }

        .header img {
            height: 64px;
            width: auto;
            display: block;
            margin: 0 auto;
        }

        /* .img-par-autre removed: table layout used instead */

        .header .university-info {
            flex: 1 1 auto;
            text-align: center;
            margin: 0 8px;
        }

        .header h2,
        .header h3 {
            margin: 0;
            font-size: 1em;
            line-height: 1.1;
        }

        .header p {
            margin: 1px 0;
            font-size: 0.85em;
        }

        .receipt-number {
            font-size: 1.2em;
            font-weight: bold;
            color: #e74c3c;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 6px 0;
            border-bottom: 1px dashed #e0e0e0;
            vertical-align: top;
            word-break: break-word;
        }

        .info-table td:first-child {
            font-weight: bold;
            width: 36%;
            max-width: 220px;
            white-space: nowrap;
        }

        .amount-text {
            font-style: italic;
            margin-top: 6px;
            display: block;
            text-align: left;
            font-size: 0.95em;
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

        /* QR code bottom-right */
        .qr-code-container {
            position: absolute;
            right: 10px;
            bottom: 380px;
            width: 100px;
            height: 100px;
        }

        .qr-code-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .qr-code-container {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px dashed #ccc;
        }

        .qr-code-container #qrcode {
            display: inline-block;
            border: 2px solid #000;
            padding: 5px;
            background: white;
        }

        .qr-code-container #qrcode img {
            display: block;
        }

        .qr-code-container p {
            margin: 5px 0 0 0;
            font-size: 0.75em;
            color: #666;
        }

        @media print {
            body {
                padding: 0;
            }

            /* Forcer la page en paysage A4 lors de l'impression */
            @page {
                size: A4 landscape;
                margin: 8mm;
            }

            .receipt-box {
                border: none;
                width: 100%;
                max-width: none;
                padding: 6mm;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-box qr-container">
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="side-cell">
                        <img src="<?php echo $logo1Data ?? ($baseUrl . '/image/logo_ufhb.png'); ?>" alt="logo ufhb">
                    </td>
                    <td class="center-cell university-info">
                        <h2>UNIVERSITE FELIX HOUPHOUET-BOIGNY</h2>
                        <p>FILIERES PROFESSIONNALISEES, UFR MI</p>
                        <p>22 B.P. 582 Abidjan 22</p>
                        <p>Tél. (Fax): 27 22 41 05 74 / 27 22 48 01 80</p>
                        <p>Cel: 07 07 89 94 26 / 07 07 69 15 04</p>
                    </td>
                    <td class="side-cell">
                        <img src="<?php echo $logo2Data ?? ($baseUrl . '/image/logo_mi_sbg.png'); ?>"
                            alt="Logo MathInfo">
                    </td>
                </tr>
            </table>
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
                <td><?php if ($qrData): ?>
                        <div class="qr-code-container">
                            <img src="<?php echo $qrData; ?>" alt="QR code">
                        </div>
                    <?php else: ?>
                        <?php // fallback to public image path if data URI not available ?>
                        <div class="qr-code-container">
                            <img src="<?php echo $baseUrl . '/image/Lien_vers_l_acceuil_de_CM-1024.png'; ?>" alt="QR code">
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="amount-text">(en toutes lettres :
                    <?php echo ReceiptUtils::numberToWords($montant); ?> FCFA)
                </td>
            </tr>
            <tr>
                <td>En règlement de :</td>
                <td>Scolarité Année Académique <?php echo htmlspecialchars($anneeAcademique); ?> -
                    <?php echo htmlspecialchars($nomNiveau); ?>
                </td>
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
                <td>Montant total scolarité :</td>
                <td><?php echo htmlspecialchars(number_format($montantScolarite, 0, ',', ' ')); ?> FCFA
                </td>
            </tr>
            <tr>
                <td>Montant total payé :</td>
                <td><?php echo htmlspecialchars(number_format($montantPaye, 0, ',', ' ')); ?> FCFA</td>
            </tr>
            <tr>
                <td>Reste à payer :</td>
                <td><?php echo htmlspecialchars(number_format($resteAPayer, 0, ',', ' ')); ?> FCFA</td>
            </tr>
        </table>

        <div class="signature">
            <p>Signature et cachet</p>
        </div>

        <p class="note">N.B.: Aucun remboursement n'est possible après versement</p>
    </div>




    <script>
        // Attendre que toutes les images aient fini de charger avant d'appeler window.print()
        function printWhenImagesLoaded() {
            const images = Array.from(document.images);
            if (images.length === 0) {
                window.print();
                return;
            }

            let loadedCount = 0;
            function checkAll() {
                loadedCount++;
                if (loadedCount === images.length) {
                    // Petit délai pour s'assurer que rendu est prêt
                    setTimeout(() => window.print(), 100);
                }
            }

            images.forEach(img => {
                if (img.complete) {
                    checkAll();
                } else {
                    img.addEventListener('load', checkAll);
                    img.addEventListener('error', checkAll); // compter aussi les erreurs
                }
            });
        }

        if (document.readyState === 'complete') {
            printWhenImagesLoaded();
        } else {
            window.addEventListener('load', printWhenImagesLoaded);
        }
    </script>
</body>

</html>