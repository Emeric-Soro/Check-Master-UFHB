<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/permissions.php';

class GestionReclamationsController {

    private $baseViewPath;
    private $reclamationModel;
    private $auditLog;

    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/gestion_reclamations/';
        $this->reclamationModel = new Reclamation();
        $this->auditLog = new AuditLog(Database::getConnection());

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
            // Récupérer les statistiques pour usage dans la vue
            global $statistiquesReclamations, $reclamationsRecentes;

            $statistiquesReclamations = $this->reclamationModel->getStatistiques();
            $reclamationsRecentes = $this->reclamationModel->getTous(5, 0);

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
            // Préparer les données pour la vue
            global $typesReclamation, $erreurs;

            $typesReclamation = [
                'academic' => 'Problème académique',
                'administrative' => 'Problème administratif',
                'technical' => 'Problème technique',
                'financial' => 'Problème financier',
                'other' => 'Autre'
            ];

            $erreurs = $_SESSION['erreurs_form'] ?? [];
            unset($_SESSION['erreurs_form']);
        }
    }

    private function traiterSoumissionReclamation()
    {
        try {
            // Debug : afficher les données reçues
            error_log("POST data: " . print_r($_POST, true));
            error_log("SESSION data: " . print_r($_SESSION, true));

            // Récupérer les données du formulaire
            $donneesReclamation = [
                'titre' => $_POST['objet'] ?? '',
                'description' => $_POST['content'] ?? '',
                'type' => $this->mapTypeFromForm($_POST['type'] ?? ''),
                'priorite' => 'Moyenne' // Par défaut
            ];

            // Validation des données
            $erreurs = $this->validerDonneesReclamation($donneesReclamation);

            if (!empty($erreurs)) {
                $_SESSION['erreurs_form'] = $erreurs;
                header('Location: ?page=gestion_reclamations&action=soumettre_reclamation');
                exit;
            }

            // Préparer les données pour la base
                if (!isset($_SESSION['num_etu'])) {
                    // Tentative de récupération via l'email si pas trouvé
                    $this->recupererNumEtu();

                    if (!isset($_SESSION['num_etu'])) {
                        throw new Exception("Impossible de récupérer votre numéro d'étudiant. Veuillez vous reconnecter.");
                    }
                }

                $donnees = [
                    'num_etu' => $_SESSION['num_etu'],
                    'titre' => trim($donneesReclamation['titre']),
                    'description' => strip_tags(trim($donneesReclamation['description'])),
                    'type' => $donneesReclamation['type'],
                'priorite' => $donneesReclamation['priorite']
            ];

            // Debug : afficher les données préparées
            error_log("Données pour insertion: " . print_r($donnees, true));

            // Créer la réclamation
            $reclamationId = $this->reclamationModel->creer($donnees);

            if ($reclamationId) {
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'reclamation', 'Succès');
                $this->afficherMessage('Réclamation soumise avec succès. Numéro de référence : REC-' . $reclamationId, 'success');
                header('Location: ?page=gestion_reclamations');
                exit;
            } else {
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'reclamation', 'Erreur');
                $this->afficherMessage('Erreur lors de la soumission de la réclamation. Veuillez réessayer.', 'error');
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

    private function recupererNumEtu() {
        try {
            if (!isset($_SESSION['login_utilisateur'])) {
                return false;
            }

            $db = Database::getConnection();
            $query = "SELECT num_etu FROM etudiants WHERE email_etu = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $_SESSION['login_utilisateur']);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $_SESSION['num_etu'] = $result['num_etu'];
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du num_etu: " . $e->getMessage());
            return false;
        }
    }

    private function mapTypeFromForm($type) {
        $map = [
            'academic' => 'Académique',
            'administrative' => 'Administrative',
            'technical' => 'Technique',
            'financial' => 'Financière',
            'other' => 'Autre'
        ];
        return $map[$type] ?? 'Autre';
    }

    private function validerDonneesReclamation($donnees)
    {
        $erreurs = [];

        if (empty($donnees['titre']) || strlen(trim($donnees['titre'])) < 5) {
            $erreurs['titre'] = 'Le titre doit contenir au moins 5 caractères.';
        }

        if (empty($donnees['description']) || strlen(strip_tags(trim($donnees['description']))) < 20) {
            $erreurs['description'] = 'La description doit contenir au moins 20 caractères.';
        }

        if (empty($donnees['type']) || !in_array($donnees['type'], ['Académique', 'Administrative', 'Technique', 'Financière', 'Autre'])) {
            $erreurs['type'] = 'Veuillez sélectionner un type de réclamation valide.';
        }

        return $erreurs;
    }

    //=============================SUIVI RECLAMATION=============================
    public function suiviHistoriqueReclamations()
    {
        try {
            global $reclamations, $statistiques, $totalPages, $page, $totalReclamations, $filtresActuels;

            $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;

            // Filtres
            $filtres = [];
            if (isset($_GET['status']) && $_GET['status'] !== 'all') {
                $statusMap = [
                    'en_attente' => 'En attente',
                    'en_cours' => 'En cours',
                    'resolue' => 'Résolue',
                    'rejetee' => 'Rejetée'
                ];
                if (isset($statusMap[$_GET['status']])) {
                    $filtres['statut'] = $statusMap[$_GET['status']];
                }
            }

            if (isset($_GET['type']) && $_GET['type'] !== 'all') {
                $typeMap = [
                    'academic' => 'Académique',
                    'administrative' => 'Administrative',
                    'technical' => 'Technique',
                    'financial' => 'Financière'
                ];
                if (isset($typeMap[$_GET['type']])) {
                    $filtres['type'] = $typeMap[$_GET['type']];
                }
            }

            // Récupérer les réclamations de l'étudiant connecté
            $reclamations = $this->reclamationModel->getTous(1000, 0); // Grande limite
            $reclamations = array_filter($reclamations, function($rec) {
                return $rec['num_etu'] == $_SESSION['num_etu'];
            });
                $totalReclamations = count($reclamations);

            // Appliquer les filtres manuellement
                if (!empty($filtres)) {
                    $reclamations = array_filter($reclamations, function($rec) use ($filtres) {
                        if (isset($filtres['statut']) && $rec['statut_reclamation'] !== $filtres['statut']) {
                            return false;
                        }
                        if (isset($filtres['type']) && $rec['type_reclamation'] !== $filtres['type']) {
                            return false;
                        }
                        return true;
                    });
                }

                $reclamations = array_slice($reclamations, $offset, $limit);

            $totalPages = ceil($totalReclamations / $limit);

            // Calculer les statistiques
            $statistiques = $this->calculerStatistiques();

            // Données pour la vue
            $filtresActuels = $_GET;

        } catch (Exception $e) {
            $this->afficherErreur("Erreur lors du chargement du suivi : " . $e->getMessage());
        }
    }

    private function calculerStatistiques()
    {
        // Récupérer les réclamations de l'étudiant connecté
        $reclamations = $this->reclamationModel->getTous(1000, 0); // Grande limite pour avoir toutes les réclamations
        
        // Filtrer pour ne garder que celles de l'étudiant connecté
        $reclamations = array_filter($reclamations, function($rec) {
            return $rec['num_etu'] == $_SESSION['num_etu'];
        });

        $stats = [
            'total' => count($reclamations),
            'en_attente' => 0,
            'en_cours' => 0,
            'resolue' => 0,
            'rejetee' => 0
        ];

        foreach ($reclamations as $rec) {
            switch ($rec['statut_reclamation']) {
                case 'En attente':
                    $stats['en_attente']++;
                    break;
                case 'En cours':
                    $stats['en_cours']++;
                    break;
                case 'Résolue':
                    $stats['resolue']++;
                    break;
                case 'Rejetée':
                    $stats['rejetee']++;
                    break;
            }
        }

        return $stats;
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

    private function verifierDroitsAdmin()
    {
        $groupesAdmin = [5, 6, 7, 8]; // Admins, secrétaires, etc.
        return in_array($_SESSION['groupe_utilisateur'] ?? 0, $groupesAdmin);
    }

    public function exporterReclamations()
    {
        if (!$this->verifierDroitsAdmin()) {
            $this->afficherErreur("Accès non autorisé.");
            return;
        }

        try {
            $reclamations = $this->reclamationModel->getTous(1000, 0); // Limite élevée pour export

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
        // Empêcher le chargement du layout pour les actions AJAX
        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo "ID de la réclamation manquant";
            return;
        }

        $reclamationId = $_GET['id'];

        // Récupérer la réclamation
        $reclamation = $this->reclamationModel->getParId($reclamationId);

        if (!$reclamation) {
            http_response_code(404);
            echo "Réclamation non trouvée";
            return;
        }

        // Vérifier que l'étudiant est propriétaire de la réclamation
        if ($reclamation['num_etu'] != $_SESSION['num_etu']) {
            http_response_code(403);
            echo "Accès non autorisé";
            return;
        }

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