<?php if (!empty($GLOBALS['selectedStudent'])): ?>
    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">
        <title>Relevé de notes</title>
        <style>
            /* Reset minimal pour impression */
            html,
            body {
                margin: 0;
                padding: 0;
                color: #000;
                font-family: Arial, Helvetica, sans-serif;
                font-size: 12px
            }

            .container {
                padding: 14px;
                max-width: 900px;
                margin: 0 auto
            }

            /* Header layout compatible Dompdf : table */
            .header-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 8px
            }

            .header-table td {
                vertical-align: middle
            }

            .logo {
                height: 72px
            }

            .title-block {
                text-align: center
            }

            .title-main {
                font-size: 18px;
                font-weight: 700;
                margin: 2px 0
            }

            .title-sub {
                font-size: 12px;
                margin: 2px 0
            }

            .faculty {
                font-size: 13px;
                font-weight: 700;
                text-align: right
            }

            .student-info {
                font-size: 12px;
                margin-bottom: 8px
            }

            .student-info strong {
                display: inline-block;
                width: 140px
            }

            .table-notes {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 10px
            }

            .table-notes th,
            .table-notes td {
                border: 1px solid #000;
                padding: 6px 8px;
                text-align: left;
                font-size: 12px
            }

            .table-notes th {
                background: #f7f7f7
            }

            .table-notes .center {
                text-align: center
            }

            .section-title {
                font-weight: 700;
                margin: 12px 0 6px
            }

            .total-row {
                font-weight: 700;
                background: #f7f7f7
            }

            .recap {
                margin-top: 10px;
                font-size: 12px
            }

            .recap strong {
                display: inline-block;
                width: 180px
            }

            /* Footer */
            .footer {
                margin-top: 30px;
                font-size: 11px
            }

            .footer .left {
                float: left
            }

            .footer .right {
                float: right;
                text-align: right
            }

            .clearfix {
                clear: both
            }

            /* Print adjustments */
            @media print {
                body {
                    padding: 0
                }

                @page {
                    size: A4 portrait;
                    margin: 8mm
                }

                .container {
                    padding: 6mm;
                    max-width: none
                }
            }
        </style>
    </head>

    <body>
        <div class="container">
            <table class="header-table">
                <tr>
                    <td style="width:20%;text-align:left">
                        <?php
                        // Logo left
                        $logoLeft = __DIR__ . '/../../public/image/logo_ufhb.png';
                        $logoLeftUrl = '/image/logo_ufhb.png';
                        if (file_exists($logoLeft) && is_readable($logoLeft)) {
                            $type = mime_content_type($logoLeft) ?: 'image/png';
                            $data = base64_encode(file_get_contents($logoLeft));
                            echo '<img class="logo" src="data:' . $type . ';base64,' . $data . '" alt="logo">';
                        } else {
                            echo '<img class="logo" src="' . ($baseUrl ?? '') . $logoLeftUrl . '" alt="logo">';
                        }
                        ?>
                    </td>
                    <td style="width:60%" class="title-block">
                        <div class="title-sub">REPUBLIQUE DE CÔTE D'IVOIRE</div>
                        <div class="title-sub">MINISTERE DE L'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE</div>
                        <div class="title-main">RELEVE DE NOTES</div>
                        <div class="title-sub">Année universitaire :
                            <?= htmlspecialchars($GLOBALS['annee_universitaire'] ?? '2025-2026') ?>
                        </div>
                    </td>
                    <td style="width:20%;text-align:right">
                        <?php
                        // Logo right
                        $logoRight = __DIR__ . '/../../public/image/logo_mi_sbg.png';
                        $logoRightUrl = '/image/logo_mi_sbg.png';
                        if (file_exists($logoRight) && is_readable($logoRight)) {
                            $type = mime_content_type($logoRight) ?: 'image/png';
                            $data = base64_encode(file_get_contents($logoRight));
                            echo '<img class="logo" src="data:' . $type . ';base64,' . $data . '" alt="logo">';
                        } else {
                            echo '<img class="logo" src="' . ($baseUrl ?? '') . $logoRightUrl . '" alt="logo">';
                        }
                        ?>
                    </td>
                </tr>
            </table>

            <div style="margin-bottom:10px">
                <div class="student-info">
                    <strong>NOM :</strong> <?= htmlspecialchars($GLOBALS['selectedStudent']->nom_etu) ?>
                    <strong style="margin-left:18px">PRENOMS :</strong>
                    <?= htmlspecialchars($GLOBALS['selectedStudent']->prenom_etu) ?><br>
                    <strong>DATE DE NAISSANCE :</strong>
                    <?= htmlspecialchars($GLOBALS['selectedStudent']->date_naiss_etu) ?>
                    <strong style="margin-left:18px">PARCOURS :</strong> MIAGE<br>
                    <strong>NIVEAU :</strong> <?= htmlspecialchars($GLOBALS['niveau'] ?? '') ?>
                    <strong style="margin-left:18px">N° CARTE ETUDIANT :</strong>
                    <?= htmlspecialchars($GLOBALS['selectedStudent']->num_carte_etud) ?>
                </div>
                <div class="faculty">FILIERES PROFESSIONNALISEES (GI-MIAGE) —
                    <?= htmlspecialchars($GLOBALS['annee_universitaire'] ?? '2023-2024') ?>
                </div>
                <div class="clearfix"></div>
            </div>
            <?php
            // Regrouper les notes par semestre et par type (majeur/mineur)
            $semestres = [];
            foreach ($GLOBALS['studentGrades'] as $grade) {
                $lib_semestre = is_object($grade) ? ($grade->lib_semestre ?? 'Semestre ?') : ($grade['lib_semestre'] ?? 'Semestre ?');
                $credit = is_object($grade) ? ($grade->credit ?? 0) : ($grade['credit'] ?? 0);
                $sem = $lib_semestre;
                $type = ($credit > 3) ? 'majeures' : 'mineures';
                $semestres[$sem][$type][] = $grade;
            }
            $semIndex = 1;
            foreach ($semestres as $sem => $types):
                $totalCredits = 0;
                $totalMoy = 0;
                $totalCoef = 0;
                ?>
                <div class="section-title">Semestre <?= $semIndex ?> — <?= htmlspecialchars($sem) ?></div>
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
                    $sumMaj = $credMaj = $moyMaj = 0;
                    if (!empty($types['majeures'])):
                        foreach ($types['majeures'] as $grade) {
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
                            echo '<td class="center">' . htmlspecialchars($moyenne ? number_format($moyenne, 2) : '-') . '</td>';
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
                        <th>UE</th>
                        <th>Coef</th>
                        <th>Moyenne /20</th>
                        <th>Crédits</th>
                        <th>Session</th>
                    </tr>
                    <?php
                    $sumMin = $credMin = $moyMin = 0;
                    if (!empty($types['mineures'])):
                        foreach ($types['mineures'] as $grade) {
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
                            echo '<td class="center">' . htmlspecialchars($moyenne ? number_format($moyenne, 2) : '-') . '</td>';
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

            <div class="recap" style="margin-top:16px">
                <strong>RESULTAT GENERAL</strong><br>
                Un Semestre n'est validé que si la moyenne des UE majeures et celle des UE mineures sont toutes >=10.<br>
                La note plancher de chaque UE est de 05/20.<br>
                L'étudiant n'est déclaré admis que s'il a obtenu 30 Crédits par semestre.<br>
                <br>
                <strong>Résultat (Délibération du jury) :</strong> Admis<br>
                <strong>Moyenne générale :</strong> <?php
                $totalNotes = 0;
                $totalCredits = 0;
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
                <div class="left">
                    Fait à Abidjan, le <?= date('d/m/Y') ?><br>
                    <span class="mention">Signature du responsable</span>
                </div>
                <div class="right">
                    <span class="mention">Cachet de l'établissement</span>
                </div>
                <div class="clearfix"></div>
            </div>
        </div>
    </body>

    </html>
<?php endif; ?>