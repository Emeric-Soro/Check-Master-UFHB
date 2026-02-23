<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Scolarite.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/AuditLog.php';

use Scolarite;
use AnneeAcademique;
use AuditLog;

/**
 * Service métier de la gestion de la scolarité
 *
 * Contient toute la logique métier pour :
 * - Récupération des listes de référence (étudiants, niveaux, années, versements)
 * - Enregistrement de versements
 * - Mise à jour de versements (type Tranche uniquement)
 * - Enregistrement de paiements (nouvelle inscription ou versement complémentaire)
 * - Audit logging des opérations
 */
class GestionScolariteService
{
    /** @var Scolarite */
    private $scolariteModel;

    /** @var AnneeAcademique */
    private $anneeAcademique;

    /** @var AuditLog */
    private $auditLog;

    /**
     * @param \PDO $db Connexion à la base de données
     */
    public function __construct($db)
    {
        $this->scolariteModel = new Scolarite($db);
        $this->anneeAcademique = new AnneeAcademique($db);
        $this->auditLog = new AuditLog($db);
    }

    /**
     * Récupère toutes les listes de référence nécessaires aux vues
     *
     * @return array Tableau associatif des listes
     */
    public function getReferenceLists(): array
    {
        return [
            'etudiantsNonInscrits' => $this->scolariteModel->getEtudiantsNonInscrits(),
            'niveaux' => $this->scolariteModel->getNiveauxEtudes(),
            'etudiantsInscrits' => $this->scolariteModel->getEtudiantsInscrits(),
            'listeAllEtudiant' => $this->scolariteModel->getAllEtudiants(),
            'listeAnnees' => $this->anneeAcademique->getAllAnneeAcademiques(),
            'listeVersement' => $this->scolariteModel->getAllVersements(),
        ];
    }

    /**
     * Récupère les informations d'un étudiant par son numéro
     *
     * @param string $numEtu Numéro de l'étudiant
     * @return mixed
     */
    public function getInfoEtudiant(string $numEtu)
    {
        return $this->scolariteModel->getInfoEtudiant($numEtu);
    }

    /**
     * Récupère un versement par son identifiant et vérifie s'il est modifiable
     *
     * @param int $id Identifiant du versement
     * @return array ['success' => bool, 'versement' => array|null, 'message' => string]
     */
    public function getVersementModifiable(int $id): array
    {
        $versement = $this->scolariteModel->getVersementById($id);
        if ($versement && $versement['type_versement'] === 'Tranche') {
            return ['success' => true, 'versement' => $versement, 'message' => ''];
        }
        return ['success' => false, 'versement' => null, 'message' => 'Ce versement ne peut pas être modifié.'];
    }

