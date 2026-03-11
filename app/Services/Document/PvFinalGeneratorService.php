<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Utils\PlanningDataUtils;
use DateTimeImmutable;
use TCPDF;

final class PvFinalGeneratorService
{
    /** @var array<int, int> */
    private const COULEUR_ANNEXE1 = [70, 130, 180];

    /** @var array<int, int> */
    private const COULEUR_ANNEXE2 = [107, 142, 35];

    /** @var array<int, int> */
    private const COULEUR_ANNEXE3 = [139, 0, 0];

    /** @var array<int, array{label: string, bareme: float, keywords: array<int, string>}> */
    private const CRITERES_ANNEXE1 = [
        [
            'label' => '1. Expose',
            'bareme' => 4.0,
            'keywords' => ['expose'],
        ],
        [
            'label' => '2. Reponses aux questions posees',
            'bareme' => 5.0,
            'keywords' => ['question', 'reponse'],
        ],
        [
            'label' => '3. Presentation du memoire',
            'bareme' => 2.0,
            'keywords' => ['presentation'],
        ],
        [
            'label' => '4. Contenu du memoire',
            'bareme' => 4.0,
            'keywords' => ['contenu'],
        ],
        [
            'label' => '5. Resolution du probleme',
            'bareme' => 5.0,
            'keywords' => ['resolution', 'probleme'],
        ],
    ];

    public function __construct(
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly PlanningDataUtils $dataUtils
    ) {
    }

