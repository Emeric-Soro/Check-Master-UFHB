<?php

namespace App\Controllers;

use PDO;
use App\Models\Reclamation;
use App\Models\AuditLog;
use App\Utils\SecurityUtils;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * GestionReclamationsController - Création/Suivi réclamations (Côté Étudiant)
 * 
 * Ce contrôleur gère les réclamations côté étudiant :
 * - Affichage du dashboard des réclamations
 * - Soumission de nouvelles réclamations
 * - Suivi et historique des réclamations
 * 
 * @package App\Controllers
 */
class GestionReclamationsController
{
    private PDO $pdo;
    private Reclamation $reclamationModel;
    private AuditLog $auditLog;
    private SecurityUtils $security;
    private LoggerInterface $logger;
    private string $baseViewPath;

    /**
     * Constructeur avec Injection de Dépendances
     */
    public function __construct(
        PDO $pdo,
        Reclamation $reclamationModel,
        AuditLog $auditLog,
        SecurityUtils $security,
        LoggerInterface $logger
    ) {
        $this->pdo = $pdo;
        $this->reclamationModel = $reclamationModel;
        $this->auditLog = $auditLog;
        $this->security = $security;
        $this->logger = $logger;
        $this->baseViewPath = __DIR__ . '/../../ressources/views/gestion_reclamations/';

        // Vérifier que l'utilisateur est connecté et est un étudiant
        if (!isset($_SESSION['num_etu'])) {
            header('Location: page_connexion.php');
            exit;
        }
    }

    /**
     * Vérification centralisée des permissions
     */
    private function checkPermission(string $action): bool
    {
        $idGroupe = $_SESSION['id_GU'] ?? 0;
        
        if (!$this->security->can($idGroupe, 'gestion_reclamations', $action)) {
            $this->logger->warning(
                "Accès refusé ({$action}) pour user " . ($_SESSION['id_utilisateur'] ?? 'inconnu') . " sur gestion_reclamations"
            );
            
            $this->afficherMessage("Vous n'avez pas les droits nécessaires.", 'error');
            if (file_exists(__DIR__ . '/../../ressources/views/errors/403.php')) {
                http_response_code(403);
                require __DIR__ . '/../../ressources/views/errors/403.php';
            }
            return false;
        }
        return true;
    }