    /**
     * Enregistre un versement pour un étudiant inscrit
     *
     * @param array $data Données du formulaire (id_etudiant, montant, methode_paiement)
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function enregistrerVersement(array $data, int $userId): array
    {
        try {
            // Validation des données
            if (empty($data['id_etudiant']) || empty($data['montant']) || empty($data['methode_paiement'])) {
                return ['success' => false, 'message' => 'Tous les champs sont obligatoires.', 'data' => null];
            }

            // Récupérer l'inscription de l'étudiant
            $inscription = $this->scolariteModel->getInscriptionByEtudiantId($data['id_etudiant']);
            if (!$inscription) {
                return ['success' => false, 'message' => 'Aucune inscription trouvée pour cet étudiant.', 'data' => null];
            }

            // Vérifier si l'étudiant a déjà soldé sa scolarité
            if ($inscription['reste_a_payer'] <= 0) {
                return ['success' => false, 'message' => 'Cet étudiant a déjà soldé sa scolarité.', 'data' => null];
            }

            // Vérifier si le montant du versement ne dépasse pas le reste à payer
            $montant = floatval($data['montant']);
            if ($montant > $inscription['reste_a_payer']) {
                return [
                    'success' => false,
                    'message' => 'Le montant du versement ne peut pas dépasser le reste à payer (' . number_format($inscription['reste_a_payer'], 2) . ' FCFA).',
                    'data' => null
                ];
            }

            // Préparer les données du versement
            $versementData = [
                'id_inscription' => $inscription['id_inscription'],
                'montant' => $montant,
                'methode_paiement' => $data['methode_paiement']
            ];

            // Enregistrer le versement
            if ($this->scolariteModel->addVersement($versementData)) {
                $this->auditLog->logCreation($userId, 'versements', 'Succès');

                // Récupérer les informations mises à jour
                $inscriptionMiseAJour = $this->scolariteModel->getInscriptionByEtudiantId($data['id_etudiant']);
                $resultData = null;
                if ($inscriptionMiseAJour) {
                    $resultData = [
                        'montantTotal' => $inscriptionMiseAJour['montant_scolarite'],
                        'montantPaye' => $inscriptionMiseAJour['montant_inscription'],
                        'resteAPayer' => $inscriptionMiseAJour['reste_a_payer'],
                    ];
                }
                return ['success' => true, 'message' => 'Versement enregistré avec succès.', 'data' => $resultData];
            } else {
                $this->auditLog->logCreation($userId, 'versements', 'Erreur');
                return ['success' => false, 'message' => "Erreur lors de l'enregistrement du versement.", 'data' => null];
            }
        } catch (\Exception $e) {
            error_log('Erreur dans enregistrerVersement : ' . $e->getMessage());
            return ['success' => false, 'message' => "Une erreur est survenue lors de l'enregistrement du versement.", 'data' => null];
        }
    }

    /**
     * Met à jour un versement existant (type Tranche uniquement)
     *
     * @param array $data Données du formulaire (id_versement, montant, methode_paiement)
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string, 'data' => array|null]
     */
    public function mettreAJourVersement(array $data, int $userId): array
    {
        try {
            // Validation des données
            if (empty($data['id_versement']) || empty($data['montant']) || empty($data['methode_paiement'])) {
                return ['success' => false, 'message' => 'Tous les champs sont obligatoires.', 'data' => null];
            }

            // Récupérer le versement pour vérifier son type
            $versement = $this->scolariteModel->getVersementById($data['id_versement']);
            if (!$versement) {
                return ['success' => false, 'message' => 'Versement introuvable.', 'data' => null];
            }

            // Vérifier si le versement est de type "Tranche"
            if ($versement['type_versement'] !== 'Tranche') {
                return ['success' => false, 'message' => "Seuls les versements de type 'Tranche' peuvent être modifiés.", 'data' => null];
            }

            // Récupérer l'inscription associée au versement
            $inscription = $this->scolariteModel->getInscriptionById($versement['id_inscription']);
            if (!$inscription) {
                return ['success' => false, 'message' => 'Inscription introuvable.', 'data' => null];
            }

            $ancienMontant = floatval($versement['montant']);
            $nouveauMontant = floatval($data['montant']);
            $difference = $ancienMontant - $nouveauMontant;

            if ($ancienMontant != $nouveauMontant) {
                // Vérifier si le nouveau montant total ne dépasse pas le montant de scolarité
                $montantTotalPaye = floatval($inscription['montant_paye']) - $difference;
                $montantScolarite = floatval($inscription['montant_total']);

                if ($montantTotalPaye > $montantScolarite) {
                    return [
                        'success' => false,
                        'message' => 'Le montant total des versements ne peut pas dépasser le montant de scolarité (' . number_format($montantScolarite, 2) . ' FCFA).',
                        'data' => null
                    ];
                }

                // Préparer les données de mise à jour
                $updateData = [
                    'montant' => $nouveauMontant,
                    'difference' => $difference,
                    'methode_paiement' => $data['methode_paiement']
                ];

                // Mettre à jour le versement
                if ($this->scolariteModel->updateVersement($data['id_versement'], $updateData)) {
                    $this->auditLog->logModification($userId, 'versement', 'Succès');

                    // Récupérer les informations mises à jour
                    $inscriptionMiseAJour = $this->scolariteModel->getInscriptionById($inscription['id_inscription']);
                    $resultData = null;
                    if ($inscriptionMiseAJour) {
                        $resultData = [
                            'montantTotal' => floatval($inscriptionMiseAJour['montant_scolarite'] ?? 0),
                            'montantPaye' => floatval($inscriptionMiseAJour['montant_paye'] ?? 0),
                            'resteAPayer' => floatval($inscriptionMiseAJour['reste_a_payer'] ?? 0),
                        ];
                    }
                    return ['success' => true, 'message' => 'Versement mis à jour avec succès.', 'data' => $resultData];
                } else {
                    $this->auditLog->logModification($userId, 'versements', 'Erreur');
                    return ['success' => false, 'message' => 'Erreur lors de la mise à jour du versement.', 'data' => null];
                }
            } else {
                // Si le montant n'a pas changé, on met juste à jour la méthode de paiement
                $updateData = [
                    'montant' => $data['montant'],
                    'difference' => $difference,
                    'methode_paiement' => $data['methode_paiement']
                ];

                if ($this->scolariteModel->updateVersement($data['id_versement'], $updateData)) {
                    $this->auditLog->logModification($userId, 'versements', 'Succès');
                    return ['success' => true, 'message' => 'Méthode de paiement mise à jour avec succès.', 'data' => null];
                } else {
                    $this->auditLog->logModification($userId, 'versements', 'Erreur');
                    return ['success' => false, 'message' => 'Erreur lors de la mise à jour de la méthode de paiement.', 'data' => null];
                }
            }
        } catch (\Exception $e) {
            error_log('Erreur dans mettreAJourVersement : ' . $e->getMessage());
            return ['success' => false, 'message' => "Une erreur est survenue lors de la mise à jour du versement.", 'data' => null];
        }
    }

