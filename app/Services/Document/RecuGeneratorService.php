<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Support\Database;
use App\Utils\RecuDataUtils;
use DateTimeImmutable;
use Throwable;

/**
 * Service de génération de reçus de paiement PDF.
 *
 * Génère des reçus au format A5 paysage avec toutes les informations de paiement,
 * enregistre le document en base, et met à jour le versement avec la référence.
 *
 * RG-DOC-005: Reçu auto-généré après chaque paiement.
 */
final class RecuGeneratorService
{
    private const TYPE_DOCUMENT = 'REC';
    private const SUBDIR = 'recus';

    public function __construct(
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly RecuDataUtils $recuDataUtils,
        private readonly Database $db
    ) {
    }

    /**
     * Génère un reçu de paiement PDF.
     *
     * @param string|int $versementId ID composite du versement
     * @param int $userId ID de l'utilisateur générant le document
     * @return array{success: bool, reference?: string, path?: string, filename?: string, size?: int|null, error?: string}
     */
    public function generate(string|int $versementId, int $userId): array
    {
        $pdo = $this->db->pdo();

        try {
            // Début transaction pour cohérence des données
            $pdo->beginTransaction();

            // 1. Récupérer le versement avec toutes les infos nécessaires
            $versement = $this->recuDataUtils->findVersementById($versementId);
            if ($versement === null) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Versement non trouvé',
                ];
            }

