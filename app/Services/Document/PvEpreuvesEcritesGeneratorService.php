<?php

declare(strict_types=1);

namespace App\Services\Document;

require_once __DIR__ . '/../UeReferentielService.php';
require_once __DIR__ . '/../EvaluationS3Service.php';

use CheckMaster\Services\EvaluationS3Service;
use PDO;
use RuntimeException;

/** Générateur du PV d'épreuves écrites conforme au modèle fourni par la scolarité. */
final class PvEpreuvesEcritesGeneratorService
{
    public function __construct(
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly EvaluationS3Service $evaluationService,
        private readonly PDO $db
    ) {
    }

    /** @return array<string, mixed> */
    public function generate(string $studentIdentifier, int $yearId, int $userId): array
    {
        $student = $this->evaluationService->findStudent($studentIdentifier);
        if ($student === null) {
            throw new RuntimeException('Étudiant introuvable.');
        }
        $studentId = (string) $student['num_carte_etud'];
        $grid = $this->evaluationService->getGrid($studentId, $yearId);
        $year = $this->getYear($yearId);
        $reference = 'PVE-' . date('Y') . '-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);

        $pdf = $this->pdfGenerator->createDocument('P', 'A4', 'PV des épreuves écrites', 'CheckMaster UFRMI');
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();
        $this->pdfGenerator->addHeader($pdf, 'PROCÈS-VERBAL DES ÉPREUVES ÉCRITES', 'MASTER 2 — MIAGE');
        $this->pdfGenerator->writeHtml($pdf, $this->buildHtml($student, $year, $grid));
        $path = $this->pdfGenerator->save($pdf, 'pv_ecrits', $reference);
        $size = is_file($path) ? (int) filesize($path) : null;

        $this->saveDocumentRecord($reference, $studentId, $yearId, $path, $size, $userId);
        return [
            'success' => true,
            'reference' => $reference,
            'path' => $path,
            'filename' => basename($path),
            'size' => $size,
        ];
    }