    /**
     * @return array{success: bool, reference?: string, path?: string, pages?: int, error?: string}
     */
    public function generate(string $soutenanceId, int $userId): array
    {
        $soutenance = $this->dataUtils->getSoutenanceWithFullDetails($soutenanceId);
        if ($soutenance === null) {
            return ['success' => false, 'error' => 'Soutenance introuvable'];
        }

        $pv = $this->dataUtils->getPvDataForSoutenance($soutenanceId);
        if ($pv === null) {
            return ['success' => false, 'error' => 'PV de deliberation introuvable'];
        }

        $decision = $this->formaterDecision((string) ($pv['decision_jury'] ?? ''));
        if ($decision === '-') {
            return ['success' => false, 'error' => 'Decision du jury indisponible'];
        }

        $matricule = (string) ($soutenance['matricule_etudiant'] ?? '');
        $annexe1Rows = $this->buildAnnexe1Rows($matricule);
        $annexe1Total = 0.0;
        foreach ($annexe1Rows as $row) {
            $annexe1Total += (float) $row['note'];
        }
        $annexe1Total = round($annexe1Total, 2);


        $moyennes = $this->dataUtils->getMoyennesAcademiques($matricule);
        $moyenneM1 = $this->resolveScore(
            $this->toNullableFloat($pv['moyenne_m1'] ?? null),
            $moyennes['moyenne_M1'] ?? null
        );
        $moyenneS1M2 = $this->resolveScore(
            $this->toNullableFloat($pv['moyenne_s1_m2'] ?? null),
            $moyennes['moyenne_M2'] ?? null
        );

        $noteFinaleSource = $this->toNullableFloat($pv['note_finale'] ?? null);
        $noteMemoire = $this->resolveScore(
            $this->toNullableFloat($pv['note_memoire'] ?? null),
            $annexe1Total > 0.0 ? $annexe1Total : null
        );

        if (
            $moyenneS1M2 <= 0.0
            && $noteFinaleSource !== null
            && $noteFinaleSource > 0.0
            && $moyenneM1 > 0.0
            && $noteMemoire > 0.0
        ) {
            $inferred = (($noteFinaleSource * 8.0) - ($moyenneM1 * 2.0) - ($noteMemoire * 3.0)) / 3.0;
            if ($inferred >= 0.0 && $inferred <= 20.0) {
                $moyenneS1M2 = round($inferred, 2);
            }
        }

        $moyenneGenerale = $moyenneM1;

        $annexe2Rows = [
            [
                'label' => '1. Moyenne Generale Master1',
                'note' => $moyenneM1,
                'coeff' => 2,
                'moyenne_coeff' => $this->calculerMoyenneCoeff($moyenneM1, 2),
            ],
            [
                'label' => '2. Moyenne Generale Semestre 1 Master2',
                'note' => $moyenneS1M2,
                'coeff' => 3,
                'moyenne_coeff' => $this->calculerMoyenneCoeff($moyenneS1M2, 3),
            ],
            [
                'label' => '3. Memoire de fin de cycle',
                'note' => $noteMemoire,
                'coeff' => 3,
                'moyenne_coeff' => $this->calculerMoyenneCoeff($noteMemoire, 3),
            ],
        ];

        $annexe2Total = 0.0;
        foreach ($annexe2Rows as $row) {
            $annexe2Total += (float) $row['moyenne_coeff'];
        }
        $annexe2Total = round($annexe2Total, 2);

        $annexe3Rows = [
            [
                'label' => '1. Moyenne Generale',
                'note' => $moyenneGenerale,
                'coeff' => 3,
                'moyenne_coeff' => $this->calculerMoyenneCoeff($moyenneGenerale, 3),
            ],
            [
                'label' => '2. Memoire',
                'note' => $noteMemoire,
                'coeff' => 3,
                'moyenne_coeff' => $this->calculerMoyenneCoeff($noteMemoire, 3),
            ],
        ];

        $annexe3Total = 0.0;
        foreach ($annexe3Rows as $row) {
            $annexe3Total += (float) $row['moyenne_coeff'];
        }
        $annexe3Total = round($annexe3Total, 2);

        $noteFinale = $this->resolveScore(
            $noteFinaleSource,
            $annexe2Total > 0.0 ? round($annexe2Total / 8, 2) : null
        );
        if ($noteFinale <= 0.0 && $annexe3Total > 0.0) {
            $noteFinale = round($annexe3Total / 6, 2);
        }

        $members = $this->extractJuryMembers($this->dataUtils->getJuryMembersForSoutenance($soutenanceId));
        $reference = $this->generateReference();
        $numeroPv = trim((string) ($pv['numero_pv'] ?? ''));
        if ($numeroPv === '') {
            $numeroPv = $reference;
        }

        $data = [
            'reference' => $reference,
            'numero_pv' => $numeroPv,
            'date_soutenance' => $this->formaterDate((string) ($soutenance['date_soutenance'] ?? '')),
            'date_deliberation' => $this->formaterDate((string) ($pv['date_finalisation'] ?? $pv['date_creation'] ?? $soutenance['date_soutenance'] ?? '')),
            'niveau' => $this->truncateText((string) ($soutenance['libelle_niveau'] ?? 'Master 2'), 40),
            'classe' => $this->truncateText((string) ($soutenance['code_filiere'] ?? $soutenance['libelle_filiere'] ?? 'MIAGE-GI'), 40),
            'theme' => $this->truncateText((string) ($soutenance['theme_soutenance'] ?? '-'), 150),
            'nom_etudiant' => $this->truncateText(trim((string) (($soutenance['nom_etudiant'] ?? '') . ' ' . ($soutenance['prenom_etudiant'] ?? ''))), 70),
            'matricule' => (string) ($soutenance['matricule_etudiant'] ?? '-'),
            'annexe1_rows' => $annexe1Rows,
            'annexe1_total' => $annexe1Total,
            'annexe2_rows' => $annexe2Rows,
            'annexe2_total' => $annexe2Total,
            'annexe3_rows' => $annexe3Rows,
            'annexe3_total' => $annexe3Total,
            'note_finale' => $noteFinale,
            'decision' => $decision,
            'mention' => (string) ($pv['libelle_mention'] ?? '-'),
            'jury_members' => $members,
        ];

        try {
            $pdf = $this->pdfGenerator->createDocument('P', 'A4', 'PV Final de Soutenance', 'CheckMaster UFRMI');
            $pdf->SetAutoPageBreak(false, 0);

            $this->addPageAnnexe1($pdf, $data);
            $this->addPageAnnexe2($pdf, $data);
            $this->addPageAnnexe3($pdf, $data);

            $pageCount = $pdf->getNumPages();
            if ($pageCount !== 3) {
                return [
                    'success' => false,
                    'error' => 'Le PV final doit contenir exactement 3 pages (genere: ' . $pageCount . ').',
                ];
            }

            $this->applyFooters($pdf, 3);

            $filename = 'PV_Final_' . $this->sanitizeFilename($data['matricule']) . '_' . (new DateTimeImmutable())->format('Ymd_His');
            $path = $this->pdfGenerator->save($pdf, 'pv_finaux', $filename);
            $fileSize = file_exists($path) ? filesize($path) : 0;

            $this->dataUtils->saveDocumentRecord([
                'reference_document' => $reference,
                'type_document' => 'pv_final',
                'nom_fichier' => basename($path),
                'chemin_fichier' => $path,
                'taille_fichier' => $fileSize !== false ? (int) $fileSize : 0,
                'mime_type' => 'application/pdf',
                'metadata' => json_encode([
                    'id_pv' => (int) ($pv['id_pv'] ?? 0),
                    'id_soutenance' => $soutenanceId,
                    'matricule_etudiant' => $data['matricule'],
                    'decision_jury' => $data['decision'],
                    'total_pages' => 3,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'id_utilisateur_generation' => $userId,
            ]);

            // Not saving to pvRepo since pv_deliberation doesn't exist

            return [
                'success' => true,
                'reference' => $reference,
                'path' => $path,
                'pages' => 3,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Erreur generation PV final: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function addPageAnnexe1(TCPDF $pdf, array $data): void
    {
        $pdf->AddPage();
        $this->addEnteteInstitutionnel($pdf, 1, self::COULEUR_ANNEXE1, (string) ($data['numero_pv'] ?? ''));

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(35, 6, 'NIVEAU :', 0, 0);
        $pdf->Cell(55, 6, (string) $data['niveau'], 0, 0);
        $pdf->Cell(25, 6, 'CLASSE :', 0, 0);
        $pdf->Cell(30, 6, (string) $data['classe'], 0, 0);
        $pdf->Cell(15, 6, 'DATE :', 0, 0);
        $pdf->Cell(0, 6, (string) $data['date_soutenance'], 0, 1);

        $pdf->Ln(1);
        $pdf->Cell(18, 6, 'THEME :', 0, 0);
        $pdf->Cell(0, 6, (string) $data['theme'], 0, 1);

        $pdf->Cell(70, 6, 'NOM ET PRENOMS DE L\'IMPETRANT :', 0, 0);
        $pdf->Cell(0, 6, (string) $data['nom_etudiant'], 0, 1);

        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(235, 235, 235);
        $pdf->Cell(105, 7, 'POINTS D\'APPRECIATION', 1, 0, 'C', true);
        $pdf->Cell(45, 7, 'NOTE OBTENUE', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'BAREME', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 9);
        foreach ($data['annexe1_rows'] as $row) {
            $pdf->Cell(105, 7, (string) $row['label'], 1, 0, 'L');
            $pdf->Cell(45, 7, $this->formatNumber((float) $row['note']), 1, 0, 'C');
            $pdf->Cell(30, 7, $this->formatNumber((float) $row['bareme']), 1, 1, 'C');
        }

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(105, 7, 'TOTAL', 1, 0, 'L');
        $pdf->Cell(45, 7, $this->formatNumber((float) $data['annexe1_total']) . ' /20', 1, 0, 'C');
        $pdf->Cell(30, 7, '20', 1, 1, 'C');

        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(45, 7, 'DECISION DU JURY :', 0, 0);
        $pdf->Cell(0, 7, (string) $data['decision'], 0, 1);

        $pdf->Ln(3);
        $juryMembers = $this->normalizeJuryMembers($data['jury_members'] ?? []);
        $this->renderJurySignatures($pdf, $juryMembers);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function addPageAnnexe2(TCPDF $pdf, array $data): void
    {
        $pdf->AddPage();
        $this->addEnteteInstitutionnel($pdf, 2, self::COULEUR_ANNEXE2, (string) ($data['numero_pv'] ?? ''));

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(15, 6, 'DATE:', 0, 0);
        $pdf->Cell(60, 6, (string) $data['date_deliberation'], 0, 0);
        $pdf->Cell(65, 6, 'NOM ET PRENOMS DE L\'IMPETRANT :', 0, 0);
        $pdf->Cell(0, 6, (string) $data['nom_etudiant'], 0, 1);

        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(235, 235, 235);
        $pdf->Cell(90, 7, 'POINTS D\'APPRECIATION', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'NOTE OBTENUE', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Coeff.', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Moyenne Coeff.', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 9);
        foreach ($data['annexe2_rows'] as $row) {
            $pdf->Cell(90, 7, (string) $row['label'], 1, 0, 'L');
            $pdf->Cell(30, 7, $this->formatNumber((float) $row['note']), 1, 0, 'C');
            $pdf->Cell(20, 7, (string) $row['coeff'], 1, 0, 'C');
            $pdf->Cell(40, 7, $this->formatNumber((float) $row['moyenne_coeff']), 1, 1, 'C');
        }

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(90, 7, 'TOTAL', 1, 0, 'L');
        $pdf->Cell(30, 7, '', 1, 0, 'C');
        $pdf->Cell(20, 7, '8', 1, 0, 'C');
        $pdf->Cell(40, 7, $this->formatNumber((float) $data['annexe2_total']) . ' /160', 1, 1, 'C');

        $pdf->Cell(90, 7, '', 1, 0, 'L');
        $pdf->Cell(30, 7, 'Moyenne', 1, 0, 'C');
        $pdf->Cell(20, 7, '', 1, 0, 'C');
        $pdf->Cell(40, 7, $this->formatNumber((float) $data['note_finale']) . ' /20', 1, 1, 'C');

        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(45, 7, 'DECISION DU JURY :', 0, 0);
        $pdf->Cell(0, 7, (string) $data['decision'], 0, 1);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(20, 7, 'Mention :', 0, 0);
        $pdf->Cell(0, 7, (string) $data['mention'], 0, 1);

        $pdf->Ln(3);
        $juryMembers = $this->normalizeJuryMembers($data['jury_members'] ?? []);
        $this->renderJurySignatures($pdf, $juryMembers);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function addPageAnnexe3(TCPDF $pdf, array $data): void
    {
        $pdf->AddPage();
        $this->addEnteteInstitutionnel($pdf, 3, self::COULEUR_ANNEXE3, (string) ($data['numero_pv'] ?? ''));

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(15, 6, 'DATE:', 0, 0);
        $pdf->Cell(60, 6, (string) $data['date_deliberation'], 0, 0);
        $pdf->Cell(65, 6, 'NOM ET PRENOMS DE L\'IMPETRANT :', 0, 0);
        $pdf->Cell(0, 6, (string) $data['nom_etudiant'], 0, 1);

        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetFillColor(235, 235, 235);
        $pdf->Cell(90, 7, 'POINTS D\'APPRECIATION', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'NOTE OBTENUE', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Coeff.', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Moyenne Coeff.', 1, 1, 'C', true);

        $pdf->SetFont('helvetica', '', 9);
        foreach ($data['annexe3_rows'] as $row) {
            $pdf->Cell(90, 7, (string) $row['label'], 1, 0, 'L');
            $pdf->Cell(30, 7, $this->formatNumber((float) $row['note']), 1, 0, 'C');
            $pdf->Cell(20, 7, (string) $row['coeff'], 1, 0, 'C');
            $pdf->Cell(40, 7, $this->formatNumber((float) $row['moyenne_coeff']), 1, 1, 'C');
        }

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(90, 7, 'TOTAL', 1, 0, 'L');
        $pdf->Cell(30, 7, '', 1, 0, 'C');
        $pdf->Cell(20, 7, '6', 1, 0, 'C');
        $pdf->Cell(40, 7, $this->formatNumber((float) $data['annexe3_total']) . ' /120', 1, 1, 'C');

        $pdf->Cell(90, 7, '', 1, 0, 'L');
        $pdf->Cell(30, 7, 'Moyenne', 1, 0, 'C');
        $pdf->Cell(20, 7, '', 1, 0, 'C');
        $pdf->Cell(40, 7, $this->formatNumber((float) $data['note_finale']) . ' /20', 1, 1, 'C');

        $pdf->Ln(4);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(45, 7, 'DECISION DU JURY :', 0, 0);
        $pdf->Cell(0, 7, (string) $data['decision'], 0, 1);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(20, 7, 'Mention :', 0, 0);
        $pdf->Cell(0, 7, (string) $data['mention'], 0, 1);

        $pdf->Ln(3);
        $juryMembers = $this->normalizeJuryMembers($data['jury_members'] ?? []);
        $this->renderJurySignatures($pdf, $juryMembers);
    }

    /**
     * @param array<int, int> $colors
     */
    public function addEnteteInstitutionnel(TCPDF $pdf, int $annexeNum, array $colors, string $numeroPv = ''): void
    {
        $numeroPv = trim($numeroPv);
        if ($numeroPv === '') {
            $numeroPv = date('Y') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $title = match ($annexeNum) {
            1 => 'SOUTENANCE DE MEMOIRE',
            2 => 'P.V. JURY N°' . $numeroPv,
            3 => 'P.V. JURY FC N°' . $numeroPv,
            default => 'P.V. FINAL',
        };

        $this->pdfGenerator->addHeader($pdf, 'ANNEXE ' . $annexeNum . ' / FILIERES / PROFESSIONNALISEES', $title);

        $pdf->SetFillColor($colors[0], $colors[1], $colors[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 7, 'UFR de Mathematiques et Informatique Filieres Professionnalisees MIAGE-GI', 0, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);
    }

    public function calculerMoyenneCoeff(float $note, int $coeff): float
    {
        return round($note * $coeff, 2);
    }

    public function formaterDecision(string $decision): string
    {
        $normalized = strtolower(trim($decision));

        return match ($normalized) {
            'admis' => 'ADMIS',
            'ajourne', 'ajourne(e)', 'ajourné', 'ajourné(e)' => 'AJOURNE',
            'refuse', 'refusé' => 'REFUSE',
            default => '-',
        };
    }

    /**
     * @return array<int, array{label: string, note: float, bareme: float}>
     */
    private function buildAnnexe1Rows(string $numEtudiant): array
    {
        $notes = $this->dataUtils->getNotesSoutenance($numEtudiant);
        $usedIndexes = [];
        $nextFallback = 0;
        $rows = [];
        foreach (self::CRITERES_ANNEXE1 as $critere) {
            $matchIndex = $this->findMatchingNoteIndex($notes, $critere['keywords'], $usedIndexes);
            if ($matchIndex === null) {
                while (isset($notes[$nextFallback]) && in_array($nextFallback, $usedIndexes, true)) {
                    $nextFallback++;
                }
                if (isset($notes[$nextFallback])) {
                    $matchIndex = $nextFallback;
                }
            }

            $normalizedNote = 0.0;
            if ($matchIndex !== null && isset($notes[$matchIndex])) {
                $usedIndexes[] = $matchIndex;
                $noteRow = $notes[$matchIndex];
                $rawNote = (float) ($noteRow['note'] ?? 0.0);
                $rawBareme = (float) ($noteRow['bareme'] ?? $critere['bareme']);

                if ($rawBareme > 0.0) {
                    $normalizedNote = ($rawNote / $rawBareme) * $critere['bareme'];
                } else {
                    $normalizedNote = $rawNote;
                }

                if ($normalizedNote < 0.0) {
                    $normalizedNote = 0.0;
                }
                if ($normalizedNote > $critere['bareme']) {
                    $normalizedNote = $critere['bareme'];
                }
            }

            $rows[] = [
                'label' => $critere['label'],
                'note' => round($normalizedNote, 2),
                'bareme' => $critere['bareme'],
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $notes
     * @param array<int, string> $keywords
     * @param array<int, int> $usedIndexes
     */
    private function findMatchingNoteIndex(array $notes, array $keywords, array $usedIndexes): ?int
    {
        foreach ($notes as $index => $note) {
            if (in_array($index, $usedIndexes, true)) {
                continue;
            }

            $label = strtolower((string) ($note['libelle_critere'] ?? ''));
            $code = strtolower((string) ($note['code_critere'] ?? ''));
            $haystack = $label . ' ' . $code;

            foreach ($keywords as $keyword) {
                if (str_contains($haystack, strtolower($keyword))) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * @param mixed $juryRaw
     * @return array{president: string, examinateur: string, directeur_memoire: string, encadreur_pedagogique: string, maitre_stage: string}
     */
    private function extractJuryMembers(mixed $juryRaw): array
    {
        $members = [
            'president' => '-',
            'examinateur' => '-',
            'directeur_memoire' => '-',
            'encadreur_pedagogique' => '-',
            'maitre_stage' => '-',
        ];

        if (!is_array($juryRaw)) {
            return $members;
        }

        $fallback = [];

        foreach ($juryRaw as $membre) {
            if (!is_array($membre)) {
                continue;
            }

            $nom = trim((string) (($membre['nom_utilisateur'] ?? '') . ' ' . ($membre['prenom_utilisateur'] ?? $membre['prenom'] ?? '')));
            if ($nom === '') {
                continue;
            }

            $role = strtolower((string) ($membre['role_jury'] ?? ''));
            $fonction = strtolower((string) ($membre['fonction'] ?? ''));
            $haystack = $role . ' ' . $fonction;

            $target = null;
            if (str_contains($haystack, 'president')) {
                $target = 'president';
            } elseif (str_contains($haystack, 'examinateur')) {
                $target = 'examinateur';
            } elseif (str_contains($haystack, 'directeur')) {
                $target = 'directeur_memoire';
            } elseif (str_contains($haystack, 'encadreur')) {
                $target = 'encadreur_pedagogique';
            } elseif (str_contains($haystack, 'maitre') || str_contains($haystack, 'stage')) {
                $target = 'maitre_stage';
            }

            if ($target !== null && $members[$target] === '-') {
                $members[$target] = $nom;
                continue;
            }

            $fallback[] = $nom;
        }

        $roleOrder = [
            'president',
            'examinateur',
            'directeur_memoire',
            'encadreur_pedagogique',
            'maitre_stage',
        ];
        $fallbackIndex = 0;
        foreach ($roleOrder as $roleKey) {
            if ($members[$roleKey] !== '-') {
                continue;
            }

            if (!isset($fallback[$fallbackIndex])) {
                continue;
            }

            $members[$roleKey] = $fallback[$fallbackIndex];
            $fallbackIndex++;
        }

        return $members;
    }

    /**
     * @param array{president: string, examinateur: string, directeur_memoire: string, encadreur_pedagogique: string, maitre_stage: string} $juryMembers
     */
    private function renderJurySignatures(TCPDF $pdf, array $juryMembers): void
    {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 6, 'NOMS ET SIGNATURES DES MEMBRES DU JURY', 0, 1);

        $pdf->SetFont('helvetica', '', 9);
        $this->renderJuryLine($pdf, 'PRESIDENT', $juryMembers['president']);
        $this->renderJuryLine($pdf, 'Examinateur', $juryMembers['examinateur']);
        $this->renderJuryLine($pdf, 'Directeur de memoire', $juryMembers['directeur_memoire']);
        $this->renderJuryLine($pdf, 'Encadreur pedagogique', $juryMembers['encadreur_pedagogique']);
        $this->renderJuryLine($pdf, 'Maitre de Stage', $juryMembers['maitre_stage']);
    }

    private function renderJuryLine(TCPDF $pdf, string $label, string $name): void
    {
        $pdf->Cell(50, 7, $label . ' :', 0, 0);
        $pdf->Cell(80, 7, $this->truncateText($name, 40), 0, 0);
        $pdf->Cell(0, 7, '____________________', 0, 1);
    }

    /**
     * @param mixed $juryRaw
     * @return array{president: string, examinateur: string, directeur_memoire: string, encadreur_pedagogique: string, maitre_stage: string}
     */
    private function normalizeJuryMembers(mixed $juryRaw): array
    {
        if (!is_array($juryRaw)) {
            return $this->extractJuryMembers([]);
        }

        $expectedKeys = ['president', 'examinateur', 'directeur_memoire', 'encadreur_pedagogique', 'maitre_stage'];
        $hasExpectedShape = true;
        foreach ($expectedKeys as $key) {
            if (!array_key_exists($key, $juryRaw) || !is_string($juryRaw[$key])) {
                $hasExpectedShape = false;
                break;
            }
        }

        if ($hasExpectedShape) {
            /** @var array{president: string, examinateur: string, directeur_memoire: string, encadreur_pedagogique: string, maitre_stage: string} $juryRaw */
            return $juryRaw;
        }

        return $this->extractJuryMembers($juryRaw);
    }

    private function applyFooters(TCPDF $pdf, int $totalPages): void
    {
        for ($page = 1; $page <= $totalPages; $page++) {
            $pdf->setPage($page);
            $pdf->SetY(-15);
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->Line(15, $pdf->GetY(), $pdf->getPageWidth() - 15, $pdf->GetY());
            $pdf->Ln(2);
            $pdf->Cell(0, 5, 'Page ' . $page . ' sur ' . $totalPages, 0, 0, 'C');
        }
    }

    private function toNullableFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }

        if (!is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function resolveScore(?float $primary, ?float $fallback): float
    {
        if ($primary !== null && $primary > 0.0) {
            return round($primary, 2);
        }

        if ($fallback !== null && $fallback > 0.0) {
            return round($fallback, 2);
        }

        if ($primary !== null) {
            return round($primary, 2);
        }

        if ($fallback !== null) {
            return round($fallback, 2);
        }

        return 0.0;
    }

    private function generateReference(): string
    {
        $raw = $this->dataUtils->generateReference('pv_final');
        if (preg_match('/^[^-]+-(\d{4})-(\d+)$/', $raw, $matches) === 1) {
            return 'PVF-' . $matches[1] . '-' . str_pad($matches[2], 5, '0', STR_PAD_LEFT);
        }

        return 'PVF-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    private function formaterDate(string $date): string
    {
        if ($date === '') {
            return '-';
        }

        try {
            return (new DateTimeImmutable($date))->format('d/m/Y');
        } catch (\Throwable) {
            return '-';
        }
    }

    private function formatNumber(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function truncateText(string $value, int $maxLength): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        if ($value === '' || $maxLength <= 0) {
            return '-';
        }

        if (strlen($value) <= $maxLength) {
            return $value;
        }

        return rtrim(substr($value, 0, $maxLength - 3)) . '...';
    }

    private function sanitizeFilename(string $value): string
    {
        $clean = preg_replace('/[^A-Za-z0-9_-]+/', '_', $value);
        $clean = is_string($clean) ? trim($clean, '_') : '';

        return $clean !== '' ? $clean : 'etudiant';
    }
}
