<?php
// Annexe 2 - Contenu seulement (pas de DOCTYPE, html, head, body)
?>
<style>
    .annexe2 {
        padding: 0;
    }

    .annexe2 .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5px;
    }

    .annexe2 .header-table td {
        vertical-align: middle;
        padding: 5px;
    }

    .annexe2 .logo-left {
        width: 90px;
    }

    .annexe2 .logo-right {
        width: 90px;
        text-align: right;
    }

    .annexe2 .header-center {
        text-align: center;
    }

    .annexe2 .logo-img {
        max-height: 75px;
        width: auto;
    }

    .annexe2 .annexe-title {
        color: #cc0066;
        font-size: 16pt;
        font-weight: bold;
        margin: 0;
        line-height: 1.1;
    }

    .annexe2 .filiere-subtitle {
        color: #cc0066;
        font-size: 14pt;
        font-weight: bold;
        margin: 0;
        line-height: 1.2;
    }

    .annexe2 .banner {
        background-color: #0066cc;
        color: white;
        padding: 5px 10px;
        text-align: center;
        font-size: 10pt;
        margin: 10px 0;
    }

    .annexe2 .banner-text {
        text-decoration: underline;
    }

    .annexe2 .main-title {
        text-align: center;
        font-size: 16pt;
        font-weight: bold;
        color: #000;
        text-decoration: underline;
        margin: 15px 0;
    }

    .annexe2 .info-line {
        margin-bottom: 5px;
        font-size: 11pt;
    }

    .annexe2 .data-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0 0 0;
        font-size: 10pt;
    }

    .annexe2 .data-table th {
        border: 1px solid #000;
        padding: 6px 8px;
        background-color: #fff;
        font-weight: bold;
        text-align: center;
    }

    .annexe2 .data-table td {
        border: 1px solid #000;
        padding: 5px 8px;
    }

    .annexe2 .data-table td.center {
        text-align: center;
    }

    .annexe2 .data-table td.right {
        text-align: right;
    }

    .annexe2 .data-table .total-row td {
        font-weight: bold;
    }

    .annexe2 .moyenne-line {
        text-align: right;
        font-weight: bold;
        font-size: 11pt;
        margin: 5px 0 15px 0;
    }

    .annexe2 .section-title {
        font-weight: normal;
        margin: 15px 0 10px 0;
        font-size: 11pt;
    }

    .annexe2 .signature-title {
        font-weight: bold;
        margin: 20px 0 10px 0;
        font-size: 11pt;
    }

    .annexe2 .signature-item {
        margin-bottom: 8px;
        font-size: 11pt;
    }

    .annexe2 .footer {
        text-align: center;
        font-size: 10pt;
        color: #000;
        margin-top: 50px;
        position: absolute;
        bottom: 20px;
        left: 0;
        right: 0;
    }
</style>

<div class="annexe2">
    <!-- Header with logos -->
    <table class="header-table">
        <tr>
            <td class="logo-left">
                <?php
                $logoLeft = __DIR__ . '/../../../public/image/logo_ufhb.png';
                if (file_exists($logoLeft) && is_readable($logoLeft)) {
                    $type = mime_content_type($logoLeft) ?: 'image/png';
                    $logoData = base64_encode(file_get_contents($logoLeft));
                    echo '<img class="logo-img" src="data:' . $type . ';base64,' . $logoData . '" alt="UFHB">';
                }
                ?>
            </td>
            <td class="header-center">
                <div class="annexe-title">ANNEXE 2</div>
                <div class="filiere-subtitle">FILIERES<br>PROFESSIONNALISEES</div>
            </td>
            <td class="logo-right">
                <?php
                $logoRight = __DIR__ . '/../../../public/image/logo_mi.png';
                if (file_exists($logoRight) && is_readable($logoRight)) {
                    $type = mime_content_type($logoRight) ?: 'image/png';
                    $logoData = base64_encode(file_get_contents($logoRight));
                    echo '<img class="logo-img" src="data:' . $type . ';base64,' . $logoData . '" alt="MI">';
                }
                ?>
            </td>
        </tr>
    </table>

    <!-- Blue Banner -->
    <div class="banner">
        <span class="banner-text">UFR de Mathématiques et Informatique Filières Professionnalisées MIAGE-GI</span>
    </div>

    <!-- Main Title -->
    <div class="main-title">P.V. JURY N°</div>

    <!-- Info Section -->
    <div class="info-line">DATE : <?= htmlspecialchars($data['date_soutenance'] ?? '') ?></div>
    
    <div class="info-line" style="margin-top: 10px;">NOM ET PRENOMS DE L'IMPETRANT : <?= htmlspecialchars($data['nom_etudiant'] ?? '') ?></div>

    <!-- Moyenne Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="text-align: left; width: 40%;">POINTS D'APPRECIATION</th>
                <th style="width: 25%;">NOTE OBTENUE</th>
                <th style="width: 15%;">Coeff.</th>
                <th style="width: 20%;">Moyenne Coeff.</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1. Moyenne Générale Master1</td>
                <td class="center"><?= number_format($data['moyenne_master1'] ?? 0, 2) ?></td>
                <td class="center"><?= $data['coef_master1'] ?? 2 ?></td>
                <td class="center"></td>
            </tr>
            <tr>
                <td>2. Moyenne Générale Semestre 1 Master2</td>
                <td class="center"><?= number_format($data['moyenne_s1_master2'] ?? 0, 2) ?></td>
                <td class="center"><?= $data['coef_s1_master2'] ?? 3 ?></td>
                <td class="center"></td>
            </tr>
            <tr>
                <td>3. Mémoire de fin de cycle</td>
                <td class="center"><?= number_format($data['note_memoire'] ?? 0, 2) ?></td>
                <td class="center"><?= $data['coef_memoire'] ?? 3 ?></td>
                <td class="center"></td>
            </tr>
            <tr class="total-row">
                <td style="text-align: center;">TOTAL</td>
                <td class="center"></td>
                <td class="center"><?= $data['total_coef'] ?? 8 ?></td>
                <td class="right">/160</td>
            </tr>
        </tbody>
    </table>

    <div class="moyenne-line">Moyenne &nbsp;&nbsp;&nbsp; <?= number_format($data['note_finale'] ?? 0, 2) ?>/20</div>

    <!-- Decision Section -->
    <div class="section-title">DECISION DU JURY</div>

    <!-- Signature Section -->
    <div class="signature-title">NOMS ET SIGNATURES DES MEMBRES DU JURY :</div>
    
    <div class="signature-item">Président : <?= htmlspecialchars($data['president'] ?? '') ?></div>
    <div class="signature-item">Examinateur : <?= htmlspecialchars($data['examinateur'] ?? '') ?></div>
    <div class="signature-item">Directeur de mémoire : <?= htmlspecialchars($data['directeur'] ?? '') ?></div>
    <div class="signature-item">Encadreur : <?= htmlspecialchars($data['encadreur'] ?? '') ?></div>
    <div class="signature-item">Maître de stage : <?= htmlspecialchars($data['maitre_stage'] ?? '') ?></div>

    <!-- Footer -->
    <div class="footer">Page 2 sur 3</div>
</div>
