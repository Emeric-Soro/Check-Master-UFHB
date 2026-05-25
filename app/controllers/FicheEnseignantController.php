<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Services/FicheEnseignantService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\FicheEnseignantService;

/**
 * Contrôleur pour l'écran Fiche Enseignante Complète (P1.2).
 *
 * - index()  : Liste de tous les enseignants avec recherche
 * - fiche()  : Fiche détaillée d'un enseignant
 */
class FicheEnseignantController
{
    private string $baseViewPath;
    private FicheEnseignantService $service;

    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/fiche_enseignante_content.php';
        $this->service = new FicheEnseignantService(Database::getConnection());
    }

    /**
     * Affiche la liste des enseignants (vue index).
     */
    public function index(): void
    {
        // Vérification des permissions (utilise gestion_rh existant)
        if (!canView('gestion_rh')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }

        $listeEnseignants = $this->service->getListeEnseignants();

        // Recherche
        $search = trim((string) ($_GET['search'] ?? ''));
        if ($search !== '') {
            $needle = mb_strtolower($search, 'UTF-8');
            $listeEnseignants = array_values(array_filter($listeEnseignants, static function ($ens) use ($needle) {
                $champs = [
                    $ens['nom_enseignant'] ?? '',
                    $ens['prenom_enseignant'] ?? '',
                    $ens['mail_enseignant'] ?? '',
                    $ens['id_enseignant'] ?? '',
                    $ens['lib_grade'] ?? '',
                    $ens['lib_specialite'] ?? '',
                ];
                foreach ($champs as $champ) {
                    if (mb_stripos($champ, $needle, 0, 'UTF-8') !== false) {
                        return true;
                    }
                }
                return false;
            }));
        }

        // Pagination simple
        $perPage = 20;
        $currentPage = max(1, (int) ($_GET['p'] ?? 1));
        $total = count($listeEnseignants);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $offset = ($currentPage - 1) * $perPage;
        $listePage = array_slice($listeEnseignants, $offset, $perPage);

        $GLOBALS['liste_enseignants'] = $listePage;
        $GLOBALS['search_enseignant'] = $search;
        $GLOBALS['pagination'] = [
            'total' => $total,
            'current' => $currentPage,
            'per_page' => $perPage,
            'last' => $lastPage,
            'offset' => $offset,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $lastPage,
        ];
    }

    /**
     * Affiche la fiche complète d'un enseignant.
     *
     * @param string $id Matricule de l'enseignant
     */
    public function fiche(string $id): void
    {
        // Vérification des permissions
        if (!canView('gestion_rh')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas l'autorisation d'effectuer cette action."]);
                exit;
            }
            $_SESSION['error'] = "Vous n'avez pas l'autorisation d'effectuer cette action.";
            $_SESSION['error_type'] = 'permission_denied';
            header('Location: layout.php?page=access_denied');
            exit;
        }

        if ($id === '') {
            $_SESSION['error'] = "Identifiant enseignant manquant.";
            header('Location: layout.php?page=fiche_enseignante');
            exit;
        }

        $data = $this->service->getFicheComplete($id);

        if (empty($data)) {
            $_SESSION['error'] = "Enseignant introuvable.";
            header('Location: layout.php?page=fiche_enseignante');
            exit;
        }

        $GLOBALS['fiche_data'] = $data;
        $GLOBALS['fiche_enseignant_id'] = $id;
    }
}
