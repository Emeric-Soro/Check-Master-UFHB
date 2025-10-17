<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Annexe 1 - Soutenance de Mémoire</title>
    <style>
        @page {
            margin: 20mm;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header-left,
        .header-center,
        .header-right {
            display: table-cell;
            vertical-align: middle;
        }

        .header-left {
            width: 20%;
        }

        .header-center {
            width: 60%;
            text-align: center;
        }

        .header-right {
            width: 20%;
            text-align: right;
        }

        .logo {
            max-width: 80px;
            height: auto;
        }

        .title {
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            font-size: 14pt;
        }

        .section-title {
            background-color: #0056b3;
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            margin: 15px 0 10px 0;
        }

        .info-row {
            margin: 8px 0;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .signature-section {
            margin-top: 30px;
        }

        .signature-box {
            margin: 15px 0;
            border-bottom: 1px dotted #000;
            padding-bottom: 3px;
        }

        .signature-label {
            font-weight: bold;
            display: inline-block;
            width: 200px;
        }
    </style>
</head>

<body>
    <!-- En-tête -->
    <div class="header">
        <div class="header-left">
            <img src="<?= __DIR__ . '/../../../public/images/FHB.png' ?>" alt="Logo" class="logo">
        </div>
        <div class="header-center">
            <strong>ANNEXE 1</strong><br>
            <strong>FILIÈRES PROFESSIONNALISÉES</strong>
        </div>
        <div class="header-right">
            <!-- Logo MIAGE-CI -->
        </div>
    </div>

    <!-- Titre section -->
    <div class="section-title">
        UFR de Mathématiques et Informatique Filières Professionnalisées MIAGE-CI
    </div>

    <div class="title">SOUTENANCE DE MEMOIRE</div>

    <!-- Informations générales -->
    <div class="info-row">
        <span class="info-label">NIVEAU :</span>
        <span><?= htmlspecialchars($data['niveau']) ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">DATE :</span>
        <span><?= htmlspecialchars($data['date_soutenance']) ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">CLASSE :</span>
        <span><?= htmlspecialchars($data['promotion']) ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">THÈME :</span>
        <span><?= htmlspecialchars($data['theme']) ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">NOM ET PRÉNOMS DE L'IMPÉTRANT :</span>
        <span><?= htmlspecialchars($data['nom_etudiant']) ?></span>
    </div>

    <!-- Table des critères -->
    <table>
        <thead>
            <tr>
                <th>POINTS D'APPRÉCIATION</th>
                <th width="100">NOTE OBTENUE</th>
                <th width="80">BAREME</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['criteres'] as $critere): ?>
                <tr>
                    <td><?= htmlspecialchars($critere['lib_critere']) ?></td>
                    <td style="text-align: center;"><?= number_format($critere['note'], 1) ?></td>
                    <td style="text-align: center;"><?= $critere['bareme'] ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th>TOTAL</th>
                <th style="text-align: center;"><?= number_format($data['note_finale'], 1) ?></th>
                <th style="text-align: center;"><?= $data['total_bareme'] ?></th>
            </tr>
        </tbody>
    </table>

    <!-- Décision du jury -->
    <div class="info-row">
        <span class="info-label">DÉCISION DU JURY :</span>
        <span>...............................................................................................................</span>
    </div>

    <!-- Signatures -->
    <div class="signature-section">
        <div style="font-weight: bold; margin-bottom: 10px;">NOM ET SIGNATURES DES MEMBRES DU JURY :</div>

        <div class="signature-box">
            <span class="signature-label">PRÉSIDENT :</span>
            <span><?= htmlspecialchars($data['president'] ?? '') ?></span>
            <span style="float: right;">.........................</span>
        </div>

        <div class="signature-box">
            <span class="signature-label">EXAMINATEUR :</span>
            <span><?= htmlspecialchars($data['examinateur'] ?? '') ?></span>
            <span style="float: right;">.........................</span>
        </div>

        <div class="signature-box">
            <span class="signature-label">DIRECTEUR DE MÉMOIRE :</span>
            <span><?= htmlspecialchars($data['directeur'] ?? '') ?></span>
            <span style="float: right;">.........................</span>
        </div>

        <div class="signature-box">
            <span class="signature-label">ENCADREUR :</span>
            <span><?= htmlspecialchars($data['encadreur'] ?? '') ?></span>
            <span style="float: right;">.........................</span>
        </div>

        <div class="signature-box">
            <span class="signature-label">MAÎTRE DE STAGE :</span>
            <span><?= htmlspecialchars($data['maitre_stage'] ?? '') ?></span>
            <span style="float: right;">.........................</span>
        </div>
    </div>
</body>

</html>