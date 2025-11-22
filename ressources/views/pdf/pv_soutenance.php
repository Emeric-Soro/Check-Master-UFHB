<?php
/**
 * Template HTML pour le PV de Soutenance
 * Variables attendues: $etudiant, $jury, $note, $mention, $date_soutenance, $titre_memoire
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procès-Verbal de Soutenance</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
            color: #000;
            line-height: 1.4;
        }
        .container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .header-table td {
            vertical-align: top;
            padding: 5px;
        }
        .logo {
            height: 70px;
            width: auto;
        }
        .center-text {
            text-align: center;
        }
        .header h1 {
            font-size: 16px;
            margin: 5px 0;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header h2 {
            font-size: 14px;
            margin: 3px 0;
            font-weight: normal;
        }
        .header p {
            font-size: 11px;
            margin: 2px 0;
        }
        .title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 25px 0;
            text-decoration: underline;
        }
        .section {
            margin: 15px 0;
        }
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 8px;
            text-decoration: underline;
        }
        .info-line {
            margin: 5px 0;
            padding-left: 10px;
        }
        .info-line strong {
            display: inline-block;
            width: 180px;
            font-weight: bold;
        }
        table.jury-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table.jury-table th,
        table.jury-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        table.jury-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 12px;
        }
        .results {
            margin: 20px 0;
            padding: 15px;
            border: 2px solid #000;
            background-color: #f9f9f9;
        }
        .results .result-line {
            margin: 8px 0;
            font-size: 13px;
        }
        .results .result-line strong {
            display: inline-block;
            width: 200px;
        }
        .note-highlight {
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }
        .signatures {
            margin-top: 40px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        .signature-box {
            text-align: center;
            margin-top: 60px;
            border-top: 1px solid #000;
            padding-top: 5px;
            display: inline-block;
            min-width: 200px;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
            font-style: italic;
            border-top: 1px dashed #ccc;
            padding-top: 10px;
        }
        @media print {
            body {
                padding: 0;
            }
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
            .container {
                padding: 0;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête avec logos -->
        <table class="header-table">
            <tr>
                <td style="width: 25%; text-align: left;">
                    <?php
                    $logoUfhb = __DIR__ . '/../../../assets/image/logo_ufhb.png';
                    if (file_exists($logoUfhb) && is_readable($logoUfhb)) {
                        $type = mime_content_type($logoUfhb) ?: 'image/png';
                        $data = base64_encode(file_get_contents($logoUfhb));
                        echo '<img class="logo" src="data:' . $type . ';base64,' . $data . '" alt="Logo UFHB">';
                    }
                    ?>
                </td>
                <td style="width: 50%;" class="center-text">
                    <h2>RÉPUBLIQUE DE CÔTE D'IVOIRE</h2>
                    <h2>MINISTÈRE DE L'ENSEIGNEMENT SUPÉRIEUR</h2>
                    <h2>ET DE LA RECHERCHE SCIENTIFIQUE</h2>
                    <h1>UNIVERSITÉ FÉLIX HOUPHOUËT-BOIGNY</h1>
                    <p>UFR MATHÉMATIQUES ET INFORMATIQUE</p>
                    <p>Filières Professionnalisées - MIAGE</p>
                </td>
                <td style="width: 25%; text-align: right;">
                    <?php
                    $logoMi = __DIR__ . '/../../../assets/image/logo_mi_sbg.png';
                    if (file_exists($logoMi) && is_readable($logoMi)) {
                        $type = mime_content_type($logoMi) ?: 'image/png';
                        $data = base64_encode(file_get_contents($logoMi));
                        echo '<img class="logo" src="data:' . $type . ';base64,' . $data . '" alt="Logo MI">';
                    }
                    ?>
                </td>
            </tr>
        </table>

        <div class="header">
            <div class="title">PROCÈS-VERBAL DE SOUTENANCE</div>
        </div>

        <!-- Informations sur l'étudiant -->
        <div class="section">
            <div class="section-title">I. IDENTIFICATION DE L'ÉTUDIANT</div>
            <div class="info-line">
                <strong>Nom et Prénoms :</strong>
                <?= htmlspecialchars($etudiant['nom'] ?? '') ?> <?= htmlspecialchars($etudiant['prenom'] ?? '') ?>
            </div>
            <div class="info-line">
                <strong>Numéro Étudiant :</strong>
                <?= htmlspecialchars($etudiant['num_etu'] ?? '') ?>
            </div>
            <div class="info-line">
                <strong>Niveau d'Études :</strong>
                <?= htmlspecialchars($etudiant['niveau'] ?? '') ?>
            </div>
            <div class="info-line">
                <strong>Année Académique :</strong>
                <?= htmlspecialchars($etudiant['annee_academique'] ?? '') ?>
            </div>
        </div>

        <!-- Informations sur le mémoire -->
        <div class="section">
            <div class="section-title">II. TRAVAIL PRÉSENTÉ</div>
            <div class="info-line">
                <strong>Titre du Mémoire :</strong>
                <?= htmlspecialchars($titre_memoire ?? '') ?>
            </div>
            <div class="info-line">
                <strong>Date de Soutenance :</strong>
                <?= htmlspecialchars(date('d/m/Y', strtotime($date_soutenance ?? 'now'))) ?>
            </div>
            <div class="info-line">
                <strong>Heure :</strong>
                <?= htmlspecialchars($heure_soutenance ?? '14h00') ?>
            </div>
            <div class="info-line">
                <strong>Lieu :</strong>
                <?= htmlspecialchars($lieu_soutenance ?? 'UFR Mathématiques et Informatique') ?>
            </div>
        </div>

        <!-- Composition du jury -->
        <div class="section">
            <div class="section-title">III. COMPOSITION DU JURY</div>
            <table class="jury-table">
                <thead>
                    <tr>
                        <th>Nom et Prénoms</th>
                        <th>Grade</th>
                        <th>Qualité</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $membres_jury = $jury ?? [];
                    foreach ($membres_jury as $membre):
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($membre['nom'] ?? '') ?></td>
                            <td><?= htmlspecialchars($membre['grade'] ?? '') ?></td>
                            <td><?= htmlspecialchars($membre['qualite'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Résultats -->
        <div class="section">
            <div class="section-title">IV. RÉSULTATS DE LA SOUTENANCE</div>
            <div class="results">
                <div class="result-line">
                    <strong>Note Obtenue :</strong>
                    <span class="note-highlight"><?= htmlspecialchars($note ?? '0') ?> / 20</span>
                </div>
                <div class="result-line">
                    <strong>Mention :</strong>
                    <span class="note-highlight"><?= htmlspecialchars($mention ?? '') ?></span>
                </div>
                <div class="result-line">
                    <strong>Décision du Jury :</strong>
                    <?php
                    $decision = 'AJOURNÉ';
                    if (($note ?? 0) >= 10) {
                        $decision = 'ADMIS';
                    }
                    echo '<span class="note-highlight">' . htmlspecialchars($decision) . '</span>';
                    ?>
                </div>
            </div>
        </div>

        <!-- Observations -->
        <div class="section">
            <div class="section-title">V. OBSERVATIONS DU JURY</div>
            <div class="info-line" style="min-height: 60px; border: 1px solid #ccc; padding: 10px;">
                <?= nl2br(htmlspecialchars($observations ?? '')) ?>
            </div>
        </div>

        <!-- Signatures -->
        <div class="signatures">
            <table class="signature-table">
                <tr>
                    <td>
                        <div style="text-align: center;">
                            <strong>Le Président du Jury</strong>
                            <div class="signature-box">
                                Signature et cachet
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="text-align: center;">
                            <strong>Le Responsable de Filière</strong>
                            <div class="signature-box">
                                Signature et cachet
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Fait à Abidjan, le <?= date('d/m/Y') ?></p>
            <p>Ce procès-verbal fait foi et ne peut être modifié.</p>
        </div>
    </div>
</body>
</html>
