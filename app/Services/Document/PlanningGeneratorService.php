<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Support\Database;
use App\Utils\PlanningDataUtils;
use DateTimeImmutable;
use Throwable;

require_once __DIR__ . '/../../utils/EmailService.php';

/**
 * Service de génération de planning des soutenances PDF.
 *
 * Génère un planning au format A4 LANDSCAPE conforme au modèle MIAGE-GI:
 * - Composition complète du jury
 * - Entreprise d'accueil
 * - Marquage visuel des salles (colonnes dynamiques)
 */
final class PlanningGeneratorService
{
    private const TYPE_DOCUMENT = 'PLN';
    private const SUBDIR = 'planning';
    private const ROOM_COLOR_PALETTE = [
        '#d9edf7',
        '#dff0d8',
        '#fcf8e3',
        '#f2dede',
        '#e8ddff',
        '#dff4ff',
        '#ffe6f0',
        '#f7f7d9',
    ];

    public function __construct(
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly PlanningDataUtils $planningDataUtils,
        private readonly Database $db,
        private readonly ?object $notificationService = null
    ) {
    }

    /**
     * Génère un planning des soutenances PDF selon des filtres de session/période.
     *
     * @param int|null $sessionId Session soutenance ID (null = toutes les soutenances programmées)
     * @param string|null $dateFrom Date de début (YYYY-MM-DD format)
     * @param string|null $dateTo Date de fin (YYYY-MM-DD format)
     * @param int $userId ID de l'utilisateur générant le document
     * @param bool $skipNotifications Si true, aucun email n'est envoyé aux étudiants (mode aperçu)
     * @return array{success: bool, reference?: string, path?: string, filename?: string, size?: int|null, error?: string, error_code?: string}
     */
    public function generate(?int $sessionId, ?string $dateFrom, ?string $dateTo, int $userId, bool $skipNotifications = false): array
    {
        try {
            $soutenances = $this->planningDataUtils->getSoutenancesForPlanning($sessionId, $dateFrom, $dateTo);
            return $this->generateFromSoutenances($soutenances, $userId, [
                'id_session_soutenance' => $sessionId,
                'date_debut' => $dateFrom,
                'date_fin' => $dateTo,
                'skip_notifications' => $skipNotifications,
            ]);
        } catch (Throwable $e) {
            $this->logFailure('generate', $e, [
                'session_id' => $sessionId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'user_id' => $userId,
            ]);
            return [
                'success' => false,
                'error' => 'Erreur lors de la génération du planning.',
                'error_code' => 'generation_failed',
            ];
        }
    }

