<?php

declare(strict_types=1);

use CheckMaster\Services\EvaluationS3Service;
use CheckMaster\Services\EvaluationS3ImportService;
use CheckMaster\Services\UeReferentielService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

test('Référentiel M2/S1: les alias S3, S9 et M2 S1 convergent', function () {
    $service = new UeReferentielService(\Database::getConnection());
    assertEquals('M2_S1', $service->normalizeSemester('S3'));
    assertEquals('M2_S1', $service->normalizeSemester('S9'));
    assertEquals('M2_S1', $service->normalizeSemester('M2 S1'));
});

test('Évaluations M2/S1: le calcul note × crédit / 20 est pondéré', function () {
    $pdo = \Database::getConnection();
    $student = $pdo->query('SELECT num_carte_etud FROM etudiants ORDER BY num_carte_etud LIMIT 1')->fetchColumn();
    $year = $pdo->query('SELECT id_annee_acad FROM annee_academique ORDER BY date_deb DESC LIMIT 1')->fetchColumn();
    assertTrue($student !== false && $year !== false, 'étudiant et année disponibles');

    $pdo->beginTransaction();
    try {
        $code = 'TEST-' . strtoupper(bin2hex(random_bytes(3)));
        $insertUe = $pdo->prepare(
            'INSERT INTO ue (code_ue, libelle_ue, credit_ue, date_credit, semestre_code, version_ue, actif)
             VALUES (:code, :label, 4, CURDATE(), "M2_S1", 1, 1)'
        );
        $insertUe->execute([':code' => $code, ':label' => 'UE de test calcul']);
        $ueId = (int) $pdo->lastInsertId();
        $insertEval = $pdo->prepare(
            'INSERT INTO evaluation_s3 (num_etu, id_ue, id_annee_acad, note_obtenue_ue, date_note, session_normale)
             VALUES (:student, :ue, :year, 15, CURDATE(), 1)'
        );
        $insertEval->execute([':student' => $student, ':ue' => $ueId, ':year' => $year]);

        $service = new EvaluationS3Service($pdo, new UeReferentielService($pdo));
        $grid = $service->getGrid((string) $student, (int) $year, [[
            'id_ue' => $ueId,
            'code_ue' => $code,
            'libelle_ue' => 'UE de test calcul',
            'credit_ue' => 4,
            'ordre_ue' => 1,
        ]]);
        assertEquals(3.0, (float) $grid['normal_total']);
        assertEquals(15.0, (float) $grid['normal_average']);
    } finally {
        $pdo->rollBack();
    }
});

test('Référentiel UE: une version antérieure reste sélectionnable par sa date', function () {
    $service = new UeReferentielService(\Database::getConnection());
    $ues = $service->getActiveUes(date('Y-m-d'));
    assertTrue(is_array($ues), 'liste UE');
});

test('Évaluations M2/S1: le référentiel signale une maquette différente de 30 crédits', function () {
    $pdo = \Database::getConnection();
    $student = $pdo->query('SELECT num_carte_etud FROM etudiants ORDER BY num_carte_etud LIMIT 1')->fetchColumn();
    $year = $pdo->query('SELECT id_annee_acad FROM annee_academique ORDER BY date_deb DESC LIMIT 1')->fetchColumn();
    assertTrue($student !== false && $year !== false, 'étudiant et année disponibles');

    $service = new EvaluationS3Service($pdo, new UeReferentielService($pdo));
    $grid = $service->getGrid((string) $student, (int) $year, [[
        'id_ue' => 999999,
        'code_ue' => 'TEST-30',
        'libelle_ue' => 'UE de contrôle',
        'credit_ue' => 4,
        'ordre_ue' => 1,
    ]]);
    assertEquals(4.0, (float) $grid['expected_credits']);
    assertFalse((bool) $grid['credits_are_compliant']);
});

test('Tableau de bord commission: les membres sont identifiés par source', function () {
    $service = new \CheckMaster\Services\DashboardCommissionService(\Database::getConnection());
    foreach ($service->getObservationMembers() as $member) {
        assertTrue(str_starts_with((string) ($member['member_key'] ?? ''), 'u:') || str_starts_with((string) ($member['member_key'] ?? ''), 'e:'));
        assertTrue(array_key_exists('nb_recus', $member));
        assertTrue(array_key_exists('nb_observations', $member));
    }
});

test('Import Excel M2/S1: prévisualisation et confirmation idempotente', function () {
    $pdo = \Database::getConnection();
    $student = $pdo->query('SELECT num_carte_etud FROM etudiants ORDER BY num_carte_etud LIMIT 1')->fetchColumn();
    $year = $pdo->query('SELECT id_annee_acad FROM annee_academique ORDER BY date_deb DESC LIMIT 1')->fetchColumn();
    assertTrue($student !== false && $year !== false, 'étudiant et année disponibles');

    $code = 'IMP-' . strtoupper(bin2hex(random_bytes(3)));
    $pdo->prepare(
        'INSERT INTO ue (code_ue, libelle_ue, credit_ue, date_credit, semestre_code, version_ue, actif)
         VALUES (:code, :label, 3, CURDATE(), "M2_S1", 1, 1)'
    )->execute([':code' => $code, ':label' => 'UE import test']);
    $ueId = (int) $pdo->lastInsertId();
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cm_eval_' . bin2hex(random_bytes(4)) . '.xlsx';
    $batchId = 0;
    $againId = 0;

    try {
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray([
            ['annee_academique', 'semestre', 'identifiant_etudiant', 'code_ue', 'note', 'date_evaluation', 'session_normale'],
            [(string) $year, 'S9', (string) $student, $code, '14,5', date('d/m/Y'), 'OUI'],
        ], null, 'A1');
        (new Xlsx($book))->save($file);

        $ues = new UeReferentielService($pdo);
        $evaluations = new EvaluationS3Service($pdo, $ues);
        $imports = new EvaluationS3ImportService($pdo, $evaluations, $ues);
        $batch = $imports->previewUploadedFile([
            'name' => basename($file), 'tmp_name' => $file, 'error' => UPLOAD_ERR_OK, 'size' => filesize($file),
        ], 0);
        $batchId = (int) $batch['id_batch'];
        assertEquals(1, (int) $batch['lignes_valides']);
        assertEquals(0, (int) $batch['lignes_erreur']);

        $confirmed = $imports->confirm($batchId, 0);
        assertEquals(1, (int) $confirmed['imported']);
        $again = $imports->previewUploadedFile([
            'name' => basename($file), 'tmp_name' => $file, 'error' => UPLOAD_ERR_OK, 'size' => filesize($file),
        ], 0);
        assertEquals(1, (int) $again['lignes_valides']);
        $againId = (int) $again['id_batch'];
        $pdo->prepare('DELETE FROM evaluation_s3_import_batch WHERE id_batch IN (?, ?)')->execute([$batchId, $againId]);
    } finally {
        $pdo->prepare('DELETE FROM evaluation_s3 WHERE id_ue = ?')->execute([$ueId]);
        if ($batchId > 0) {
            $pdo->prepare('DELETE FROM evaluation_s3_import_batch WHERE id_batch = ?')->execute([$batchId]);
        }
        $pdo->prepare('DELETE FROM ue WHERE id_ue = ?')->execute([$ueId]);
        @unlink($file);
    }
});
