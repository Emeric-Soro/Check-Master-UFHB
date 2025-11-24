<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé de Notes</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10pt;
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
        }
        .header-left {
            text-align: left;
            width: 50%;
        }
        .header-right {
            text-align: right;
            width: 50%;
        }
        h1 {
            text-align: center;
            font-size: 16pt;
            text-transform: uppercase;
            margin: 20px 0;
            background-color: #eee;
            padding: 5px;
        }
        .student-info {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
        }
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .grades-table th, .grades-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }
        .grades-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .grades-table td.left {
            text-align: left;
        }
        .summary-table {
            width: 50%;
            margin-left: auto;
            border-collapse: collapse;
        }
        .summary-table td {
            border: 1px solid #000;
            padding: 5px;
        }
        .footer {
            margin-top: 40px;
            width: 100%;
        }
        .signature {
            float: right;
            width: 200px;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td class="header-left">
                <strong>Université Félix Houphouët-Boigny</strong><br>
                UFR Mathématiques et Informatique<br>
                Abidjan, Côte d'Ivoire
            </td>
            <td class="header-right">
                Année Académique : <strong><?= $annee_academique ?? '2024-2025' ?></strong><br>
                Date d'édition : <?= date('d/m/Y') ?>
            </td>
        </tr>
    </table>

    <h1>RELEVÉ DE NOTES</h1>

    <div class="student-info">
        <strong>Nom et Prénoms :</strong> <?= $etudiant['nom_complet'] ?? 'NOM Prénoms' ?><br>
        <strong>Matricule :</strong> <?= $etudiant['matricule'] ?? 'N/A' ?><br>
        <strong>Niveau :</strong> <?= $etudiant['niveau'] ?? 'Master 1' ?><br>
        <strong>Filière :</strong> <?= $etudiant['filiere'] ?? 'MIAGE' ?>
    </div>

    <table class="grades-table">
        <thead>
            <tr>
                <th>Code UE</th>
                <th>Intitulé de l'Unité d'Enseignement</th>
                <th>Crédits</th>
                <th>Moyenne</th>
                <th>Décision</th>
            </tr>
        </thead>
        <tbody>
            <?php if (isset($notes) && is_array($notes)): ?>
                <?php foreach ($notes as $ue): ?>
                <tr>
                    <td><?= $ue['code'] ?></td>
                    <td class="left"><?= $ue['intitule'] ?></td>
                    <td><?= $ue['credits'] ?></td>
                    <td><?= number_format($ue['moyenne'], 2, ',', ' ') ?></td>
                    <td><?= $ue['decision'] ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Exemple statique -->
                <tr>
                    <td>INF401</td>
                    <td class="left">Génie Logiciel Avancé</td>
                    <td>6</td>
                    <td>14,50</td>
                    <td>Validé</td>
                </tr>
                <tr>
                    <td>INF402</td>
                    <td class="left">Bases de Données NoSQL</td>
                    <td>6</td>
                    <td>12,00</td>
                    <td>Validé</td>
                </tr>
                <tr>
                    <td>MGT401</td>
                    <td class="left">Management de Projet</td>
                    <td>4</td>
                    <td>15,00</td>
                    <td>Validé</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td><strong>Moyenne Générale</strong></td>
            <td><strong><?= isset($moyenne_generale) ? number_format($moyenne_generale, 2, ',', ' ') : '13,83' ?> / 20</strong></td>
        </tr>
        <tr>
            <td><strong>Crédits Total Validés</strong></td>
            <td><strong><?= $total_credits ?? '16' ?> / 30</strong></td>
        </tr>
        <tr>
            <td><strong>Décision du Jury</strong></td>
            <td><strong><?= $decision_jury ?? 'ADMIS' ?></strong></td>
        </tr>
    </table>

    <div class="footer">
        <div class="signature">
            <p>Le Directeur de l'UFR</p>
            <br><br><br>
            <p>____________________</p>
        </div>
    </div>
</body>
</html>
