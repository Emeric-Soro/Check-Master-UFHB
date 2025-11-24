<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Procès-Verbal de Soutenance</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 18pt;
            text-transform: uppercase;
            margin: 0;
        }
        .header h2 {
            font-size: 14pt;
            margin: 5px 0;
        }
        .content {
            margin: 20px 0;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-row {
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 200px;
        }
        .value {
            display: inline-block;
            border-bottom: 1px dotted #000;
            min-width: 300px;
        }
        .jury-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .jury-table th, .jury-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .jury-table th {
            background-color: #f0f0f0;
        }
        .decision-box {
            border: 2px solid #000;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }
        .signatures {
            margin-top: 50px;
            width: 100%;
        }
        .signature-box {
            float: left;
            width: 45%;
            height: 100px;
            border: 1px solid transparent; /* Placeholder for alignment */
        }
        .signature-box.right {
            float: right;
            text-align: right;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Université Félix Houphouët-Boigny</h1>
        <h2>UFR Mathématiques et Informatique</h2>
        <h3>PROCÈS-VERBAL DE SOUTENANCE</h3>
    </div>

    <div class="content">
        <div class="info-section">
            <div class="info-row">
                <span class="label">Année Académique :</span>
                <span class="value"><?= $annee_academique ?? '2024-2025' ?></span>
            </div>
            <div class="info-row">
                <span class="label">Candidat(e) :</span>
                <span class="value"><?= $etudiant['nom'] ?? 'NOM' ?> <?= $etudiant['prenoms'] ?? 'Prénoms' ?></span>
            </div>
            <div class="info-row">
                <span class="label">Matricule :</span>
                <span class="value"><?= $etudiant['matricule'] ?? '000000' ?></span>
            </div>
            <div class="info-row">
                <span class="label">Filière :</span>
                <span class="value"><?= $etudiant['filiere'] ?? 'MIAGE' ?></span>
            </div>
            <div class="info-row">
                <span class="label">Sujet :</span>
                <span class="value"><?= $sujet ?? 'Titre du mémoire' ?></span>
            </div>
        </div>

        <h3>Membres du Jury</h3>
        <table class="jury-table">
            <thead>
                <tr>
                    <th>Qualité</th>
                    <th>Nom et Prénoms</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($jury) && is_array($jury)): ?>
                    <?php foreach ($jury as $membre): ?>
                    <tr>
                        <td><?= $membre['qualite'] ?></td>
                        <td><?= $membre['nom_complet'] ?></td>
                        <td><?= $membre['grade'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td>Président</td>
                        <td>__________________________</td>
                        <td>__________________________</td>
                    </tr>
                    <tr>
                        <td>Rapporteur</td>
                        <td>__________________________</td>
                        <td>__________________________</td>
                    </tr>
                    <tr>
                        <td>Examinateur</td>
                        <td>__________________________</td>
                        <td>__________________________</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="decision-box">
            <h3>DÉCISION DU JURY</h3>
            <p>Note attribuée : <strong><?= $note ?? '__' ?> / 20</strong></p>
            <p>Mention : <strong><?= $mention ?? '__________________' ?></strong></p>
            <p>Le jury a décidé d'accepter le présent mémoire.</p>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <p><strong>Le Président du Jury</strong></p>
            </div>
            <div class="signature-box right">
                <p><strong>Le Directeur de l'UFR</strong></p>
            </div>
            <div class="clear"></div>
        </div>
    </div>
</body>
</html>
