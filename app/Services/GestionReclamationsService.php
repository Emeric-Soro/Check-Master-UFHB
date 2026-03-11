<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../models/AuditLog.php';

use Reclamation;
use AuditLog;
use Database;
use PDO;
use Exception;

/**
 * Service métier pour la gestion des réclamations
 *
 * Contient toute la logique métier extraite du contrôleur :
 * - Récupération des statistiques et réclamations récentes (dashboard)
 * - Validation et soumission de réclamations
 * - Suivi / historique avec filtres et pagination
 * - Calcul de statistiques par étudiant
 * - Export CSV
 * - Récupération des détails d'une réclamation
 */
class GestionReclamationsService
{
    /** @var Reclamation */
    private $reclamationModel;

    /** @var AuditLog */
    private $auditLog;

    /** @var array Types de réclamation affichés dans le formulaire */
    private const TYPES_RECLAMATION = [
        'academic'       => 'Problème académique',
        'administrative' => 'Problème administratif',
        'technical'      => 'Problème technique',
        'financial'      => 'Problème financier',
        'other'          => 'Autre',
    ];

    /** @var array Correspondance formulaire → base de données */
    private const TYPE_MAP = [
        'academic'       => 'Académique',
        'administrative' => 'Administrative',
        'technical'      => 'Technique',
        'financial'      => 'Financière',
        'other'          => 'Autre',
    ];

    /** @var array Correspondance filtre → libellé statut */
    private const STATUS_MAP = [
        'en_attente' => 'En attente',
        'en_cours'   => 'En cours',
        'resolue'    => 'Résolue',
        'rejetee'    => 'Rejetée',
    ];

    /** @var array Correspondance filtre → libellé type */
    private const TYPE_FILTER_MAP = [
        'academic'       => 'Académique',
        'administrative' => 'Administrative',
        'technical'      => 'Technique',
        'financial'      => 'Financière',
    ];

    /** @var array Types valides en base */
    private const VALID_TYPES = ['Académique', 'Administrative', 'Technique', 'Financière', 'Autre'];

    /**
     * @param Reclamation $reclamationModel
     * @param AuditLog    $auditLog
     */
    public function __construct(Reclamation $reclamationModel, AuditLog $auditLog)
    {
        $this->reclamationModel = $reclamationModel;
        $this->auditLog = $auditLog;
    }

    // ===================== DASHBOARD =====================

    /**
     * Récupère les données du dashboard (statistiques globales et réclamations récentes).
     *
     * @return array{statistiques: array, reclamationsRecentes: array}
     */
    public function getDashboardData(): array
    {
        return [
            'statistiques'         => $this->reclamationModel->getStatistiques(),
            'reclamationsRecentes' => $this->reclamationModel->getTous(5, 0),
        ];
    }

    // ===================== SOUMISSION =====================

    /**
     * Retourne la liste des types de réclamation pour le formulaire.
     *
     * @return array
     */
    public function getTypesReclamation(): array
    {
        return self::TYPES_RECLAMATION;
    }

    /**
     * Convertit un type de formulaire en libellé base de données.
     *
     * @param string $type
     * @return string
     */
    public function mapTypeFromForm(string $type): string
    {
        return self::TYPE_MAP[$type] ?? 'Autre';
    }

    /**
     * Valide les données d'une réclamation.
     *
     * @param array $donnees ['titre', 'description', 'type', 'priorite']
     * @return array Tableau d'erreurs (vide si valide)
     */
    public function validerDonneesReclamation(array $donnees): array
    {
        $erreurs = [];

        if (empty($donnees['titre']) || strlen(trim($donnees['titre'])) < 5) {
            $erreurs['titre'] = 'Le titre doit contenir au moins 5 caractères.';
        }

        if (empty($donnees['description']) || strlen(strip_tags(trim($donnees['description']))) < 20) {
            $erreurs['description'] = 'La description doit contenir au moins 20 caractères.';
        }

        if (empty($donnees['type']) || !in_array($donnees['type'], self::VALID_TYPES)) {
            $erreurs['type'] = 'Veuillez sélectionner un type de réclamation valide.';
        }

        return $erreurs;
    }

    /**
     * Prépare les données POST en tableau de réclamation prêt à valider.
     *
     * @param array $postData Données $_POST
     * @return array
     */
    public function preparerDonneesFormulaire(array $postData): array
    {
        return [
            'titre'       => $postData['objet'] ?? '',
            'description' => $postData['content'] ?? '',
            'type'        => $this->mapTypeFromForm($postData['type'] ?? ''),
            'priorite'    => 'Moyenne',
        ];
    }

