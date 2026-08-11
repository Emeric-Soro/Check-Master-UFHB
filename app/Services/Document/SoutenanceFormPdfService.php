<?php

declare(strict_types=1);

namespace App\Services\Document;

use PDO;
use RuntimeException;

/** Génère les formulaires d'autorisation et de suivi d'encadrement fournis par l'UFRMI. */
final class SoutenanceFormPdfService
{
    public function __construct(
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly PDO $db
    ) {
    }

    /** @return array<string, mixed> */
    public function generate(string $type, string $studentIdentifier, int $yearId, int $userId): array
    {
        $data = $this->getStudentData($studentIdentifier);
        if ($data === null) {
            throw new RuntimeException('Étudiant introuvable.');
        }
        $type = in_array($type, ['autorisation', 'suivi_directeur', 'suivi_encadreur'], true) ? $type : 'autorisation';
        $appointments = $this->getAppointments((string) $data['num_carte_etud'], $yearId, $type);
        $code = match ($type) {
            'autorisation' => 'AUT_SOUT',
            default => 'SUIVI_ENC',
        };
        $reference = $code . '-' . date('Ymd') . '-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);

        $pdf = $this->pdfGenerator->createDocument('P', 'A4', $this->title($type), 'CheckMaster UFRMI');
        $pdf->SetMargins(12, 12, 12);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();
        $this->pdfGenerator->addHeader($pdf, $this->title($type), 'UFR Mathématiques et Informatique');
        $this->pdfGenerator->writeHtml($pdf, $this->buildHtml($type, $data, $yearId, $appointments));
        $path = $this->pdfGenerator->save($pdf, $type === 'autorisation' ? 'autorisation_soutenance' : 'suivi_encadrement', $reference);
        $size = is_file($path) ? (int) filesize($path) : null;
        $this->saveDocumentRecord($reference, $code, (string) $data['num_carte_etud'], $yearId, $path, $size, $userId);

        return ['success' => true, 'reference' => $reference, 'path' => $path, 'filename' => basename($path), 'size' => $size];
    }

