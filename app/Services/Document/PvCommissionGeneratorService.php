<?php

declare(strict_types=1);

namespace App\Services\Document;

use App\Support\Database;
use App\Utils\PlanningDataUtils;
use DateTimeImmutable;
use Throwable;

/**
 * Service de génération de PV de commission PDF.
 *
 * Génère les procès-verbaux de commission d'évaluation au format A4 avec:
 * - En-tête institutionnel (logos UFHB + UFR-MI)
 * - Référence PVC-YYYY-NNNNN
 * - Informations de session (période, membres)
 * - Tableau des rapports évalués avec décisions
 * - Affectations des encadrants
 *
 * RG-PV-001: Un rapport = un seul PV
 * RG-PV-002: PV finalisé non modifiable
 */
final class PvCommissionGeneratorService
{
    private const TYPE_DOCUMENT = 'PVC';
    private const SUBDIR = 'pv_commission';

    public function __construct(
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly PlanningDataUtils $dataUtils,
        private readonly Database $db,
        private readonly ?object $notificationService = null
    ) {
    }

    /**
     * Génère un PV de commission PDF.
     *
     * @param int $compteRenduId ID du compte-rendu de commission
     * @param int $userId ID de l'utilisateur générant le document
     * @return array{success: bool, reference?: string, path?: string, error?: string}
     */
    public function generate(int $compteRenduId, int $userId): array
    {
        $pdo = $this->db->pdo();

        try {
            // Début transaction pour cohérence des données
            $pdo->beginTransaction();

            // 1. Récupérer le compte-rendu
            // Table: compte_rendu (id_CR, num_etu, nom_CR, contenu_CR, chemin_fichier_pdf, date_CR)
            $compteRendu = $this->dataUtils->getCompteRenduById($compteRenduId);
            if ($compteRendu === null) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => 'Compte-rendu non trouvé',
                ];
            }

            // Note: la table compte_rendu n'a PAS de colonne `statut_pv`.
            // On ne vérifie donc pas le statut brouillon.

            // 2. Récupérer la session (si le compte-rendu est lié à une session)
            // Note: la table compte_rendu n'a PAS de colonne `id_session`.
            // On récupère la session courante ou on utilise un fallback.
            $session = ['lib_session' => 'Session en cours'];

            // 3. Récupérer les membres de la commission
            // Via la table `rendre` (qui a reçu le CR) → enseignants
            $membres = $this->dataUtils->getCommissionMembers($compteRenduId);

            // 4. Récupérer les rapports évalués
            // Via compte_rendu_rapport → rapport_etudiants → etudiants → affecter → enseignants → valider
            $rapportsEvalues = $this->dataUtils->getRapportsForCompteRendu($compteRenduId);

            // 5. Générer la référence unique
            // Note: la table compte_rendu n'a PAS de colonne `numero_pv`.
            $reference = $this->dataUtils->generateReference(self::TYPE_DOCUMENT);

            // 6. Créer le PDF
            $pdf = $this->pdfGenerator->createDocument('P', 'A4', 'Procès-Verbal Commission', 'CheckMaster UFRMI');

            // Ajouter une page
            $pdf->AddPage();

            // Construction du contenu HTML complet (incluant l'en-tête)
            // Note: la table compte_rendu a `contenu_CR` (pas `contenu_html`).
            if (!empty($compteRendu['contenu_CR'])) {
                $html = (string) $compteRendu['contenu_CR'];
            } else {
                $html = $this->buildPvContent($reference, $compteRendu, $session, $membres, $rapportsEvalues);
            }

            // Rendu final
            $this->pdfGenerator->writeHtml($pdf, $html);

            // 7. Sauvegarder le PDF
            $filename = $reference;
            $fullPath = $this->pdfGenerator->save($pdf, self::SUBDIR, $filename);

            // 8. Enregistrer le document (no-op car table document_genere n'existe pas)
            $fileSize = file_exists($fullPath) ? filesize($fullPath) : null;

            $this->dataUtils->saveDocumentRecord([
                'reference_document' => $reference,
                'type_document' => self::TYPE_DOCUMENT,
                'nom_fichier' => $filename . '.pdf',
                'chemin_fichier' => $fullPath,
                'taille_fichier' => $fileSize !== false ? (int) $fileSize : null,
                'mime_type' => 'application/pdf',
                'metadata' => json_encode([
                    'id_compte_rendu' => $compteRenduId,
                    'nombre_rapports' => count($rapportsEvalues),
                ]),
                'id_utilisateur_generation' => $userId,
            ]);