            // 2. Récupérer l'inscription liée
            $inscription = $this->recuDataUtils->findInscriptionById((string) ($versement['id_inscription'] ?? ''));
            if ($inscription === null) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Inscription non trouvée',
                ];
            }

            // 3. Récupérer l'étudiant
            $matriculeEtudiant = (string) ($inscription['num_carte_etud'] ?? $versement['matricule_etudiant'] ?? '');
            $etudiant = $this->recuDataUtils->findEtudiantByMatricule($matriculeEtudiant);
            if ($etudiant === null) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Étudiant non trouvé',
                ];
            }

            // 4. Générer la référence unique si pas déjà présente
            $reference = !empty($versement['reference_paiement_genere']) 
                ? $versement['reference_paiement_genere'] 
                : $this->recuDataUtils->generateReference(self::TYPE_DOCUMENT);

            // 5. Créer le PDF (Orientation L pour Paysage)
            $pdf = $this->pdfGenerator->createDocument('L', 'A5', 'Reçu de Paiement', 'CheckMaster UFRMI');

            // Marges compactes pour garantir un reçu sur une seule page A5 paysage.
            $pdf->SetMargins(8, 8, 8);
            $pdf->SetAutoPageBreak(true, 8);
            $pdf->AddPage();

            // Contenu du reçu haute fidélité
            $html = $this->buildRecuContent($reference, $versement, $inscription, $etudiant);
            $this->pdfGenerator->writeHtml($pdf, $html);

            // 6. Sauvegarder le PDF
            $filename = $reference;
            $fullPath = $this->pdfGenerator->save($pdf, self::SUBDIR, $filename);

            // 7. Enregistrer le document en base
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : null;

            $metadata = json_encode([
                'id_versement' => $versementId,
                'id_inscription' => (string) ($versement['id_inscription'] ?? ''),
                'matricule_etudiant' => $versement['matricule_etudiant'],
            ]);

            $documentId = $this->recuDataUtils->saveDocumentRecord([
                'reference_document' => $reference,
                'type_document' => self::TYPE_DOCUMENT,
                'id_source' => (string) $versementId,
                'nom_fichier' => $filename . '.pdf',
                'chemin_fichier' => $fullPath,
                'taille_fichier' => $fileSize !== false ? (int) $fileSize : null,
                'mime_type' => 'application/pdf',
                'metadata' => $metadata,
                'id_utilisateur_generation' => $userId,
            ]);

            // 8. Mettre à jour le versement avec le chemin du reçu et la référence
            $this->recuDataUtils->marquerRecuGenere($versementId, $fullPath, $reference);

            // Commit transaction
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

            return [
                'success' => false,
                'error' => 'Erreur lors de la génération du reçu: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Construit le contenu HTML du reçu (Modèle Haute Fidélité - Pixel Perfect).
     */
    private function buildRecuContent(
        string $reference,
        array $versement,
        array $inscription,
        array $etudiant
    ): string {
        $montant = (int)$versement['montant_versement'];
        $montantFmt = number_format((float) $montant, 0, ',', ' ');
        $montantLettres = ucfirst(RecuDataUtils::intToWords($montant));
        
        $dateVersement = $this->formatReceiptDate($versement['date_versement'] ?? null);
        
        $nomEtudiant = strtoupper($etudiant['nom_etudiant'] . ' ' . $etudiant['prenom_etudiant']);
        
        $anneeAcademique = trim((string) ($inscription['libelle_annee'] ?? '2025-2026'));
        if (preg_match('/^(\d{4})-\d{2}-\d{2}\s*\/\s*(\d{4})-\d{2}-\d{2}$/', $anneeAcademique, $matches) === 1) {
            $anneeAcademique = $matches[1] . '-' . $matches[2];
        }

        $niveau = trim((string) ($inscription['code_niveau'] ?? 'M2'));
        $filiere = trim((string) ($inscription['code_filiere'] ?? ''));
        if ($filiere === '' || strcasecmp($filiere, $niveau) === 0) {
            $filiere = 'MIAGE-GI';
        }
        
        $reglement = match ($versement['type_versement']) {
            'inscription' => 'DROIT D\'INSCRIPTION',
            'scolarite' => 'FRAIS DE SCOLARITÉ',
            default => strtoupper($versement['type_versement']),
        };

        $isEspeces = $versement['methode_paiement'] === 'especes' ? 'checked="checked"' : '';
        $isCheque = $versement['methode_paiement'] === 'cheque' ? 'checked="checked"' : '';
        $numCheque = '....................'; // No longer storing external reference in specific field

        $logos = $this->pdfGenerator->resolveLogoPaths();
        
        $logoUniversite = $logos['logo_universite']
            ? '<img src="' . htmlspecialchars($logos['logo_universite']) . '" style="max-height: 42px; width: auto;" />'
            : '<div class="logo-placeholder">LOGO<br>UFHB</div>';

        $logoFiliere = $logos['logo_filiere']
            ? '<img src="' . htmlspecialchars($logos['logo_filiere']) . '" style="max-height: 42px; width: auto;" />'
            : '<div style="border: 1px solid #000; padding: 5px; width: 60px; margin: 5px auto;">M & I</div>';

        $resteNumeric = max(0.0, (float) ($inscription['reste_a_payer'] ?? 0.0));
        $resteAPayer = $resteNumeric > 0
            ? number_format($resteNumeric, 0, ',', ' ') . ' F CFA'
            : 'SOLDE (0 F CFA)';
        $prochainVersement = number_format($resteNumeric, 0, ',', ' ') . ' F CFA';
        $prochaineDate = $resteNumeric > 0
            ? $this->estimateNextVersementDate(
                $versement['date_versement'] ?? ($inscription['date_inscription'] ?? null)
            )
            : '—';

        $html = <<<HTML
<style>
    body { font-family: helvetica; font-size: 8.8pt; color: #000; line-height: 1.03; }
    table { border-collapse: collapse; }
    .header-table { width: 100%; border: none; margin-bottom: 2px; }
    .header-table td { vertical-align: top; }
    .logo-placeholder { border: 1px solid #000; padding: 4px; text-align: center; width: 64px; font-size: 6pt; margin: 0 auto; }
    .header-left { text-align: center; font-size: 7.2pt; line-height: 1.05; }
    .header-center { text-align: center; font-size: 7.8pt; line-height: 1.08; }
    .header-right { text-align: center; font-size: 7pt; line-height: 1.02; }

    .title-table { width: 100%; margin: 4px 0 5px; }
    .title-table td { vertical-align: bottom; }
    .recu-no { font-size: 14.5pt; font-weight: bold; white-space: nowrap; }
    .recu-val { font-size: 14.5pt; font-weight: bold; color: #d00; }
    .bpf-cell { text-align: right; font-size: 12pt; font-weight: bold; white-space: nowrap; }

    .content-table { width: 100%; margin-top: 0; }
    .content-table td { padding: 1px 0; vertical-align: top; }
    .label { width: 16%; font-size: 10pt; }
    .value { font-weight: bold; border-bottom: 1px solid #222; font-size: 10.8pt; }
    .value.is-tight { white-space: nowrap; }
    .helper-row td { padding-top: 0; font-size: 7pt; text-align: center; }

    .payment-line { margin-top: 4px; font-size: 9.6pt; white-space: nowrap; }
    .payment-line .value-inline { font-weight: bold; border-bottom: 1px solid #222; display: inline-block; min-width: 70px; text-align: center; }

    .footer-table { width: 100%; margin-top: 6px; page-break-inside: avoid; }
    .footer-table tr, .footer-table td { page-break-inside: avoid; }
    .signature-box { text-align: left; width: 31%; font-size: 8.4pt; }
    .stamp-box { text-align: center; width: 26%; }
    .situation-box { text-align: right; width: 43%; font-size: 8.6pt; line-height: 1.15; }

    .stamp-mark {
        display: inline-block;
        border: 1px solid #000;
        min-width: 92px;
        padding: 3px 10px 2px;
        line-height: 1.04;
        font-size: 8.2pt;
    }

    .nb { font-size: 7.2pt; font-style: italic; margin-top: 5px; text-align: right; }
    .muted { font-size: 7pt; }
</style>

<table class="header-table">
    <tr>
        <td width="19%" class="header-left">
            UNIVERSITE<br>
            <div style="margin: 5px 0;">{$logoUniversite}</div>
            <span style="font-size: 7pt; font-weight: bold;">FELIX HOUPHOUET-BOIGNY</span>
        </td>
        <td width="62%" class="header-center">
            <strong>FILIERES PROFESSIONNALISEES, UFR MI</strong><br>
            <strong>UNIVERSITE DE COCODY</strong><br>
            22 B.P. 582 Abidjan 22<br>
            Tél.: (Fax) : 22 41 05 74 / 22 48 01 40<br>
            Cel : 07 89 94 26 / 07 69 15 04
        </td>
        <td width="19%" class="header-right">
            UFR<br>
            MATHEMATIQUES<br>
            ET INFORMATIQUE<br>
            <div style="margin: 5px auto;">{$logoFiliere}</div>
        </td>
    </tr>
</table>

<table class="title-table">
    <tr>
        <td class="recu-no">Reçu N° <span class="recu-val">{$reference}</span></td>
        <td class="bpf-cell">B.P.F. # {$montantFmt} F CFA</td>
    </tr>
</table>

<table class="content-table">
    <tr>
        <td width="15%">Reçu de M. </td>
        <td width="85%" class="value is-tight">{$nomEtudiant}</td>
    </tr>
    <tr>
        <td width="15%">La somme de : </td>
        <td width="85%" class="value">{$montantLettres} francs CFA</td>
    </tr>
    <tr class="helper-row">
        <td colspan="2" align="center" style="font-size: 8pt;">(en toutes lettres)</td>
    </tr>
    <tr>
        <td width="15%">En règlement de : </td>
        <td width="85%" class="value">{$reglement} {$anneeAcademique}</td>
    </tr>
    <tr>
        <td width="15%">Année d'Etudes : </td>
        <td width="85%" class="value is-tight">{$niveau}</td>
    </tr>
</table>

<div class="payment-line">
    <input type="checkbox" readonly="readonly" {$isEspeces} /> Espèces &nbsp;&nbsp;
    <input type="checkbox" readonly="readonly" {$isCheque} /> Chèque n° : <span class="value-inline">{$numCheque}</span> &nbsp;&nbsp;
    Date : <span class="value-inline">{$dateVersement}</span>
</div>

<table class="footer-table">
    <tr>
        <td class="signature-box" valign="top">
            <strong>Signature et cachet</strong><br>
            <span class="muted">(Signé numériquement)</span>
        </td>
        <td class="stamp-box" valign="middle">
             <div class="stamp-mark">
                Filière Professionnalisée<br>
                <strong>{$filiere}</strong>
             </div>
        </td>
        <td class="situation-box" valign="top">
            Reste à payer : <strong>{$resteAPayer}</strong><br>
            Montant prochain versement : <strong>{$prochainVersement}</strong><br>
            Date prochain versement : <strong>{$prochaineDate}</strong>
        </td>
    </tr>
</table>

<div class="nb">N.B.: Aucun remboursement n'est possible après versement</div>
HTML;

        return $html;
    }

    private function formatReceiptDate(?string $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '0000-00-00' || $raw === '0000-00-00 00:00:00') {
            return '—';
        }

        try {
            return (new DateTimeImmutable($raw))->format('d/m/Y');
        } catch (Throwable) {
            return '—';
        }
    }

    private function estimateNextVersementDate(?string $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '0000-00-00' || $raw === '0000-00-00 00:00:00') {
            return '—';
        }

        try {
            return (new DateTimeImmutable($raw))->modify('+3 months')->format('d/m/Y');
        } catch (Throwable) {
            return '—';
        }
    }
}