    /**
     * Tente de récupérer le num_etu d'un étudiant à partir de son email de session.
     *
     * @param string $email
     * @return string|null Le num_etu ou null si introuvable
     */
    public function recupererNumEtuParEmail(string $email): ?string
    {
        try {
            $db = \Database::getConnection();
            $query = "SELECT num_etu FROM etudiants WHERE email_etu = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['num_etu'] : null;
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du num_etu: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée une réclamation en base et journalise l'opération.
     *
     * @param array  $donneesReclamation Données validées ['titre','description','type','priorite']
     * @param string $numEtu             Numéro étudiant
     * @param int    $idUtilisateur      ID de l'utilisateur connecté
     * @return array{success: bool, reclamationId: int|null, message: string}
     */
    public function creerReclamation(array $donneesReclamation, string $numEtu, int $idUtilisateur): array
    {
        $donnees = [
            'num_etu'     => $numEtu,
            'titre'       => trim($donneesReclamation['titre']),
            'description' => strip_tags(trim($donneesReclamation['description'])),
            'type'        => $donneesReclamation['type'],
            'priorite'    => $donneesReclamation['priorite'],
        ];

        error_log("Données pour insertion: " . print_r($donnees, true));

        $reclamationId = $this->reclamationModel->creer($donnees);

        if ($reclamationId) {
            $this->auditLog->logCreation($idUtilisateur, 'reclamation', 'Succès');
            return [
                'success'        => true,
                'reclamationId'  => $reclamationId,
                'message'        => 'Réclamation soumise avec succès. Numéro de référence : REC-' . $reclamationId,
            ];
        }

        $this->auditLog->logCreation($idUtilisateur, 'reclamation', 'Erreur');
        return [
            'success'        => false,
            'reclamationId'  => null,
            'message'        => 'Erreur lors de la soumission de la réclamation. Veuillez réessayer.',
        ];
    }

    // ===================== SUIVI / HISTORIQUE =====================

    /**
     * Construit les filtres à partir des paramètres GET.
     *
     * @param array $queryParams $_GET
     * @return array Filtres applicables
     */
    public function construireFiltres(array $queryParams): array
    {
        $filtres = [];

        if (isset($queryParams['status']) && $queryParams['status'] !== 'all') {
            if (isset(self::STATUS_MAP[$queryParams['status']])) {
                $filtres['statut'] = self::STATUS_MAP[$queryParams['status']];
            }
        }

        if (isset($queryParams['type']) && $queryParams['type'] !== 'all') {
            if (isset(self::TYPE_FILTER_MAP[$queryParams['type']])) {
                $filtres['type'] = self::TYPE_FILTER_MAP[$queryParams['type']];
            }
        }

        return $filtres;
    }

    /**
     * Récupère les réclamations paginées et filtrées pour un étudiant.
     *
     * @param string $numEtu      Numéro étudiant
     * @param int    $page        Page courante (1-based)
     * @param int    $limit       Nombre par page
     * @param array  $filtres     Filtres ['statut' => ..., 'type' => ...]
     * @return array{reclamations: array, totalReclamations: int, totalPages: int, page: int}
     */
    public function getSuiviReclamations(string $numEtu, int $page, int $limit, array $filtres): array
    {
        $offset = ($page - 1) * $limit;

        // Récupérer toutes les réclamations et filtrer par étudiant
        $reclamations = $this->reclamationModel->getTous(1000, 0);
        $reclamations = array_filter($reclamations, function ($rec) use ($numEtu) {
            return $rec['num_etu'] == $numEtu;
        });

        $totalReclamations = count($reclamations);

        // Appliquer les filtres manuellement
        if (!empty($filtres)) {
            $reclamations = array_filter($reclamations, function ($rec) use ($filtres) {
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
        $totalPages = (int) ceil($totalReclamations / $limit);

        return [
            'reclamations'      => $reclamations,
            'totalReclamations' => $totalReclamations,
            'totalPages'        => $totalPages,
            'page'              => $page,
        ];
    }

    /**
     * Calcule les statistiques des réclamations pour un étudiant.
     *
     * @param string $numEtu
     * @return array{total: int, en_attente: int, en_cours: int, resolue: int, rejetee: int}
     */
    public function calculerStatistiquesEtudiant(string $numEtu): array
    {
        $reclamations = $this->reclamationModel->getTous(1000, 0);

        $reclamations = array_filter($reclamations, function ($rec) use ($numEtu) {
            return $rec['num_etu'] == $numEtu;
        });

        $stats = [
            'total'      => count($reclamations),
            'en_attente' => 0,
            'en_cours'   => 0,
            'resolue'    => 0,
            'rejetee'    => 0,
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

    // ===================== EXPORT =====================

    /**
     * Vérifie si l'utilisateur a les droits administrateur.
     *
     * @param int $groupeUtilisateur
     * @return bool
     */
    public function verifierDroitsAdmin(int $groupeUtilisateur): bool
    {
        $groupesAdmin = [5, 6, 7, 8];
        return in_array($groupeUtilisateur, $groupesAdmin);
    }

    /**
     * Récupère toutes les réclamations pour l'export.
     *
     * @return array
     */
    public function getReclamationsPourExport(): array
    {
        return $this->reclamationModel->getTous(1000, 0);
    }

    // ===================== DÉTAILS =====================

    /**
     * Récupère les détails d'une réclamation et vérifie la propriété.
     *
     * @param int    $reclamationId
     * @param string $numEtu
     * @return array{success: bool, reclamation: array|null, error: string|null, httpCode: int}
     */
    public function getReclamationDetails(int $reclamationId, string $numEtu): array
    {
        $reclamation = $this->reclamationModel->getParId($reclamationId);

        if (!$reclamation) {
            return [
                'success'      => false,
                'reclamation'  => null,
                'error'        => 'Réclamation non trouvée',
                'httpCode'     => 404,
            ];
        }

        if ($reclamation['num_etu'] != $numEtu) {
            return [
                'success'      => false,
                'reclamation'  => null,
                'error'        => 'Accès non autorisé',
                'httpCode'     => 403,
            ];
        }

        return [
            'success'      => true,
            'reclamation'  => $reclamation,
            'error'        => null,
            'httpCode'     => 200,
        ];
    }
}
