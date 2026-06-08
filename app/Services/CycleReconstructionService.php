<?php
namespace CheckMaster\Services;

/**
 * CycleReconstructionService
 *
 * Gère la reconstitution du cycle d'un étudiant existant :
 *   - Brouillon de reconstruction (valeurs par défaut, référentiels)
 *   - Reconstruction complète transactionnelle
 *   - Stockage des documents dans `documents`
 *   - Rattachement aux objets collectifs (CR, planning)
 *   - Recalcul des statuts cycle
 */
class CycleReconstructionService
{
    private $pdo;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
    }

    // ================================================================
    //  BROUILLON DE RECONSTRUCTION
    // ================================================================

    /**
     * Génère un brouillon de reconstruction pour un étudiant.
     * Inclut les référentiels utiles et les valeurs par défaut.
     *
     * @param  string $numEtu  Matricule ou identifiant
     * @return array
     */
    public function getReconstructionDraft(string $numEtu): array
    {
        $purgeService = new PurgeCycleService($this->pdo);
        $resolved = $purgeService->resolveStudentIdentifiers($numEtu);

        if (!$resolved['student']) {
            return ['success' => false, 'message' => 'Étudiant non trouvé.'];
        }

        // Charger les référentiels
        $referentiels = [];

        // Années académiques
        $stmt = $this->pdo->query("SELECT id_annee_acad, date_deb, date_fin FROM annee_academique ORDER BY id_annee_acad DESC");
        $referentiels['annees_academiques'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Niveaux d'étude
        $stmt = $this->pdo->query("SELECT id_niv_etude, lib_niv_etude FROM niveau_etude ORDER BY lib_niv_etude");
        $referentiels['niveaux_etude'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Entreprises
        $stmt = $this->pdo->query("SELECT id_entreprise, lib_long_entreprise FROM entreprises ORDER BY lib_long_entreprise");
        $referentiels['entreprises'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Maîtres de stage
        $stmt = $this->pdo->query("SELECT id_maitre_stage, Nom, prenom FROM maitre_de_stage ORDER BY Nom");
        $referentiels['maitres_stage'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Enseignants
        $stmt = $this->pdo->query("SELECT id_enseignant, nom_enseignant, prenom_enseignant FROM enseignants ORDER BY nom_enseignant");
        $referentiels['enseignants'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Rôles jury
        $stmt = $this->pdo->query("SELECT id_jury, lib_jury FROM statut_jury ORDER BY lib_jury");
        $referentiels['roles_jury'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Salles
        $stmt = $this->pdo->query("SELECT id_salle, lib_salle FROM salles ORDER BY lib_salle");
        $referentiels['salles'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Sessions
        $stmt = $this->pdo->query("SELECT id_session, lib_session FROM session ORDER BY lib_session");
        $referentiels['sessions'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Domaines
        $stmt = $this->pdo->query("SELECT id_domaine, lib_domaine FROM domaine ORDER BY lib_domaine");
        $referentiels['domaines'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Critères d'évaluation
        try {
            $stmt = $this->pdo->query("SELECT id_critere, lib_critere FROM critere_evaluation ORDER BY lib_critere");
            $referentiels['criteres_evaluation'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $referentiels['criteres_evaluation'] = [];
        }

        // CR existants (pour rattachement)
        $stmt = $this->pdo->query("SELECT id_CR, nom_CR, date_CR FROM compte_rendu ORDER BY date_CR DESC LIMIT 100");
        $referentiels['comptes_rendus_existants'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Plannings existants (pour rattachement)
        $stmt = $this->pdo->query(
            "SELECT d.id_document, d.nom_fichier, d.reference, d.date_creation
             FROM documents d
             WHERE d.type_document = 'planning' AND d.statut = 'actif'
             ORDER BY d.date_creation DESC LIMIT 50"
        );
        $referentiels['plannings_existants'] = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Valeurs par défaut
        $defaults = [];

        // Dernière année académique active
        if (!empty($referentiels['annees_academiques'])) {
            $defaults['id_annee_acad'] = $referentiels['annees_academiques'][0]['id_annee_acad'];
        }

        // Dernière inscription de l'étudiant
        $ids = $resolved['identifiers'];
        $ph = $this->placeholders($ids);
        $params = $this->paramMap($ids);
        $stmt = $this->pdo->prepare(
            "SELECT * FROM inscriptions WHERE num_carte_etud IN ($ph) ORDER BY id_annee_acad DESC LIMIT 1"
        );
        $stmt->execute($params);
        $lastInscription = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($lastInscription) {
            $defaults['last_inscription'] = $lastInscription;
        }

        return [
            'success' => true,
            'student' => $resolved['student'],
            'referentiels' => $referentiels,
            'defaults' => $defaults,
        ];
    }

    // ================================================================
    //  RECONSTRUCTION
    // ================================================================

    /**
     * Reconstruit le cycle complet d'un étudiant.
     *
     * @param  string $numEtu   Matricule
     * @param  array  $payload  Données du wizard (sections activées)
     * @return array
     */
    public function rebuildCycle(string $numEtu, array $payload): array
    {
        $purgeService = new PurgeCycleService($this->pdo);
        $resolved = $purgeService->resolveStudentIdentifiers($numEtu);

        if (!$resolved['student']) {
            return ['success' => false, 'message' => 'Étudiant non trouvé.'];
        }

        $primary = $resolved['primary'];
        $details = ['created' => [], 'documents' => [], 'attached' => []];
        $context = [
            'id_annee_acad' => $this->firstNonEmpty([
                $payload['inscription']['id_annee_acad'] ?? null,
                $payload['rapport']['id_annee_acad'] ?? null,
                $payload['soutenance']['id_annee_acad'] ?? null,
                $this->getLatestAcademicYearId(),
            ]),
            'candidature_id' => null,
            'stage_id' => null,
        ];

        $this->pdo->beginTransaction();
        try {
            // ── 1. Inscription ──────────────────────────────────
            if (!empty($payload['inscription']['enabled'])) {
                $result = $this->createInscription($primary, $payload['inscription']);
                $details['created']['inscriptions'] = $result['count'];
                if (!empty($result['documents'])) {
                    $details['documents'] = array_merge($details['documents'], $result['documents']);
                }
            }

            // ── 2. Stage ───────────────────────────────────────
            if (!empty($payload['stage']['enabled'])) {
                $result = $this->createStage($primary, $payload['stage']);
                $details['created']['informations_stage'] = $result['count'];
                $context['stage_id'] = $result['stage_id'] ?? null;
            }

            // ── 3. Candidature ──────────────────────────────────
            if (!empty($payload['candidature']['enabled'])) {
                $result = $this->createCandidature($primary, $payload['candidature']);
                $details['created']['candidature_soutenance'] = $result['count'];
                $details['created']['resume_candidature'] = $result['resume_count'] ?? 0;
                $context['candidature_id'] = $result['candidature_id'] ?? null;
            }

            // ── 4. Rapport + dépôt ─────────────────────────────
            $rapportId = null;
            if (!empty($payload['rapport']['enabled'])) {
                $result = $this->createRapport($primary, $payload['rapport'], $context);
                $rapportId = $result['rapport_id'];
                $details['created']['rapport_etudiants'] = $result['count'];
                $details['created']['deposer'] = $result['deposer_count'] ?? 0;
                if (!empty($result['documents'])) {
                    $details['documents'] = array_merge($details['documents'], $result['documents']);
                }
            }

            // ── 5. Mémoire ─────────────────────────────────────
            if (!empty($payload['memoire']['enabled']) && $rapportId) {
                $result = $this->createMemoire($primary, $rapportId, $payload['memoire'], $context);
                $details['created']['memoire_metadonnees'] = $result['meta_count'] ?? 0;
                $details['created']['evaluations_memoires'] = $result['eval_count'] ?? 0;
                if (!empty($result['documents'])) {
                    $details['documents'] = array_merge($details['documents'], $result['documents']);
                }
            }

            // ── 6. Validation commission ────────────────────────
            if (!empty($payload['validation']['enabled']) && $rapportId) {
                $result = $this->createValidationCommission($rapportId, $payload['validation']);
                $details['created']['evaluations_rapports'] = $result['eval_count'] ?? 0;
                $details['created']['valider'] = $result['valider_count'] ?? 0;
                $details['created']['affecter'] = $result['affecter_count'] ?? 0;
            }

            // ── 7. Soutenance et jury ───────────────────────────
            $soutenanceId = null;
            if (!empty($payload['soutenance']['enabled'])) {
                $result = $this->createSoutenance($primary, $payload['soutenance'], $context);
                $soutenanceId = $result['soutenance_id'];
                $details['created']['programmer_soutenance'] = $result['count'];
                $details['created']['enseignant_jury'] = $result['jury_count'] ?? 0;
                $details['created']['evaluer'] = $result['eval_count'] ?? 0;
                $details['created']['evaluation_soutenance_meta'] = $result['meta_count'] ?? 0;
            }

            // ── 8. CR / PV commission ───────────────────────────
            if (!empty($payload['compte_rendu']['enabled'])) {
                $result = $this->attachOrCreateCompteRendu($primary, $rapportId, $payload['compte_rendu']);
                $details['created']['compte_rendu'] = $result['cr_count'] ?? 0;
                $details['created']['compte_rendu_rapport'] = $result['link_count'] ?? 0;
                $details['attached']['compte_rendu'] = $result['attached'] ?? false;
                if (!empty($result['documents'])) {
                    $details['documents'] = array_merge($details['documents'], $result['documents']);
                }
            }

            // ── 9. Planning ─────────────────────────────────────
            if (!empty($payload['planning']['enabled']) && $soutenanceId) {
                $result = $this->attachOrCreatePlanning((string) $soutenanceId, $primary, $payload['planning'], $context);
                $details['created']['documents_planning'] = $result['doc_count'] ?? 0;
                $details['attached']['planning'] = $result['attached'] ?? false;
                if (!empty($result['documents'])) {
                    $details['documents'] = array_merge($details['documents'], $result['documents']);
                }
            }

            // ── 10. PV final ────────────────────────────────────
            if (!empty($payload['pv_final']['enabled']) && $soutenanceId) {
                $result = $this->createPvFinal((string) $soutenanceId, $payload['pv_final']);
                $details['created']['documents_pv_final'] = $result['doc_count'] ?? 0;
                if (!empty($result['documents'])) {
                    $details['documents'] = array_merge($details['documents'], $result['documents']);
                }
            }

            // ── 11. Recalcul statuts cycle ──────────────────────
            $this->refreshCycleStatus($primary);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Reconstruction du cycle terminée avec succès.',
                'student' => $resolved['student'],
                'details' => $details,
            ];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors de la reconstruction : ' . $e->getMessage(),
                'details' => $details,
            ];
        }
    }

    // ================================================================
    //  CRÉATION PAR SECTION
    // ================================================================

    private function createInscription(string $numCarte, array $data): array
    {
        $count = 0;
        $documents = [];

        if (empty($data['id_annee_acad'])) {
            return ['count' => 0, 'documents' => []];
        }

        $numVersement = (int) ($data['num_versement'] ?? 1);
        $montant = (float) ($data['montant_verser'] ?? 0);

        $stmt = $this->pdo->prepare(
            "INSERT INTO inscriptions (num_carte_etud, id_annee_acad, num_versement, id_niv_etude, montant_verser, date_inscription, date_versement, solde)
             VALUES (:num, :annee, :versement, :niveau, :montant, :date_inscription, :date_versement, :solde)
             ON DUPLICATE KEY UPDATE
                id_niv_etude = VALUES(id_niv_etude),
                montant_verser = VALUES(montant_verser),
                date_inscription = VALUES(date_inscription)"
        );
        $stmt->execute([
            ':num' => $numCarte,
            ':annee' => $data['id_annee_acad'],
            ':versement' => $numVersement,
            ':niveau' => $this->nullIfEmpty($data['id_niv_etude'] ?? null),
            ':montant' => $montant,
            ':date_inscription' => $this->dateTimeValue($data['date_inscription'] ?? null),
            ':date_versement' => $this->dateTimeValue($data['date_versement'] ?? ($data['date_inscription'] ?? null)),
            ':solde' => (float) ($data['solde'] ?? 0),
        ]);
        $count = $stmt->rowCount() > 0 ? 1 : 0;

        // Document fiche_inscription
        if (!empty($data['document_fiche'])) {
            $docId = $this->storeCycleDocument(
                'fiche_inscription',
                'inscriptions',
                $numCarte . '-' . $data['id_annee_acad'] . '-' . $numVersement,
                $data['document_fiche']
            );
            if ($docId) $documents[] = $docId;
        }

        // Document reçu
        if (!empty($data['document_recu'])) {
            $docId = $this->storeCycleDocument(
                'recu',
                'inscriptions',
                $numCarte . '-' . $data['id_annee_acad'] . '-' . $numVersement,
                $data['document_recu']
            );
            if ($docId) $documents[] = $docId;
        }

        return ['count' => $count, 'documents' => $documents];
    }

    private function createStage(string $numEtu, array $data): array
    {
        $entrepriseId = $this->nullIfEmpty($data['id_entreprise'] ?? null);
        $maitreId = $this->nullIfEmpty($data['id_maitre_stage'] ?? null);

        if ($entrepriseId === null || $maitreId === null) {
            return ['count' => 0, 'stage_id' => null];
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO informations_stage (num_etu, id_entreprise, id_maitre_stage, date_debut_stage, date_fin_stage, sujet_stage)
             VALUES (:num, :entreprise, :maitre, :debut, :fin, :sujet)"
        );
        $stmt->execute([
            ':num' => $numEtu,
            ':entreprise' => $entrepriseId,
            ':maitre' => $maitreId,
            ':debut' => $this->dateValue($data['date_debut'] ?? null),
            ':fin' => $this->dateValue($data['date_fin'] ?? null),
            ':sujet' => $data['sujet_stage'] ?? 'Sujet de stage à préciser',
        ]);

        return ['count' => $stmt->rowCount() > 0 ? 1 : 0, 'stage_id' => (int) $this->pdo->lastInsertId()];
    }

    private function createCandidature(string $numEtu, array $data): array
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO candidature_soutenance (num_etu, date_candidature, statut_candidature, date_traitement, commentaire_admin)
             VALUES (:num, :date, :statut, :traitement, :commentaire)"
        );
        $stmt->execute([
            ':num' => $numEtu,
            ':date' => $data['date_candidature'] ?? date('Y-m-d H:i:s'),
            ':statut' => $data['statut'] ?? 'Validée',
            ':traitement' => $data['date_traitement'] ?? date('Y-m-d H:i:s'),
            ':commentaire' => $data['commentaire'] ?? 'Reconstitution manuelle',
        ]);
        $candidatureId = $this->pdo->lastInsertId();
        $count = $candidatureId ? 1 : 0;
        $resumeCount = 0;

        // Résumé candidature optionnel
        if ($candidatureId && !empty($data['resume'])) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO resume_candidature (id_candidature, num_etu, resume_json, decision)
                 VALUES (:cand, :num, :resume, :decision)"
            );
            $stmt->execute([
                ':cand' => $candidatureId,
                ':num' => $numEtu,
                ':resume' => $this->normalizeJsonText((string) $data['resume']),
                ':decision' => $data['statut'] ?? 'Validée',
            ]);
            $resumeCount = $stmt->rowCount() > 0 ? 1 : 0;
        }

        return ['count' => $count, 'resume_count' => $resumeCount, 'candidature_id' => $candidatureId ? (int) $candidatureId : null];
    }

    private function createRapport(string $numEtu, array $data, array $context): array
    {
        $documents = [];
        $theme = $data['theme'] ?? 'Thème à définir';
        $status = $this->normalizeRapportStatus((string) ($data['statut'] ?? 'valider'));

        $stmt = $this->pdo->prepare(
            "INSERT INTO rapport_etudiants (num_etu, date_redaction_rapport, theme_rapport, nom_rapport, statut_rapport, id_candidature, id_info_stage, taille_fichier)
             VALUES (:num, :date, :theme, :nom, :statut, :candidature, :stage, :taille)"
        );
        $stmt->execute([
            ':num' => $numEtu,
            ':date' => $this->dateTimeValue($data['date_soumission'] ?? null),
            ':theme' => $theme,
            ':nom' => $this->fileNameFromData($data['document_rapport'] ?? null, 'Rapport_' . $numEtu . '.pdf'),
            ':statut' => $status,
            ':candidature' => $context['candidature_id'] ?? null,
            ':stage' => $context['stage_id'] ?? null,
            ':taille' => $this->fileSizeFromData($data['document_rapport'] ?? null),
        ]);
        $rapportId = $this->pdo->lastInsertId();
        $count = $rapportId ? 1 : 0;
        $deposerCount = 0;

        // Dépôt
        if ($rapportId && !empty($data['depose'])) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO deposer (id_rapport, num_etu, date_depot) VALUES (:rapport, :num, :date)"
            );
            $stmt->execute([
                ':rapport' => $rapportId,
                ':num' => $numEtu,
                ':date' => $data['date_depot'] ?? date('Y-m-d'),
            ]);
            $deposerCount = $stmt->rowCount() > 0 ? 1 : 0;
        }

        // Document rapport
        if ($rapportId && !empty($data['document_rapport'])) {
            $type = $data['document_type'] ?? 'rapport';
            $docId = $this->storeCycleDocument($type, 'rapport_etudiants', (string) $rapportId, $data['document_rapport']);
            if ($docId) $documents[] = $docId;
        }

        return ['rapport_id' => $rapportId, 'count' => $count, 'deposer_count' => $deposerCount, 'documents' => $documents];
    }

    private function createMemoire(string $numEtu, int $rapportId, array $data, array $context): array
    {
        $documents = [];
        $metaCount = 0;
        $evalCount = 0;

        // Document mémoire (optionnel car auto-incrément peut échouer si pas de vrai document stocké)
        $docId = null;
        if (!empty($data['document_memoire'])) {
            $docId = $this->storeCycleDocument('memoire', 'rapport_etudiants', (string) $rapportId, $data['document_memoire']);
            if ($docId) $documents[] = $docId;
        }

        // Métadonnées mémoire : n'insérer QUE si on a un id_document valide
        // car memoire_metadonnees.id_document est UNIQUE et NOT NULL
        if ($docId && !empty($data['theme'])) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO memoire_metadonnees (id_document, id_rapport, num_etu, theme_memoire, num_session_previsionnelle, date_debut_session, date_fin_session)
                 VALUES (:doc, :rapport, :num, :theme, :session, :debut, :fin)"
            );
            $stmt->execute([
                ':doc' => $docId,
                ':rapport' => $rapportId,
                ':num' => $numEtu,
                ':theme' => $data['theme'],
                ':session' => $this->nullIfEmpty($data['num_session_previsionnelle'] ?? null),
                ':debut' => $this->nullIfEmpty($data['date_debut_session'] ?? null),
                ':fin' => $this->nullIfEmpty($data['date_fin_session'] ?? null),
            ]);
            $metaCount = $stmt->rowCount() > 0 ? 1 : 0;
        }

        // Évaluations mémoire
        if (!empty($data['evaluations']) && is_array($data['evaluations'])) {
            foreach ($data['evaluations'] as $eval) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO evaluations_memoires (id_document, id_rapport, id_evaluateur, type_evaluateur, decision, commentaire)
                     VALUES (:doc, :rapport, :evaluateur, :type_evaluateur, :decision, :commentaire)"
                );
                $stmt->execute([
                    ':doc' => $docId ?: 0,
                    ':rapport' => $rapportId,
                    ':evaluateur' => (int) ($eval['id_evaluateur'] ?? ($_SESSION['id_utilisateur'] ?? 0)),
                    ':type_evaluateur' => $eval['type_evaluateur'] ?? 'responsable_filiere',
                    ':decision' => $this->normalizeDecision($eval['decision'] ?? 'valider'),
                    ':commentaire' => $eval['commentaire'] ?? null,
                ]);
                if ($stmt->rowCount() > 0) $evalCount++;
            }
        }

        return ['meta_count' => $metaCount, 'eval_count' => $evalCount, 'documents' => $documents];
    }

    private function createValidationCommission(int $rapportId, array $data): array
    {
        $evalCount = 0;
        $validerCount = 0;
        $affecterCount = 0;

        // Affecter encadrant/directeur
        if (!empty($data['affectations']) && is_array($data['affectations'])) {
            foreach ($data['affectations'] as $aff) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO affecter (id_enseignant, role, id_rapport) VALUES (:ens, :role, :rapport)"
                );
                $stmt->execute([
                    ':ens' => $aff['id_enseignant'],
                    ':role' => $aff['role'] ?? 'encadrant',
                    ':rapport' => $rapportId,
                ]);
                if ($stmt->rowCount() > 0) $affecterCount++;
            }
        }

        // Évaluations rapport — conforme au vrai schéma evaluations_rapports
        if (!empty($data['evaluations']) && is_array($data['evaluations'])) {
            foreach ($data['evaluations'] as $eval) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO evaluations_rapports (id_rapport, id_evaluateur, decision_evaluation, commentaire, date_evaluation)
                     VALUES (:rapport, :eval, :decision, :comment, :date)"
                );
                $stmt->execute([
                    ':rapport' => $rapportId,
                    ':eval' => $this->nullIfEmpty($eval['id_evaluateur'] ?? null) ?? 0,
                    ':decision' => $this->nullIfEmpty($eval['decision_evaluation'] ?? 'valider'),
                    ':comment' => $eval['commentaire'] ?? null,
                    ':date' => $this->dateTimeValue($eval['date_evaluation'] ?? null) ?: date('Y-m-d H:i:s'),
                ]);
                if ($stmt->rowCount() > 0) $evalCount++;
            }
        }

        // Valider — conforme au vrai schéma valider(id_enseignant, id_rapport, ...)
        if (!empty($data['valider'])) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO valider (id_enseignant, id_rapport, date_validation, commentaire_validation, decision_validation)
                 VALUES (:ens, :rapport, :date, :comment, :decision)"
            );
            $stmt->execute([
                ':ens' => $data['valider']['id_enseignant'] ?? '',
                ':rapport' => $rapportId,
                ':date' => $this->dateTimeValue($data['valider']['date'] ?? null) ?: date('Y-m-d H:i:s'),
                ':comment' => $data['valider']['commentaire'] ?? '',
                ':decision' => $data['valider']['decision_validation'] ?? 'valider',
            ]);
            $validerCount = $stmt->rowCount() > 0 ? 1 : 0;
        }

        return ['eval_count' => $evalCount, 'valider_count' => $validerCount, 'affecter_count' => $affecterCount];
    }

    private function createSoutenance(string $numEtu, array $data, array $context): array
    {
        // Générer un identifiant unique pour num_soutenance (varchar PK, pas auto-incrément)
        $numSoutenance = $this->nullIfEmpty($data['num_soutenance'] ?? null);
        if ($numSoutenance === null) {
            $numSoutenance = 'RECONST-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO programmer_soutenance (num_soutenance, num_etud, theme_soutenance, id_domaine, id_session, id_salle, date_soutenance, heure_soutenance, id_annee_acad)
             VALUES (:num, :etu, :theme, :domaine, :session, :salle, :date, :heure, :annee)"
        );
        $stmt->execute([
            ':num' => $numSoutenance,
            ':etu' => $numEtu,
            ':theme' => $data['theme_soutenance'] ?? 'Soutenance reconstituée',
            ':domaine' => $this->nullIfEmpty($data['id_domaine'] ?? null),
            ':session' => $this->nullIfEmpty($data['id_session'] ?? null),
            ':salle' => $this->nullIfEmpty($data['id_salle'] ?? null),
            ':date' => $this->nullIfEmpty($data['date_soutenance'] ?? null),
            ':heure' => $this->nullIfEmpty($data['heure_soutenance'] ?? $data['heure'] ?? null),
            ':annee' => $context['id_annee_acad'] ?? null,
        ]);
        $count = $stmt->rowCount() > 0 ? 1 : 0;
        $juryCount = 0;
        $evalCount = 0;
        $metaCount = 0;

        // Jury via enseignant_jury
        if (!empty($data['jury']) && is_array($data['jury'])) {
            foreach ($data['jury'] as $j) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO enseignant_jury (num_soutenance, id_enseignant, id_qualite_jury, date_composer_jury)
                     VALUES (:sout, :ens, :qualite, :date)"
                );
                $stmt->execute([
                    ':sout' => $numSoutenance,
                    ':ens' => $j['id_enseignant'],
                    ':qualite' => $j['id_qualite_jury'] ?? $j['id_role_jury'] ?? null,
                    ':date' => $this->dateTimeValue($j['date_composer_jury'] ?? null),
                ]);
                if ($stmt->rowCount() > 0) $juryCount++;
            }
        }

        // Évaluation soutenance meta — une seule ligne par soutenance, pas par jury
        if (!empty($data['evaluation_meta'])) {
            $evalMeta = $data['evaluation_meta'];
            $stmt = $this->pdo->prepare(
                "INSERT INTO evaluation_soutenance_meta (num_etudiant, jury_ref, id_annee_acad, decision, commentaire_general, note_finale)
                 VALUES (:etu, :jury, :annee, :decision, :commentaire, :note)"
            );
            $stmt->execute([
                ':etu' => $numEtu,
                ':jury' => $numSoutenance,
                ':annee' => $context['id_annee_acad'] ?? null,
                ':decision' => $evalMeta['decision'] ?? null,
                ':commentaire' => $evalMeta['commentaire_general'] ?? null,
                ':note' => $this->nullIfEmpty($evalMeta['note_finale'] ?? null),
            ]);
            if ($stmt->rowCount() > 0) $metaCount++;
        }

        // Évaluations par critère (table evaluer)
        if (!empty($data['evaluations']) && is_array($data['evaluations'])) {
            foreach ($data['evaluations'] as $ev) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO evaluer (num_etudiant, num_jury, id_critere, date_eval, note)
                     VALUES (:etu, :jury, :critere, :date, :note)"
                );
                $stmt->execute([
                    ':etu' => $numEtu,
                    ':jury' => $numSoutenance,
                    ':critere' => $ev['id_critere'],
                    ':date' => $ev['date_eval'] ?? $data['date_soutenance'] ?? date('Y-m-d'),
                    ':note' => $ev['note'] ?? null,
                ]);
                if ($stmt->rowCount() > 0) $evalCount++;
            }
        }

        return [
            'soutenance_id' => $numSoutenance,
            'count' => $count,
            'jury_count' => $juryCount,
            'eval_count' => $evalCount,
            'meta_count' => $metaCount,
        ];
    }

    private function attachOrCreateCompteRendu(string $numEtu, ?int $rapportId, array $data): array
    {
        $documents = [];

        if (!empty($data['id_CR_existant']) && $rapportId) {
            // Rattachement à un CR existant
            $stmt = $this->pdo->prepare(
                "INSERT INTO compte_rendu_rapport (id_CR, id_rapport) VALUES (:cr, :rapport)"
            );
            $stmt->execute([':cr' => $data['id_CR_existant'], ':rapport' => $rapportId]);

            return [
                'cr_count' => 0,
                'link_count' => $stmt->rowCount() > 0 ? 1 : 0,
                'attached' => true,
                'documents' => [],
            ];
        }

        // Création d'un nouveau CR
        $stmt = $this->pdo->prepare(
            "INSERT INTO compte_rendu (num_etu, nom_CR, contenu_CR, date_CR)
             VALUES (:num, :nom, :contenu, :date)"
        );
        $stmt->execute([
            ':num' => $numEtu,
            ':nom' => $data['nom_CR'] ?? ('CR ' . $numEtu),
            ':contenu' => $data['contenu_CR'] ?? null,
            ':date' => $data['date_CR'] ?? date('Y-m-d H:i:s'),
        ]);
        $crId = $this->pdo->lastInsertId();
        $crCount = $crId ? 1 : 0;
        $linkCount = 0;

        // Lier au rapport
        if ($crId && $rapportId) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO compte_rendu_rapport (id_CR, id_rapport) VALUES (:cr, :rapport)"
            );
            $stmt->execute([':cr' => $crId, ':rapport' => $rapportId]);
            $linkCount = $stmt->rowCount() > 0 ? 1 : 0;
        }

        // Document CR
        if ($crId && !empty($data['document_cr'])) {
            $type = $data['document_type'] ?? 'compte_rendu';
            $docId = $this->storeCycleDocument($type, 'compte_rendu', (string) $crId, $data['document_cr']);
            if ($docId) $documents[] = $docId;
        }

        // Document PV commission
        if ($crId && !empty($data['document_pv'])) {
            $docId = $this->storeCycleDocument('pv_commission', 'compte_rendu', (string) $crId, $data['document_pv']);
            if ($docId) $documents[] = $docId;
        }

        return ['cr_count' => $crCount, 'link_count' => $linkCount, 'attached' => false, 'documents' => $documents];
    }

    private function attachOrCreatePlanning(string $numSoutenance, string $numEtu, array $data, array $context): array
    {
        $documents = [];

        if (!empty($data['id_document_planning'])) {
            // Rattachement à un planning existant
            $stmt = $this->pdo->prepare(
                "UPDATE programmer_soutenance SET id_document_planning = :plan WHERE num_soutenance = :sout"
            );
            $stmt->execute([':plan' => $data['id_document_planning'], ':sout' => $numSoutenance]);

            // Associer dans pv_etudiants_associes
            $stmt = $this->pdo->prepare(
                "INSERT INTO pv_etudiants_associes (num_etu, id_document, type_pv) VALUES (:num, :doc, 'planning')"
            );
            $stmt->execute([':num' => $numEtu, ':doc' => $data['id_document_planning']]);

            return ['doc_count' => 0, 'attached' => true, 'documents' => []];
        }

        // Création d'un nouveau planning
        $docId = null;
        if (!empty($data['document_planning'])) {
            $docId = $this->storeCycleDocument('planning', null, null, $data['document_planning']);
            if ($docId) $documents[] = $docId;
        }

        // Lier à la soutenance
        if ($docId) {
            $stmt = $this->pdo->prepare(
                "UPDATE programmer_soutenance SET id_document_planning = :plan WHERE num_soutenance = :sout"
            );
            $stmt->execute([':plan' => $docId, ':sout' => $numSoutenance]);

            // Associer dans pv_etudiants_associes
            $userId = (int) ($_SESSION['id_utilisateur'] ?? 0);
            $stmt = $this->pdo->prepare(
                "INSERT INTO pv_etudiants_associes (num_etu, id_document, type_pv, id_utilisateur)
                 VALUES (:num, :doc, 'planning', :uid)"
            );
            $stmt->execute([':num' => $numEtu, ':doc' => $docId, ':uid' => $userId]);

            // document_genere — colonnes réelles : id_document, reference, type_document,
            // id_utilisateur, id_source, chemin_fichier, nom_fichier, taille_fichier
            $stmt = $this->pdo->prepare(
                "INSERT INTO document_genere (id_document, reference, type_document, id_utilisateur, id_source, chemin_fichier, nom_fichier, taille_fichier)
                 VALUES (:doc, :ref, 'PLN', :uid, :src, :chemin, :nom, :taille)"
            );
            $stmt->execute([
                ':doc' => $docId,
                ':ref' => 'PLN-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
                ':uid' => $userId,
                ':src' => (string) $numSoutenance,
                ':chemin' => $data['nom_planning'] ?? 'Planning.pdf',
                ':nom' => $data['nom_planning'] ?? 'Planning.pdf',
                ':taille' => strlen($data['document_planning']['content'] ?? '') ?: 0,
            ]);
        }

        return ['doc_count' => $docId ? 1 : 0, 'attached' => false, 'documents' => $documents];
    }

    private function createPvFinal(string $numSoutenance, array $data): array
    {
        $documents = [];

        if (!empty($data['document_pv_final'])) {
            $docId = $this->storeCycleDocument('pv_final', 'programmer_soutenance', $numSoutenance, $data['document_pv_final']);
            if ($docId) {
                $documents[] = $docId;

                // document_genere — colonnes réelles
                $userId = (int) ($_SESSION['id_utilisateur'] ?? 0);
                $stmt = $this->pdo->prepare(
                    "INSERT INTO document_genere (id_document, reference, type_document, id_utilisateur, id_source, chemin_fichier, nom_fichier, taille_fichier)
                     VALUES (:doc, :ref, 'PVF', :uid, :src, :chemin, :nom, :taille)"
                );
                $stmt->execute([
                    ':doc' => $docId,
                    ':ref' => 'PVF-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
                    ':uid' => $userId,
                    ':src' => $numSoutenance,
                    ':chemin' => $data['nom_pv'] ?? 'PV_Final.pdf',
                    ':nom' => $data['nom_pv'] ?? 'PV_Final.pdf',
                    ':taille' => strlen($data['document_pv_final']['content'] ?? '') ?: 0,
                ]);
            }
        }

        return ['doc_count' => count($documents), 'documents' => $documents];
    }

    // ================================================================
    //  STOCKAGE DOCUMENT
    // ================================================================

    /**
     * Stocke un document dans la table `documents`.
     *
     * @param  string      $typeDocument  Type de document
     * @param  string|null $entiteType    Type d'entité
     * @param  string|null $entiteId      ID de l'entité
     * @param  array       $fileData      Données du fichier ['name', 'content', 'mime', 'extension']
     * @return int|null    ID du document créé
     */
    private function storeCycleDocument(string $typeDocument, ?string $entiteType, ?string $entiteId, array $fileData): ?int
    {
        $content = $fileData['content'] ?? null;
        if (empty($content)) return null;

        $nomFichier = $fileData['name'] ?? 'document.pdf';
        $extension = $fileData['extension'] ?? pathinfo($nomFichier, PATHINFO_EXTENSION) ?: 'pdf';
        $mime = $fileData['mime'] ?? 'application/pdf';
        $reference = strtoupper($typeDocument) . '-' . date('Y') . '-' . bin2hex(random_bytes(4));

        $stmt = $this->pdo->prepare(
            "INSERT INTO documents (type_document, reference, nom_fichier, extension, type_mime, entite_type, entite_id, contenu, taille_fichier)
             VALUES (:type, :ref, :nom, :ext, :mime, :entite_type, :entite_id, :contenu, :taille)"
        );
        $stmt->execute([
            ':type' => $typeDocument,
            ':ref' => $reference,
            ':nom' => $nomFichier,
            ':ext' => $extension,
            ':mime' => $mime,
            ':entite_type' => $entiteType,
            ':entite_id' => $entiteId,
            ':contenu' => $content,
            ':taille' => strlen($content),
        ]);

        $docId = $this->pdo->lastInsertId();
        return $docId ? (int) $docId : null;
    }

    // ================================================================
    //  RECALCUL STATUTS CYCLE
    // ================================================================

    /**
     * Recalcule les statuts du cycle pour un étudiant via CycleEtudiantService.
     */
    private function refreshCycleStatus(string $numEtu): void
    {
        try {
            $cycleService = new CycleEtudiantService($this->pdo);
            // Recalculer pour toutes les années académiques de l'étudiant
            $stmt = $this->pdo->prepare(
                "SELECT DISTINCT id_annee_acad FROM inscriptions WHERE num_carte_etud = :num"
            );
            $stmt->execute([':num' => $numEtu]);
            $annees = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];

            foreach ($annees as $anneeId) {
                $cycleService->mettreAJourStatutCycle($numEtu, (int) $anneeId);
            }
        } catch (\Throwable $e) {
            // Le recalcul des statuts ne doit pas faire échouer la reconstruction
            error_log("CycleReconstructionService::refreshCycleStatus error for $numEtu: " . $e->getMessage());
        }
    }

    // ================================================================
    //  AUDIT (délégation)
    // ================================================================

    public function logAudit(int $userId, string $action, string $status): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO pister (id_utilisateur, action, statut_action, contexte)
                 VALUES (:uid, :action, :status, 'purge_cycle_etudiant')"
            );
            $stmt->execute([
                ':uid' => $userId,
                ':action' => mb_substr($action, 0, 120),
                ':status' => $status,
            ]);
        } catch (\Throwable $e) {
            error_log("CycleReconstructionService::logAudit error: " . $e->getMessage());
        }
    }

    // ================================================================
    //  UTILITAIRES
    // ================================================================

    private function placeholders(array $values, string $prefix = 'p'): string
    {
        $parts = [];
        foreach (array_values($values) as $i => $v) {
            $parts[] = ":{$prefix}{$i}";
        }
        return implode(', ', $parts);
    }

    private function paramMap(array $values, string $prefix = 'p'): array
    {
        $params = [];
        foreach (array_values($values) as $i => $v) {
            $params[":{$prefix}{$i}"] = $v;
        }
        return $params;
    }

    // ================================================================
    //  HELPERS MANQUANTS
    // ================================================================

    /**
     * Retourne la première valeur non nulle/non vide d'un tableau.
     */
    private function firstNonEmpty(array $values): mixed
    {
        foreach ($values as $v) {
            if ($v !== null && $v !== '' && $v !== []) {
                return $v;
            }
        }
        return null;
    }

    /**
     * Récupère l'ID de la dernière année académique.
     */
    private function getLatestAcademicYearId(): ?int
    {
        try {
            $stmt = $this->pdo->query("SELECT MAX(id_annee_acad) FROM annee_academique");
            $val = $stmt->fetchColumn();
            return $val !== false && $val !== null ? (int) $val : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Retourne null si la valeur est vide, sinon la valeur.
     */
    private function nullIfEmpty(mixed $value): mixed
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        return $value;
    }

    /**
     * Convertit une valeur en datetime MySQL valide ou null.
     */
    private function dateTimeValue(mixed $value): ?string
    {
        if (empty($value)) return null;
        $dt = $value instanceof \DateTime
            ? $value
            : @new \DateTime((string) $value);
        if ($dt === false || $dt->format('Y') === '0000') return null;
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * Convertit une valeur en date MySQL valide ou null.
     */
    private function dateValue(mixed $value): ?string
    {
        if (empty($value)) return null;
        $dt = $value instanceof \DateTime
            ? $value
            : @new \DateTime((string) $value);
        if ($dt === false || $dt->format('Y') === '0000') return null;
        return $dt->format('Y-m-d');
    }

    /**
     * Normalise un texte JSON (valide ou transforme en JSON).
     */
    private function normalizeJsonText(string $text): string
    {
        if ($text === '') return '{}';
        json_decode($text);
        if (json_last_error() === JSON_ERROR_NONE) return $text;
        // Tenter de créer un objet JSON à partir du texte
        $encoded = json_encode(['raw' => $text], JSON_UNESCAPED_UNICODE);
        return $encoded !== false ? $encoded : '{}';
    }

    /**
     * Normalise le statut d'un rapport vers les valeurs de l'enum statut_rapport.
     */
    private function normalizeRapportStatus(string $status): string
    {
        $map = [
            'valider' => 'valider',
            'validé' => 'valider',
            'valide' => 'valider',
            'en_cours' => 'en_cours',
            'en cours' => 'en_cours',
            'rejeter' => 'rejeter',
            'rejeté' => 'rejeter',
            'rejete' => 'rejeter',
            'en_attente' => 'en_attente',
            'en attente' => 'en_attente',
        ];
        $normalized = mb_strtolower(trim($status));
        return $map[$normalized] ?? 'en_cours';
    }

    /**
     * Extrait le nom de fichier depuis les données upload ou retourne un défaut.
     */
    private function fileNameFromData(mixed $fileData, string $default): string
    {
        if (is_array($fileData) && !empty($fileData['name'])) {
            return $fileData['name'];
        }
        return $default;
    }

    /**
     * Extrait la taille du fichier depuis les données upload.
     */
    private function fileSizeFromData(mixed $fileData): int
    {
        if (is_array($fileData)) {
            if (!empty($fileData['size'])) return (int) $fileData['size'];
            if (!empty($fileData['content'])) return strlen($fileData['content']);
        }
        return 0;
    }

    /**
     * Normalise une décision de validation vers une valeur valide.
     */
    private function normalizeDecision(string $decision): string
    {
        $map = [
            'valider' => 'valider',
            'validé' => 'valider',
            'valide' => 'valider',
            'rejeter' => 'rejeter',
            'rejeté' => 'rejeter',
            'rejete' => 'rejeter',
        ];
        return $map[mb_strtolower(trim($decision))] ?? $decision;
    }
}
