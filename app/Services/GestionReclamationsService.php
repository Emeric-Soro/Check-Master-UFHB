<?php
namespace CheckMaster\Services;

use CheckMaster\Core\AppConfig;
use CheckMaster\Core\Messages;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../utils/EmailService.php';
require_once __DIR__ . '/../utils/NotificationService.php';

use Reclamation;
use AuditLog;
use Database;
use App\Support\DatabaseService;
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

    private DatabaseService $dbService;

    private $emailService;

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
        $this->dbService = new DatabaseService(\Database::getConnection());
        $this->emailService = new \EmailService();
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
            $erreurs['titre'] = Messages::get('validation.min_length', ['min' => 5]);
        }

        if (empty($donnees['description']) || strlen(strip_tags(trim($donnees['description']))) < 20) {
            $erreurs['description'] = Messages::get('validation.min_length', ['min' => 20]);
        }

        if (empty($donnees['type']) || !in_array($donnees['type'], self::VALID_TYPES)) {
            $erreurs['type'] = Messages::get('error.invalid_input');
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
            $result = $this->dbService->selectOne(
                "SELECT num_carte_etud FROM etudiants WHERE email_etu = :email LIMIT 1",
                [':email' => $email]
            );
            return $result ? $result['num_carte_etud'] : null;
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
                'message'        => Messages::get('business.claim_submitted') . ' Référence : REC-' . $reclamationId,
            ];
        }

        $this->auditLog->logCreation($idUtilisateur, 'reclamation', 'Erreur');
        return [
            'success'        => false,
            'reclamationId'  => null,
            'message'        => Messages::get('error.generic'),
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

    // ===================== HISTORIQUE =====================

    /**
     * Récupère l'historique des actions pour une réclamation.
     *
     * @param int $reclamationId
     * @return array
     */
    public function getHistoriqueReclamation(int $reclamationId): array
    {
        try {
            return $this->dbService->select(
                "SELECT al.action, al.date_creation AS date_action,
                         COALESCE(u.nom_utilisateur, u.login_utilisateur) AS nom_etu,
                         '' AS prenom_etu,
                         NULL AS commentaire
                  FROM pister al
                  LEFT JOIN utilisateur u ON al.id_utilisateur = u.id_utilisateur
                  WHERE al.contexte = 'reclamation'
                    AND al.id_piste = :id
                  ORDER BY al.date_creation DESC",
                [':id' => $reclamationId]
            );
        } catch (Exception $e) {
            error_log('Erreur getHistoriqueReclamation: ' . $e->getMessage());
            return [];
        }
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
                'error'        => Messages::get('error.not_found'),
                'httpCode'     => 404,
            ];
        }

        if ($reclamation['num_etu'] != $numEtu) {
            return [
                'success'      => false,
                'reclamation'  => null,
                'error'        => Messages::get('auth.unauthorized'),
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

    public function notifierReclamationSoumise(int $idReclamation, string $objet, string $type, string $numEtu): void
    {
        try {
            $etu = $this->dbService->selectOne(
                "SELECT prenom_etu, nom_etu, email_etu FROM etudiants WHERE num_etu = :num_etu",
                [':num_etu' => $numEtu]
            );
            $nomEtudiant = $etu ? trim(($etu['prenom_etu'] ?? '') . ' ' . ($etu['nom_etu'] ?? '')) : $numEtu;

            $notifService = new \NotificationService();
            $notifService->sendToUserGroups([5, 6, 7, 8], 'RECLAMATION_SOUMISE_ADMIN', [
                'nom_etudiant' => htmlspecialchars($nomEtudiant, ENT_QUOTES, 'UTF-8'),
                'objet' => htmlspecialchars($objet, ENT_QUOTES, 'UTF-8'),
                'type' => htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
                'id' => $idReclamation,
                'admin_url' => AppConfig::appUrl() . '/?page=gestion_reclamations_scolarite',
            ]);
        } catch (\Exception $e) {
            error_log('Erreur notifierReclamationSoumise: ' . $e->getMessage());
        }
    }

    public function notifierReclamationStatut(int $idReclamation, string $objet, string $statut, string $numEtu, string $commentaire = ''): void
    {
        try {
            $etu = $this->dbService->selectOne(
                "SELECT prenom_etu, nom_etu, email_etu FROM etudiants WHERE num_etu = :num_etu",
                [':num_etu' => $numEtu]
            );
            if (!$etu || empty($etu['email_etu'])) {
                return;
            }
            $nom = trim(($etu['prenom_etu'] ?? '') . ' ' . ($etu['nom_etu'] ?? ''));
            $couleurs = ['En attente' => '#f59e0b', 'En cours' => '#3b82f6', 'Resolue' => '#10b981', 'Rejetee' => '#ef4444'];

            $data = [
                'nom' => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'id' => $idReclamation,
                'objet' => htmlspecialchars($objet, ENT_QUOTES, 'UTF-8'),
                'statut' => htmlspecialchars($statut, ENT_QUOTES, 'UTF-8'),
                'statut_couleur' => $couleurs[$statut] ?? '#64748b',
            ];
            if ($commentaire !== '') {
                $data['commentaire'] = '<p style="background: #f1f5f9; padding: 12px; border-radius: 6px; margin-top: 8px;">' . htmlspecialchars($commentaire, ENT_QUOTES, 'UTF-8') . '</p>';
            } else {
                $data['commentaire'] = '';
            }
            $this->emailService->sendTemplate('RECLAMATION_STATUT', $etu['email_etu'], $data);
        } catch (\Exception $e) {
            error_log('Erreur notifierReclamationStatut: ' . $e->getMessage());
        }
    }

    public function notifierReclamationReponse(int $idReclamation, string $objet, string $reponse, string $numEtu): void
    {
        try {
            $etu = $this->dbService->selectOne(
                "SELECT prenom_etu, nom_etu, email_etu FROM etudiants WHERE num_etu = :num_etu",
                [':num_etu' => $numEtu]
            );
            if (!$etu || empty($etu['email_etu'])) {
                return;
            }
            $nom = trim(($etu['prenom_etu'] ?? '') . ' ' . ($etu['nom_etu'] ?? ''));
            $this->emailService->sendTemplate('RECLAMATION_REPONSE', $etu['email_etu'], [
                'nom' => htmlspecialchars($nom, ENT_QUOTES, 'UTF-8'),
                'id' => $idReclamation,
                'objet' => htmlspecialchars($objet, ENT_QUOTES, 'UTF-8'),
                'reponse' => nl2br(htmlspecialchars($reponse, ENT_QUOTES, 'UTF-8')),
            ]);
        } catch (\Exception $e) {
            error_log('Erreur notifierReclamationReponse: ' . $e->getMessage());
        }
    }
}
