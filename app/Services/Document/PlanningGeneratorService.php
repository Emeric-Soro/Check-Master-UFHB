<?php

declare(strict_types=1);

namespace App\Services\Document;

// TODO: À créer quand le service de notification sera implémenté
use App\Support\Database;
use App\Utils\PlanningDataUtils;
use DateTimeImmutable;
use Throwable;

/**
 * Service de génération de planning des soutenances PDF.
 *
 * Génère un planning au format A4 LANDSCAPE conforme au modèle MIAGE-GI:
 * - Composition complète du jury
 * - Entreprise d'accueil
 * - Marquage visuel des salles (S. CONF / S. 207)
 */
final class PlanningGeneratorService
{
    private const TYPE_DOCUMENT = 'PLN';
    private const SUBDIR = 'planning';

    public function __construct(
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly PlanningDataUtils $planningDataUtils,
        private readonly Database $db,
        private readonly ?object $notificationService = null
    ) {
    }

    /**
     * Génère un planning des soutenances PDF.
     *
     * @param int|null $sessionId Session soutenance ID (null = toutes les soutenances programmées)
     * @param string|null $dateFrom Date de début (YYYY-MM-DD format)
     * @param string|null $dateTo Date de fin (YYYY-MM-DD format)
     * @param int $userId ID de l'utilisateur générant le document
     * @return array{success: bool, reference?: string, path?: string, error?: string}
     */
    public function generate(?int $sessionId, ?string $dateFrom, ?string $dateTo, int $userId): array
    {
        $pdo = $this->db->pdo();

        try {
            // Début transaction pour cohérence des données
            $pdo->beginTransaction();

            // 1. Récupérer les soutenances selon les filtres
            $soutenances = $this->planningDataUtils->getSoutenancesForPlanning($sessionId, $dateFrom, $dateTo);

            if (count($soutenances) === 0) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Aucune soutenance trouvée pour les critères spécifiés',
                ];
            }

            // 2. Enrichir les soutenances avec les informations du jury complet
            $soutenances = $this->enrichWithFullJurys($soutenances);

            // 3. Grouper par date pour affichage
            $groupedByDate = $this->groupByDate($soutenances);

            // 4. Générer la référence unique
            $reference = $this->planningDataUtils->generateReference(self::TYPE_DOCUMENT);

            // 5. Créer le PDF en LANDSCAPE (A4)
            $pdf = $this->pdfGenerator->createDocument('L', 'A4', 'Composition de Jury de Soutenance MIAGE-GI', 'CheckMaster UFRMI');