    /** @param array<string, mixed> $student @param array<string, mixed>|false $year @param array<string, mixed> $grid */
    private function buildHtml(array $student, array|false $year, array $grid): string
    {
        $esc = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $name = trim((string) ($student['nom_etu'] ?? '') . ' ' . (string) ($student['prenom_etu'] ?? ''));
        $yearLabel = is_array($year) && !empty($year['date_deb']) && !empty($year['date_fin'])
            ? date('Y', strtotime((string) $year['date_deb'])) . '-' . date('Y', strtotime((string) $year['date_fin']))
            : '—';
        $rows = '';
        foreach ((array) ($grid['rows'] ?? []) as $row) {
            $ue = (array) ($row['ue'] ?? []);
            $normal = is_array($row['normal'] ?? null) ? $row['normal'] : [];
            $rattrapage = is_array($row['rattrapage'] ?? null) ? $row['rattrapage'] : [];
            $rows .= '<tr>'
                . '<td>' . $esc($ue['code_ue'] ?? '') . '</td>'
                . '<td class="label">' . $esc($ue['libelle_ue'] ?? '') . '</td>'
                . '<td>' . $esc($normal['note'] ?? '') . '</td>'
                . '<td>' . $this->number($ue['credit_ue'] ?? 0) . '</td>'
                . '<td>' . ($row['normal_points'] !== null ? $this->number($row['normal_points']) : '') . '</td>'
                . '<td>' . $esc($rattrapage['note'] ?? '') . '</td>'
                . '<td>' . $this->number($ue['credit_ue'] ?? 0) . '</td>'
                . '<td>' . ($row['rattrapage_points'] !== null ? $this->number($row['rattrapage_points']) : '') . '</td>'
                . '</tr>';
        }
        $rows .= '<tr class="total"><td colspan="2">TOTAL</td><td></td><td>' . $this->number($grid['expected_credits'] ?? 0) . '</td><td>' . $this->number($grid['normal_total'] ?? 0) . '</td><td></td><td>' . $this->number($grid['expected_credits'] ?? 0) . '</td><td>' . $this->number($grid['rattrapage_total'] ?? 0) . '</td></tr>';
        $rows .= '<tr class="average"><td colspan="2">MOYENNE / 20</td><td colspan="3">' . ($grid['normal_average'] !== null ? $this->number($grid['normal_average']) . ' / 20' : 'Non calculée') . '</td><td colspan="3">' . ($grid['rattrapage_average'] !== null ? $this->number($grid['rattrapage_average']) . ' / 20' : 'Non calculée') . '</td></tr>';

        return '<style>
            body { font-family: dejavusans; font-size: 8pt; color: #1e293b; }
            .identity { border: 0.4pt solid #94a3b8; padding: 6px; margin-bottom: 8px; }
            .identity td { padding: 2px 4px; }
            .section { background-color: #e8eef5; font-weight: bold; padding: 5px; margin-top: 6px; }
            table.pv { width: 100%; border-collapse: collapse; }
            table.pv th, table.pv td { border: 0.4pt solid #64748b; padding: 4px 3px; text-align: center; vertical-align: middle; }
            table.pv th { font-weight: bold; background-color: #dbe5ef; }
            table.pv th.normal { background-color: #d8efe7; }
            table.pv th.rattrapage { background-color: #f7e8d2; }
            table.pv td.label { text-align: left; }
            table.pv tr.total td, table.pv tr.average td { font-weight: bold; background-color: #f1f5f9; }
            .result { margin-top: 8px; border: 0.4pt solid #94a3b8; padding: 6px; }
            .signatures { margin-top: 18px; }
        </style>
        <div class="identity">
            <table width="100%"><tr><td width="50%"><b>Nom et prénoms :</b> ' . $esc($name) . '</td><td><b>N° Carte :</b> ' . $esc($student['num_carte_etud'] ?? '') . '</td></tr>
            <tr><td><b>Identifiant permanent :</b> ' . $esc($student['num_ident_etud'] ?? '—') . '</td><td><b>Statut :</b> ' . (!empty($student['nouveau']) ? 'Nouveau' : 'Redoublant') . '</td></tr>
            <tr><td><b>Année académique :</b> ' . $esc($yearLabel) . '</td><td><b>Semestre :</b> M2 S1 (S3 / S9)</td></tr></table>
        </div>
        <div class="section">ÉPREUVES ÉCRITES — Année de validation du S3 : ' . $esc($yearLabel) . '</div>
        <table class="pv" cellpadding="3" cellspacing="0">
            <thead><tr><th rowspan="2" width="11%">Codes UE</th><th rowspan="2" width="25%">Intitulé des unités d’enseignement</th><th colspan="3" class="normal">Session normale</th><th colspan="3" class="rattrapage">Session de rattrapage</th></tr>
            <tr><th width="9%" class="normal">Note /20</th><th width="8%" class="normal">Crédits</th><th width="10%" class="normal">Total</th><th width="9%" class="rattrapage">Note /20</th><th width="8%" class="rattrapage">Crédits</th><th width="10%" class="rattrapage">Total</th></tr></thead>
            <tbody>' . $rows . '</tbody>
        </table>
        <div class="section">SOUTENANCE DU MÉMOIRE</div>
        <div class="result"><b>Sujet du mémoire :</b><br/><br/>........................................................................................................................................<br/><br/><b>Résultat final</b><br/><br/>Moyenne des épreuves écrites : ' . ($grid['normal_average'] !== null ? $this->number($grid['normal_average']) : '—') . ' /20 &nbsp;&nbsp;&nbsp; Moyenne soutenance : ........ /20 &nbsp;&nbsp;&nbsp; Moyenne générale : ........ /20<br/><br/>M./Mlle ' . $esc($name) . ' est déclaré(e) définitivement ................................................ au Diplôme de Master, spécialité ................................................</div>
        <div class="signatures"><b>Le Jury : Nom, Prénoms, Grade et Signature</b><br/><br/><br/>........................................................................................................................................</div>';
    }

    private function getYear(int $yearId): array|false
    {
        $stmt = $this->db->prepare('SELECT id_annee_acad, date_deb, date_fin FROM annee_academique WHERE id_annee_acad = :id LIMIT 1');
        $stmt->execute([':id' => $yearId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function saveDocumentRecord(string $reference, string $studentId, int $yearId, string $path, ?int $size, int $userId): void
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO document_genere
                    (reference, type_document, id_utilisateur, id_source, chemin_fichier, nom_fichier, taille_fichier, date_generation, nb_consultations)
                 VALUES (:reference, "PV_EPREUVES_ECRITES", :user, :source, :path, :name, :size, NOW(), 0)'
            );
            $stmt->execute([
                ':reference' => substr($reference, 0, 20),
                ':user' => $userId > 0 ? $userId : 0,
                ':source' => $studentId . ':' . $yearId,
                ':path' => $path,
                ':name' => basename($path),
                ':size' => $size,
            ]);
        } catch (\Throwable $e) {
            error_log('PV épreuves écrites: impossible d’enregistrer la métadonnée: ' . $e->getMessage());
        }
    }

    private function number(mixed $value): string
    {
        return number_format((float) $value, 2, ',', ' ');
    }
}
