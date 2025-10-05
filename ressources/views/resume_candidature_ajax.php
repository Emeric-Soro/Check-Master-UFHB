<?php
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['num_etu'])) {
    echo json_encode(['error' => 'Paramètre manquant']);
    exit;
}

$num_etu = $_GET['num_etu'];
$db = Database::getConnection();
$etudiant = new Etudiant($db);
$resume = $etudiant->getResumeCandidature($num_etu);

if ($resume) {
    echo json_encode([
        'resume' => $resume['resume_json'],
        'decision' => $resume['decision'],
        'date_resume' => $resume['date_enregistrement']
    ]);
} else {
    echo json_encode(['resume' => null]);
} 