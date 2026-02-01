<?php
// Annexe 1 - Contenu seulement (pas de DOCTYPE, html, head, body)
?>
<style>
    .annexe1 {
        padding: 0;
    }

    .annexe1 .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5px;
    }

    .annexe1 .header-table td {
        vertical-align: middle;
        padding: 5px;
    }

    .annexe1 .logo-left {
        width: 90px;
    }

    .annexe1 .logo-right {
        width: 90px;
        text-align: right;
    }

    .annexe1 .header-center {
        text-align: center;
    }

    .annexe1 .logo-img {
        max-height: 75px;
        width: auto;
    }

    .annexe1 .annexe-title {
        color: #0066cc;
        font-size: 16pt;
        font-weight: bold;
        margin: 0;
        line-height: 1.1;
    }

    .annexe1 .filiere-subtitle {
        color: #0066cc;
        font-size: 14pt;
        font-weight: bold;
        margin: 0;
        line-height: 1.2;
    }

    .annexe1 .banner {
        background-color: #0066cc;
        color: white;
        padding: 5px 10px;
        text-align: center;
        font-size: 10pt;
        margin: 10px 0;
    }

    .annexe1 .banner-text {
        text-decoration: underline;
    }

    .annexe1 .main-title {
        text-align: center;
        font-size: 16pt;
        font-weight: bold;
        color: #000;
        text-decoration: underline;
        margin: 15px 0;
    }

    .annexe1 .info-line {
        margin-bottom: 5px;
        font-size: 11pt;
    }

    .annexe1 .info-line-flex {
        margin-bottom: 5px;
        font-size: 11pt;
    }

    .annexe1 .data-table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
        font-size: 10pt;
    }

    .annexe1 .data-table th {
        border: 1px solid #000;
        padding: 6px 8px;
        background-color: #fff;
        font-weight: bold;
        text-align: center;
    }

    .annexe1 .data-table td {
        border: 1px solid #000;
        padding: 5px 8px;
    }

    .annexe1 .data-table td.center {
        text-align: center;
    }

    .annexe1 .data-table .total-row td {
        font-weight: bold;
    }

    .annexe1 .section-title {
        font-weight: normal;
        margin: 15px 0 10px 0;
        font-size: 11pt;
    }

    .annexe1 .signature-title {
        font-weight: bold;
        margin: 20px 0 10px 0;
        font-size: 11pt;
    }

    .annexe1 .signature-item {
        margin-bottom: 8px;
        font-size: 11pt;
    }

    .annexe1 .footer {
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

<div class="annexe1">
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
                <div class="annexe-title">ANNEXE 1</div>
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
    <div class="main-title">SOUTENANCE DE MEMOIRE</div>

    <!-- Info Section -->
    <table style="width: 100%; margin-bottom: 5px;">
        <tr>
            <td style="width: 50%; font-size: 11pt;">NIVEAU : <?= htmlspecialchars($data['niveau'] ?? '') ?></td>
            <td style="width: 50%; font-size: 11pt; text-align: right;">CLASSE : <?= htmlspecialchars($data['promotion'] ?? '') ?></td>
        </tr>
    </table>
    
    <div class="info-line">DATE : <?= htmlspecialchars($data['date_soutenance'] ?? '') ?></div>
    
    <div class="info-line" style="margin-top: 10px;">THEME : <?= htmlspecialchars($data['theme'] ?? '') ?></div>
    
    <div class="info-line" style="margin-top: 10px;">NOM ET PRENOMS DE L'IMPETRANT : <?= htmlspecialchars($data['nom_etudiant'] ?? '') ?></div>

    <!-- Appreciation Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="text-align: left; width: 50%;">POINTS D'APPRECIATION</th>
                <th style="width: 25%;">NOTE OBTENUE</th>
                <th style="width: 25%;">BAREME</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($data['criteres']) && is_array($data['criteres'])): ?>
                <?php foreach ($data['criteres'] as $critere): ?>
                <tr>
                    <td><?= htmlspecialchars($critere['lib_critere'] ?? '') ?></td>
                    <td class="center"><?= number_format($critere['note'] ?? 0, 1) ?></td>
                    <td class="center"><?= $critere['bareme'] ?? '' ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <tr class="total-row">
                <td style="text-align: center;">TOTAL</td>
                <td class="center"><?= number_format($data['note_finale'] ?? 0, 1) ?></td>
                <td class="center"><?= $data['total_bareme'] ?? '' ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Decision Section -->
    <div class="section-title">DECISION DU JURY :</div>

    <!-- Signature Section -->
    <div class="signature-title">NOMS ET SIGNATURES DES MEMBRES DU JURY</div>
    
    <div class="signature-item">Président : <?= htmlspecialchars($data['president'] ?? '') ?></div>
    <div class="signature-item">Examinateur : <?= htmlspecialchars($data['examinateur'] ?? '') ?></div>
    <div class="signature-item">Directeur de mémoire : <?= htmlspecialchars($data['directeur'] ?? '') ?></div>
    <div class="signature-item">Encadreur : <?= htmlspecialchars($data['encadreur'] ?? '') ?></div>
    <div class="signature-item">Maître de stage : <?= htmlspecialchars($data['maitre_stage'] ?? '') ?></div>

    <!-- Footer -->
    <div class="footer">Page 1 sur 3</div>
</div>