            // Configurer le document
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 15);

            // Ajouter les pages par date
            foreach ($groupedByDate as $date => $dateSoutenances) {
                $pdf->AddPage();
                
                // En-tête institutionnel
                $this->addInstitutionalHeader($pdf, $sessionId, $date);

                // Contenu du planning pour cette date
                $html = $this->buildDatePlanningContent($dateSoutenances);
                $this->pdfGenerator->writeHtml($pdf, $html);
                
                // Pied de page (numérotation)
                $this->pdfGenerator->addFooter($pdf);
            }

            // 6. Sauvegarder le PDF
            $filename = $reference;
            $fullPath = $this->pdfGenerator->save($pdf, self::SUBDIR, $filename);

            // 7. Enregistrer le document en base
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : null;

            $metadata = json_encode([
                'id_session_soutenance' => $sessionId,
                'date_debut' => $dateFrom,
                'date_fin' => $dateTo,
                'nb_soutenances' => count($soutenances),
                'format' => 'landscape_miage_gi'
            ]);

            $documentId = $this->planningDataUtils->saveDocumentRecord([
                'reference_document' => $reference,
                'type_document' => self::TYPE_DOCUMENT,
                'nom_fichier' => $filename . '.pdf',
                'chemin_fichier' => $fullPath,
                'taille_fichier' => $fileSize !== false ? (int) $fileSize : null,
                'mime_type' => 'application/pdf',
                'metadata' => $metadata,
                'id_utilisateur_generation' => $userId,
            ]);

            // 8. Notification des étudiants
            $this->notifyStudentsOfPlanning($soutenances, $userId);

            // Commit transaction
            $pdo->commit();

            return [
                'success' => true,
                'reference' => $reference,
                'path' => $fullPath,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                'success' => false,
                'error' => 'Erreur lors de la génération du planning: ' . $e->getMessage(),
            ];
        }
    }



    private function enrichWithFullJurys(array $soutenances): array
    {
        foreach ($soutenances as &$soutenance) {
            $numSoutenance = (string) $soutenance['num_soutenance'];
            $soutenance['jury_details'] = $this->planningDataUtils->getJuryDetails($numSoutenance);
        }
        return $soutenances;
    }

    private function groupByDate(array $soutenances): array
    {
        $grouped = [];
        foreach ($soutenances as $s) {
            $date = (string)($s['date_soutenance'] ?? date('Y-m-d'));
            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][] = $s;
        }
        ksort($grouped);
        return $grouped;
    }

    private function addInstitutionalHeader(\TCPDF $pdf, ?int $sessionId, string $date): void
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
        
        $sessionName = 'OCTOBRE 2025'; 
        $pdf->Cell(0, 7, 'SESSION ' . strtoupper($sessionName), 0, 1, 'C');

        $dateObj = new DateTimeImmutable($date);
        $dateLongue = $this->formatDateLongue($dateObj);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'jury du ' . $dateLongue, 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, "LIEU: Salle de Conférences de l'UFRMI/SALLE 207", 0, 1, 'C');
        
        $pdf->Ln(5);
    }

    private function formatDateLongue(DateTimeImmutable $date): string
    {
        $jours = ['Sunday' => 'Dimanche', 'Monday' => 'Lundi', 'Tuesday' => 'Mardi', 'Wednesday' => 'Mercredi', 'Thursday' => 'Jeudi', 'Friday' => 'Vendredi', 'Saturday' => 'Samedi'];
        $mois = ['January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars', 'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin', 'July' => 'Juillet', 'August' => 'Août', 'September' => 'Septembre', 'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre'];
        
        $j = (string)($jours[$date->format('l')] ?? $date->format('l'));
        $d = (string)$date->format('d');
        $m = (string)strtoupper((string)($mois[$date->format('F')] ?? $date->format('F')));
        $y = (string)$date->format('Y');
        
        return "{$j} {$d} {$m} {$y}";
    }

    private function buildDatePlanningContent(array $soutenances): string
    {
        $wNo = 4;
        $wNom = 16;
        $wJury = 24;
        $wHeure = 8;
        $wTheme = 24;
        $wEnt = 12;
        $wS1 = 6;
        $wS2 = 6;

        $html = <<<HTML
<style>
    table.planning { width: 100%; border-collapse: collapse; }
    th { background-color: #F0F0F0; font-weight: bold; text-align: center; font-size: 8pt; vertical-align: middle; border: 0.5pt solid black; }
    td { font-size: 8pt; vertical-align: top; border: 0.5pt solid black; padding: 5px; }
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
            <th width="{$wS1}%">S. CONF</th>
            <th width="{$wS2}%">S. 207</th>
        </tr>
    </thead>
    <tbody>
HTML;

        $idx = 1;
        foreach ($soutenances as $s) {
            $nom = (string)strtoupper((string)($s['nom_etudiant'] ?? '')) . ' ' . (string)($s['prenom_etudiant'] ?? '');
            $heure = (string)str_replace(':', ' H ', substr((string)($s['heure_debut'] ?? '00:00'), 0, 5));
            $theme = (string)htmlspecialchars((string)($s['theme_soutenance'] ?? ''));
            $entreprise = (string)htmlspecialchars((string)($s['entreprise_accueil'] ?? 'N/A'));
            
            $codeSalle = (string)strtoupper((string)($s['code_salle'] ?? ''));
            $styleConf = (stripos($codeSalle, 'CONF') !== false) ? ' bgcolor="#5D8AA8"' : '';
            $style207 = (stripos($codeSalle, '207') !== false) ? ' bgcolor="#4F7942"' : '';

            $j = $s['jury_details'] ?? [];
            $juryHtml = '';
            if (!empty($j['president'])) $juryHtml .= '<b>Président :</b> ' . (string)htmlspecialchars((string)$j['president']) . '<br/>';
            if (!empty($j['examinateur'])) {
                $exs = (array)$j['examinateur'];
                foreach ($exs as $i => $ex) {
                    $num = count($exs) > 1 ? ' ' . ($i + 1) : '';
                    $juryHtml .= '<b>Examinateur' . $num . ' :</b> ' . (string)htmlspecialchars((string)$ex) . '<br/>';
                }
            }
            if (!empty($j['maitre_stage'])) $juryHtml .= '<b>Maître de stage :</b> ' . (string)htmlspecialchars((string)$j['maitre_stage']) . '<br/>';
            if (!empty($j['directeur'])) $juryHtml .= '<b>Directeur :</b> ' . (string)htmlspecialchars((string)$j['directeur']) . '<br/>';
            if (!empty($j['encadreur'])) $juryHtml .= '<b>Encadreur :</b> ' . (string)htmlspecialchars((string)$j['encadreur']) . '<br/>';

            $html .= <<<HTML
        <tr>
            <td width="{$wNo}%" align="center">{$idx}</td>
            <td width="{$wNom}%"><b>{$nom}</b></td>
            <td width="{$wJury}%">{$juryHtml}</td>
            <td width="{$wHeure}%" align="center"><b>{$heure}</b></td>
            <td width="{$wTheme}%">{$theme}</td>
            <td width="{$wEnt}%">{$entreprise}</td>
            <td width="{$wS1}%"{$styleConf}></td>
            <td width="{$wS2}%"{$style207}></td>
        </tr>
HTML;
            $idx++;
        }

        $html .= '</tbody></table>';
        return $html;
    }

    private function notifyStudentsOfPlanning(array $soutenances, int $userId): void
    {
        if ($this->notificationService === null) {
            return;
        }

        foreach ($soutenances as $s) {
            if (empty($s['email_etudiant'])) {
                continue;
            }

            $this->notificationService->dispatchEvent('SOUTENANCE_SCHEDULED', [
                'nom_utilisateur' => (string)($s['nom_etudiant'] ?? '') . ' ' . (string)($s['prenom_etudiant'] ?? ''),
                'theme' => (string)($s['theme_soutenance'] ?? ''),
                'date' => (string)($s['date_soutenance'] ?? ''),
                'heure' => (string)substr((string)($s['heure_debut'] ?? '00:00'), 0, 5),
                'salle' => (string)($s['lib_salle'] ?? $s['id_salle'] ?? 'N/A')
            ], [
                'email' => (string)$s['email_etudiant'],
                'name' => (string)($s['nom_etudiant'] ?? '') . ' ' . (string)($s['prenom_etudiant'] ?? '')
            ], $userId);
        }
    }
}
