<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../Services/GestionReclamationsService.php';
require_once __DIR__ . '/../utils/permissions_helper.php';

use CheckMaster\Services\GestionReclamationsService;

class GestionReclamationsController {

    private $baseViewPath;
    private $service;

    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/gestion_reclamations/';

        $reclamationModel = new Reclamation();
        $auditLog = new AuditLog(Database::getConnection());
        $this->service = new GestionReclamationsService($reclamationModel, $auditLog);

        // Vérifier que l'utilisateur est connecté et est un étudiant
        if (!isset($_SESSION['num_etu'])) {
            header('Location: page_connexion.php');
            exit;
        }
    }

    // Afficher le dashboard des réclamations
    public function index()
    {
        try {
            global $statistiquesReclamations, $reclamationsRecentes;

            $data = $this->service->getDashboardData();
            $statistiquesReclamations = $data['statistiques'];
            $reclamationsRecentes = $data['reclamationsRecentes'];

        } catch (Exception $e) {
            $this->afficherErreur("Erreur lors du chargement du dashboard : " . $e->getMessage());
        }
    }

    //=============================SOUMETTRE RECLAMATION=============================
    public function soumettreReclamations()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->traiterSoumissionReclamation();
        } else {
            global $typesReclamation, $erreurs;

            $typesReclamation = $this->service->getTypesReclamation();

            $erreurs = $_SESSION['erreurs_form'] ?? [];
            unset($_SESSION['erreurs_form']);
        }
    }

    private function traiterSoumissionReclamation()
    {
        if (!canView('gestion_reclamations')) {
            $this->afficherMessage("Accès non autorisé.", 'error');
            header('Location: ?page=gestion_reclamations');
            exit;
        }
        try {
            error_log("POST data: " . print_r($_POST, true));
            error_log("SESSION data: " . print_r($_SESSION, true));

            $donneesReclamation = $this->service->preparerDonneesFormulaire($_POST);

            $erreurs = $this->service->validerDonneesReclamation($donneesReclamation);

            if (!empty($erreurs)) {
                $_SESSION['erreurs_form'] = $erreurs;
                header('Location: ?page=gestion_reclamations&action=soumettre_reclamation');
                exit;
            }

            // Résoudre le num_etu si absent de la session
            if (!isset($_SESSION['num_etu'])) {
                if (isset($_SESSION['login_utilisateur'])) {
                    $numEtu = $this->service->recupererNumEtuParEmail($_SESSION['login_utilisateur']);
                    if ($numEtu !== null) {
                        $_SESSION['num_etu'] = $numEtu;
                    }
                }

                if (!isset($_SESSION['num_etu'])) {
                    throw new Exception("Impossible de récupérer votre numéro d'étudiant. Veuillez vous reconnecter.");
                }
            }

            $resultat = $this->service->creerReclamation(
                $donneesReclamation,
                $_SESSION['num_etu'],
                (int) $_SESSION['id_utilisateur']
            );

            if ($resultat['success']) {
                $this->afficherMessage($resultat['message'], 'success');
                header('Location: ?page=gestion_reclamations');
                exit;
            } else {
                $this->afficherMessage($resultat['message'], 'error');
                header('Location: ?page=gestion_reclamations&action=soumettre_reclamation');
                exit;
            }

        } catch (Exception $e) {
            error_log("Exception dans traiterSoumissionReclamation: " . $e->getMessage());
            $this->afficherMessage("Erreur lors de la soumission : " . $e->getMessage(), 'error');
            header('Location: ?page=gestion_reclamations&action=soumettre_reclamation');
            exit;
        }
    }

    //=============================SUIVI RECLAMATION=============================
    public function suiviHistoriqueReclamations()
    {
        try {
            global $reclamations, $statistiques, $totalPages, $page, $totalReclamations, $filtresActuels;

            $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
            $limit = 10;

            $filtres = $this->service->construireFiltres($_GET);

            $suiviData = $this->service->getSuiviReclamations(
                $_SESSION['num_etu'],
                $page,
                $limit,
                $filtres
            );

            $reclamations = $suiviData['reclamations'];
            $totalReclamations = $suiviData['totalReclamations'];
            $totalPages = $suiviData['totalPages'];
            $page = $suiviData['page'];

            $statistiques = $this->service->calculerStatistiquesEtudiant($_SESSION['num_etu']);

            $filtresActuels = $_GET;

        } catch (Exception $e) {
            $this->afficherErreur("Erreur lors du chargement du suivi : " . $e->getMessage());
        }
    }

    //=============================MÉTHODES UTILITAIRES=============================
    private function afficherMessage($message, $type = 'info')
    {
        $_SESSION['message'] = ['text' => $message, 'type' => $type];
    }

    private function afficherErreur($message)
    {
        $this->afficherMessage($message, 'error');
    }

    public function exporterReclamations()
    {
        if (!$this->service->verifierDroitsAdmin((int) ($_SESSION['groupe_utilisateur'] ?? 0))) {
            $this->afficherErreur("Accès non autorisé.");
            return;
        }

        try {
            $reclamations = $this->service->getReclamationsPourExport();

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=reclamations_' . date('Y-m-d') . '.csv');

            $output = fopen('php://output', 'w');

            // En-têtes CSV
            fputcsv($output, [
                'ID', 'Titre', 'Type', 'Statut', 'Priorité',
                'Date création', 'Demandeur', 'Admin assigné'
            ]);

            // Données
            foreach ($reclamations as $rec) {
                fputcsv($output, [
                    $rec['id_reclamation'],
                    $rec['titre_reclamation'],
                    $rec['type_reclamation'],
                    $rec['statut_reclamation'],
                    $rec['priorite_reclamation'],
                    $rec['date_creation'],
                    $rec['nom_etu'] ?? $rec['nom_utilisateur'],
                    $rec['nom_admin_assigne']
                ]);
            }

            fclose($output);
        } catch (Exception $e) {
            $this->afficherErreur("Erreur lors de l'export : " . $e->getMessage());
        }
    }

    public function getReclamationDetailsAjax()
    {
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo "ID de la réclamation manquant";
            return;
        }

        $result = $this->service->getReclamationDetails((int) $_GET['id'], $_SESSION['num_etu']);

        if (!$result['success']) {
            http_response_code($result['httpCode']);
            echo $result['error'];
            return;
        }

        $reclamation = $result['reclamation'];

        // Générer le HTML pur sans layout
        ob_start();
        ?>
