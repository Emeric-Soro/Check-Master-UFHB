<?php
/**
 * Test PDF Generation
 * Run this script to test the PDF generation functionality
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/utils/DocumentGeneratorService.php';

echo "Testing PDF Generation...\n\n";

// Test 1: Payment Receipt
echo "1. Testing Payment Receipt PDF...\n";
$receiptData = [
    'numeroRecu' => 'REC-2024-001',
    'dateEmission' => date('d/m/Y'),
    'anneeAcademique' => '2024-2025',
    'nomEtudiant' => 'KOUAME Jean-Baptiste',
    'numeroEtudiant' => 'ETU20240001',
    'niveauEtude' => 'Master 2 MIAGE',
    'specialite' => 'Méthodes Informatiques Appliquées à la Gestion',
    'descriptionPaiement' => 'Frais de scolarité - Semestre 1',
    'montantPaye' => 350000,
    'modePaiement' => 'Virement bancaire',
    'referenceTransaction' => 'VIR-2024-123456'
];

try {
    DocumentGeneratorService::generateReceiptPdf($receiptData, 'test_recu.pdf', 'F');
    echo "✓ Payment receipt generated successfully: test_recu.pdf\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 2: Grade Report
echo "2. Testing Grade Report PDF...\n";
$gradeData = [
    'nomComplet' => 'KOUAME Jean-Baptiste',
    'numeroEtudiant' => 'ETU20240001',
    'niveau' => 'Master 2 MIAGE',
    'anneeAcademique' => '2024-2025',
    'semestre' => 'Semestre 1',
    'ues' => [
        [
            'code_ue' => 'UE1',
            'lib_ue' => 'Systèmes d\'Information Avancés',
            'credit_ue' => 6,
            'moyenne_ue' => 14.5,
            'ecues' => [
                [
                    'code_ecue' => 'ECUE1.1',
                    'lib_ecue' => 'Architectures SI',
                    'credit' => 3,
                    'note_cc' => 15,
                    'note_ex' => 14,
                    'moyenne' => 14.5
                ],
                [
                    'code_ecue' => 'ECUE1.2',
                    'lib_ecue' => 'ERP et CRM',
                    'credit' => 3,
                    'note_cc' => 14,
                    'note_ex' => 15,
                    'moyenne' => 14.5
                ]
            ]
        ],
        [
            'code_ue' => 'UE2',
            'lib_ue' => 'Gestion de Projet',
            'credit_ue' => 6,
            'moyenne_ue' => 13.0,
            'ecues' => [
                [
                    'code_ecue' => 'ECUE2.1',
                    'lib_ecue' => 'Management de Projet',
                    'credit' => 3,
                    'note_cc' => 13,
                    'note_ex' => 13,
                    'moyenne' => 13.0
                ],
                [
                    'code_ecue' => 'ECUE2.2',
                    'lib_ecue' => 'Méthodologies Agiles',
                    'credit' => 3,
                    'note_cc' => 12,
                    'note_ex' => 14,
                    'moyenne' => 13.0
                ]
            ]
        ]
    ],
    'moyenneGenerale' => 13.75,
    'creditsAcquis' => 12,
    'creditsTotal' => 30,
    'rang' => 5,
    'effectif' => 45
];

try {
    DocumentGeneratorService::generateGradeReportPdf($gradeData, 'test_releve.pdf', 'F');
    echo "✓ Grade report generated successfully: test_releve.pdf\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

// Test 3: Defense Report
echo "3. Testing Defense Report PDF...\n";
$defenseData = [
    'anneeAcademique' => '2024-2025',
    'dateSoutenance' => '15/01/2025',
    'heureSoutenance' => '14h00',
    'lieuSoutenance' => 'Salle de conférence - UFR MI',
    'nomComplet' => 'KOUAME Jean-Baptiste',
    'numeroEtudiant' => 'ETU20240001',
    'niveau' => 'Master 2 MIAGE',
    'titreMémoire' => 'Conception et développement d\'une plateforme de gestion des soutenances universitaires',
    'entreprise' => 'CheckMaster Solutions',
    'jury' => [
        [
            'nom' => 'Prof. YAPI Kouadio',
            'grade' => 'Professeur Titulaire',
            'role' => 'Président'
        ],
        [
            'nom' => 'Dr. KOFFI Marie',
            'grade' => 'Maître de Conférences',
            'role' => 'Directeur de mémoire'
        ],
        [
            'nom' => 'Dr. DIABATE Seydou',
            'grade' => 'Maître Assistant',
            'role' => 'Rapporteur'
        ]
    ],
    'noteDocument' => 15,
    'noteOrale' => 16,
    'noteDefense' => 14,
    'noteFinale' => 15.0,
    'observations' => 'Excellent travail. La candidate a fait preuve d\'une grande maîtrise du sujet et d\'une bonne capacité d\'analyse.'
];

try {
    DocumentGeneratorService::generateDefenseReportPdf($defenseData, 'test_pv.pdf', 'F');
    echo "✓ Defense report generated successfully: test_pv.pdf\n\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n\n";
}

echo "PDF Generation Tests Complete!\n";
echo "Check the generated files: test_recu.pdf, test_releve.pdf, test_pv.pdf\n";