    /**
     * Action : Afficher le dashboard des réclamations (READ)
     */
    public function index(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            global $statistiquesReclamations, $reclamationsRecentes;

            $statistiquesReclamations = $this->reclamationModel->getStatistiques();
            $reclamationsRecentes = $this->reclamationModel->getTous(5, 0);
        } catch (Exception $e) {
            $this->logger->error("Erreur index GestionReclamations: " . $e->getMessage());
            $this->afficherErreur("Erreur lors du chargement du dashboard.");
        }
    }

    /**
     * Action : Soumettre une nouvelle réclamation (CREATE)
     */
    public function soumettreReclamations(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->checkPermission('create')) {
                return;
            }
            $this->traiterSoumissionReclamation();
        } else {
            if (!$this->checkPermission('read')) {
                return;
            }
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

    /**
     * Traite la soumission d'une réclamation
     */
    private function traiterSoumissionReclamation(): void
    {
        try {
            // Récupérer les données du formulaire
            $donneesReclamation = [
                'titre' => $this->security->sanitizeInput($_POST['objet'] ?? ''),
                'description' => $_POST['content'] ?? '',
                'type' => $this->mapTypeFromForm($this->security->sanitizeInput($_POST['type'] ?? '')),
                'priorite' => 'Moyenne'
            ];

            // Validation des données
            $erreurs = $this->validerDonneesReclamation($donneesReclamation);

            if (!empty($erreurs)) {
                $_SESSION['erreurs_form'] = $erreurs;
                header('Location: ?page=gestion_reclamations&action=soumettre_reclamation');
                exit;
            }

            // Récupérer le numéro d'étudiant
            if (!isset($_SESSION['num_etu'])) {
                $this->recupererNumEtu();

                if (!isset($_SESSION['num_etu'])) {
                    throw new Exception("Impossible de récupérer votre numéro d'étudiant.");
                }
            }

            $donnees = [
                'num_etu' => $_SESSION['num_etu'],
                'titre' => trim($donneesReclamation['titre']),
                'description' => strip_tags(trim($donneesReclamation['description'])),
                'type' => $donneesReclamation['type'],
                'priorite' => $donneesReclamation['priorite']
            ];

            // Créer la réclamation
            $reclamationId = $this->reclamationModel->creer($donnees);

            if ($reclamationId) {
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'reclamation', 'Succès');
                $this->logger->info("Réclamation {$reclamationId} créée par étudiant " . $_SESSION['num_etu']);
                $this->afficherMessage('Réclamation soumise avec succès. Numéro de référence : REC-' . $reclamationId, 'success');
                header('Location: ?page=gestion_reclamations');
                exit;
            } else {
                $this->auditLog->logCreation($_SESSION['id_utilisateur'], 'reclamation', 'Erreur');
                $this->afficherMessage('Erreur lors de la soumission de la réclamation.', 'error');
                header('Location: ?page=gestion_reclamations&action=soumettre_reclamation');
                exit;
            }

        } catch (Exception $e) {
            $this->logger->error("Erreur traiterSoumissionReclamation: " . $e->getMessage());
            $this->afficherMessage("Erreur lors de la soumission : " . $e->getMessage(), 'error');
            header('Location: ?page=gestion_reclamations&action=soumettre_reclamation');
            exit;
        }
    }

    /**
     * Récupère le numéro d'étudiant via l'email
     */
    private function recupererNumEtu(): bool
    {
        try {
            if (!isset($_SESSION['login_utilisateur'])) {
                return false;
            }

            $query = "SELECT num_etu FROM etudiants WHERE email_etu = :email";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':email', $_SESSION['login_utilisateur']);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $_SESSION['num_etu'] = $result['num_etu'];
                return true;
            }
            return false;
        } catch (Exception $e) {
            $this->logger->error("Erreur recupererNumEtu: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mappe le type du formulaire vers le format base de données
     */
    private function mapTypeFromForm(string $type): string
    {
        $map = [
            'academic' => 'Académique',
            'administrative' => 'Administrative',
            'technical' => 'Technique',
            'financial' => 'Financière',
            'other' => 'Autre'
        ];
        return $map[$type] ?? 'Autre';
    }

    /**
     * Valide les données de la réclamation
     */
    private function validerDonneesReclamation(array $donnees): array
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

    /**
     * Action : Suivi et historique des réclamations (READ)
     */
    public function suiviHistoriqueReclamations(): void
    {
        if (!$this->checkPermission('read')) {
            return;
        }

        try {
            global $reclamations, $statistiques, $totalPages, $page, $totalReclamations, $filtresActuels;

            $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;

            // Filtres
            $filtres = $this->preparerFiltres();

            // Récupérer les réclamations de l'étudiant connecté
            $reclamations = $this->reclamationModel->getTous(1000, 0);
            $reclamations = array_filter($reclamations, function($rec) {
                return $rec['num_etu'] == $_SESSION['num_etu'];
            });
            $totalReclamations = count($reclamations);

            // Appliquer les filtres
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
            $statistiques = $this->calculerStatistiques();
            $filtresActuels = $_GET;

        } catch (Exception $e) {
            $this->logger->error("Erreur suiviHistoriqueReclamations: " . $e->getMessage());
            $this->afficherErreur("Erreur lors du chargement du suivi.");
        }
    }

    /**
     * Prépare les filtres à partir des paramètres GET
     */
    private function preparerFiltres(): array
    {
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

        return $filtres;
    }

    /**
     * Calcule les statistiques des réclamations de l'étudiant
     */
    private function calculerStatistiques(): array
    {
        $reclamations = $this->reclamationModel->getTous(1000, 0);
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

    /**
     * Affiche un message à l'utilisateur
     */
    private function afficherMessage(string $message, string $type = 'info'): void
    {
        $_SESSION['message'] = ['text' => $message, 'type' => $type];
    }

    /**
     * Affiche un message d'erreur
     */
    private function afficherErreur(string $message): void
    {
        $this->afficherMessage($message, 'error');
    }

    /**
     * Action : Récupère les détails d'une réclamation (READ - AJAX)
     */
    public function getReclamationDetailsAjax(): void
    {
        if (!$this->checkPermission('read')) {
            http_response_code(403);
            echo "Accès refusé";
            return;
        }

        if (!isset($_GET['id'])) {
            http_response_code(400);
            echo "ID de la réclamation manquant";
            return;
        }

        try {
            $reclamationId = (int)$_GET['id'];
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

            // Générer le HTML
            ob_start();
            $this->renderReclamationDetails($reclamation);
            $html = ob_get_clean();
            echo $html;
            
        } catch (Exception $e) {
            $this->logger->error("Erreur getReclamationDetailsAjax: " . $e->getMessage());
            http_response_code(500);
            echo "Erreur serveur";
        }
    }

    /**
     * Génère le HTML pour les détails d'une réclamation
     */
    private function renderReclamationDetails(array $reclamation): void
    {
        ?>
<div class="space-y-6">
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Informations de la réclamation</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><strong>Numéro :</strong> REC-<?php echo htmlspecialchars($reclamation['id_reclamation']); ?></div>
            <div><strong>Date de création :</strong> <?php echo date('d/m/Y à H:i', strtotime($reclamation['date_creation'])); ?></div>
            <div><strong>Type :</strong> <?php echo htmlspecialchars($reclamation['type_reclamation']); ?></div>
            <div><strong>Statut :</strong> <?php echo htmlspecialchars($reclamation['statut_reclamation']); ?></div>
            <div><strong>Priorité :</strong> <?php echo htmlspecialchars($reclamation['priorite_reclamation']); ?></div>
            <?php if (!empty($reclamation['date_mise_a_jour'])): ?>
            <div><strong>Dernière mise à jour :</strong> <?php echo date('d/m/Y à H:i', strtotime($reclamation['date_mise_a_jour'])); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-3">Détails de la réclamation</h3>
        <div class="mb-4">
            <strong class="block text-sm font-medium text-gray-700 mb-1">Titre :</strong>
            <p class="text-gray-900"><?php echo htmlspecialchars($reclamation['titre_reclamation']); ?></p>
        </div>
        <div>
            <strong class="block text-sm font-medium text-gray-700 mb-1">Description :</strong>
            <div class="text-gray-900 bg-gray-50 p-3 rounded-lg">
                <?php echo nl2br(htmlspecialchars($reclamation['description_reclamation'])); ?>
            </div>
        </div>
    </div>
</div>
        <?php
    }
}