    /**
     * Génère un planning PDF à partir d'une sélection explicite de soutenances.
     *
     * @param array<int, string> $selectedIds
     * @param int $userId
     * @return array{success: bool, reference?: string, path?: string, error?: string, error_code?: string, missing_ids?: array<int, string>}
     */
    public function generateFromSelectedSoutenances(array $selectedIds, int $userId): array
    {
        $normalizedIds = array_values(array_unique(array_filter(array_map(
            static fn($id): string => trim((string) $id),
            $selectedIds
        ), static fn(string $id): bool => $id !== '')));

        if (empty($normalizedIds)) {
            return [
                'success' => false,
                'error' => 'Aucune soutenance sélectionnée.',
                'error_code' => 'no_selection',
            ];
        }

        try {
            $soutenances = $this->planningDataUtils->getSoutenancesByIds($normalizedIds);
            if (empty($soutenances)) {
                return [
                    'success' => false,
                    'error' => 'Les soutenances sélectionnées n’existent plus. Veuillez actualiser la page et recommencer.',
                    'error_code' => 'missing_soutenances',
                    'missing_ids' => $normalizedIds,
                ];
            }

            $found = [];
            foreach ($soutenances as $row) {
                $id = trim((string) ($row['num_soutenance'] ?? ''));
                if ($id !== '') {
                    $found[$id] = true;
                }
            }
            $missingIds = array_values(array_filter($normalizedIds, static fn(string $id): bool => !isset($found[$id])));
            if (!empty($missingIds)) {
                return [
                    'success' => false,
                    'error' => 'Certaines soutenances sélectionnées n’existent plus. Veuillez actualiser la page et recommencer.',
                    'error_code' => 'missing_soutenances',
                    'missing_ids' => $missingIds,
                ];
            }

            return $this->generateFromSoutenances($soutenances, $userId, [
                'selected_ids' => $normalizedIds,
                'selection_count' => count($normalizedIds),
            ]);
        } catch (Throwable $e) {
            $this->logFailure('generateFromSelectedSoutenances', $e, [
                'selected_ids' => $normalizedIds,
                'user_id' => $userId,
            ]);
            return [
                'success' => false,
                'error' => 'Erreur lors de la génération du planning.',
                'error_code' => 'generation_failed',
            ];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $soutenances
     * @param array<string, mixed> $metadataContext
     * @return array{success: bool, reference?: string, path?: string, error?: string, error_code?: string}
     */
    private function generateFromSoutenances(array $soutenances, int $userId, array $metadataContext): array
    {
        $pdo = $this->db->pdo();
        $skipNotifications = (bool) ($metadataContext['skip_notifications'] ?? false);

        try {
            if (count($soutenances) === 0) {
                return [
                    'success' => false,
                    'error' => 'Aucune soutenance trouvée pour les critères spécifiés.',
                    'error_code' => 'no_selection',
                ];
            }

            $invalidSoutenances = $this->collectInvalidScheduledSoutenances($soutenances);
            if (!empty($invalidSoutenances)) {
                $this->logValidationFailure('generateFromSoutenances', $invalidSoutenances, [
                    'user_id' => $userId,
                    'selection_count' => count($soutenances),
                ]);

                return [
                    'success' => false,
                    'error' => 'Certaines soutenances sélectionnées ne sont pas entièrement programmées (date invalide ou manquante).',
                    'error_code' => 'invalid_schedule_data',
                ];
            }

            $pdo->beginTransaction();

            $soutenances = $this->enrichWithFullJurys($soutenances);
            $groupedByDate = $this->groupByDate($soutenances);
            $reference = $this->planningDataUtils->generateReference(self::TYPE_DOCUMENT);

            $pdf = $this->pdfGenerator->createDocument('L', 'A4', 'Composition de Jury de Soutenance MIAGE-GI', 'CheckMaster UFRMI');
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 15);

            foreach ($groupedByDate as $date => $dateSoutenances) {
                $pdf->AddPage();
                $this->addInstitutionalHeader($pdf, $date, $dateSoutenances);
                $html = $this->buildDatePlanningContent($dateSoutenances);
                $this->pdfGenerator->writeHtml($pdf, $html);
                $this->pdfGenerator->addFooter($pdf);
            }

            $fullPath = $this->pdfGenerator->save($pdf, self::SUBDIR, $reference);
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : null;

            $metadata = json_encode(array_merge($metadataContext, [
                'nb_soutenances' => count($soutenances),
                'nb_jours' => count($groupedByDate),
                'format' => 'landscape_miage_gi',
            ]));

            $this->planningDataUtils->saveDocumentRecord([
                'reference_document' => $reference,
                'type_document' => self::TYPE_DOCUMENT,
                'nom_fichier' => $reference . '.pdf',
                'chemin_fichier' => $fullPath,
                'taille_fichier' => $fileSize !== false ? (int) $fileSize : null,
                'mime_type' => 'application/pdf',
                'metadata' => $metadata,
                'id_utilisateur_generation' => $userId,
            ]);

            if (!$skipNotifications) {
                $this->notifyStudentsOfPlanning($soutenances, $userId);
            }
            $pdo->commit();

            return [
                'success' => true,
                'reference' => $reference,
                'path' => $fullPath,
                'filename' => basename($fullPath),
                'size' => $fileSize !== false ? (int) $fileSize : null,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $this->logFailure('generateFromSoutenances', $e, [
                'user_id' => $userId,
                'selection_count' => count($soutenances),
                'metadata' => $metadataContext,
            ]);

            return [
                'success' => false,
                'error' => 'Erreur lors de la génération du planning.',
                'error_code' => 'generation_failed',
            ];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $soutenances
     * @return array<int, array<string, mixed>>
     */
    private function enrichWithFullJurys(array $soutenances): array
    {
        foreach ($soutenances as &$soutenance) {
            $numSoutenance = (string) ($soutenance['num_soutenance'] ?? '');
            $juryDetails = $numSoutenance !== ''
                ? $this->planningDataUtils->getJuryDetails($numSoutenance)
                : [];

            // Fallback explicite depuis les champs alimentés par les codes PJ/MS.
            if (empty($juryDetails['president'])) {
                $presidentNom = trim((string) ($soutenance['president_jury_nom'] ?? ''));
                if ($presidentNom !== '') {
                    $juryDetails['president'] = 'M. ' . $presidentNom;
                }
            }

            // Fallback: si le maitre de stage n'est pas dans le jury compose,
            // utiliser la fiche stage de l'etudiant.
            $currentMaitreStage = $this->normalizeMaitreStageForDisplay($juryDetails['maitre_stage'] ?? '');
            if ($currentMaitreStage === '') {
                $maitreStageNom = $this->normalizeMaitreStageForDisplay($soutenance['maitre_stage_jury_nom'] ?? '');
                if ($maitreStageNom === '') {
                    $maitreStageNom = $this->normalizeMaitreStageForDisplay($soutenance['maitre_stage_nom'] ?? '');
                }
                if ($maitreStageNom !== '') {
                    $juryDetails['maitre_stage'] = $maitreStageNom;
                } else {
                    unset($juryDetails['maitre_stage']);
                }
            } else {
                $juryDetails['maitre_stage'] = $currentMaitreStage;
            }

            $soutenance['jury_details'] = $juryDetails;
        }
        unset($soutenance);

        return $soutenances;
    }

    /**
     * @param array<int, array<string, mixed>> $soutenances
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupByDate(array $soutenances): array
    {
        $grouped = [];
        foreach ($soutenances as $soutenance) {
            $date = (string) ($soutenance['date_soutenance'] ?? date('Y-m-d'));
            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][] = $soutenance;
        }

        foreach ($grouped as &$dateRows) {
            usort($dateRows, function (array $left, array $right): int {
                $leftHeure = substr((string) ($left['heure_soutenance'] ?? $left['heure_debut'] ?? ''), 0, 5);
                $rightHeure = substr((string) ($right['heure_soutenance'] ?? $right['heure_debut'] ?? ''), 0, 5);
                return strcmp($leftHeure, $rightHeure);
            });
        }
        unset($dateRows);

        ksort($grouped);
        return $grouped;
    }

    /**
     * @param array<int, array<string, mixed>> $dateSoutenances
     */
    private function addInstitutionalHeader(\TCPDF $pdf, string $date, array $dateSoutenances): void
    {
        $logos = $this->pdfGenerator->resolveLogoPaths();
        $logoUfhb = $logos['logo_universite'];
        $logoUfrMi = $logos['logo_filiere'];

        $startY = $pdf->GetY();
        $logoWidth = 25.0;

        if ($logoUfhb !== '' && is_file($logoUfhb)) {
            $pdf->Image($logoUfhb, 10, $startY, $logoWidth, 0, '');
        }

        $pageWidth = $pdf->getPageWidth();
        $logoRightX = $pageWidth - 10 - $logoWidth;
        if ($logoUfrMi !== '' && is_file($logoUfrMi)) {
            $pdf->Image($logoUfrMi, $logoRightX, $startY, $logoWidth, 0, '');
        }

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 7, 'COMPOSITION DE JURY DE SOUTENANCE MIAGE-GI', 0, 1, 'C');

        $dateObj = new DateTimeImmutable($date);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'SESSION ' . $this->formatSessionForDate($dateObj), 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'JURY DU ' . $this->formatDateLongue($dateObj), 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, 'LIEU : ' . $this->buildLieuLabel($dateSoutenances), 0, 1, 'C');
        $pdf->Ln(4);
    }

    private function formatDateLongue(DateTimeImmutable $date): string
    {
        $jours = [
            'Sunday' => 'Dimanche',
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
        ];
        $mois = [
            'January' => 'Janvier',
            'February' => 'Fevrier',
            'March' => 'Mars',
            'April' => 'Avril',
            'May' => 'Mai',
            'June' => 'Juin',
            'July' => 'Juillet',
            'August' => 'Aout',
            'September' => 'Septembre',
            'October' => 'Octobre',
            'November' => 'Novembre',
            'December' => 'Decembre',
        ];

        $jour = (string) ($jours[$date->format('l')] ?? $date->format('l'));
        $moisNom = (string) ($mois[$date->format('F')] ?? $date->format('F'));
        $moisNom = function_exists('mb_strtoupper') ? mb_strtoupper($moisNom, 'UTF-8') : strtoupper($moisNom);

        return $jour . ' ' . $date->format('d') . ' ' . $moisNom . ' ' . $date->format('Y');
    }

    /**
     * @param array<int, array<string, mixed>> $soutenances
     */
    private function buildDatePlanningContent(array $soutenances): string
    {
        $wNo = 4.0;
        $wNom = 16.0;
        $wJury = 22.0;
        $wHeure = 7.0;
        $wTheme = 20.0;
        $wEnt = 9.0;
        $wSalle = 22.0;

        $html = <<<HTML
<style>
    table.planning { width: 100%; border-collapse: collapse; }
    th { background-color: #F0F0F0; font-weight: bold; text-align: center; font-size: 8pt; vertical-align: middle; border: 0.5pt solid black; }
    td { font-size: 8pt; vertical-align: top; border: 0.5pt solid black; padding: 4px; }
</style>
<table class="planning" border="0.5" cellpadding="4" cellspacing="0">
    <thead>
        <tr style="background-color: #F0F0F0;">
            <th width="{$wNo}%">N°</th>
            <th width="{$wNom}%">NOM ET PRENOMS</th>
            <th width="{$wJury}%">JURY</th>
            <th width="{$wHeure}%">HEURE</th>
            <th width="{$wTheme}%">THEME</th>
            <th width="{$wEnt}%">ENTREPRISE D'ACCUEIL</th>
            <th width="{$wSalle}%">SALLE</th>
        </tr>
    </thead>
    <tbody>
HTML;

        $idx = 1;
        foreach ($soutenances as $soutenance) {
            $nom = trim((string) ($soutenance['nom_etudiant'] ?? '') . ' ' . (string) ($soutenance['prenom_etudiant'] ?? ''));
            $nom = function_exists('mb_strtoupper') ? mb_strtoupper($nom, 'UTF-8') : strtoupper($nom);
            $heureRaw = substr((string) ($soutenance['heure_soutenance'] ?? $soutenance['heure_debut'] ?? '00:00'), 0, 5);
            $theme = htmlspecialchars((string) ($soutenance['theme_soutenance'] ?? ''), ENT_QUOTES, 'UTF-8');
            $entreprise = htmlspecialchars((string) ($soutenance['entreprise_accueil'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
            $juryHtml = $this->buildJuryHtml((array) ($soutenance['jury_details'] ?? []));

            $html .= '<tr>';
            $html .= '<td width="' . $wNo . '%" align="center">' . $idx . '</td>';
            $html .= '<td width="' . $wNom . '%"><b>' . htmlspecialchars($nom, ENT_QUOTES, 'UTF-8') . '</b></td>';
            $html .= '<td width="' . $wJury . '%">' . $juryHtml . '</td>';
            $html .= '<td width="' . $wHeure . '%" align="center"><b>' . htmlspecialchars($heureRaw, ENT_QUOTES, 'UTF-8') . '</b></td>';
            $html .= '<td width="' . $wTheme . '%">' . $theme . '</td>';
            $html .= '<td width="' . $wEnt . '%">' . $entreprise . '</td>';

            $roomLabel = trim((string) ($soutenance['lib_salle'] ?? ''));
            if ($roomLabel === '') {
                $roomId = trim((string) ($soutenance['id_salle'] ?? ''));
                $roomLabel = $roomId !== '' ? 'Salle ' . $roomId : 'non renseignee';
            }
            $html .= '<td width="' . $wSalle . '%">' . htmlspecialchars($roomLabel, ENT_QUOTES, 'UTF-8') . '</td>';

            $html .= '</tr>';
            $idx++;
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * @param array<string, mixed> $juryDetails
     */
    private function buildJuryHtml(array $juryDetails): string
    {
        $chunks = [];
        if (!empty($juryDetails['president'])) {
            $chunks[] = '<b>Président :</b> ' . htmlspecialchars((string) $juryDetails['president'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($juryDetails['examinateur'])) {
            $examinateurs = (array) $juryDetails['examinateur'];
            foreach ($examinateurs as $index => $examinateur) {
                $label = count($examinateurs) > 1 ? 'Examinateur ' . ($index + 1) : 'Examinateur';
                $chunks[] = '<b>' . $label . ' :</b> ' . htmlspecialchars((string) $examinateur, ENT_QUOTES, 'UTF-8');
            }
        }
        if (!empty($juryDetails['directeur'])) {
            $chunks[] = '<b>Directeur :</b> ' . htmlspecialchars((string) $juryDetails['directeur'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($juryDetails['encadreur'])) {
            $chunks[] = '<b>Encadreur :</b> ' . htmlspecialchars((string) $juryDetails['encadreur'], ENT_QUOTES, 'UTF-8');
        }
        if (!empty($juryDetails['maitre_stage'])) {
            $maitreStage = $this->normalizeMaitreStageForDisplay($juryDetails['maitre_stage']);
            if ($maitreStage !== '') {
                $chunks[] = '<b>Maître de stage :</b> ' . htmlspecialchars($maitreStage, ENT_QUOTES, 'UTF-8');
            } else {
                $chunks[] = '<b>Maître de stage :</b> non renseigner';
            }
        } else {
            $chunks[] = '<b>Maître de stage :</b> non renseigner';
        }

        return implode('<br/>', $chunks);
    }

    /**
     * Nettoie les valeurs parasites (ex: "STAGE MAITRE") avant rendu PDF.
     *
     * @param mixed $value
     */
    private function normalizeMaitreStageForDisplay($value): string
    {
        $name = trim((string) $value);
        if ($name === '') {
            return '';
        }

        $name = trim((string) preg_replace('/^\s*M\.\s*/u', '', $name));
        $name = (string) preg_replace('/\s+/u', ' ', $name);
        $upper = strtoupper($name);

        $invalidLabels = [
            'STAGE MAITRE',
            'MAITRE STAGE',
            'NON RENSEIGNE',
            'NON RENSEIGNER',
            'N/A',
            'NA',
            '-',
        ];

        return in_array($upper, $invalidLabels, true) ? '' : $name;
    }

    private function formatSessionForDate(DateTimeImmutable $date): string
    {
        $months = [
            1 => 'JANVIER',
            2 => 'FEVRIER',
            3 => 'MARS',
            4 => 'AVRIL',
            5 => 'MAI',
            6 => 'JUIN',
            7 => 'JUILLET',
            8 => 'AOUT',
            9 => 'SEPTEMBRE',
            10 => 'OCTOBRE',
            11 => 'NOVEMBRE',
            12 => 'DECEMBRE',
        ];

        return ($months[(int) $date->format('n')] ?? strtoupper($date->format('F'))) . ' ' . $date->format('Y');
    }

    /**
     * @param array<int, array<string, mixed>> $soutenances
     */
    private function buildLieuLabel(array $soutenances): string
    {
        $labels = [];
        foreach ($soutenances as $soutenance) {
            $label = trim((string) ($soutenance['lib_salle'] ?? ''));
            if ($label === '') {
                $idSalle = trim((string) ($soutenance['id_salle'] ?? ''));
                $label = $idSalle !== '' ? 'Salle ' . $idSalle : 'Salle non définie';
            }
            $labels[$label] = true;
        }

        return implode(', ', array_keys($labels));
    }

    /**
     * @param array<int, array<string, mixed>> $soutenances
     * @return array<int, array{key: string, label: string}>
     */
    private function extractRoomsForDay(array $soutenances): array
    {
        $rooms = [];
        foreach ($soutenances as $soutenance) {
            $key = $this->resolveRoomKey($soutenance);
            if ($key === '') {
                continue;
            }

            $label = trim((string) ($soutenance['lib_salle'] ?? ''));
            if ($label === '') {
                $idSalle = trim((string) ($soutenance['id_salle'] ?? ''));
                $label = $idSalle !== '' ? 'Salle ' . $idSalle : 'Salle non définie';
            }

            if (!isset($rooms[$key])) {
                $rooms[$key] = [
                    'key' => $key,
                    'label' => $label,
                ];
            }
        }

        $result = array_values($rooms);
        usort($result, static fn(array $left, array $right): int => strcmp((string) $left['label'], (string) $right['label']));
        return $result;
    }

    /**
     * @param array<int, array{key: string, label: string}> $rooms
     * @return array<string, string>
     */
    private function buildRoomColorMap(array $rooms): array
    {
        $map = [];
        $paletteCount = count(self::ROOM_COLOR_PALETTE);
        foreach ($rooms as $index => $room) {
            $key = (string) ($room['key'] ?? '');
            if ($key === '') {
                continue;
            }
            $map[$key] = self::ROOM_COLOR_PALETTE[$index % $paletteCount];
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $soutenance
     */
    private function resolveRoomKey(array $soutenance): string
    {
        $idSalle = trim((string) ($soutenance['id_salle'] ?? ''));
        if ($idSalle !== '') {
            return 'id:' . $idSalle;
        }

        $label = trim((string) ($soutenance['lib_salle'] ?? ''));
        if ($label === '') {
            return '';
        }

        $label = function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);
        return 'label:' . $label;
    }

    /**
     * @param array<int, array<string, mixed>> $soutenances
     */
    private function notifyStudentsOfPlanning(array $soutenances, int $userId): void
    {
        $emailService = new \EmailService();
        foreach ($soutenances as $soutenance) {
            $email = $soutenance['email_etudiant'] ?? null;
            if (empty($email)) {
                continue;
            }
            $nom = ($soutenance['nom_etudiant'] ?? '') . ' ' . ($soutenance['prenom_etudiant'] ?? '');
            $dateSout = (string)($soutenance['date_soutenance'] ?? '');
            $heureSout = (string)substr((string)($soutenance['heure_soutenance'] ?? $soutenance['heure_debut'] ?? '00:00'), 0, 5);
            $salle = (string)($soutenance['lib_salle'] ?? $soutenance['id_salle'] ?? 'N/A');
            
            $emailService->sendTemplate('SOUTENANCE_PROGRAMMEE', $email, [
                'nom' => htmlspecialchars(trim($nom), ENT_QUOTES, 'UTF-8'),
                'nom_etudiant' => htmlspecialchars(trim($nom), ENT_QUOTES, 'UTF-8'),
                'theme' => htmlspecialchars((string)($soutenance['theme_soutenance'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'date_soutenance' => $dateSout,
                'heure_soutenance' => $heureSout,
                'salle' => htmlspecialchars($salle, ENT_QUOTES, 'UTF-8'),
                'composition_jury' => '',
            ]);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $soutenances
     * @return array<int, array<string, string>>
     */
    private function collectInvalidScheduledSoutenances(array $soutenances): array
    {
        $invalid = [];

        foreach ($soutenances as $soutenance) {
            $rawDate = trim((string) ($soutenance['date_soutenance'] ?? ''));
            if ($this->normalizeDateValue($rawDate) !== null) {
                continue;
            }

            $invalid[] = [
                'num_soutenance' => trim((string) ($soutenance['num_soutenance'] ?? '')),
                'num_etud' => trim((string) ($soutenance['num_etud'] ?? '')),
                'date_soutenance' => $rawDate,
            ];
        }

        return $invalid;
    }

    private function normalizeDateValue(string $date): ?string
    {
        if ($date === '' || $date === '0000-00-00') {
            return null;
        }

        $dateObject = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        $errors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0);

        if ($dateObject === false || $hasErrors) {
            return null;
        }

        return $dateObject->format('Y-m-d');
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logFailure(string $operation, Throwable $e, array $context = []): void
    {
        error_log(sprintf(
            '[PlanningGeneratorService] %s failed: %s in %s:%d | context=%s',
            $operation,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ));
    }

    /**
     * @param array<int, array<string, string>> $invalidSoutenances
     * @param array<string, mixed> $context
     */
    private function logValidationFailure(string $operation, array $invalidSoutenances, array $context = []): void
    {
        error_log(sprintf(
            '[PlanningGeneratorService] %s validation failed: invalid scheduled soutenances=%s | context=%s',
            $operation,
            json_encode($invalidSoutenances, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ));
    }

    /**
     * Génère un aperçu PDF du modèle planning de soutenance avec des données fictives.
     * Utilise le vrai rendu (en-tête institutionnel + tableau complet) sans aucune écriture.
     *
     * @return string Contenu PDF binaire
     */
    public function generatePreview(): string
    {
        $today = date('Y-m-d');

        $fakeSoutenances = [
            [
                'date_soutenance'     => $today,
                'heure_soutenance'    => '08:00',
                'heure_debut'         => '08:00',
                'nom_etudiant'        => 'KOUASSI',
                'prenom_etudiant'     => 'Jean-Baptiste',
                'theme_soutenance'    => 'Conception d\'une plateforme de suivi académique',
                'entreprise_accueil'  => 'SINT',
                'lib_salle'           => 'Amphi A',
                'id_salle'            => '1',
                'jury_details'        => [
                    'president'             => 'Prof. YAPI Gnagne Serge',
                    'examinateur'           => 'Dr. AKA Sylvie',
                    'directeur_memoire'     => 'Dr. BROU Kofi',
                    'encadreur_pedagogique' => 'M. KONAN Etienne',
                    'maitre_stage'          => 'M. ASSI Kouamé',
                ],
            ],
            [
                'date_soutenance'     => $today,
                'heure_soutenance'    => '10:00',
                'heure_debut'         => '10:00',
                'nom_etudiant'        => 'DIALLO',
                'prenom_etudiant'     => 'Mariama',
                'theme_soutenance'    => 'Automatisation des processus RH par système expert',
                'entreprise_accueil'  => 'ORANGE CI',
                'lib_salle'           => 'Salle 201',
                'id_salle'            => '2',
                'jury_details'        => [
                    'president'             => 'Prof. YAPI Gnagne Serge',
                    'examinateur'           => 'Dr. AKA Sylvie',
                    'directeur_memoire'     => 'Dr. BROU Kofi',
                    'encadreur_pedagogique' => 'Mme. COULIBALY Awa',
                    'maitre_stage'          => 'M. TRAORE Boubacar',
                ],
            ],
            [
                'date_soutenance'     => $today,
                'heure_soutenance'    => '14:00',
                'heure_debut'         => '14:00',
                'nom_etudiant'        => 'N\'GORAN',
                'prenom_etudiant'     => 'Serge Aubin',
                'theme_soutenance'    => 'Développement d\'une application mobile de gestion des stocks',
                'entreprise_accueil'  => 'MTN CI',
                'lib_salle'           => 'Amphi A',
                'id_salle'            => '1',
                'jury_details'        => [
                    'president'             => 'Dr. AKA Sylvie',
                    'examinateur'           => 'M. KONAN Etienne',
                    'directeur_memoire'     => 'Prof. YAPI Gnagne Serge',
                    'encadreur_pedagogique' => 'Dr. BROU Kofi',
                    'maitre_stage'          => 'Mme. DOUMBIA Aminata',
                ],
            ],
        ];

        $pdf = $this->pdfGenerator->createDocument('L', 'A4', 'Composition de Jury de Soutenance MIAGE-GI', 'CheckMaster UFRMI');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);

        $pdf->AddPage();
        $this->addInstitutionalHeader($pdf, $today, $fakeSoutenances);
        $html = $this->buildDatePlanningContent($fakeSoutenances);
        $this->pdfGenerator->writeHtml($pdf, $html);
        $this->pdfGenerator->addFooter($pdf);

        return (string) $pdf->Output('', 'S');
    }
}
