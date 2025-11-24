<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé de Notes</title>
    <style>
        @page {
            margin: 1.5cm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #1a5276;
            padding-bottom: 15px;
        }
        .institution {
            font-size: 13pt;
            font-weight: bold;
            color: #1a5276;
            margin: 3px 0;
        }
        .subtitle {
            font-size: 9pt;
            color: #666;
        }
        .document-title {
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            color: #1a5276;
            margin: 20px 0;
            text-transform: uppercase;
            background-color: #e8f4f8;
            padding: 10px;
        }
        .student-info {
            margin: 15px 0;
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #1a5276;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .info-item {
            margin: 5px 0;
        }
        .label {
            font-weight: bold;
            color: #1a5276;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 9pt;
        }
        .grades-table th {
            background-color: #1a5276;
            color: white;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #0d3a5c;
        }
        .grades-table td {
            padding: 6px;
            border: 1px solid #ddd;
            text-align: center;
        }
        .grades-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .ue-header {
            background-color: #d4e6f1 !important;
            font-weight: bold;
            text-align: left !important;
        }
        .ecue-row {
            background-color: #fff;
        }
        .moyenne-ue {
            background-color: #aed6f1 !important;
            font-weight: bold;
        }
        .summary-section {
            margin: 20px 0;
            background-color: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 10px;
        }
        .summary-item {
            text-align: center;
            padding: 10px;
            background-color: white;
            border-radius: 5px;
        }
        .summary-label {
            font-size: 9pt;
            color: #666;
            margin-bottom: 5px;
        }
        .summary-value {
            font-size: 14pt;
            font-weight: bold;
            color: #1a5276;
        }
        .decision {
            margin: 20px 0;
            padding: 15px;
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
        }
        .decision.admis {
            background-color: #d5f4e6;
            color: #0a6938;
            border: 2px solid #0a6938;
        }
        .decision.ajourné {
            background-color: #fdecea;
            color: #c0392b;
            border: 2px solid #c0392b;
        }
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            text-align: center;
            width: 45%;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 9pt;
        }
        .legend {
            margin: 15px 0;
            font-size: 8pt;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .watermark {
            text-align: center;
            color: #ccc;
            font-size: 8pt;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="institution">RÉPUBLIQUE DE CÔTE D'IVOIRE</div>
        <div class="subtitle">Union - Discipline - Travail</div>
        <div class="institution" style="margin-top: 10px;">UNIVERSITÉ FÉLIX HOUPHOUËT-BOIGNY</div>
        <div class="subtitle">UFR Mathématiques et Informatique</div>
        <div class="subtitle">Master MIAGE</div>
    </div>

    <div class="document-title">Relevé de Notes - <?= htmlspecialchars($semestre ?? 'Semestre') ?></div>

    <div class="student-info">
        <div class="info-grid">
            <div class="info-item">
                <span class="label">Nom et Prénoms :</span>
                <?= htmlspecialchars($nomComplet ?? '') ?>
            </div>
            <div class="info-item">
                <span class="label">Numéro Étudiant :</span>
                <?= htmlspecialchars($numeroEtudiant ?? '') ?>
            </div>
            <div class="info-item">
                <span class="label">Niveau :</span>
                <?= htmlspecialchars($niveau ?? '') ?>
            </div>
            <div class="info-item">
                <span class="label">Année Académique :</span>
                <?= htmlspecialchars($anneeAcademique ?? '') ?>
            </div>
        </div>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th style="width: 40%;">Unité d'Enseignement / ECUE</th>
                <th style="width: 10%;">Crédit</th>
                <th style="width: 10%;">Note CC</th>
                <th style="width: 10%;">Note EX</th>
                <th style="width: 10%;">Moy.</th>
                <th style="width: 10%;">Crédit Acquis</th>
                <th style="width: 10%;">Observation</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($ues) && is_array($ues)): ?>
                <?php foreach ($ues as $ue): ?>
                    <tr class="ue-header">
                        <td colspan="7">
                            <strong><?= htmlspecialchars($ue['code_ue'] ?? '') ?> - <?= htmlspecialchars($ue['lib_ue'] ?? '') ?></strong>
                        </td>
                    </tr>
                    <?php if (isset($ue['ecues']) && is_array($ue['ecues'])): ?>
                        <?php foreach ($ue['ecues'] as $ecue): ?>
                            <tr class="ecue-row">
                                <td style="text-align: left; padding-left: 20px;">
                                    <?= htmlspecialchars($ecue['code_ecue'] ?? '') ?> - <?= htmlspecialchars($ecue['lib_ecue'] ?? '') ?>
                                </td>
                                <td><?= htmlspecialchars($ecue['credit'] ?? '0') ?></td>
                                <td><?= number_format($ecue['note_cc'] ?? 0, 2) ?></td>
                                <td><?= number_format($ecue['note_ex'] ?? 0, 2) ?></td>
                                <td><strong><?= number_format($ecue['moyenne'] ?? 0, 2) ?></strong></td>
                                <td><?= ($ecue['moyenne'] ?? 0) >= 10 ? $ecue['credit'] : '0' ?></td>
                                <td><?= ($ecue['moyenne'] ?? 0) >= 10 ? 'Acquis' : 'Non Acquis' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="moyenne-ue">
                        <td style="text-align: right;"><strong>Moyenne UE :</strong></td>
                        <td><strong><?= htmlspecialchars($ue['credit_ue'] ?? '0') ?></strong></td>
                        <td colspan="2"></td>
                        <td><strong><?= number_format($ue['moyenne_ue'] ?? 0, 2) ?></strong></td>
                        <td><strong><?= ($ue['moyenne_ue'] ?? 0) >= 10 ? $ue['credit_ue'] : '0' ?></strong></td>
                        <td><strong><?= ($ue['moyenne_ue'] ?? 0) >= 10 ? 'Validé' : 'Non Validé' ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary-section">
        <div style="text-align: center; font-weight: bold; margin-bottom: 10px;">RÉSUMÉ</div>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Moyenne Générale</div>
                <div class="summary-value"><?= number_format($moyenneGenerale ?? 0, 2) ?>/20</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Crédits Acquis</div>
                <div class="summary-value"><?= htmlspecialchars($creditsAcquis ?? '0') ?>/<?= htmlspecialchars($creditsTotal ?? '30') ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Rang</div>
                <div class="summary-value"><?= htmlspecialchars($rang ?? 'N/A') ?>/<?= htmlspecialchars($effectif ?? 'N/A') ?></div>
            </div>
        </div>
    </div>

    <?php
    $decision = 'Ajourné(e)';
    $decisionClass = 'ajourné';
    if (isset($moyenneGenerale) && $moyenneGenerale >= 10) {
        $decision = 'Admis(e)';
        $decisionClass = 'admis';
    }
    ?>
    <div class="decision <?= $decisionClass ?>">
        DÉCISION : <?= $decision ?>
    </div>

    <div class="legend">
        <strong>Légende :</strong> CC = Contrôle Continu | EX = Examen | Moy. = Moyenne | 
        Validation UE : Moyenne ≥ 10/20 | Compensation possible selon règlement pédagogique
    </div>

    <div class="footer">
        <div class="signature-box">
            <div class="signature-line">Le Chef de Département</div>
        </div>
        <div class="signature-box">
            <div>Fait à Abidjan, le <?= date('d/m/Y') ?></div>
            <div class="signature-line">Le Directeur de l'UFR MI</div>
        </div>
    </div>

    <div class="watermark">
        Document officiel généré par CheckMaster UFHB - <?= date('d/m/Y H:i') ?>
    </div>
</body>
</html>
