<?php if (!empty($GLOBALS['selectedStudent'])): ?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Relevé de notes</title>
    <style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
        color: #000;
    }

    .header-table {
        width: 100%;
        border: none;
        margin-bottom: 10px;
    }

    .header-table td {
        vertical-align: top;
    }

    .logo {
        height: 60px;
    }

    .titre {
        font-size: 18px;
        font-weight: bold;
        text-align: center;
    }

    .sous-titre {
        font-size: 12px;
        text-align: center;
    }

    .filiere {
        font-size: 13px;
        font-weight: bold;
        text-align: right;
    }

    .infos {
        font-size: 12px;
    }

    .infos strong {
        display: inline-block;
        width: 120px;
    }

    .table-notes {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }

    .table-notes th,
    .table-notes td {
        border: 1px solid #000;
        padding: 3px 5px;
        text-align: left;
    }

    .table-notes th {
        background: #f4f4f4;
        font-size: 12px;
    }

    .table-notes .center {
        text-align: center;
    }

    .table-notes .right {
        text-align: right;
    }

    .section-title {
        font-weight: bold;
        margin-top: 10px;
        margin-bottom: 2px;
    }

    .total-row {
        font-weight: bold;
        background: #f4f4f4;
    }

    .recap {
        margin-top: 10px;
        font-size: 12px;
    }

    .recap strong {
        width: 180px;
        display: inline-block;
    }

    .footer {
        margin-top: 30px;
        display: flex;
        justify-content: space-between;
        font-size: 11px;
    }

    .mention {
        font-size: 11px;
        margin-top: 8px;
    }
    </style>
</head>

<body>
    <table class="header-table">
        <tr>
            <td style="width: 20%;"></td>
            <td style="width: 60%; text-align: center;">
                <div class="titre">REPUBLIQUE DE CÔTE D'IVOIRE</div>
                <div class="sous-titre">MINISTERE DE L'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE</div>
                <div class="titre" style="margin-top: 8px;">RELEVE DE NOTES</div>
                <div class="sous-titre">Année universitaire : 2023-2024</div>
            </td>
            <td style="width: 20%; text-align: right;"></td>
        </tr>
    </table>



    <table style="width: 100%; margin-bottom: 8px;">
        <tr>
            <td style="width: 60%; vertical-align: top;">
                <div class="infos">
                    <strong>NOM :</strong> <?= htmlspecialchars($GLOBALS['selectedStudent']->nom_etu) ?><br>
                    <strong>PRENOMS :</strong> <?= htmlspecialchars($GLOBALS['selectedStudent']->prenom_etu) ?><br>
                    <strong>DATE DE NAISSANCE :</strong>
                    <?= htmlspecialchars($GLOBALS['selectedStudent']->date_naiss_etu) ?><br>
                    <strong>PARCOURS :</strong> MIAGE<br>
                    <strong>NIVEAU :</strong> <?= htmlspecialchars($GLOBALS['niveau'] ?? '') ?><br>
                    <strong>N° CARTE ETUDIANT :</strong>
                    <?= htmlspecialchars($GLOBALS['selectedStudent']->num_etu) ?><br>
                </div>
            </td>
            <td style="width: 40%; vertical-align: top;">
                <div class="filiere">
                    FILIERES PROFESSIONNALISEES (GI-MIAGE)<br>
                    2023-2024<br>
                </div>
            </td>
        </tr>
    </table>
    <?php
// Regrouper les notes par semestre et par type (majeur/mineur)
$semestres = [];
foreach ($GLOBALS['studentGrades'] as $grade) {
    // Vérifier si $grade est un objet ou un tableau
    $lib_semestre = is_object($grade) ? ($grade->lib_semestre ?? 'Semestre ?') : ($grade['lib_semestre'] ?? 'Semestre ?');
    $credit = is_object($grade) ? ($grade->credit ?? 0) : ($grade['credit'] ?? 0);
    $sem = $lib_semestre;
    $type = ($credit > 3) ? 'majeures' : 'mineures';
    $semestres[$sem][$type][] = $grade;
}
$semIndex = 1;
foreach ($semestres as $sem => $types) :
    $totalCredits = 0; $totalMoy = 0; $totalCoef = 0;