    private function getStudentData(string $identifier): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT e.num_carte_etud, e.num_ident_etud, e.nom_etu, e.prenom_etu, e.email_etu,
                    i.date_debut_stage, i.date_fin_stage, i.sujet_stage,
                    ent.lib_long_entreprise, ms.Nom AS maitre_nom, ms.prenom AS maitre_prenom,
                    rs.theme_rapport
             FROM etudiants e
             LEFT JOIN informations_stage i ON i.num_etu = e.num_carte_etud
             LEFT JOIN entreprises ent ON ent.id_entreprise = i.id_entreprise
             LEFT JOIN maitre_de_stage ms ON ms.id_maitre_stage = i.id_maitre_stage
             LEFT JOIN rapport_etudiants rs ON rs.num_etu = e.num_carte_etud
             WHERE e.num_carte_etud = :id OR e.num_ident_etud = :id
             ORDER BY i.id_info_stage DESC, rs.id_rapport DESC
             LIMIT 1'
        );
        $stmt->execute([':id' => trim($identifier)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : null;
    }

    private function title(string $type): string
    {
        return match ($type) {
            'autorisation' => 'DEMANDE D’AUTORISATION DE SOUTENANCE',
            'suivi_directeur' => 'FICHE DE SUIVI D’ENCADREMENT — DIRECTEUR DE MÉMOIRE',
            default => 'FICHE DE SUIVI D’ENCADREMENT — ENCADREUR PÉDAGOGIQUE',
        };
    }

    /** @param array<int, array<string, mixed>> $appointments */
    private function buildHtml(string $type, array $data, int $yearId, array $appointments = []): string
    {
        $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $studentName = trim((string) ($data['nom_etu'] ?? '') . ' ' . (string) ($data['prenom_etu'] ?? ''));
        $stage = trim((string) ($data['sujet_stage'] ?? $data['theme_rapport'] ?? ''));
        $company = trim((string) ($data['lib_long_entreprise'] ?? ''));
        $year = $this->yearLabel($yearId);
        $base = '<style>
            body { font-family: dejavusans; font-size: 9pt; color: #1f2937; }
            .field { border-bottom: 0.4pt solid #64748b; padding: 4px; }
            .box { border: 0.5pt solid #64748b; padding: 7px; margin-bottom: 8px; }
            .section { background-color: #e8eef5; padding: 5px; font-weight: bold; margin-top: 7px; }
            table.form { width: 100%; border-collapse: collapse; }
            table.form td, table.form th { border: 0.4pt solid #94a3b8; padding: 5px; vertical-align: middle; }
            table.form th { background-color: #e8eef5; text-align: center; }
            .blank { height: 26px; }
        </style>
        <div class="box"><table width="100%"><tr><td width="55%"><b>Nom et prénoms :</b> ' . $e($studentName) . '</td><td><b>Année académique :</b> ' . $e($year) . '</td></tr>
        <tr><td><b>Niveau :</b> Master 2 &nbsp;&nbsp; <b>Option :</b> MIAGE / GI</td><td><b>Contacts :</b> ' . $e($data['email_etu'] ?? '') . '</td></tr></table></div>
        <div class="section">INFORMATIONS DU STAGE ET DU MÉMOIRE</div>
        <div class="box"><table width="100%"><tr><td><b>Entreprise d’accueil :</b> ' . $e($company ?: '................................................................') . '</td></tr>
        <tr><td><b>Durée du stage :</b> du ' . $e($data['date_debut_stage'] ?? '...............') . ' au ' . $e($data['date_fin_stage'] ?? '...............') . '</td></tr>
        <tr><td><b>Thème :</b><br/><br/>' . $e($stage ?: '................................................................................................................................') . '</td></tr>
        <tr><td><b>Maître de stage :</b> ' . $e(trim(($data['maitre_nom'] ?? '') . ' ' . ($data['maitre_prenom'] ?? '')) ?: '................................................') . '</td></tr></table></div>';

        if ($type === 'autorisation') {
            return $base . '<div class="section">ENCADREMENT ET SESSION</div><div class="box"><table width="100%"><tr><td>Encadreur pédagogique : ............................................................</td><td>Directeur de mémoire : ............................................................</td></tr><tr><td>Session demandée : Normale / Rattrapage</td><td>Date : ................................................</td></tr></table></div><div class="section">AVIS ET SIGNATURES</div><table class="form"><tr><th>Maître de stage</th><th>Encadreur pédagogique</th><th>Directeur de mémoire</th><th>Responsable de filière</th></tr><tr><td class="blank"></td><td class="blank"></td><td class="blank"></td><td class="blank"></td></tr></table>';
        }

        $label = $type === 'suivi_directeur' ? 'Directeur de Mémoire' : 'Encadreur Pédagogique';
        $rows = '';
        for ($i = 1; $i <= max(10, count($appointments)); $i++) {
            $appointment = $appointments[$i - 1] ?? [];
            $rows .= '<tr><td align="center">' . $i . '</td><td>' . $e($appointment['date_rdv'] ?? '') . '</td><td>' . $e($appointment['signature_etudiant'] ?? '') . '</td><td>' . $e($appointment['signature_encadrant'] ?? '') . '</td></tr>';
        }
        return $base . '<div class="section">FICHE DE SUIVI — ' . $label . '</div><p>Session demandée : Normale / Rattrapage</p><table class="form"><thead><tr><th width="10%">N°</th><th>Date de RDV</th><th>Signature de l’impétrant</th><th>Signature du ' . $label . '</th></tr></thead><tbody>' . $rows . '</tbody></table><p>Fait à Abidjan, le ................................................</p>';
    }

    /** @return array<int, array<string, mixed>> */
    private function getAppointments(string $studentId, int $yearId, string $type): array
    {
        $encadrementType = $type === 'suivi_directeur' ? 'directeur_memoire' : 'encadreur_pedagogique';
        try {
            $stmt = $this->db->prepare(
                'SELECT date_rdv, signature_etudiant, signature_encadrant, observation
                 FROM suivi_encadrement_rdv
                 WHERE num_etu = :student AND id_annee_acad = :year AND type_encadrement = :type
                 ORDER BY date_rdv, id_rdv'
            );
            $stmt->execute([':student' => $studentId, ':year' => $yearId, ':type' => $encadrementType]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    private function yearLabel(int $yearId): string
    {
        $stmt = $this->db->prepare('SELECT date_deb, date_fin FROM annee_academique WHERE id_annee_acad = :id LIMIT 1');
        $stmt->execute([':id' => $yearId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($row) && !empty($row['date_deb']) && !empty($row['date_fin'])) {
            return date('Y', strtotime((string) $row['date_deb'])) . '-' . date('Y', strtotime((string) $row['date_fin']));
        }
        return '................................';
    }

    private function saveDocumentRecord(string $reference, string $code, string $studentId, int $yearId, string $path, ?int $size, int $userId): void
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO document_genere
                    (reference, type_document, id_utilisateur, id_source, chemin_fichier, nom_fichier, taille_fichier, date_generation, nb_consultations)
                 VALUES (:reference, :code, :user, :source, :path, :name, :size, NOW(), 0)'
            );
            $stmt->execute([
                ':reference' => substr($reference, 0, 20), ':code' => $code,
                ':user' => $userId > 0 ? $userId : 0, ':source' => $studentId . ':' . $yearId,
                ':path' => $path, ':name' => basename($path), ':size' => $size,
            ]);
        } catch (\Throwable $e) {
            error_log('Formulaire soutenance: métadonnée non enregistrée: ' . $e->getMessage());
        }
    }
}
