<?php
/**
 * Endpoint AJAX pour recuperer le resume d'une candidature
 *
 * GET ?id_candidature=X → JSON avec toutes les infos candidature
 * Jointure avec etudiants, resume_candidature, rapport_etudiants
 */

declare(strict_types=1);

// --- Securite : empecher l'acces direct si pas en contexte AJAX ---
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Acces direct non autorise.']);
    exit;
}

// --- Demarrage session si pas deja fait ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Verification authentification ---
if (!isset($_SESSION['id_utilisateur'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Authentification requise.']);
    exit;
}

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/utils/permissions_helper.php';

try {
    $db = Database::getConnection();

    // Recuperer l'ID candidature
    $idCandidature = isset($_GET['id_candidature']) ? (int) $_GET['id_candidature'] : 0;

    if ($idCandidature <= 0) {
        http_response_code(400);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'ID candidature invalide.']);
        exit;
    }

    // Requete avec jointures
    $sql = "SELECT
                cs.id_candidature,
                cs.num_etu,
                cs.date_candidature,
                cs.statut_candidature,
                cs.observations,
                cs.date_traitement,
                e.nom_etu,
                e.prenom_etu,
                e.num_carte_etud,
                e.promotion_etu,
                e.email_etu,
                e.telephone_etu,
                rc.resume_json,
                rc.decision AS resume_decision,
                rc.date_enregistrement AS resume_date,
                re.id_rapport,
                re.theme_rapport,
                re.statut_rapport,
                re.date_redaction_rapport,
                re.chemin_fichier,
                COALESCE(re.note_rapport, 0) AS note_rapport,
                COALESCE(re.note_soutenance, 0) AS note_soutenance
            FROM candidature_soutenance cs
            JOIN etudiants e ON cs.num_etu = e.num_carte_etud
            LEFT JOIN resume_candidature rc ON cs.id_candidature = rc.id_candidature
            LEFT JOIN rapport_etudiants re ON cs.num_etu = re.num_etu
            WHERE cs.id_candidature = ?
            ORDER BY rc.date_enregistrement DESC, re.date_redaction_rapport DESC
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->execute([$idCandidature]);
    $candidature = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$candidature) {
        http_response_code(404);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['success' => false, 'message' => 'Candidature introuvable.']);
        exit;
    }

    // Decoder le JSON du resume si present
    if (!empty($candidature['resume_json'])) {
        $candidature['resume_json_decode'] = json_decode($candidature['resume_json'], true);
    } else {
        $candidature['resume_json_decode'] = null;
    }

    // Formater les dates
    $candidature['date_candidature_formatted'] = !empty($candidature['date_candidature'])
        ? date('d/m/Y H:i', strtotime($candidature['date_candidature']))
        : '-';
    $candidature['date_traitement_formatted'] = !empty($candidature['date_traitement'])
        ? date('d/m/Y H:i', strtotime($candidature['date_traitement']))
        : '-';

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'data' => $candidature,
    ]);
    exit;

} catch (Exception $e) {
    error_log('Erreur resume_candidature_ajax: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['success' => false, 'message' => 'Erreur interne.']);
    exit;
}
