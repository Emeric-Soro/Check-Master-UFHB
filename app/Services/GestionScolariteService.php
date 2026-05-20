<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Scolarite.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../Services/Document/DocumentStorageService.php';
require_once __DIR__ . '/../utils/AcademicYear.php';

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
    /** @var \PDO */
    private $db;

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
        $this->db = $db;
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
        $selectedYearId = \AcademicYear::getSelectedIdFromSession();
        $etudiantsInscrits = $this->scolariteModel->getEtudiantsInscrits();
        $listeAllEtudiant = $this->scolariteModel->getAllEtudiants();
        $listeVersement = $this->scolariteModel->getAllVersements();

        if ($selectedYearId !== null && $selectedYearId > 0) {
            $etudiantsInscrits = array_values(array_filter($etudiantsInscrits, static function (array $row) use ($selectedYearId): bool {
                return (int) ($row['id_annee_acad'] ?? 0) === $selectedYearId;
            }));

            $listeVersement = array_values(array_filter($listeVersement, static function (array $row) use ($selectedYearId): bool {
                $label = trim((string) ($row['date_deb'] ?? '')) !== '' && trim((string) ($row['date_fin'] ?? '')) !== ''
                    ? date('Y', strtotime((string) $row['date_deb'])) . '-' . date('Y', strtotime((string) $row['date_fin']))
                    : '';

                return $label !== '' && $label === \AcademicYear::getSelectedLabelFromSession();
            }));
        }

        $inscritsByStudent = [];
        foreach ($etudiantsInscrits as $row) {
            $studentId = (string) ($row['num_ident_etud'] ?? $row['num_carte_etud'] ?? '');
            if ($studentId !== '') {
                $inscritsByStudent[$studentId] = true;
            }
        }

        $etudiantsNonInscrits = array_values(array_filter($listeAllEtudiant, static function (array $row) use ($inscritsByStudent): bool {
            $studentId = (string) ($row['num_ident_etud'] ?? $row['num_carte_etud'] ?? '');
            return $studentId !== '' && !isset($inscritsByStudent[$studentId]);
        }));

        // Récupérer l'année d'écriture pour avoir les montants de frais_inscription
        $writableYearId = \AcademicYear::getWritableIdFromSession();

        return [
            'etudiantsNonInscrits' => $etudiantsNonInscrits,
            'niveaux' => $this->scolariteModel->getNiveauxEtudes($writableYearId),
            'etudiantsInscrits' => $etudiantsInscrits,
            'listeAllEtudiant' => $listeAllEtudiant,
            'listeAnnees' => $this->anneeAcademique->getAllAnneeAcademiques(),
            'listeVersement' => $listeVersement,
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

            $writeGuard = \AcademicYear::ensureWritableYear($this->db, $inscription['id_annee_acad'] ?? null, 'un versement');
            if (!$writeGuard['success']) {
                return ['success' => false, 'message' => $writeGuard['message'], 'data' => null];
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

            // Préparer les données du versement avec la nouvelle structure
            $versementData = [
                'num_carte_etud' => $inscription['num_carte_etud'],
                'id_niv_etude' => $inscription['id_niv_etude'],
                'id_annee_acad' => $inscription['id_annee_acad'],
                'montant' => $montant,
                'methode_paiement' => $data['methode_paiement'],
                'num_piece' => $data['num_piece'] ?? null
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

            $writeGuard = \AcademicYear::ensureWritableYear($this->db, $inscription['id_annee_acad'] ?? null, 'un versement');
            if (!$writeGuard['success']) {
                return ['success' => false, 'message' => $writeGuard['message'], 'data' => null];
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
    public function enregistrerPaiement(array $data, array $files, int $userId): array
    {
        try {
            $isNewInscription = isset($data['is_new_inscription']) && $data['is_new_inscription'] === 'true';

            if ($isNewInscription) {
                return $this->enregistrerNouvelleInscription($data, $files, $userId);
            } else {
                return $this->enregistrerVersementComplementaire($data, $files, $userId);
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
    private function enregistrerNouvelleInscription(array $data, array $files, int $userId): array
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

        $writeGuard = \AcademicYear::ensureWritableYear($this->db, $id_annee_acad, "une inscription");
        if (!$writeGuard['success']) {
            return ['success' => false, 'message' => $writeGuard['message'], 'refreshLists' => false];
        }

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

        $uploadValidationError = $this->validateInscriptionUploadsForSubmission($files);
        if ($uploadValidationError !== null) {
            return ['success' => false, 'message' => $uploadValidationError, 'refreshLists' => false];
        }

        $inscriptionSuccess = $this->scolariteModel->creerInscription(
            $id_etudiant,
            $id_niveau,
            $id_annee_acad,
            $montant_premier_versement,
            $methode_paiement,
            $num_piece
        );

        if ($inscriptionSuccess) {
            $reste_a_payer = $montant_total - $montant_premier_versement;
            $this->auditLog->logCreation($userId, 'inscriptions', 'Succès');
            $documentFeedback = $this->processInscriptionDocumentsAfterPayment(
                (string) $id_etudiant,
                (int) $id_annee_acad,
                $files,
                $userId
            );
            return [
                'success' => true,
                'message' => $this->buildPaiementSuccessMessage(
                    '✅ Inscription créée avec succès ! Versement de ' . number_format($montant_premier_versement, 0, ',', ' ') . ' FCFA. Reste : ' . number_format($reste_a_payer, 0, ',', ' ') . ' FCFA.',
                    $documentFeedback
                ),
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
    private function enregistrerVersementComplementaire(array $data, array $files, int $userId): array
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

        $id_niveau = $derniere_inscription['id_niv_etude'] ?? $derniere_inscription['id_niveau'] ?? null;
        $id_annee_acad = $derniere_inscription['id_annee_acad'];

        if ($id_niveau === null || $id_niveau === '') {
            return ['success' => false, 'message' => '⚠️ Niveau d\'inscription introuvable pour cet étudiant.', 'refreshLists' => false];
        }

        $writeGuard = \AcademicYear::ensureWritableYear($this->db, $id_annee_acad, "un versement");
        if (!$writeGuard['success']) {
            return ['success' => false, 'message' => $writeGuard['message'], 'refreshLists' => false];
        }

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

        $uploadValidationError = $this->validateInscriptionUploadsForSubmission($files);
        if ($uploadValidationError !== null) {
            return ['success' => false, 'message' => $uploadValidationError, 'refreshLists' => false];
        }

        $inscriptionSuccess = $this->scolariteModel->creerInscription(
            $id_etudiant,
            $id_niveau,
            $id_annee_acad,
            $montant,
            $methode_paiement,
            $num_piece
        );

        if ($inscriptionSuccess) {
            $this->auditLog->logCreation($userId, 'inscriptions', 'Succès - Versement');
            $nouveau_reste = $infos_paiement['reste_a_payer'] - $montant;
            $documentFeedback = $this->processInscriptionDocumentsAfterPayment(
                (string) $id_etudiant,
                (int) $id_annee_acad,
                $files,
                $userId
            );
            return [
                'success' => true,
                'message' => $this->buildPaiementSuccessMessage(
                    '✅ Versement de ' . number_format($montant, 0, ',', ' ') . ' FCFA enregistré ! Reste : ' . number_format($nouveau_reste, 0, ',', ' ') . ' FCFA.',
                    $documentFeedback
                ),
                'refreshLists' => true
            ];
        } else {
            $this->auditLog->logCreation($userId, 'inscriptions', 'Erreur');
            return ['success' => false, 'message' => "❌ Erreur lors de l'enregistrement.", 'refreshLists' => false];
        }
    }

    /**
     * Pré-valide les documents optionnels avant l'enregistrement du paiement.
     *
     * @param array<string, mixed> $files
     */
    private function validateInscriptionUploadsForSubmission(array $files): ?string
    {
        foreach ($this->getInscriptionDocumentDefinitions() as $field => $definition) {
            $file = $files[$field] ?? null;
            if (!$this->isUploadPresent($file)) {
                continue;
            }

            $error = $this->validateInscriptionUpload(is_array($file) ? $file : [], (string) $definition['label']);
            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getInscriptionDocumentDefinitions(): array
    {
        return [
            'fiche_inscription_file' => [
                'type_document' => 'fiche_inscription',
                'label' => "fiche d'inscription",
                'success_label' => "fiche d'inscription",
                'update_fiche' => true,
            ],
            'recu_inscription_file' => [
                'type_document' => 'recu',
                'label' => "reçu d'inscription",
                'success_label' => "reçu d'inscription",
                'update_fiche' => false,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $feedback
     */
    private function buildPaiementSuccessMessage(string $baseMessage, array $feedback): string
    {
        $message = $baseMessage;
        $messages = is_array($feedback['messages'] ?? null) ? $feedback['messages'] : [];
        $warnings = is_array($feedback['warnings'] ?? null) ? $feedback['warnings'] : [];

        if ($messages !== []) {
            $message .= ' Documents importés : ' . implode(', ', array_map('strval', $messages)) . '.';
        }

        if ($warnings !== []) {
            $message .= ' Avertissement : ' . implode(' ', array_map('strval', $warnings));
        }

        return $message;
    }

    /**
     * @param array<string, mixed> $files
     * @return array{messages: array<int, string>, warnings: array<int, string>}
     */
    private function processInscriptionDocumentsAfterPayment(string $numCarteEtud, int $idAnneeAcad, array $files, int $userId): array
    {
        $idInscription = $this->getLatestInscriptionCompositeId($numCarteEtud, $idAnneeAcad);
        if ($idInscription === null) {
            return [
                'messages' => [],
                'warnings' => ['Impossible de retrouver l\'inscription pour associer les documents.'],
            ];
        }

        $messages = [];
        $warnings = [];

        foreach ($this->getInscriptionDocumentDefinitions() as $field => $definition) {
            $file = $files[$field] ?? null;
            if (!$this->isUploadPresent($file)) {
                continue;
            }

            $result = $this->storeInscriptionDocument(
                $idInscription,
                is_array($file) ? $file : null,
                $userId,
                (string) $definition['type_document'],
                (string) $definition['label'],
                (bool) $definition['update_fiche']
            );

            if (($result['success'] ?? false) === true && ($result['stored'] ?? false) === true) {
                $messages[] = (string) $definition['success_label'];
                continue;
            }

            if (!empty($result['message'])) {
                $warnings[] = (string) $result['message'];
            }
        }

        return [
            'messages' => $messages,
            'warnings' => $warnings,
        ];
    }

    private function getLatestInscriptionCompositeId(string $numCarteEtud, int $idAnneeAcad): ?string
    {
        if ($numCarteEtud === '' || $idAnneeAcad <= 0) {
            return null;
        }

        try {
            $stmt = $this->db->prepare(
                'SELECT MAX(num_versement) AS num_versement
                 FROM inscriptions
                 WHERE num_carte_etud = :num_carte_etud
                   AND id_annee_acad = :id_annee_acad'
            );
            $stmt->execute([
                ':num_carte_etud' => $numCarteEtud,
                ':id_annee_acad' => $idAnneeAcad,
            ]);
            $numVersement = (int) ($stmt->fetchColumn() ?: 0);
            if ($numVersement <= 0) {
                return null;
            }

            return $numCarteEtud . '-' . $idAnneeAcad . '-' . $numVersement;
        } catch (\Throwable $e) {
            error_log('Erreur getLatestInscriptionCompositeId: ' . $e->getMessage());
            return null;
        }
    }

    private function isUploadPresent($file): bool
    {
        if (!is_array($file)) {
            return false;
        }

        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            return false;
        }

        return trim((string) ($file['name'] ?? '')) !== '';
    }

    /**
     * @param array<string, mixed> $file
     */
    private function validateInscriptionUpload(array $file, string $label): ?string
    {
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            return 'Erreur lors du téléchargement de ' . $label . ' (code ' . $errorCode . ').';
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_file($tmpName) || !is_readable($tmpName)) {
            return 'Le fichier pour ' . $label . ' est invalide.';
        }

        $detectedMime = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    $detectedMime = $mime;
                }
            }
        }

        if ($detectedMime === null && function_exists('mime_content_type')) {
            $mime = @mime_content_type($tmpName);
            if (is_string($mime) && $mime !== '') {
                $detectedMime = $mime;
            }
        }

        if ($detectedMime === null) {
            return 'Impossible de déterminer le type du fichier pour ' . $label . '.';
        }

        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($detectedMime, $allowedMimes, true)) {
            return 'Type de fichier non autorisé pour ' . $label . ' (PDF, JPEG, PNG acceptés).';
        }

        $maxSize = 5 * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) > $maxSize) {
            return 'Le fichier pour ' . $label . ' dépasse la taille maximale autorisée (5 Mo).';
        }

        return null;
    }

    private function getUploadExtensionFromMime(string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            default => 'bin',
        };
    }

    /**
     * @param array<string, mixed>|null $file
     * @return array{success: bool, stored: bool, message: string}
     */
    private function storeInscriptionDocument(string $idInscription, ?array $file, int $userId, string $typeDocument, string $label, bool $updateFicheColumn = false): array
    {
        if ($idInscription === '' || !$this->isUploadPresent($file)) {
            return ['success' => true, 'stored' => false, 'message' => ''];
        }

        $fileInfo = $file;
        if ($fileInfo === null) {
            return ['success' => false, 'stored' => false, 'message' => 'Fichier manquant pour ' . $label . '.'];
        }

        $validationError = $this->validateInscriptionUpload($fileInfo, $label);
        if ($validationError !== null) {
            return ['success' => false, 'stored' => false, 'message' => $validationError];
        }

        $tmpName = (string) ($fileInfo['tmp_name'] ?? '');
        $mimeType = null;
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = finfo_file($finfo, $tmpName);
                finfo_close($finfo);
                if (is_string($mime) && $mime !== '') {
                    $mimeType = $mime;
                }
            }
        }
        if ($mimeType === null && function_exists('mime_content_type')) {
            $mime = @mime_content_type($tmpName);
            if (is_string($mime) && $mime !== '') {
                $mimeType = $mime;
            }
        }

        if ($mimeType === null) {
            return ['success' => false, 'stored' => false, 'message' => 'Impossible de déterminer le type du fichier pour ' . $label . '.'];
        }

        $subDir = $typeDocument === 'recu' ? 'recus' : 'fiches';
        $uploadDir = __DIR__ . '/../../ressources/uploads/' . $subDir;

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            error_log("Impossible de créer le dossier: $uploadDir");
            return ['success' => false, 'stored' => false, 'message' => 'Erreur interne lors de la création du dossier de stockage.'];
        }

        $extension = $this->getUploadExtensionFromMime($mimeType);
        $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', $idInscription);
        $safeFilename = $typeDocument . '_' . $safeId . '.' . $extension;
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $safeFilename;

        if (is_file($destination) && !unlink($destination)) {
            return ['success' => false, 'stored' => false, 'message' => 'Impossible de remplacer le fichier existant pour ' . $label . '.'];
        }

        if (!move_uploaded_file($tmpName, $destination)) {
            return ['success' => false, 'stored' => false, 'message' => 'Erreur lors du déplacement du fichier pour ' . $label . '.'];
        }

        $relativePath = $subDir . '/' . $safeFilename;

        if ($updateFicheColumn && !$this->scolariteModel->updateFicheInscription($idInscription, $relativePath)) {
            return ['success' => false, 'stored' => false, 'message' => 'Erreur lors de l\'enregistrement du chemin de la fiche.'];
        }

        try {
            $storage = new \App\Services\Document\DocumentStorageService($this->db, dirname(__DIR__, 2));
            $document = $storage->storeFileFromPath(
                $typeDocument,
                $destination,
                'inscriptions',
                $idInscription,
                $userId > 0 ? $userId : null,
                null,
                $updateFicheColumn ? 'source' : 'upload',
                true
            );

            if ($document === null) {
                return ['success' => false, 'stored' => false, 'message' => 'Le fichier a été copié, mais l\'index documentaire de ' . $label . ' n\'a pas pu être créé.'];
            }
        } catch (\Throwable $e) {
            error_log('Erreur storeInscriptionDocument: ' . $e->getMessage());
            return ['success' => false, 'stored' => false, 'message' => 'Erreur lors de l\'enregistrement documentaire de ' . $label . '.'];
        }

        $this->auditLog->logModification($userId, 'inscriptions', 'Succès - ' . ucfirst(strtolower($label)) . ' importé');

        return [
            'success' => true,
            'stored' => true,
            'message' => ucfirst($label) . ' importé avec succès.',
        ];
    }

    /**
     * Upload de la fiche d'inscription (scan du dossier).
     *
     * @param array $post  Données POST (doit contenir 'id_inscription')
     * @param array $files Fichier uploadé (doit contenir 'fiche')
     * @param int   $userId
     * @return array{success: bool, message: string}
     */
    public function uploadFicheInscription(array $post, array $files, int $userId): array
    {
        $idInscription = isset($post['id_inscription']) ? trim((string) $post['id_inscription']) : '';
        if ($idInscription === '') {
            return ['success' => false, 'message' => 'Identifiant d\'inscription manquant.'];
        }

        $result = $this->storeInscriptionDocument(
            $idInscription,
            is_array($files['fiche'] ?? null) ? $files['fiche'] : null,
            $userId,
            'fiche_inscription',
            "fiche d'inscription",
            true
        );

        if (($result['success'] ?? false) === true) {
            return ['success' => true, 'message' => 'Fiche d\'inscription uploadée avec succès.'];
        }

        return ['success' => false, 'message' => (string) ($result['message'] ?? 'Erreur lors de l\'upload de la fiche.')];
    }
}