    /**
     * Enregistre un paiement (nouvelle inscription ou versement complémentaire)
     *
     * @param array $data Données du formulaire
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string, 'refreshLists' => bool]
     */
    public function enregistrerPaiement(array $data, int $userId): array
    {
        try {
            $isNewInscription = isset($data['is_new_inscription']) && $data['is_new_inscription'] === 'true';

            if ($isNewInscription) {
                return $this->enregistrerNouvelleInscription($data, $userId);
            } else {
                return $this->enregistrerVersementComplementaire($data, $userId);
            }
        } catch (\Exception $e) {
            error_log('Erreur dans enregistrerPaiement : ' . $e->getMessage());
            return ['success' => false, 'message' => '❌ Erreur : ' . $e->getMessage(), 'refreshLists' => false];
        }
    }

    /**
     * Enregistre une nouvelle inscription avec premier versement
     *
     * @param array $data Données du formulaire
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string, 'refreshLists' => bool]
     */
    private function enregistrerNouvelleInscription(array $data, int $userId): array
    {
        if (
            empty($data['etudiant']) || empty($data['niveau']) ||
            empty($data['annee_academique']) || empty($data['montant_versement']) ||
            empty($data['methode_paiement'])
        ) {
            return ['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.', 'refreshLists' => false];
        }

        $id_etudiant = $data['etudiant'];
        $id_niveau = $data['niveau'];
        $id_annee_acad = $data['annee_academique'];
        $montant_premier_versement = floatval($data['montant_versement']);
        $methode_paiement = $data['methode_paiement'];
        $num_piece = isset($data['num_piece']) ? $data['num_piece'] : null;

        $inscriptionExistante = $this->scolariteModel->getDerniereInscription($id_etudiant);
        if ($inscriptionExistante && $inscriptionExistante['id_annee_acad'] == $id_annee_acad) {
            return ['success' => false, 'message' => 'Cet étudiant est déjà inscrit pour cette année académique.', 'refreshLists' => false];
        }

        $montant_total = $this->scolariteModel->getMontantScolarite($id_niveau);

        if ($montant_premier_versement > $montant_total) {
            return [
                'success' => false,
                'message' => 'Le montant ne peut pas dépasser le montant total (' . number_format($montant_total, 0, ',', ' ') . ' FCFA).',
                'refreshLists' => false
            ];
        }

        $id_inscription = $this->scolariteModel->creerInscription(
            $id_etudiant,
            $id_niveau,
            $id_annee_acad,
            $montant_premier_versement,
            $methode_paiement,
            $num_piece
        );

        if ($id_inscription) {
            $reste_a_payer = $montant_total - $montant_premier_versement;
            $this->auditLog->logCreation($userId, 'inscriptions', 'Succès');
            return [
                'success' => true,
                'message' => '✅ Inscription créée avec succès ! Versement de ' . number_format($montant_premier_versement, 0, ',', ' ') . ' FCFA. Reste : ' . number_format($reste_a_payer, 0, ',', ' ') . ' FCFA.',
                'refreshLists' => true
            ];
        } else {
            $this->auditLog->logCreation($userId, 'inscriptions', 'Erreur');
            return ['success' => false, 'message' => "❌ Erreur lors de la création de l'inscription.", 'refreshLists' => false];
        }
    }

