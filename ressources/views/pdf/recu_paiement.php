<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            color: #333;
        }
        .container {
            border: 1px solid #ccc;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #1a5276;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo-area {
            width: 100px;
        }
        .title-area {
            text-align: right;
        }
        .title-area h1 {
            color: #1a5276;
            margin: 0;
            font-size: 24px;
        }
        .receipt-number {
            color: #666;
            font-size: 14px;
        }
        .details-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .row {
            display: table-row;
        }
        .cell {
            display: table-cell;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .label {
            font-weight: bold;
            color: #555;
            width: 40%;
        }
        .amount-box {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            margin: 20px 0;
            border-radius: 5px;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #1a5276;
        }
        .footer {
            text-align: center;
            font-size: 10px;
            color: #888;
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .stamp-area {
            height: 100px;
            margin-top: 20px;
            text-align: right;
            padding-right: 50px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-area">
                <!-- Logo placeholder -->
                <strong>UFHB</strong>
            </div>
            <div class="title-area">
                <h1>REÇU DE PAIEMENT</h1>
                <div class="receipt-number">N° <?= $paiement['reference'] ?? 'REF-000000' ?></div>
            </div>
        </div>

        <div class="details-grid">
            <div class="row">
                <div class="cell label">Date du paiement</div>
                <div class="cell"><?= $paiement['date'] ?? date('d/m/Y') ?></div>
            </div>
            <div class="row">
                <div class="cell label">Étudiant</div>
                <div class="cell"><?= $etudiant['nom_complet'] ?? 'Nom Prénoms' ?> (<?= $etudiant['matricule'] ?? 'N/A' ?>)</div>
            </div>
            <div class="row">
                <div class="cell label">Motif</div>
                <div class="cell"><?= $paiement['motif'] ?? 'Frais de scolarité' ?></div>
            </div>
            <div class="row">
                <div class="cell label">Mode de paiement</div>
                <div class="cell"><?= $paiement['mode'] ?? 'Espèces' ?></div>
            </div>
        </div>

        <div class="amount-box">
            <div>Montant payé</div>
            <div class="amount"><?= number_format($paiement['montant'] ?? 0, 0, ',', ' ') ?> FCFA</div>
        </div>

        <div class="stamp-area">
            <p>Cachet et Signature</p>
        </div>

        <div class="footer">
            <p>Ce reçu est une preuve de paiement. Veuillez le conserver précieusement.</p>
            <p>Généré par CheckMaster le <?= date('d/m/Y à H:i') ?></p>
        </div>
    </div>
</body>
</html>
