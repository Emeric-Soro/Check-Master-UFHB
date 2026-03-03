<?php

declare(strict_types=1);

namespace App\Services\Document;

use TCPDF;

/**
 * Trait StudentInfoTrait
 *
 * Encapsulates common PDF generation logic for student information sections.
 */
trait StudentInfoTrait
{
    /**
     * Add student information section to PDF.
     *
     * @param TCPDF $pdf
     * @param array<string, mixed> $etudiant
     * @param array<string, mixed> $soutenance
     */
    private function addStudentInfo(TCPDF $pdf, array $etudiant, array $soutenance): void
    {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'INFORMATIONS ÉTUDIANT', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);

        $info = [
            ['Nom', htmlspecialchars($etudiant['nom_etudiant'] ?? '')],
            ['Prénom', htmlspecialchars($etudiant['prenom_etudiant'] ?? '')],
            ['Matricule', htmlspecialchars($etudiant['matricule_etudiant'] ?? '')],
            ['Filière', htmlspecialchars($etudiant['libelle_filiere'] ?? '')],
            ['Année Académique', htmlspecialchars($soutenance['libelle_annee_academique'] ?? '')],
        ];

        foreach ($info as $row) {
            $pdf->Cell(50, 6, $row[0] . ':', 0, 0, 'L');
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, $row[1], 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
        }
        $pdf->Ln(5);
    }
}
