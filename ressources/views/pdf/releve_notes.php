<?php
/**
 * Template HTML pour le Relevé de Notes
 * Variables attendues: $etudiant, $notes, $annee_academique, $niveau
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé de Notes</title>
    <style>
        /* Reset minimal pour impression */
        html, body {
            margin: 0;
            padding: 0;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }
        .container {
            padding: 15px;
            max-width: 900px;
            margin: 0 auto;
        }
        /* Header layout compatible avec mPDF */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0 5px;
        }
        .logo {
            height: 75px;
        }
        .title-block {
            text-align: center;
        }
        .title-main {
            font-size: 18px;
            font-weight: 700;
            margin: 3px 0;
        }
        .title-sub {
            font-size: 12px;
            margin: 2px 0;
        }
        .faculty {
            font-size: 13px;
            font-weight: 700;
            text-align: right;
            margin-bottom: 10px;
        }
        .student-info {
            font-size: 12px;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .student-info strong {
            display: inline-block;
            width: 150px;
        }
        .table-notes {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .table-notes th,
        .table-notes td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
            font-size: 11px;
        }
        .table-notes th {
            background: #e8e8e8;
            font-weight: bold;
        }
        .table-notes .center {
            text-align: center;
        }
        .section-title {
            font-weight: 700;
            margin: 15px 0 8px;
            font-size: 13px;
            text-decoration: underline;
        }
        .total-row {
            font-weight: 700;
            background: #f0f0f0;
        }
        .recap {
            margin-top: 12px;
            font-size: 12px;
            line-height: 1.6;
        }
        .recap strong {
            display: inline-block;
            width: 200px;
        }
        /* Footer */
        .footer {
            margin-top: 35px;
            font-size: 11px;
        }
        .footer .left {
            float: left;
        }
        .footer .right {
            float: right;
            text-align: right;
        }
        .clearfix {
            clear: both;
        }
        /* Print adjustments */
        @media print {
            body {
                padding: 0;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            .container {
                padding: 8mm;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <table class="header-table">
            <tr>
                <td style="width:22%;text-align:left">
                    <?php
                    $logoUfhb = __DIR__ . '/../../../assets/image/logo_ufhb.png';
                    if (file_exists($logoUfhb) && is_readable($logoUfhb)) {
                        $type = mime_content_type($logoUfhb) ?: 'image/png';
                        $data = base64_encode(file_get_contents($logoUfhb));
                        echo '<img class="logo" src="data:' . $type . ';base64,' . $data . '" alt="Logo UFHB">';
                    }
                    ?>
                </td>
                <td style="width:56%" class="title-block">
                    <div class="title-sub">RÉPUBLIQUE DE CÔTE D'IVOIRE</div>
                    <div class="title-sub">MINISTÈRE DE L'ENSEIGNEMENT SUPÉRIEUR ET DE LA RECHERCHE SCIENTIFIQUE</div>
                    <div class="title-main">RELEVÉ DE NOTES</div>
                    <div class="title-sub">Année universitaire : <?= htmlspecialchars($annee_academique ?? '2024-2025') ?></div>
                </td>
                <td style="width:22%;text-align:right">
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

        <div style="margin-bottom:12px">
            <div class="student-info">
                <strong>NOM :</strong> <?= htmlspecialchars($etudiant['nom'] ?? '') ?>
                <strong style="margin-left:20px">PRÉNOMS :</strong> <?= htmlspecialchars($etudiant['prenom'] ?? '') ?><br>
                <strong>DATE DE NAISSANCE :</strong> <?= htmlspecialchars($etudiant['date_naissance'] ?? '') ?>
                <strong style="margin-left:20px">PARCOURS :</strong> MIAGE<br>
                <strong>NIVEAU :</strong> <?= htmlspecialchars($niveau ?? '') ?>
                <strong style="margin-left:20px">N° CARTE ÉTUDIANT :</strong> <?= htmlspecialchars($etudiant['num_etu'] ?? '') ?>
            </div>
            <div class="faculty">
                FILIÈRES PROFESSIONNALISÉES (GI-MIAGE) — <?= htmlspecialchars($annee_academique ?? '2024-2025') ?>
            </div>
            <div class="clearfix"></div>
        </div>

        <?php
        // Regrouper les notes par semestre et par type (majeur/mineur)
        $semestres = [];
        if (isset($notes) && is_array($notes)) {
            foreach ($notes as $note) {
                $lib_semestre = $note['lib_semestre'] ?? 'Semestre 1';
                $credit = floatval($note['credit'] ?? 0);
                $sem = $lib_semestre;
                $type = ($credit > 3) ? 'majeures' : 'mineures';
                $semestres[$sem][$type][] = $note;
            }
        }

        $semIndex = 1;
        foreach ($semestres as $sem => $types):
            $totalCredits = 0;
            $totalMoy = 0;
            $totalCoef = 0;
        ?>
            <div class="section-title">Semestre <?= $semIndex ?> — <?= htmlspecialchars($sem) ?></div>
            
            <!-- UE Majeures -->
            <table class="table-notes">
                <tr>
                    <th>Code</th>
                    <th>UE</th>
                    <th>Coef</th>
                    <th>Moyenne /20</th>
                    <th>Crédits</th>
                    <th>Session</th>
                </tr>
                <?php
                $sumMaj = $credMaj = 0;
                if (!empty($types['majeures'])):
                    foreach ($types['majeures'] as $note) {
                        $moyenne = floatval($note['moyenne'] ?? 0);
                        $credit = floatval($note['credit'] ?? 0);
                        $code = $note['code_ecue'] ?? ($note['code_ue'] ?? '-');
                        $libelle = $note['lib_ue'] ?? ($note['lib_ecue'] ?? '-');

                        $sumMaj += $moyenne * $credit;
                        $credMaj += $credit;
                        $totalCredits += $credit;
                        $totalMoy += $moyenne;
                        $totalCoef += $credit;
                        
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($code) . '</td>';
                        echo '<td>' . htmlspecialchars($libelle) . '</td>';
                        echo '<td class="center">' . htmlspecialchars($credit ?: '-') . '</td>';
                        echo '<td class="center">' . htmlspecialchars($moyenne ? number_format($moyenne, 2) : '-') . '</td>';
                        echo '<td class="center">' . htmlspecialchars($credit ?: '-') . '</td>';
                        echo '<td class="center">1</td>';
                        echo '</tr>';
                    }
                    $moyMaj = $credMaj ? round($sumMaj / $credMaj, 2) : 0;
                endif;
                ?>
                <tr class="total-row">
                    <td colspan="2">Moyenne UE Majeures et crédits</td>
                    <td class="center"><?= $credMaj ?></td>
                    <td class="center"><?= number_format($moyMaj ?? 0, 2) ?></td>
                    <td class="center"><?= $credMaj ?></td>
                    <td></td>
                </tr>
            </table>

            <!-- UE Mineures -->
            <table class="table-notes">
                <tr>
                    <th>Code</th>
                    <th>UE</th>
                    <th>Coef</th>
                    <th>Moyenne /20</th>
                    <th>Crédits</th>
                    <th>Session</th>
                </tr>
                <?php
                $sumMin = $credMin = 0;
                if (!empty($types['mineures'])):
                    foreach ($types['mineures'] as $note) {
                        $moyenne = floatval($note['moyenne'] ?? 0);
                        $credit = floatval($note['credit'] ?? 0);
                        $code = $note['code_ecue'] ?? ($note['code_ue'] ?? '-');
                        $libelle = $note['lib_ue'] ?? ($note['lib_ecue'] ?? '-');

                        $sumMin += $moyenne * $credit;
                        $credMin += $credit;
                        $totalCredits += $credit;
                        $totalMoy += $moyenne;
                        $totalCoef += $credit;
                        
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($code) . '</td>';
                        echo '<td>' . htmlspecialchars($libelle) . '</td>';
                        echo '<td class="center">' . htmlspecialchars($credit ?: '-') . '</td>';
                        echo '<td class="center">' . htmlspecialchars($moyenne ? number_format($moyenne, 2) : '-') . '</td>';
                        echo '<td class="center">' . htmlspecialchars($credit ?: '-') . '</td>';
                        echo '<td class="center">1</td>';
                        echo '</tr>';
                    }
                    $moyMin = $credMin ? round($sumMin / $credMin, 2) : 0;
                endif;
                ?>
                <tr class="total-row">
                    <td colspan="2">Moyenne UE Mineures et crédits</td>
                    <td class="center"><?= $credMin ?></td>
                    <td class="center"><?= number_format($moyMin ?? 0, 2) ?></td>
                    <td class="center"><?= $credMin ?></td>
                    <td></td>
                </tr>
            </table>

            <div class="recap">
                <strong>Total crédits :</strong> <?= $credMaj + $credMin ?><br>
                <strong>Résultat Semestre <?= $semIndex ?> :</strong> 
                <?php
                $resultatSem = 'AJOURNÉ';
                if (($moyMaj ?? 0) >= 10 && ($moyMin ?? 0) >= 10 && ($credMaj + $credMin) >= 30) {
                    $resultatSem = 'ADMIS';
                }
                echo htmlspecialchars($resultatSem);
                ?><br>
                <strong>Moyenne semestre <?= $semIndex ?> :</strong>
                <?= $totalCoef ? number_format(($sumMaj + $sumMin) / ($credMaj + $credMin), 2) : '0.00' ?><br>
            </div>
        <?php 
            $semIndex++; 
        endforeach; 
        ?>

        <div class="recap" style="margin-top:18px">
            <strong>RÉSULTAT GÉNÉRAL</strong><br>
            Un Semestre n'est validé que si la moyenne des UE majeures et celle des UE mineures sont toutes ≥ 10.<br>
            La note plancher de chaque UE est de 05/20.<br>
            L'étudiant n'est déclaré admis que s'il a obtenu 30 Crédits par semestre.<br>
            <br>
            <strong>Résultat (Délibération du jury) :</strong> 
            <?php
            // Calculer le résultat général
            $totalNotes = 0;
            $totalCreditsGen = 0;
            $resultat_general = 'ADMIS';
            
            if (isset($notes) && is_array($notes)) {
                foreach ($notes as $note) {
                    $moyenne = floatval($note['moyenne'] ?? 0);
                    $credit = floatval($note['credit'] ?? 0);
                    $totalNotes += $moyenne * $credit;
                    $totalCreditsGen += $credit;
                    
                    // Vérifier la note plancher
                    if ($moyenne < 5) {
                        $resultat_general = 'AJOURNÉ';
                    }
                }
            }
            
            echo htmlspecialchars($resultat_general);
            ?><br>
            <strong>Moyenne générale :</strong> 
            <?= $totalCreditsGen > 0 ? number_format($totalNotes / $totalCreditsGen, 2) : '0.00' ?><br>
        </div>

        <div class="footer">
            <div class="left">
                Fait à Abidjan, le <?= date('d/m/Y') ?><br>
                <span style="margin-top: 60px; display: inline-block; border-top: 1px solid #000; padding-top: 5px;">
                    Signature du responsable
                </span>
            </div>
            <div class="right">
                <span style="margin-top: 60px; display: inline-block; border-top: 1px solid #000; padding-top: 5px;">
                    Cachet de l'établissement
                </span>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</body>
</html>
