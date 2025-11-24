<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procès-Verbal de Soutenance</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 3px double #1a5276;
            padding-bottom: 15px;
        }
        .logo-section {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .logo-left, .logo-right {
            display: table-cell;
            width: 33%;
            text-align: center;
            vertical-align: middle;
        }
        .logo-center {
            display: table-cell;
            width: 34%;
            text-align: center;
            vertical-align: middle;
        }
        .institution {
            font-size: 13pt;
            font-weight: bold;
            color: #1a5276;
            margin: 3px 0;
        }
        .subtitle {
            font-size: 10pt;
            color: #666;
        }
        .republic {
            font-size: 11pt;
            font-weight: bold;
            color: #000;
        }
        .document-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            color: #1a5276;
            margin: 25px 0;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .section {
            margin: 20px 0;
        }
        .section-title {
            font-size: 12pt;
            font-weight: bold;
            color: #1a5276;
            margin: 15px 0 10px 0;
            border-bottom: 2px solid #1a5276;
            padding-bottom: 5px;
        }
        .info-table {
            width: 100%;
            margin: 10px 0;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .info-label {
            font-weight: bold;
            background-color: #f0f0f0;
            width: 35%;
        }
        .jury-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .jury-table th {
            background-color: #1a5276;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #0d3a5c;
        }
        .jury-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .jury-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .evaluation-section {
            margin: 20px 0;
            background-color: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
        }
        .evaluation-grid {
            display: table;
            width: 100%;
            margin: 10px 0;
        }
        .eval-row {
            display: table-row;
        }
        .eval-label, .eval-value {
            display: table-cell;
            padding: 8px;
            border: 1px solid #ccc;
        }
        .eval-label {
            font-weight: bold;
            background-color: #f8f9fa;
            width: 60%;
        }
        .eval-value {
            text-align: center;
            width: 20%;
        }
        .final-grade {
            margin: 20px 0;
            padding: 20px;
            background-color: #d5f4e6;
            border: 3px solid #0a6938;
            text-align: center;
        }
        .final-grade-label {
            font-size: 12pt;
            font-weight: bold;
            color: #0a6938;
        }
        .final-grade-value {
            font-size: 24pt;
            font-weight: bold;
            color: #0a6938;
            margin: 10px 0;
        }
        .mention {
            font-size: 14pt;
            font-weight: bold;
            color: #1a5276;
            margin-top: 10px;
        }
        .decision-box {
            margin: 20px 0;
            padding: 15px;
            border: 2px solid #1a5276;
            background-color: #f8f9fa;
        }
        .decision-text {
            font-size: 11pt;
            line-height: 1.8;
            text-align: justify;
        }
        .signatures {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-grid {
            display: table;
            width: 100%;
            margin-top: 30px;
        }
        .signature-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .signature-box {
            margin: 10px;
        }
        .signature-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .signature-name {
            margin: 5px 0;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
            font-size: 9pt;
        }
        .footer-info {
            margin-top: 30px;
            font-size: 9pt;
            color: #666;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-section">
            <div class="logo-left">
                <!-- Logo Côte d'Ivoire -->
            </div>
            <div class="logo-center">
                <div class="republic">RÉPUBLIQUE DE CÔTE D'IVOIRE</div>
                <div class="subtitle">Union - Discipline - Travail</div>
            </div>
            <div class="logo-right">
                <!-- Logo UFHB -->
            </div>
        </div>
        <div class="institution">UNIVERSITÉ FÉLIX HOUPHOUËT-BOIGNY</div>
        <div class="subtitle">UFR Mathématiques et Informatique</div>
        <div class="subtitle">Master MIAGE - Méthodes Informatiques Appliquées à la Gestion des Entreprises</div>
    </div>

    <div class="document-title">Procès-Verbal de Soutenance</div>

    <div class="section">
        <table class="info-table">
            <tr>
                <td class="info-label">Année Académique</td>
                <td><?= htmlspecialchars($anneeAcademique ?? '') ?></td>
            </tr>
            <tr>
                <td class="info-label">Date de soutenance</td>
                <td><?= htmlspecialchars($dateSoutenance ?? date('d/m/Y')) ?></td>
            </tr>
            <tr>
                <td class="info-label">Heure</td>
                <td><?= htmlspecialchars($heureSoutenance ?? '') ?></td>
            </tr>
            <tr>
                <td class="info-label">Lieu</td>
                <td><?= htmlspecialchars($lieuSoutenance ?? 'UFR MI - UFHB') ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Candidat(e)</div>
        <table class="info-table">
            <tr>
                <td class="info-label">Nom et Prénoms</td>
                <td><?= htmlspecialchars($nomComplet ?? '') ?></td>
            </tr>
            <tr>
                <td class="info-label">Numéro Étudiant</td>
                <td><?= htmlspecialchars($numeroEtudiant ?? '') ?></td>
            </tr>
            <tr>
                <td class="info-label">Niveau</td>
                <td><?= htmlspecialchars($niveau ?? 'Master 2 MIAGE') ?></td>
            </tr>
            <tr>
                <td class="info-label">Titre du mémoire</td>
                <td><strong><?= htmlspecialchars($titreMémoire ?? '') ?></strong></td>
            </tr>
            <tr>
                <td class="info-label">Entreprise d'accueil</td>
                <td><?= htmlspecialchars($entreprise ?? 'N/A') ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Composition du Jury</div>
        <table class="jury-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Nom et Prénoms</th>
                    <th style="width: 25%;">Grade</th>
                    <th style="width: 20%;">Qualité</th>
                    <th style="width: 15%;">Signature</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($jury) && is_array($jury)): ?>
                    <?php foreach ($jury as $membre): ?>
                        <tr>
                            <td><?= htmlspecialchars($membre['nom'] ?? '') ?></td>
                            <td><?= htmlspecialchars($membre['grade'] ?? '') ?></td>
                            <td><strong><?= htmlspecialchars($membre['role'] ?? '') ?></strong></td>
                            <td></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td><?= htmlspecialchars($president ?? '') ?></td>
                        <td><?= htmlspecialchars($gradePresident ?? '') ?></td>
                        <td><strong>Président</strong></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><?= htmlspecialchars($directeur ?? '') ?></td>
                        <td><?= htmlspecialchars($gradeDirecteur ?? '') ?></td>
                        <td><strong>Directeur de mémoire</strong></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><?= htmlspecialchars($rapporteur ?? '') ?></td>
                        <td><?= htmlspecialchars($gradeRapporteur ?? '') ?></td>
                        <td><strong>Rapporteur</strong></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Évaluation</div>
        <div class="evaluation-section">
            <div class="evaluation-grid">
                <div class="eval-row">
                    <div class="eval-label">Qualité du document écrit</div>
                    <div class="eval-value"><?= htmlspecialchars($noteDocument ?? '') ?> /20</div>
                </div>
                <div class="eval-row">
                    <div class="eval-label">Qualité de la présentation orale</div>
                    <div class="eval-value"><?= htmlspecialchars($noteOrale ?? '') ?> /20</div>
                </div>
                <div class="eval-row">
                    <div class="eval-label">Pertinence des réponses aux questions</div>
                    <div class="eval-value"><?= htmlspecialchars($noteDefense ?? '') ?> /20</div>
                </div>
            </div>
        </div>
    </div>

    <div class="final-grade">
        <div class="final-grade-label">NOTE FINALE</div>
        <div class="final-grade-value"><?= number_format($noteFinale ?? 0, 2) ?> / 20</div>
        <div class="mention">
            <?php
            $mention = 'Ajourné(e)';
            if (isset($noteFinale)) {
                if ($noteFinale >= 16) {
                    $mention = 'Très Bien';
                } elseif ($noteFinale >= 14) {
                    $mention = 'Bien';
                } elseif ($noteFinale >= 12) {
                    $mention = 'Assez Bien';
                } elseif ($noteFinale >= 10) {
                    $mention = 'Passable';
                }
            }
            echo 'Mention : ' . htmlspecialchars($mention);
            ?>
        </div>
    </div>

    <div class="decision-box">
        <div class="decision-text">
            Après délibération, le jury décide à l'unanimité 
            <?php if (isset($noteFinale) && $noteFinale >= 10): ?>
                d'<strong>ADMETTRE</strong> le/la candidat(e) 
                <?= htmlspecialchars($nomComplet ?? '') ?> 
                au diplôme de <strong>Master MIAGE</strong> avec la mention 
                <strong><?= htmlspecialchars($mention) ?></strong>.
            <?php else: ?>
                d'<strong>AJOURNER</strong> le/la candidat(e) 
                <?= htmlspecialchars($nomComplet ?? '') ?>.
            <?php endif; ?>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Observations du Jury</div>
        <div style="min-height: 80px; border: 1px solid #ddd; padding: 10px; background-color: #fafafa;">
            <?= nl2br(htmlspecialchars($observations ?? 'Aucune observation particulière.')) ?>
        </div>
    </div>

    <div class="signatures">
        <div style="text-align: center; margin-bottom: 20px;">
            Fait à Abidjan, le <?= date('d/m/Y') ?>
        </div>
        <div class="signature-grid">
            <div class="signature-cell">
                <div class="signature-box">
                    <div class="signature-title">Le Président du Jury</div>
                    <div class="signature-name"><?= htmlspecialchars($president ?? '') ?></div>
                    <div class="signature-line">Signature et Cachet</div>
                </div>
            </div>
            <div class="signature-cell">
                <div class="signature-box">
                    <div class="signature-title">Le Directeur de l'UFR MI</div>
                    <div class="signature-line">Signature et Cachet</div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer-info">
        Document officiel - Procès-verbal généré par CheckMaster UFHB<br>
        Date de génération : <?= date('d/m/Y H:i:s') ?>
    </div>
</body>
</html>