    /**
     * Enregistre un versement complémentaire pour un étudiant déjà inscrit
     *
     * @param array $data Données du formulaire
     * @param int   $userId Identifiant de l'utilisateur connecté
     * @return array ['success' => bool, 'message' => string, 'refreshLists' => bool]
     */
    private function enregistrerVersementComplementaire(array $data, int $userId): array
    {
        if (
            empty($data['etudiant']) || empty($data['montant_versement']) ||
            empty($data['methode_paiement'])
        ) {
            return ['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis.', 'refreshLists' => false];
        }

        $id_etudiant = $data['etudiant'];
        $montant = floatval($data['montant_versement']);
        $methode_paiement = $data['methode_paiement'];
        $num_piece = isset($data['num_piece']) ? $data['num_piece'] : null;

        $derniere_inscription = $this->scolariteModel->getDerniereInscription($id_etudiant);

        if (!$derniere_inscription) {
            return ['success' => false, 'message' => '⚠️ Aucune inscription trouvée.', 'refreshLists' => false];
        }

        $id_niveau = $derniere_inscription['id_niveau'];
        $id_annee_acad = $derniere_inscription['id_annee_acad'];

        $infos_paiement = $this->scolariteModel->getInfosPaiementEtudiant($id_etudiant, $id_annee_acad);

        if (!$infos_paiement) {
            return ['success' => false, 'message' => '⚠️ Impossible de récupérer les informations.', 'refreshLists' => false];
        }

        if ($infos_paiement['reste_a_payer'] <= 0) {
            return ['success' => false, 'message' => '⚠️ Scolarité déjà soldée.', 'refreshLists' => false];
        }

        if ($montant > $infos_paiement['reste_a_payer']) {
            return [
                'success' => false,
                'message' => '❌ Montant supérieur au reste à payer (' . number_format($infos_paiement['reste_a_payer'], 0, ',', ' ') . ' FCFA).',
                'refreshLists' => false
            ];
        }

        $id_inscription = $this->scolariteModel->creerInscription(
            $id_etudiant,
            $id_niveau,
            $id_annee_acad,
            $montant,
            $methode_paiement,
            $num_piece
        );

        if ($id_inscription) {
            $this->auditLog->logCreation($userId, 'inscriptions', 'Succès - Versement');
            $nouveau_reste = $infos_paiement['reste_a_payer'] - $montant;
            return [
                'success' => true,
                'message' => '✅ Versement de ' . number_format($montant, 0, ',', ' ') . ' FCFA enregistré ! Reste : ' . number_format($nouveau_reste, 0, ',', ' ') . ' FCFA.',
                'refreshLists' => true
            ];
        } else {
            $this->auditLog->logCreation($userId, 'inscriptions', 'Erreur');
            return ['success' => false, 'message' => "❌ Erreur lors de l'enregistrement.", 'refreshLists' => false];
        }
    }
}