?>
    <div class="section-title">Semestre <?= $semIndex ?></div>
    <table class="table-notes">
        <tr>
            <th>Code</th>
            <th>UE MAJEURES</th>
            <th>Coef</th>
            <th>Moyenne déff/20</th>
            <th>Crédits</th>
            <th>Session</th>
        </tr>
        <?php
    $sumMaj = $credMaj = $moyMaj = 0;
    if (!empty($types['majeures'])) :
        foreach ($types['majeures'] as $grade) {
            // Vérifier si $grade est un objet ou un tableau
            $moyenne = is_object($grade) ? ($grade->moyenne ?? 0) : ($grade['moyenne'] ?? 0);
            $credit = is_object($grade) ? ($grade->credit ?? 0) : ($grade['credit'] ?? 0);
            $code_ecue = is_object($grade) ? ($grade->code_ecue ?? '') : ($grade['code_ecue'] ?? '');
            $code_ue = is_object($grade) ? ($grade->code_ue ?? '') : ($grade['code_ue'] ?? '');
            $lib_ue = is_object($grade) ? ($grade->lib_ue ?? '') : ($grade['lib_ue'] ?? '');
            $lib_ecue = is_object($grade) ? ($grade->lib_ecue ?? '') : ($grade['lib_ecue'] ?? '');
            
            $sumMaj += $moyenne * $credit;
            $credMaj += $credit;
            $totalCredits += $credit;
            $totalMoy += $moyenne;
            $totalCoef += $credit;
            $code = !empty($code_ecue) ? $code_ecue : (!empty($code_ue) ? $code_ue : '-');
            echo '<tr>';
            echo '<td>' . htmlspecialchars($code) . '</td>';
            echo '<td>' . htmlspecialchars(!empty($lib_ue) ? $lib_ue : (!empty($lib_ecue) ? $lib_ecue : '-')) . '</td>';
            echo '<td class="center">' . htmlspecialchars($credit ?: '-') . '</td>';
            echo '<td class="center">' . htmlspecialchars($moyenne ? number_format($moyenne,2) : '-') . '</td>';
            echo '<td class="center">' . htmlspecialchars($credit ?: '-') . '</td>';
            echo '<td class="center">1</td>';
            echo '</tr>';
        }
        $moyMaj = $credMaj ? round($sumMaj / $credMaj, 2) : '-';
    endif;
    ?>
        <tr class="total-row">
            <td colspan="2">Moyenne UE Majeures et crédits</td>
            <td class="center"><?= $credMaj ?></td>
            <td class="center"><?= $moyMaj ?></td>
            <td class="center"><?= $credMaj ?></td>
            <td></td>
        </tr>
    </table>
    <table class="table-notes">
        <tr>
            <th>Code</th>
            <th>UE MINEURES</th>
            <th>Coef</th>
            <th>Moyenne déff/20</th>
            <th>Crédits</th>
            <th>Session</th>
        </tr>
        <?php
    $sumMin = $credMin = $moyMin = 0;
    if (!empty($types['mineures'])) :
        foreach ($types['mineures'] as $grade) {
            // Vérifier si $grade est un objet ou un tableau
            $moyenne = is_object($grade) ? ($grade->moyenne ?? 0) : ($grade['moyenne'] ?? 0);
            $credit = is_object($grade) ? ($grade->credit ?? 0) : ($grade['credit'] ?? 0);
            $code_ecue = is_object($grade) ? ($grade->code_ecue ?? '') : ($grade['code_ecue'] ?? '');
            $code_ue = is_object($grade) ? ($grade->code_ue ?? '') : ($grade['code_ue'] ?? '');
            $lib_ue = is_object($grade) ? ($grade->lib_ue ?? '') : ($grade['lib_ue'] ?? '');
            $lib_ecue = is_object($grade) ? ($grade->lib_ecue ?? '') : ($grade['lib_ecue'] ?? '');
            
            $sumMin += $moyenne * $credit;
            $credMin += $credit;
            $totalCredits += $credit;
            $totalMoy += $moyenne;
            $totalCoef += $credit;
            $code = !empty($code_ecue) ? $code_ecue : (!empty($code_ue) ? $code_ue : '-');
            echo '<tr>';
            echo '<td>' . htmlspecialchars($code) . '</td>';
            echo '<td>' . htmlspecialchars(!empty($lib_ue) ? $lib_ue : (!empty($lib_ecue) ? $lib_ecue : '-')) . '</td>';
            echo '<td class="center">' . htmlspecialchars($credit ?: '-') . '</td>';
            echo '<td class="center">' . htmlspecialchars($moyenne ? number_format($moyenne,2) : '-') . '</td>';
            echo '<td class="center">' . htmlspecialchars($credit ?: '-') . '</td>';
            echo '<td class="center">1</td>';
            echo '</tr>';
        }
        $moyMin = $credMin ? round($sumMin / $credMin, 2) : '-';
    endif;
    ?>
        <tr class="total-row">
            <td colspan="2">Moyenne UE Mineures et crédits</td>
            <td class="center"><?= $credMin ?></td>
            <td class="center"><?= $moyMin ?></td>
            <td class="center"><?= $credMin ?></td>
            <td></td>
        </tr>
    </table>
    <div class="recap">
        <strong>Total crédits :</strong> <?= $credMaj + $credMin ?><br>
        <strong>Résultat Semestre <?= $semIndex ?> :</strong> Admis<br>
        <strong>Moyenne semestre <?= $semIndex ?> :</strong>
        <?= $totalCoef ? number_format(($sumMaj + $sumMin) / ($credMaj + $credMin), 2) : '-' ?><br>
    </div>
    <?php $semIndex++; endforeach; ?>
    <div class="recap">
        <strong>RESULTAT GENERAL</strong><br>
        Un Semestre n'est validé que si la moyenne des UE majeures et celle des UE mineures sont toutes >=10.<br>
        La note plancher de chaque UE est de 05/20.<br>
        L'étudiant n'est déclaré admis que s'il a obtenu 30 Crédits par semestre.<br>
        <br>
        <strong>Résultat (Délibération du jury) :</strong> Admis<br>
        <strong>Moyenne générale :</strong> <?php
        $totalNotes = 0; $totalCredits = 0;
        foreach ($GLOBALS['studentGrades'] as $grade) {
            $moyenne = is_object($grade) ? ($grade->moyenne ?? 0) : ($grade['moyenne'] ?? 0);
            $credit = is_object($grade) ? ($grade->credit ?? 0) : ($grade['credit'] ?? 0);
            $totalNotes += $moyenne * $credit;
            $totalCredits += $credit;
        }
        echo $totalCredits > 0 ? number_format($totalNotes / $totalCredits, 2) : '0.00';
    ?><br>
    </div>
    <div class="footer">
        <div>
            Fait à Abidjan, le <?= date('d/m/Y') ?><br>
            <span class="mention">Signature du responsable</span>
        </div>
        <div style="text-align: right;">
            <span class="mention">Cachet de l'établissement</span>
        </div>
    </div>
</body>

</html>
<?php endif; ?>