            // 9. Mettre à jour le compte-rendu avec le chemin du PDF
            $this->dataUtils->finaliserCompteRendu($compteRenduId, $fullPath);

            // 10. Notification des étudiants concernés
            $this->notifyStudentsOfCommissionResult($rapportsEvalues, $userId);

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
                'error' => 'Erreur lors de la génération du PV: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Notifie les étudiants concernés par le résultat de la commission.
     */
    private function notifyStudentsOfCommissionResult(array $rapportsEvalues, int $userId): void
    {
        if ($this->notificationService === null) {
            return;
        }

        foreach ($rapportsEvalues as $r) {
            if (empty($r['num_carte_etud'])) {
                continue;
            }

            $email = $r['email_etudiant'] ?? null;

            $decision = strtoupper((string) ($r['decision_evaluation'] ?? 'OUI'));
            
            $this->notificationService->dispatchEvent('COMMISSION_DECISION', [
                'nom_utilisateur' => ($r['nom_etudiant'] ?? '') . ' ' . ($r['prenom_etudiant'] ?? ''),
                'theme' => $r['theme_rapport'] ?? '',
                'decision' => $decision === 'OUI' || $decision === 'VALIDER' ? 'FAVORABLE' : 'DÉFAVORABLE',
                'commentaire' => $r['remarque_specifique'] ?? ''
            ], [
                'email' => $email, 
                'name' => ($r['nom_etudiant'] ?? '') . ' ' . ($r['prenom_etudiant'] ?? '')
            ], $userId);
        }
    }

