<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1a5276;
            padding-bottom: 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 10px;
        }
        .institution {
            font-size: 14pt;
            font-weight: bold;
            color: #1a5276;
            margin: 5px 0;
        }
        .subtitle {
            font-size: 10pt;
            color: #666;
        }
        .document-title {
            text-align: center;
            font-size: 16pt;
            font-weight: bold;
            color: #1a5276;
            margin: 30px 0;
            text-transform: uppercase;
        }
        .info-section {
            margin: 20px 0;
        }
        .info-row {
            margin: 8px 0;
            padding: 5px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 180px;
        }
        .value {
            display: inline-block;
        }
        .payment-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .payment-table th {
            background-color: #1a5276;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .payment-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .payment-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .total-row {
            font-weight: bold;
            background-color: #e8f4f8 !important;
        }
        .footer {
            margin-top: 40px;
            text-align: right;
        }
        .signature-section {
            margin-top: 60px;
        }
        .signature-box {
            display: inline-block;
            text-align: center;
            margin: 0 20px;
        }
        .signature-line {
            width: 200px;
            border-top: 1px solid #333;
            margin-top: 60px;
            padding-top: 5px;
        }
        .watermark {
            text-align: center;
            color: #ccc;
            font-size: 9pt;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <!-- Logo placeholder - can be added with mPDF image support -->
        </div>
        <div class="institution">UNIVERSITÉ FÉLIX HOUPHOUËT-BOIGNY</div>
        <div class="subtitle">UFR Mathématiques et Informatique</div>
        <div class="subtitle">Master MIAGE - Méthodes Informatiques Appliquées à la Gestion des Entreprises</div>
    </div>

    <div class="document-title">Reçu de Paiement N° <?= htmlspecialchars($numeroRecu ?? 'N/A') ?></div>

    <div class="info-section">
        <div class="info-row">
            <span class="label">Date d'émission :</span>
            <span class="value"><?= htmlspecialchars($dateEmission ?? date('d/m/Y')) ?></span>
        </div>
        <div class="info-row">
            <span class="label">Année Académique :</span>
            <span class="value"><?= htmlspecialchars($anneeAcademique ?? '') ?></span>
        </div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <span class="label">Étudiant(e) :</span>
            <span class="value"><?= htmlspecialchars($nomEtudiant ?? '') ?></span>
        </div>
        <div class="info-row">
            <span class="label">Numéro Étudiant :</span>
            <span class="value"><?= htmlspecialchars($numeroEtudiant ?? '') ?></span>
        </div>
        <div class="info-row">
            <span class="label">Niveau d'étude :</span>
            <span class="value"><?= htmlspecialchars($niveauEtude ?? '') ?></span>
        </div>
        <div class="info-row">
            <span class="label">Spécialité :</span>
            <span class="value"><?= htmlspecialchars($specialite ?? '') ?></span>
        </div>
    </div>

    <table class="payment-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right; width: 150px;">Montant (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($paiements) && is_array($paiements)): ?>
                <?php foreach ($paiements as $paiement): ?>
                    <tr>
                        <td><?= htmlspecialchars($paiement['description'] ?? '') ?></td>
                        <td style="text-align: right;"><?= number_format($paiement['montant'] ?? 0, 0, ',', ' ') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td><?= htmlspecialchars($descriptionPaiement ?? 'Frais de scolarité') ?></td>
                    <td style="text-align: right;"><?= number_format($montantPaye ?? 0, 0, ',', ' ') ?></td>
                </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td style="text-align: right;"><strong>TOTAL :</strong></td>
                <td style="text-align: right;"><strong><?= number_format($montantTotal ?? $montantPaye ?? 0, 0, ',', ' ') ?></strong></td>
            </tr>
        </tbody>
    </table>

    <div class="info-section">
        <div class="info-row">
            <span class="label">Mode de paiement :</span>
            <span class="value"><?= htmlspecialchars($modePaiement ?? 'Espèces') ?></span>
        </div>
        <?php if (isset($referenceTransaction)): ?>
        <div class="info-row">
            <span class="label">Référence :</span>
            <span class="value"><?= htmlspecialchars($referenceTransaction) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="signature-section" style="text-align: right;">
        <div class="signature-box">
            <div>Fait à Abidjan, le <?= date('d/m/Y') ?></div>
            <div class="signature-line">Le Responsable Scolarité</div>
        </div>
    </div>

    <div class="watermark">
        Document généré automatiquement par CheckMaster UFHB
    </div>
</body>
</html>