<div class="space-y-6">
    <!-- Informations principales -->
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Informations de la réclamation</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><strong>Numéro :</strong> REC-<?php echo $reclamation['id_reclamation']; ?></div>
            <div><strong>Date de création :</strong>
                <?php echo date('d/m/Y à H:i', strtotime($reclamation['date_creation'])); ?></div>
            <div><strong>Type :</strong> <?php echo htmlspecialchars($reclamation['type_reclamation']); ?></div>
            <div><strong>Statut :</strong> <?php echo htmlspecialchars($reclamation['statut_reclamation']); ?></div>
            <div><strong>Priorité :</strong> <?php echo htmlspecialchars($reclamation['priorite_reclamation']); ?></div>
            <?php if ($reclamation['date_mise_a_jour']): ?>
            <div><strong>Dernière mise à jour :</strong>
                <?php echo date('d/m/Y à H:i', strtotime($reclamation['date_mise_a_jour'])); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Titre et description -->
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Détails de la réclamation</h3>
        <div class="mb-4">
            <strong class="block text-sm font-medium text-gray-700 mb-1">Titre :</strong>
            <p class="text-gray-900"><?php echo htmlspecialchars($reclamation['titre_reclamation']); ?></p>
        </div>
        <div>
            <strong class="block text-sm font-medium text-gray-700 mb-1">Description :</strong>
            <div class="text-gray-900 bg-gray-50 p-3 rounded-lg">
                <?php echo nl2br(htmlspecialchars($reclamation['description_reclamation'])); ?></div>
        </div>
    </div>

    <!-- Historique des actions -->
    <?php if (!empty($historique)): ?>
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Historique des actions</h3>
        <div class="space-y-3">
            <?php foreach ($historique as $action): ?>
            <div class="border-l-4 border-blue-500 pl-4 py-2">
                <div class="flex justify-between items-start">
                    <div>
                        <strong class="text-gray-800"><?php echo htmlspecialchars($action['action']); ?></strong>
                        <p class="text-sm text-gray-600">
                            Par <?php echo htmlspecialchars($action['nom_etu'] . ' ' . $action['prenom_etu']); ?>
                        </p>
                        <?php if (!empty($action['commentaire'])): ?>
                        <p class="text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars($action['commentaire'])); ?></p>
                        <?php endif; ?>
                    </div>
                    <span
                        class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($action['date_action'])); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
        $html = ob_get_clean();
        echo $html;
    }
 
}