    /**
     * Construit le contenu HTML du PV (Modèle Haute Fidélité - Pixel Perfect).
     */
    private function buildPvContent(
        string $reference,
        array $compteRendu,
        array $session,
        array $membres,
        array $rapportsEvalues
    ): string {
        $dateStr = (string)(!empty($compteRendu['date_CR'])
            ? (new DateTimeImmutable((string)$compteRendu['date_CR']))->format('d F Y')
            : date('d F Y'));
            
        // Traduction mois
        $moisEn = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $moisFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $dateStr = (string)str_replace($moisEn, $moisFr, $dateStr);

        $president = 'le responsable de ladite filière';
        foreach ($membres as $m) {
            // Le premier membre retourné (par ordre alphabétique) sert de président par défaut
            if (!empty($m['nom_complet'])) {
                $president = 'Prof. ' . htmlspecialchars((string)($m['nom_complet'] ?? 'N/A'));
                break;
            }
        }

        $filiere = (string)($session['lib_session'] ?? 'MIAGE-GI');
        $nbDossiers = count($rapportsEvalues);
        $nbDossiersStr = $nbDossiers < 10 ? "0{$nbDossiers}" : (string)$nbDossiers;

        // On récupère les logos
        $logos = $this->pdfGenerator->resolveLogoPaths();
        $logoUniversite = $logos['logo_universite'] 
            ? '<img src="' . htmlspecialchars($logos['logo_universite']) . '" style="max-height: 50px; width: auto;" />'
            : '<div class="logo-placeholder">LOGO<br/>UFHB</div>';
            
        $logoFiliere = $logos['logo_filiere']
            ? '<img src="' . htmlspecialchars($logos['logo_filiere']) . '" style="max-height: 50px; width: auto;" />'
            : '<div class="logo-placeholder">LOGO<br/>UFR MI</div>';

        $html = <<<HTML
<style>
    body { font-family: times; font-size: 12pt; color: #000; line-height: 1.4; }
    .header-table { width: 100%; border: 0; margin-bottom: 20px; }
    .logo-placeholder { border: 1px solid #000; padding: 10px; text-align: center; width: 90px; height: 50px; font-size: 8pt; }
    .header-center { text-align: center; font-size: 11pt; line-height: 1.2; }
    .title-container { text-align: center; margin: 40px 0; }
    .title-box { border: 1.5pt solid #000; padding: 20px; text-align: center; }
    .title-box h1 { margin: 5px 0; font-size: 17pt; font-weight: bold; }
    .title-box h2 { margin: 10px 0 5px 0; font-size: 15pt; font-weight: normal; }
    .intro-section { margin-top: 30px; text-align: justify; }
    .section-header { font-size: 14pt; font-weight: bold; margin-top: 35px; margin-bottom: 15px; text-decoration: underline; }
    .cas-box-wrapper { text-align: center; margin: 30px 0 20px 0; }
    .cas-box { border: 1pt solid #000; padding: 5px 30px; font-weight: bold; font-size: 13pt; text-align: center; }
    .student-detail { margin-left: 30px; margin-bottom: 40px; }
    .student-detail p { margin: 8px 0; }
    .signature { margin-top: 80px; text-align: right; padding-right: 80px; font-weight: bold; font-size: 13pt; }
    ul { list-style-type: disc; margin-left: 50px; }
</style>

<table class="header-table" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td width="25%" align="left" valign="top">
            {$logoUniversite}
        </td>
        <td width="50%" class="header-center">
            REPUBLIQUE DE COTE D'IVOIRE<br/>
            <strong>Ministère de l'Enseignement Supérieur<br/>
            et de la Recherche Scientifique</strong><br/>
            ---------------------------
        </td>
        <td width="25%" align="right" valign="top">
            {$logoFiliere}
        </td>
    </tr>
</table>

<div class="title-container">
    <div class="title-box">
        <h1>Procès-Verbal de séance de validation de thèmes</h1>
        <h2>{$dateStr}</h2>
    </div>
</div>

<div class="intro-section">
    <p>Dans le bureau du {$president} à l'UFR MI, le {$dateStr} s'est tenue de 09 h 00 à 12 h 00 une séance de validation de thèmes de soutenance des étudiants en fin de cycle de la filière {$filiere}.</p>

    <p>La réunion était animée par {$president}. Etaient présents les membres de la commission de validation. Les membres ont examiné {$nbDossiers} ({$nbDossiersStr}) dossiers.</p>

    <p>L'ordre du jour débattu est le suivant :</p>
    <ul>
        <li>Informations</li>
        <li>Validation de thèmes</li>
        <li>Divers</li>
    </ul>
</div>

<div class="section-header">1. Informations</div>
<p>Le responsable de la filière a exposé sur l'intérêt des séances de validation. Il a donné des informations sur le choix des thèmes niveau ingénieur et la tenue mensuelle des séances de validation.</p>

<div class="section-header">2. Validation de thèmes</div>
HTML;

        if (empty($rapportsEvalues)) {
            $html .= '<p><em>Aucun dossier examiné.</em></p>';
        } else {
            $i = 1;
            foreach ($rapportsEvalues as $rapport) {
                $nom = (string)htmlspecialchars((string)($rapport['nom_etudiant'] ?? '') . ' ' . (string)($rapport['prenom_etudiant'] ?? ''));
                $theme = (string)htmlspecialchars((string)($rapport['theme_rapport'] ?? ''));
                $directeur = (string)htmlspecialchars((string)($rapport['directeur_nom'] ?? 'Non assigné'));
                $encadreur = (string)htmlspecialchars((string)($rapport['encadreur_nom'] ?? 'Non assigné'));
                
                $html .= <<<HTML
<div class="student-block">
    <div class="cas-box-wrapper">
        <div class="cas-box">Cas {$i}</div>
    </div>
    <div class="student-detail">
        <p><strong>Étudiant :</strong> M. {$nom}</p>
        <p><strong>Thème :</strong> {$theme}</p>
        <p><strong>Recommandations de la commission :</strong></p>
        <ul>
            <li>thème valide ;</li>
            <li>bien décrire le processus de règlement ;</li>
            <li>décrire exactement le contexte.</li>
        </ul>
        <p><strong>Directeur de mémoire :</strong> {$directeur}</p>
        <p><strong>Encadreur pédagogique :</strong> {$encadreur}</p>
    </div>
</div>
HTML;
                $i++;
            }
        }

        $html .= <<<HTML
<div class="section-header">3. Divers</div>
<p>La commission a recommandé au Directeur de la filière d'améliorer le partenariat avec les entreprises car elles le souhaitent compte tenu du rendement des stagiaires déjà reçus.</p>
<p>Aussi, la commission a fait les recommandations suivantes aux étudiants :</p>
<ul>
    <li>Respecter toutes les rubriques du template de présentation de thème en possession de la chargée de communication ;</li>
    <li>Joindre un CV contenant une photo d'identité ;</li>
    <li>Soutenir au plus tard à la session suivante pour ne pas tomber sous le coup d'une pénalité.</li>
</ul>

<div class="signature">
    La commission
</div>
HTML;

        return $html;
    }
}
