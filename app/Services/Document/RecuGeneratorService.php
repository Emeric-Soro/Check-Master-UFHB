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
     * @param int $versementId ID du versement
     * @param int $userId ID de l'utilisateur générant le document
     * @return array{success: bool, reference?: string, path?: string, error?: string}
     */
    public function generate(int $versementId, int $userId): array
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
            $inscription = $this->recuDataUtils->findInscriptionById((int) $versement['id_inscription']);
            if ($inscription === null) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Inscription non trouvée',
                ];
            }

            // 3. Récupérer l'étudiant
            $matriculeEtudiant = (string) ($inscription['id_etudiant'] ?? $versement['matricule_etudiant'] ?? '');
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

            // Marge pour Paysage A5
            $pdf->SetMargins(10, 10, 10);
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
                'id_inscription' => (int) $versement['id_inscription'],
                'matricule_etudiant' => $versement['matricule_etudiant'],
            ]);

            $documentId = $this->recuDataUtils->saveDocumentRecord([
                'reference_document' => $reference,
                'type_document' => self::TYPE_DOCUMENT,
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
        
        $dateVersement = (new DateTimeImmutable($versement['date_versement']))->format('d/m/Y');
        
        $nomEtudiant = strtoupper($etudiant['nom_etudiant'] . ' ' . $etudiant['prenom_etudiant']);
        
        $anneeAcademique = $inscription['libelle_annee'] ?? '2025-2026';
        $niveau = $inscription['code_niveau'] ?? 'M2';
        $filiere = $inscription['code_filiere'] ?? 'MIAGE-GI';
        
        $reglement = match ($versement['type_versement']) {
            'inscription' => 'DROIT D\'INSCRIPTION',
            'scolarite' => 'FRAIS DE SCOLARITE',
            default => strtoupper($versement['type_versement']),
        };

        $isEspeces = $versement['methode_paiement'] === 'especes' ? 'checked="checked"' : '';
        $isCheque = $versement['methode_paiement'] === 'cheque' ? 'checked="checked"' : '';
        $numCheque = '....................'; // No longer storing external reference in specific field

        $logos = $this->pdfGenerator->resolveLogoPaths();
        
        $logoUniversite = $logos['logo_universite'] 
            ? '<img src="' . htmlspecialchars($logos['logo_universite']) . '" style="max-height: 50px; width: auto;" />'
            : '<div class="logo-placeholder">LOGO<br>UFHB</div>';
            
        $logoFiliere = $logos['logo_filiere']
            ? '<img src="' . htmlspecialchars($logos['logo_filiere']) . '" style="max-height: 50px; width: auto;" />'
            : '<div style="border: 1px solid #000; padding: 5px; width: 60px; margin: 5px auto;">M & I</div>';

        $resteAPayer = (float)($inscription['reste_a_payer'] ?? 0.0) <= 0 ? 'SOLDE' : number_format((float)($inscription['reste_a_payer']), 0, ',', ' ') . ' F CFA';

        $html = <<<HTML
<style>
    body { font-family: helvetica; font-size: 10pt; color: #000; }
    .header-table { width: 100%; border: none; }
    .logo-placeholder { border: 1px solid #000; padding: 5px; text-align: center; width: 80px; font-size: 7pt; margin: 0 auto; }
    .header-center { text-align: center; font-size: 9pt; line-height: 1.2; }
    .header-right { text-align: center; font-size: 8pt; line-height: 1.1; }
    
    .title-row { text-align: center; margin: 15px 0; }
    .recu-no { font-size: 18pt; font-weight: bold; }
    .recu-val { font-size: 18pt; font-weight: bold; color: #d00; }
    .bpf { float: right; font-size: 12pt; font-weight: bold; border-bottom: 1px dotted #000; padding-bottom: 2px; }

    .content-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
    .content-table td { padding: 5px 0; }
    .label { width: 150px; }
    .value { font-weight: bold; border-bottom: 1px dotted #000; }
    .dotted-line { border-bottom: 1px dotted #000; flex-grow: 1; min-height: 1em; }

    .footer-table { width: 100%; margin-top: 20px; }
    .signature-box { text-align: left; width: 35%; }
    .stamp-box { text-align: center; width: 30%; }
    .situation-box { text-align: right; width: 35%; font-size: 9pt; }
    
    .stamp-circle { border: 2px solid #0056b3; border-radius: 50%; width: 100px; height: 100px; margin: 0 auto; padding: 10px; color: #0056b3; font-size: 8pt; font-weight: bold; }
    
    .nb { font-size: 8pt; font-style: italic; margin-top: 10px; text-align: right; }
</style>

<table class="header-table">
    <tr>
        <td width="20%" align="center">
            UNIVERSITE<br>
            <div style="margin: 5px 0;">{$logoUniversite}</div>
            <span style="font-size: 7pt; font-weight: bold;">FELIX HOUPHOUET-BOIGNY</span>
        </td>
        <td width="60%" class="header-center">
            <strong>FILIERES PROFESSIONNALISEES, UFR MI</strong><br>
            <strong>UNIVERSITE DE COCODY</strong><br>
            22 B.P. 582 Abidjan 22<br>
            Tél.: (Fax) : 22 41 05 74 / 22 48 01 40<br>
            Cel : 07 89 94 26 / 07 69 15 04
        </td>
        <td width="20%" class="header-right">
            UFR<br>
            MATHEMATIQUES<br>
            ET INFORMATIQUE<br>
            <div style="margin: 5px auto;">{$logoFiliere}</div>
        </td>
    </tr>
</table>

<div style="margin-top: 10px; position: relative;">
    <span style="font-size: 16pt; font-weight: bold; margin-left: 200px;">Reçu N° <span style="color: #d00;">{$reference}</span></span>
    <span class="bpf">B.P.F. # {$montantFmt} F CFA</span>
</div>

<table class="content-table">
    <tr>
        <td width="15%">Reçu de M. </td>
        <td width="85%" class="value">{$nomEtudiant}</td>
    </tr>
    <tr>
        <td width="15%">La somme de : </td>
        <td width="85%" class="value">{$montantLettres} francs CFA</td>
    </tr>
    <tr>
        <td colspan="2" align="center" style="font-size: 8pt;">(en toutes lettres)</td>
    </tr>
    <tr>
        <td width="15%">En règlement de : </td>
        <td width="85%" class="value">{$reglement} {$anneeAcademique}</td>
    </tr>
    <tr>
        <td width="15%">Année d'Etudes : </td>
        <td width="85%" class="value">{$niveau}</td>
    </tr>
</table>

<div style="margin-top: 10px;">
    <input type="checkbox" readonly="readonly" {$isEspeces} /> Espèces &nbsp;&nbsp;&nbsp;
    <input type="checkbox" readonly="readonly" {$isCheque} /> Chèque n° : <span class="value">{$numCheque}</span> &nbsp;&nbsp;&nbsp;
    Date : <span class="value">{$dateVersement}</span>
</div>

<table class="footer-table">
    <tr>
        <td class="signature-box" valign="top">
            <strong>Signature et cachet</strong><br><br>
            <span style="font-size: 8pt;">(Signé numériquement)</span>
        </td>
        <td class="stamp-box" valign="middle">
             <div style="border: 1px solid #000; border-radius: 50%; width: 110px; height: 110px; padding-top: 30px;">
                Filière<br>
                Professionnalisée<br>
                <strong>{$filiere}</strong>
             </div>
        </td>
        <td class="situation-box" valign="top">
            Reste à payer : <strong>{$resteAPayer}</strong><br>
            Montant prochain versement : ....................<br>
            Date prochain versement : ........................
        </td>
    </tr>
</table>

<div class="nb">N.B.: Aucun remboursement n'est possible après versement</div>
HTML;

        return $html;
    }
}
