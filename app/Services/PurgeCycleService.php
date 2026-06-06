<?php
namespace CheckMaster\Services;

/**
 * PurgeCycleService
 *
 * Gère la purge complète du cycle d'un étudiant :
 *   - Recherche étudiants
 *   - Inventaire du cycle
 *   - Dry-run (simulation)
 *   - Purge atomique avec classification documentaire
 *   - Audit dans pister
 */
class PurgeCycleService
{
    private $pdo;

    public function __construct($pdo = null)
    {
        $this->pdo = $pdo ?: \Database::getConnection();
    }

    // ================================================================
    //  RECHERCHE ÉTUDIANTS
    // ================================================================

    /**
     * Recherche un étudiant par matricule, identifiant MESRS, nom ou prénom.
     *
     * @param  string $query  Terme de recherche (min 2 caractères)
     * @return array  Liste d'étudiants trouvés
     */
    public function searchStudents(string $query): array
    {
        $like = '%' . $query . '%';
        $sql = "SELECT num_carte_etud, num_ident_etud, nom_etu, prenom_etu, email_etu, promotion_etu
                FROM etudiants
                WHERE num_carte_etud LIKE :q1
                   OR num_ident_etud LIKE :q2
                   OR nom_etu LIKE :q3
                   OR prenom_etu LIKE :q4
                ORDER BY nom_etu, prenom_etu
                LIMIT 20";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    // ================================================================
    //  RÉSOLUTION IDENTIFIANTS
    // ================================================================

    /**
     * Résout tous les identifiants d'un étudiant (matricule + ident MESRS).
     *
     * @param  string $input  Matricule ou identifiant MESRS
     * @return array  ['primary' => string, 'identifiers' => string[], 'student' => array|null]
     */
    public function resolveStudentIdentifiers(string $input): array
    {
        $input = trim($input);

        // Chercher par num_carte_etud ou num_ident_etud
        $sql = "SELECT num_carte_etud, num_ident_etud, nom_etu, prenom_etu, email_etu, promotion_etu
                FROM etudiants
                WHERE num_carte_etud = :q OR num_ident_etud = :q2
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':q' => $input, ':q2' => $input]);
        $student = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$student) {
            return ['primary' => $input, 'identifiers' => [$input], 'student' => null];
        }

        $identifiers = array_values(array_filter([
            $student['num_carte_etud'],
            $student['num_ident_etud'],
        ]));

        return [
            'primary' => $student['num_carte_etud'],
            'identifiers' => array_unique($identifiers),
            'student' => $student,
        ];
    }

    // ================================================================
    //  INVENTAIRE DU CYCLE
    // ================================================================

    /**
     * Construit l'inventaire complet du cycle d'un étudiant.
     *
     * @param  string $numEtu  Matricule ou identifiant
     * @return array  Inventaire structuré (voir PRD §6.3)
     */
    public function getCycleInventory(string $numEtu): array
    {
        $resolved = $this->resolveStudentIdentifiers($numEtu);

        if (!$resolved['student']) {
            return ['success' => false, 'message' => 'Étudiant non trouvé.'];
        }

        $ids = $resolved['identifiers'];
        $placeholders = $this->placeholders($ids);
        $params = $this->paramMap($ids);

        // Compteurs par table
        $counts = [];
        $counts['inscriptions'] = $this->countRows('inscriptions', 'num_carte_etud', $ids);
        $counts['notes'] = $this->countRows('notes', 'num_etu', $ids);
        $counts['stage'] = $this->countRows('informations_stage', 'num_etu', $ids);
        $counts['candidatures'] = $this->countRows('candidature_soutenance', 'num_etu', $ids);
        $counts['resume_candidature'] = $this->countRows('resume_candidature', 'num_etu', $ids);
        $counts['rapports'] = $this->countRows('rapport_etudiants', 'num_etu', $ids);
        $counts['deposer'] = $this->countRows('deposer', 'num_etu', $ids);
        $counts['affecter'] = $this->countRowsJoined('affecter', 'rapport_etudiants', 'id_rapport', 'num_etu', $ids);
        $counts['valider'] = $this->countRowsJoined('valider', 'rapport_etudiants', 'id_rapport', 'num_etu', $ids);
        $counts['evaluations_rapports'] = $this->countRowsJoined('evaluations_rapports', 'rapport_etudiants', 'id_rapport', 'num_etu', $ids);
        $counts['memoires'] = $this->countRows('memoire_metadonnees', 'num_etu', $ids);
        $counts['evaluations_memoires'] = $this->countRowsJoined('evaluations_memoires', 'rapport_etudiants', 'id_rapport', 'num_etu', $ids);
        $counts['soutenances'] = $this->countRows('programmer_soutenance', 'num_etud', $ids);
        $counts['enseignant_jury'] = $this->countRowsJoined('enseignant_jury', 'programmer_soutenance', 'num_soutenance', 'num_etud', $ids);
        $counts['evaluer'] = $this->countRows('evaluer', 'num_etudiant', $ids);
        $counts['evaluation_soutenance_meta'] = $this->countRows('evaluation_soutenance_meta', 'num_etudiant', $ids);
        $counts['comptes_rendus_directs'] = $this->countRows('compte_rendu', 'num_etu', $ids);
        $counts['comptes_rendus_lies'] = $this->countRowsJoined('compte_rendu_rapport', 'rapport_etudiants', 'id_rapport', 'num_etu', $ids);
        $counts['reclamations'] = $this->countRows('reclamations', 'num_carte_etud', $ids);

        // Collecter les IDs clés
        $rapportIds = $this->collectColumn('rapport_etudiants', 'id_rapport', 'num_etu', $ids);
        $soutenanceIds = $this->collectSoutenanceIds($ids);
        $candidatureIds = $this->collectColumn('candidature_soutenance', 'id_candidature', 'num_etu', $ids);
        $compteRenduIds = $this->collectCompteRenduIds($rapportIds, $ids);

        // Collecter et classifier les documents
        $documents = $this->collectAndClassifyDocuments($resolved, $rapportIds, $soutenanceIds, $compteRenduIds);

        $counts['documents_exclusifs'] = 0;
        $counts['documents_partages'] = 0;
        $counts['documents_orphelins_apres_detachement'] = 0;
        foreach ($documents as $doc) {
            if ($doc['classification'] === 'exclusif') $counts['documents_exclusifs']++;
            elseif ($doc['classification'] === 'partage') $counts['documents_partages']++;
            elseif ($doc['classification'] === 'orphelin_apres_detachement') $counts['documents_orphelins_apres_detachement']++;
        }

        // Keys pour la reconstruction
        $inscriptionKeys = $this->collectInscriptionKeys($ids);
        $memoireDocIds = $this->collectMemoireDocumentIds($ids, $rapportIds);
        $documentGenereIds = $this->collectDocumentGenereIds($documents);

        $warnings = [];
        if ($counts['comptes_rendus_lies'] > 0) {
            $warnings[] = 'Ce dossier contient des comptes-rendus collectifs liés à d\'autres étudiants.';
        }

        return [
            'success' => true,
            'student' => $resolved['student'],
            'identifiers' => $resolved['identifiers'],
            'counts' => $counts,
            'ids' => [
                'inscription_keys' => $inscriptionKeys,
                'rapport_ids' => $rapportIds,
                'memoire_document_ids' => $memoireDocIds,
                'soutenance_ids' => $soutenanceIds,
                'compte_rendu_ids' => $compteRenduIds,
                'candidature_ids' => $candidatureIds,
                'document_ids' => array_column($documents, 'id_document'),
                'document_genere_ids' => $documentGenereIds,
            ],
            'documents' => $documents,
            'warnings' => $warnings,
        ];
    }

    // ================================================================
    //  DRY-RUN (SIMULATION)
    // ================================================================

    /**
     * Simule la purge sans modifier aucune donnée.
     *
     * @param  string $numEtu
     * @return array
     */
    public function dryRunPurge(string $numEtu): array
    {
        $inventory = $this->getCycleInventory($numEtu);

        if (!$inventory['success']) {
            return $inventory;
        }

        $ids = $inventory['identifiers'];
        $rapportIds = $inventory['ids']['rapport_ids'];
        $soutenanceIds = $inventory['ids']['soutenance_ids'];
        $compteRenduIds = $inventory['ids']['compte_rendu_ids'];
        $candidatureIds = $inventory['ids']['candidature_ids'];
        $memoireDocIds = $inventory['ids']['memoire_document_ids'];

        // Séparer les documents par classification
        $docsToDelete = [];
        $docsToKeep = [];
        $docsToDeleteAfterDetach = [];

        foreach ($inventory['documents'] as $doc) {
            if ($doc['classification'] === 'exclusif') {
                $docsToDelete[] = $doc;
            } elseif ($doc['classification'] === 'partage') {
                $docsToKeep[] = $doc;
            } else {
                $docsToDeleteAfterDetach[] = $doc;
            }
        }

        $deletableDocuments = array_merge($docsToDelete, $docsToDeleteAfterDetach);
        $deletableDocIds = array_column($deletableDocuments, 'id_document');
        $deletableDocumentGenereIds = $this->collectDocumentGenereIds($deletableDocuments);

        // Compter les suppressions par table
        $tables = [];
        $tables['pv_etudiants_associes'] = $this->countRows('pv_etudiants_associes', 'num_etu', $ids);
        $tables['documents_consultations'] = $deletableDocIds ? $this->countRowsIn('documents_consultations', 'id_document', $deletableDocIds) : 0;
        $tables['documents'] = count($deletableDocIds);
        $tables['document_genere'] = $deletableDocumentGenereIds ? $this->countRowsIn('document_genere', 'id_document', $deletableDocumentGenereIds) : 0;
        $tables['evaluations_memoires'] = $this->countRowsInOr('evaluations_memoires', 'id_document', $memoireDocIds, 'id_rapport', $rapportIds);
        $tables['memoire_metadonnees'] = $this->countRowsInOr('memoire_metadonnees', 'num_etu', $ids, 'id_rapport', $rapportIds);
        $tables['evaluer'] = $this->countRowsInOr('evaluer', 'num_etudiant', $ids, 'num_jury', $this->numericSoutenanceIds($soutenanceIds));
        $tables['evaluation_soutenance_meta'] = $this->countRowsInOr('evaluation_soutenance_meta', 'num_etudiant', $ids, 'jury_ref', $soutenanceIds);
        $tables['enseignant_jury'] = $soutenanceIds ? $this->countRowsIn('enseignant_jury', 'num_soutenance', $soutenanceIds) : 0;
        $tables['programmer_soutenance'] = $soutenanceIds ? $this->countRowsIn('programmer_soutenance', 'num_soutenance', $soutenanceIds) : 0;
        // Also count by num_etud
        $tables['programmer_soutenance'] += $this->countRowsNotIn('programmer_soutenance', 'num_etud', $ids, 'num_soutenance', $soutenanceIds);

        // CR orphelins
        $orphanCrIds = $this->findOrphanCompteRendus($rapportIds, $ids);
        $tables['rendre'] = $orphanCrIds ? $this->countRowsIn('rendre', 'id_CR', $orphanCrIds) : 0;
        $tables['compte_rendu_rapport_orphans'] = $orphanCrIds ? $this->countRowsIn('compte_rendu_rapport', 'id_CR', $orphanCrIds) : 0;
        $tables['compte_rendu'] = count($orphanCrIds);

        $tables['evaluations_rapports'] = $rapportIds ? $this->countRowsIn('evaluations_rapports', 'id_rapport', $rapportIds) : 0;
        $tables['valider'] = $rapportIds ? $this->countRowsIn('valider', 'id_rapport', $rapportIds) : 0;
        $tables['affecter'] = $rapportIds ? $this->countRowsIn('affecter', 'id_rapport', $rapportIds) : 0;
        $tables['deposer'] = $rapportIds ? $this->countRowsIn('deposer', 'id_rapport', $rapportIds) : 0;
        $tables['rapport_etudiants'] = $rapportIds ? $this->countRowsIn('rapport_etudiants', 'id_rapport', $rapportIds) : 0;
        $tables['resume_candidature'] = $this->countRowsInOr('resume_candidature', 'num_etu', $ids, 'id_candidature', $candidatureIds);
        $tables['candidature_soutenance'] = $this->countRowsInOr('candidature_soutenance', 'num_etu', $ids, 'id_candidature', $candidatureIds);
        $tables['informations_stage'] = $this->countRows('informations_stage', 'num_etu', $ids);
        $tables['notes'] = $this->countRows('notes', 'num_etu', $ids);
        $tables['reclamations'] = $this->countRows('reclamations', 'num_carte_etud', $ids);
        $tables['inscriptions'] = $this->countRows('inscriptions', 'num_carte_etud', $ids);
        $tables['cycle_etudiant_statut'] = $this->countRows('cycle_etudiant_statut', 'num_etu', $ids);

        // Détachements (pas suppression)
        $detachments = [];
        $detachments['pv_etudiants_associes'] = $tables['pv_etudiants_associes'];
        $detachments['compte_rendu_rapport'] = $this->countRowsJoined('compte_rendu_rapport', 'rapport_etudiants', 'id_rapport', 'num_etu', $ids);
        $detachments['programmer_soutenance.id_document_planning'] = $soutenanceIds ? $this->countRowsInNotNull('programmer_soutenance', 'id_document_planning', 'num_soutenance', $soutenanceIds) : 0;

        return [
            'success' => true,
            'inventory' => $inventory,
            'delete_plan' => [
                'tables' => $tables,
                'detachments' => $detachments,
                'documents' => [
                    'delete' => $docsToDelete,
                    'keep_shared' => $docsToKeep,
                    'delete_after_detach' => $docsToDeleteAfterDetach,
                ],
                'orphan_cr_ids' => $orphanCrIds,
            ],
        ];
    }

    // ================================================================
    //  PURGE (EXÉCUTION)
    // ================================================================

    /**
     * Exécute la purge complète du cycle d'un étudiant.
     * Transaction atomique : commit total ou rollback.
     *
     * @param  string $numEtu
     * @param  array  $confirmation  ['irreversible' => true, 'matricule' => string]
     * @return array
     */
    public function purge(string $numEtu, array $confirmation): array
    {
        // Validation
        if (empty($confirmation['irreversible'])) {
            return ['success' => false, 'message' => 'Confirmation irréversible requise.'];
        }

        $resolved = $this->resolveStudentIdentifiers($numEtu);
        if (!$resolved['student']) {
            return ['success' => false, 'message' => 'Étudiant non trouvé.'];
        }

        if ($resolved['primary'] !== $confirmation['matricule']) {
            return ['success' => false, 'message' => 'Le matricule de confirmation ne correspond pas.'];
        }

        $ids = $resolved['identifiers'];
        $primary = $resolved['primary'];

        // Dry-run pour obtenir le plan
        $dryRun = $this->dryRunPurge($numEtu);
        if (!$dryRun['success']) {
            return $dryRun;
        }

        $plan = $dryRun['delete_plan'];
        $deletableDocuments = array_merge($plan['documents']['delete'], $plan['documents']['delete_after_detach']);
        $deletableDocIds = array_column($deletableDocuments, 'id_document');
        $deletableDocumentGenereIds = $this->collectDocumentGenereIds($deletableDocuments);

        $rapportIds = $dryRun['inventory']['ids']['rapport_ids'];
        $soutenanceIds = $dryRun['inventory']['ids']['soutenance_ids'];
        $compteRenduIds = $dryRun['inventory']['ids']['compte_rendu_ids'];
        $candidatureIds = $dryRun['inventory']['ids']['candidature_ids'];
        $memoireDocIds = $dryRun['inventory']['ids']['memoire_document_ids'];
        $orphanCrIds = $plan['orphan_cr_ids'] ?? [];

        $details = ['tables' => [], 'documents' => ['deleted' => 0, 'kept_shared' => 0]];

        $this->pdo->beginTransaction();
        try {
            // ── Étape 1 : Détacher documents collectifs ──────────
            $details['tables']['pv_etudiants_associes'] = $this->deleteRows('pv_etudiants_associes', 'num_etu', $ids);

            if ($soutenanceIds) {
                $ph = $this->placeholders($soutenanceIds);
                $params = $this->paramMap($soutenanceIds);
                $stmt = $this->pdo->prepare("UPDATE programmer_soutenance SET id_document_planning = NULL WHERE num_soutenance IN ($ph)");
                $stmt->execute($params);
                $details['tables']['programmer_soutenance_detach_planning'] = $stmt->rowCount();
            }

            // Détacher CR collectifs
            if ($rapportIds) {
                $ph = $this->placeholders($rapportIds);
                $params = $this->paramMap($rapportIds);
                $stmt = $this->pdo->prepare(
                    "DELETE crr FROM compte_rendu_rapport crr
                     JOIN rapport_etudiants r ON r.id_rapport = crr.id_rapport
                     WHERE r.id_rapport IN ($ph)"
                );
                $stmt->execute($params);
                $details['tables']['compte_rendu_rapport_detach'] = $stmt->rowCount();
            }

            // ── Étape 2 : Traces des documents supprimables ─────
            if ($deletableDocIds) {
                $ph = $this->placeholders($deletableDocIds);
                $params = $this->paramMap($deletableDocIds);

                $stmt = $this->pdo->prepare("DELETE FROM documents_consultations WHERE id_document IN ($ph)");
                $stmt->execute($params);
                $details['tables']['documents_consultations'] = $stmt->rowCount();

                if ($deletableDocumentGenereIds) {
                    $phGen = $this->placeholders($deletableDocumentGenereIds, 'dg');
                    $paramsGen = $this->paramMap($deletableDocumentGenereIds, 'dg');
                    $stmt = $this->pdo->prepare("DELETE FROM document_genere WHERE id_document IN ($phGen)");
                    $stmt->execute($paramsGen);
                    $details['tables']['document_genere'] = $stmt->rowCount();
                } else {
                    $details['tables']['document_genere'] = 0;
                }
            }

            // ── Étape 3 : Évaluations mémoire ───────────────────
            if ($memoireDocIds || $rapportIds) {
                $conditions = [];
                $params = [];
                if ($memoireDocIds) {
                    $ph = $this->placeholders($memoireDocIds, 'md');
                    $conditions[] = "id_document IN ($ph)";
                    $params = array_merge($params, $this->paramMap($memoireDocIds, 'md'));
                }
                if ($rapportIds) {
                    $ph = $this->placeholders($rapportIds, 'rp');
                    $conditions[] = "id_rapport IN ($ph)";
                    $params = array_merge($params, $this->paramMap($rapportIds, 'rp'));
                }
                $where = implode(' OR ', $conditions);
                $stmt = $this->pdo->prepare("DELETE FROM evaluations_memoires WHERE $where");
                $stmt->execute($params);
                $details['tables']['evaluations_memoires'] = $stmt->rowCount();
            }

            // ── Étape 4 : Métadonnées mémoire ───────────────────
            {
                $conditions = [];
                $params = [];
                $ph = $this->placeholders($ids, 'et');
                $conditions[] = "num_etu IN ($ph)";
                $params = array_merge($params, $this->paramMap($ids, 'et'));
                if ($rapportIds) {
                    $ph = $this->placeholders($rapportIds, 'rp');
                    $conditions[] = "id_rapport IN ($ph)";
                    $params = array_merge($params, $this->paramMap($rapportIds, 'rp'));
                }
                if ($memoireDocIds) {
                    $ph = $this->placeholders($memoireDocIds, 'md');
                    $conditions[] = "id_document IN ($ph)";
                    $params = array_merge($params, $this->paramMap($memoireDocIds, 'md'));
                }
                $where = implode(' OR ', $conditions);
                $stmt = $this->pdo->prepare("DELETE FROM memoire_metadonnees WHERE $where");
                $stmt->execute($params);
                $details['tables']['memoire_metadonnees'] = $stmt->rowCount();
            }

            // ── Étape 5 : Évaluations soutenance ────────────────
            if ($soutenanceIds) {
                // Supprimer par num_etudiant d'abord (toujours safe)
                $phEt = $this->placeholders($ids, 'et');
                $paramsEt = $this->paramMap($ids, 'et');
                $stmt = $this->pdo->prepare("DELETE FROM evaluer WHERE num_etudiant IN ($phEt)");
                $stmt->execute($paramsEt);
                $evalRowCount = $stmt->rowCount();

                // Puis par num_jury, uniquement pour les soutenanceIds strictement numériques
                $numericSoutIds = $this->numericSoutenanceIds($soutenanceIds);
                if ($numericSoutIds) {
                    $phNs = $this->placeholders($numericSoutIds, 'nsi');
                    $paramsNs = $this->paramMap($numericSoutIds, 'nsi');
                    // Éviter la double suppression: uniquement les lignes dont num_etudiant n'est pas dans les ids
                    $phEt2 = $this->placeholders($ids, 'et2');
                    $paramsNs = array_merge($paramsNs, $this->paramMap($ids, 'et2'));
                    $stmt = $this->pdo->prepare(
                        "DELETE FROM evaluer WHERE num_jury IN ($phNs) AND num_etudiant NOT IN ($phEt2)"
                    );
                    $stmt->execute($paramsNs);
                    $evalRowCount += $stmt->rowCount();
                }
                $details['tables']['evaluer'] = $evalRowCount;

                // evaluation_soutenance_meta — jury_ref est VARCHAR, toujours safe
                $phEt3 = $this->placeholders($ids, 'et3');
                $paramsSm = $this->paramMap($ids, 'et3');
                $phSu = $this->placeholders($soutenanceIds, 'su');
                $paramsSm = array_merge($paramsSm, $this->paramMap($soutenanceIds, 'su'));
                $stmt = $this->pdo->prepare(
                    "DELETE FROM evaluation_soutenance_meta WHERE num_etudiant IN ($phEt3) OR jury_ref IN ($phSu)"
                );
                $stmt->execute($paramsSm);
                $details['tables']['evaluation_soutenance_meta'] = $stmt->rowCount();
            }

            // ── Étape 6 : Jury et programmation ─────────────────
            if ($soutenanceIds) {
                $ph = $this->placeholders($soutenanceIds);
                $params = $this->paramMap($soutenanceIds);

                $stmt = $this->pdo->prepare("DELETE FROM enseignant_jury WHERE num_soutenance IN ($ph)");
                $stmt->execute($params);
                $details['tables']['enseignant_jury'] = $stmt->rowCount();

                $stmt = $this->pdo->prepare("DELETE FROM programmer_soutenance WHERE num_soutenance IN ($ph) OR num_etud IN (" . $this->placeholders($ids) . ")");
                $stmt->execute(array_merge($params, $this->paramMap($ids)));
                $details['tables']['programmer_soutenance'] = $stmt->rowCount();
            }

            // ── Étape 7 : CR devenus orphelins ──────────────────
            if ($orphanCrIds) {
                $ph = $this->placeholders($orphanCrIds);
                $params = $this->paramMap($orphanCrIds);

                $stmt = $this->pdo->prepare("DELETE FROM rendre WHERE id_CR IN ($ph)");
                $stmt->execute($params);
                $details['tables']['rendre'] = $stmt->rowCount();

                $stmt = $this->pdo->prepare("DELETE FROM compte_rendu_rapport WHERE id_CR IN ($ph)");
                $stmt->execute($params);
                $details['tables']['compte_rendu_rapport_orphans'] = $stmt->rowCount();

                $stmt = $this->pdo->prepare("DELETE FROM compte_rendu WHERE id_CR IN ($ph)");
                $stmt->execute($params);
                $details['tables']['compte_rendu'] = $stmt->rowCount();
            }

            // ── Étape 8 : Validations et évaluations rapport ────
            if ($rapportIds) {
                $ph = $this->placeholders($rapportIds);
                $params = $this->paramMap($rapportIds);

                $stmt = $this->pdo->prepare("DELETE FROM evaluations_rapports WHERE id_rapport IN ($ph)");
                $stmt->execute($params);
                $details['tables']['evaluations_rapports'] = $stmt->rowCount();

                $stmt = $this->pdo->prepare("DELETE FROM valider WHERE id_rapport IN ($ph)");
                $stmt->execute($params);
                $details['tables']['valider'] = $stmt->rowCount();

                $stmt = $this->pdo->prepare("DELETE FROM affecter WHERE id_rapport IN ($ph)");
                $stmt->execute($params);
                $details['tables']['affecter'] = $stmt->rowCount();

                $ph2 = $this->placeholders($rapportIds, 'rp2');
                $params2 = $this->paramMap($rapportIds, 'rp2');
                $ph3 = $this->placeholders($ids, 'et2');
                $params3 = $this->paramMap($ids, 'et2');
                $stmt = $this->pdo->prepare("DELETE FROM deposer WHERE id_rapport IN ($ph2) OR num_etu IN ($ph3)");
                $stmt->execute(array_merge($params2, $params3));
                $details['tables']['deposer'] = $stmt->rowCount();
            }

            // ── Étape 9 : Rapports ──────────────────────────────
            if ($rapportIds) {
                $ph = $this->placeholders($rapportIds, 'rp');
                $params = $this->paramMap($rapportIds, 'rp');
                $ph2 = $this->placeholders($ids, 'et');
                $params2 = $this->paramMap($ids, 'et');
                $stmt = $this->pdo->prepare("DELETE FROM rapport_etudiants WHERE id_rapport IN ($ph) OR num_etu IN ($ph2)");
                $stmt->execute(array_merge($params, $params2));
                $details['tables']['rapport_etudiants'] = $stmt->rowCount();
            }

            // ── Étape 10 : Candidatures ─────────────────────────
            if ($candidatureIds) {
                $ph = $this->placeholders($ids, 'et');
                $params = $this->paramMap($ids, 'et');
                $ph2 = $this->placeholders($candidatureIds, 'ca');
                $params2 = $this->paramMap($candidatureIds, 'ca');

                $stmt = $this->pdo->prepare("DELETE FROM resume_candidature WHERE num_etu IN ($ph) OR id_candidature IN ($ph2)");
                $stmt->execute(array_merge($params, $params2));
                $details['tables']['resume_candidature'] = $stmt->rowCount();

                $stmt = $this->pdo->prepare("DELETE FROM candidature_soutenance WHERE num_etu IN ($ph) OR id_candidature IN ($ph2)");
                $stmt->execute(array_merge($params, $params2));
                $details['tables']['candidature_soutenance'] = $stmt->rowCount();
            } else {
                $details['tables']['resume_candidature'] = $this->deleteRows('resume_candidature', 'num_etu', $ids);
                $details['tables']['candidature_soutenance'] = $this->deleteRows('candidature_soutenance', 'num_etu', $ids);
            }

            // ── Étape 11 : Stage ────────────────────────────────
            $details['tables']['informations_stage'] = $this->deleteRows('informations_stage', 'num_etu', $ids);

            // ── Étape 12 : Notes et réclamations ────────────────
            $details['tables']['notes'] = $this->deleteRows('notes', 'num_etu', $ids);
            $details['tables']['reclamations'] = $this->deleteRows('reclamations', 'num_carte_etud', $ids);

            // ── Étape 13 : Inscriptions ─────────────────────────
            $details['tables']['inscriptions'] = $this->deleteRows('inscriptions', 'num_carte_etud', $ids);

            // ── Étape 14 : Documents BLOB supprimables ──────────
            if ($deletableDocIds) {
                $ph = $this->placeholders($deletableDocIds);
                $params = $this->paramMap($deletableDocIds);
                $stmt = $this->pdo->prepare("DELETE FROM documents WHERE id_document IN ($ph)");
                $stmt->execute($params);
                $details['documents']['deleted'] = $stmt->rowCount();
            }
            $details['documents']['kept_shared'] = count($plan['documents']['keep_shared']);

            // ── Étape 15 : Cache cycle ──────────────────────────
            $details['tables']['cycle_etudiant_statut'] = $this->deleteRows('cycle_etudiant_statut', 'num_etu', $ids);

            // ── Étape 16 : Vérification invariant ───────────────
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM etudiants WHERE num_carte_etud = :p");
            $stmt->execute([':p' => $primary]);
            $studentExists = (int) $stmt->fetchColumn();

            if ($studentExists !== 1) {
                throw new \RuntimeException("Invariant violé : la fiche étudiant $primary n'existe plus après purge.");
            }

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Purge du cycle terminée avec succès.',
                'student' => $resolved['student'],
                'details' => $details,
            ];
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => 'Erreur lors de la purge : ' . $e->getMessage(),
                'details' => $details,
            ];
        }
    }

    // ================================================================
    //  AUDIT
    // ================================================================

    /**
     * Journalise une action dans la table pister.
     */
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
            // L'audit ne doit jamais faire échouer l'opération principale
            error_log("PurgeCycleService::logAudit error: " . $e->getMessage());
        }
    }

    // ================================================================
    //  MÉTHODES INTERNES — COLLECTE
    // ================================================================

    /**
     * Collecte les IDs de soutenance d'un étudiant.
     */
    private function collectSoutenanceIds(array $ids): array
    {
        $ph = $this->placeholders($ids);
        $params = $this->paramMap($ids);
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT num_soutenance FROM programmer_soutenance WHERE num_etud IN ($ph)"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Collecte les IDs de compte-rendu liés aux rapports de l'étudiant.
     */
    private function collectCompteRenduIds(array $rapportIds, array $ids): array
    {
        $crIds = [];

        // Via compte_rendu_rapport
        if ($rapportIds) {
            $ph = $this->placeholders($rapportIds);
            $params = $this->paramMap($rapportIds);
            $stmt = $this->pdo->prepare(
                "SELECT DISTINCT id_CR FROM compte_rendu_rapport WHERE id_rapport IN ($ph)"
            );
            $stmt->execute($params);
            $crIds = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        }

        // Via compte_rendu.num_etu direct
        $ph = $this->placeholders($ids);
        $params = $this->paramMap($ids);
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT id_CR FROM compte_rendu WHERE num_etu IN ($ph)"
        );
        $stmt->execute($params);
        $directCrIds = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        return array_unique(array_merge($crIds, $directCrIds));
    }

    /**
     * Collecte les clés d'inscription (composite key).
     */
    private function collectInscriptionKeys(array $ids): array
    {
        $ph = $this->placeholders($ids);
        $params = $this->paramMap($ids);
        $stmt = $this->pdo->prepare(
            "SELECT CONCAT(num_carte_etud, '-', id_annee_acad, '-', num_versement) as key_val
             FROM inscriptions WHERE num_carte_etud IN ($ph)"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Collecte les IDs de documents mémoire.
     */
    private function collectMemoireDocumentIds(array $ids, array $rapportIds): array
    {
        $docIds = [];

        // Via memoire_metadonnees
        $ph = $this->placeholders($ids);
        $params = $this->paramMap($ids);
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT id_document FROM memoire_metadonnees WHERE num_etu IN ($ph) AND id_document IS NOT NULL"
        );
        $stmt->execute($params);
        $docIds = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        // Via rapport
        if ($rapportIds) {
            $ph = $this->placeholders($rapportIds);
            $params = $this->paramMap($rapportIds);
            $stmt = $this->pdo->prepare(
                "SELECT DISTINCT id_document FROM memoire_metadonnees WHERE id_rapport IN ($ph) AND id_document IS NOT NULL"
            );
            $stmt->execute($params);
            $docIds = array_merge($docIds, $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        }

        return array_unique($docIds);
    }

    /**
     * Collecte les IDs de document_genere liés aux documents.
     */
    private function collectDocumentGenereIds(array $documents): array
    {
        if (empty($documents)) return [];

        $refs = [];
        $paths = [];
        $sourceIds = [];
        $sourceIdsByType = [];
        $blobIds = [];
        foreach ($documents as $doc) {
            if (!empty($doc['id_document'])) $blobIds[] = (int) $doc['id_document'];
            if (!empty($doc['reference'])) $refs[] = $doc['reference'];
            if (!empty($doc['physical_paths'])) $paths = array_merge($paths, $doc['physical_paths']);
            if (!empty($doc['entite_id'])) {
                $sourceIds[] = (string) $doc['entite_id'];
                $legacyTypes = $this->legacyDocumentTypesFor((string) ($doc['type_document'] ?? ''));
                foreach ($legacyTypes as $legacyType) {
                    $sourceIdsByType[$legacyType][] = (string) $doc['entite_id'];
                }
            }
        }

        $ids = [];

        if ($blobIds) {
            $ph = $this->placeholders($blobIds);
            $params = $this->paramMap($blobIds);
            $stmt = $this->pdo->prepare("SELECT id_document FROM document_genere WHERE id_document IN ($ph)");
            $stmt->execute($params);
            $ids = array_merge($ids, $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        }

        if ($refs) {
            $ph = $this->placeholders($refs);
            $params = $this->paramMap($refs);
            $stmt = $this->pdo->prepare("SELECT id_document FROM document_genere WHERE reference IN ($ph)");
            $stmt->execute($params);
            $ids = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        }

        if ($paths) {
            $ph = $this->placeholders($paths);
            $params = $this->paramMap($paths);
            $stmt = $this->pdo->prepare("SELECT id_document FROM document_genere WHERE chemin_fichier IN ($ph)");
            $stmt->execute($params);
            $ids = array_merge($ids, $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        }

        if ($sourceIds) {
            $ph = $this->placeholders($sourceIds);
            $params = $this->paramMap($sourceIds);
            $stmt = $this->pdo->prepare("SELECT id_document FROM document_genere WHERE id_source IN ($ph)");
            $stmt->execute($params);
            $ids = array_merge($ids, $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        }

        foreach ($sourceIdsByType as $legacyType => $typedSourceIds) {
            $typedSourceIds = array_values(array_unique($typedSourceIds));
            if (!$typedSourceIds) {
                continue;
            }

            $ph = $this->placeholders($typedSourceIds, 'src');
            $params = $this->paramMap($typedSourceIds, 'src');
            $params[':type_document'] = $legacyType;
            $stmt = $this->pdo->prepare("SELECT id_document FROM document_genere WHERE type_document = :type_document AND id_source IN ($ph)");
            $stmt->execute($params);
            $ids = array_merge($ids, $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        }

        return array_unique($ids);
    }

    private function legacyDocumentTypesFor(string $typeDocument): array
    {
        $map = [
            'rapport' => ['RAP'],
            'html_doc' => ['RAP'],
            'pv_commission' => ['PVC'],
            'compte_rendu' => ['PVC'],
            'bulletin' => ['PVC'],
            'pv_final' => ['PVF', 'PV_FINAL'],
            'planning' => ['PLN'],
        ];

        return $map[$typeDocument] ?? [];
    }

    /**
     * Trouve les CR qui deviendront orphelins après suppression des rapports de l'étudiant.
     */
    private function findOrphanCompteRendus(array $rapportIds, array $ids): array
    {
        if (empty($rapportIds)) return [];

        // CR liés aux rapports de l'étudiant
        $ph = $this->placeholders($rapportIds);
        $params = $this->paramMap($rapportIds);
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT id_CR FROM compte_rendu_rapport WHERE id_rapport IN ($ph)"
        );
        $stmt->execute($params);
        $crIds = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        // Aussi les CR directs de l'étudiant
        $ph2 = $this->placeholders($ids);
        $params2 = $this->paramMap($ids);
        $stmt = $this->pdo->prepare("SELECT DISTINCT id_CR FROM compte_rendu WHERE num_etu IN ($ph2)");
        $stmt->execute($params2);
        $directCrIds = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];

        $allCrIds = array_unique(array_merge($crIds, $directCrIds));
        if (empty($allCrIds)) return [];

        // Pour chaque CR, vérifier s'il a d'autres rapports non liés à l'étudiant
        $orphanIds = [];
        $ph3 = $this->placeholders($rapportIds);
        $params3 = $this->paramMap($rapportIds);

        foreach ($allCrIds as $crId) {
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM compte_rendu_rapport crr
                 JOIN rapport_etudiants r ON r.id_rapport = crr.id_rapport
                 WHERE crr.id_CR = :cr AND r.id_rapport NOT IN ($ph3)"
            );
            $paramsCheck = array_merge([':cr' => $crId], $params3);
            $stmt->execute($paramsCheck);
            $otherRapports = (int) $stmt->fetchColumn();

            if ($otherRapports === 0) {
                $orphanIds[] = $crId;
            }
        }

        return $orphanIds;
    }

    // ================================================================
    //  CLASSIFICATION DOCUMENTAIRE
    // ================================================================

    /**
     * Collecte tous les documents associés à l'étudiant et les classe.
     */
    private function collectAndClassifyDocuments(array $resolved, array $rapportIds, array $soutenanceIds, array $compteRenduIds): array
    {
        $ids = $resolved['identifiers'];
        $documents = [];

        // 1. Documents liés aux rapports
        if ($rapportIds) {
            $ph = $this->placeholders($rapportIds);
            $params = $this->paramMap($rapportIds);
            $stmt = $this->pdo->prepare(
                "SELECT id_document, type_document, entite_type, entite_id, reference, chemin_original
                 FROM documents WHERE entite_type = 'rapport_etudiants' AND entite_id IN ($ph) AND statut = 'actif'"
            );
            $stmt->execute($params);
            $documents = array_merge($documents, $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
        }

        // 2. Documents liés aux inscriptions
        $inscriptionKeys = $this->collectInscriptionKeys($ids);
        foreach ($inscriptionKeys as $key) {
            $stmt = $this->pdo->prepare(
                "SELECT id_document, type_document, entite_type, entite_id, reference, chemin_original
                 FROM documents WHERE entite_type = 'inscriptions' AND entite_id = :eid AND statut = 'actif'"
            );
            $stmt->execute([':eid' => $key]);
            $documents = array_merge($documents, $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
        }

        // 3. Documents liés aux CR
        if ($compteRenduIds) {
            $ph = $this->placeholders($compteRenduIds);
            $params = $this->paramMap($compteRenduIds);
            $stmt = $this->pdo->prepare(
                "SELECT id_document, type_document, entite_type, entite_id, reference, chemin_original
                 FROM documents WHERE entite_type = 'compte_rendu' AND entite_id IN ($ph) AND statut = 'actif'"
            );
            $stmt->execute($params);
            $documents = array_merge($documents, $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
        }

        // 4. Documents liés aux soutenances
        if ($soutenanceIds) {
            $ph = $this->placeholders($soutenanceIds);
            $params = $this->paramMap($soutenanceIds);
            $stmt = $this->pdo->prepare(
                "SELECT id_document, type_document, entite_type, entite_id, reference, chemin_original
                 FROM documents WHERE entite_type = 'programmer_soutenance' AND entite_id IN ($ph) AND statut = 'actif'"
            );
            $stmt->execute($params);
            $documents = array_merge($documents, $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
        }

        // 5. Documents via pv_etudiants_associes
        $ph = $this->placeholders($ids);
        $params = $this->paramMap($ids);
        $stmt = $this->pdo->prepare(
            "SELECT DISTINCT d.id_document, d.type_document, d.entite_type, d.entite_id, d.reference, d.chemin_original
             FROM documents d
             JOIN pv_etudiants_associes pva ON pva.id_document = d.id_document
             WHERE pva.num_etu IN ($ph) AND d.statut = 'actif'"
        );
        $stmt->execute($params);
        $documents = array_merge($documents, $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);

        // Dédupliquer par id_document
        $uniqueDocs = [];
        foreach ($documents as $doc) {
            $uniqueDocs[$doc['id_document']] = $doc;
        }
        $documents = array_values($uniqueDocs);

        // Classifier chaque document
        foreach ($documents as &$doc) {
            $classification = $this->classifyDocument($doc, $ids);
            $doc['classification'] = $classification['status'];
            $doc['reason'] = $classification['reason'];
            $doc['other_student_refs_count'] = $classification['other_refs'];
            $doc['physical_paths'] = $this->resolvePhysicalPaths($doc);
        }
        unset($doc);

        return $documents;
    }

    /**
     * Classe un document comme exclusif, partagé ou orphelin_apres_detachement.
     */
    private function classifyDocument(array $doc, array $ids): array
    {
        $docId = $doc['id_document'];
        $otherRefs = $this->countOtherStudentReferences($docId, $ids);

        if ($otherRefs === 0) {
            // Vérifier si le document devient orphelin après détachement
            // Un planning partagé qui ne concerne que cet étudiant
            if ($doc['type_document'] === 'planning') {
                // Vérifier les soutenances restantes
                $ph = $this->placeholders($ids);
                $params = $this->paramMap($ids);
                $stmt = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM programmer_soutenance ps
                     WHERE ps.id_document_planning = :docId
                     AND ps.num_etud NOT IN ($ph)"
                );
                $params[':docId'] = $docId;
                $stmt->execute($params);
                $otherSoutenances = (int) $stmt->fetchColumn();

                if ($otherSoutenances === 0) {
                    return ['status' => 'orphelin_apres_detachement', 'reason' => 'Planning ne concerne que cet étudiant après détachement', 'other_refs' => 0];
                }
            }

            return ['status' => 'exclusif', 'reason' => 'Aucune autre référence étudiant', 'other_refs' => 0];
        }

        return ['status' => 'partage', 'reason' => "Référencé par $otherRefs autre(s) association(s) étudiant", 'other_refs' => $otherRefs];
    }

    /**
     * Compte les références actives d'autres étudiants vers un document.
     */
    private function countOtherStudentReferences(int $docId, array $ids): int
    {
        $count = 0;
        $ph = $this->placeholders($ids);

        // 1. pv_etudiants_associes
        $params1 = $this->paramMap($ids);
        $params1[':docId'] = $docId;
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM pv_etudiants_associes WHERE id_document = :docId AND num_etu NOT IN ($ph)"
        );
        $stmt->execute($params1);
        $count += (int) $stmt->fetchColumn();

        // 2. programmer_soutenance.id_document_planning
        $params2 = $this->paramMap($ids);
        $params2[':docId2'] = $docId;
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM programmer_soutenance WHERE id_document_planning = :docId2 AND num_etud NOT IN ($ph)"
        );
        $stmt->execute($params2);
        $count += (int) $stmt->fetchColumn();

        // 3. Via entite_type/entite_id
        $docStmt = $this->pdo->prepare("SELECT entite_type, entite_id FROM documents WHERE id_document = :docId3");
        $docStmt->execute([':docId3' => $docId]);
        $docInfo = $docStmt->fetch(\PDO::FETCH_ASSOC);

        if ($docInfo) {
            switch ($docInfo['entite_type']) {
                case 'rapport_etudiants':
                    $paramsR = $this->paramMap($ids);
                    $paramsR[':eid'] = $docInfo['entite_id'];
                    $stmt = $this->pdo->prepare(
                        "SELECT COUNT(*) FROM rapport_etudiants WHERE id_rapport = :eid AND num_etu NOT IN ($ph)"
                    );
                    $stmt->execute($paramsR);
                    $count += (int) $stmt->fetchColumn();
                    break;

                case 'compte_rendu':
                    $paramsCR = $this->paramMap($ids);
                    $paramsCR[':eid2'] = $docInfo['entite_id'];
                    $stmt = $this->pdo->prepare(
                        "SELECT COUNT(*) FROM compte_rendu_rapport crr
                         JOIN rapport_etudiants r ON r.id_rapport = crr.id_rapport
                         WHERE crr.id_CR = :eid2 AND r.num_etu NOT IN ($ph)"
                    );
                    $stmt->execute($paramsCR);
                    $count += (int) $stmt->fetchColumn();
                    break;

                case 'programmer_soutenance':
                    $paramsPS = $this->paramMap($ids);
                    $paramsPS[':eid3'] = $docInfo['entite_id'];
                    $stmt = $this->pdo->prepare(
                        "SELECT COUNT(*) FROM programmer_soutenance WHERE num_soutenance = :eid3 AND num_etud NOT IN ($ph)"
                    );
                    $stmt->execute($paramsPS);
                    $count += (int) $stmt->fetchColumn();
                    break;

                case 'inscriptions':
                    $isOurInscription = false;
                    foreach ($ids as $id) {
                        if (str_starts_with($docInfo['entite_id'], $id)) {
                            $isOurInscription = true;
                            break;
                        }
                    }
                    if (!$isOurInscription) {
                        $count++;
                    }
                    break;
            }
        }

        return $count;
    }

    /**
     * Résout les chemins physiques historiques d'un document.
     */
    private function resolvePhysicalPaths(array $doc): array
    {
        $paths = [];

        if (!empty($doc['chemin_original'])) {
            $paths[] = $doc['chemin_original'];
        }

        // Chercher dans document_genere
        $stmt = $this->pdo->prepare(
            "SELECT chemin_fichier FROM document_genere WHERE id_document = :id OR reference = :ref"
        );
        $stmt->execute([':id' => $doc['id_document'], ':ref' => $doc['reference'] ?? '']);
        $generePaths = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        $paths = array_merge($paths, $generePaths);

        return array_unique($paths);
    }

    // ================================================================
    //  MÉTHODES UTILITAIRES SQL
    // ================================================================

    /**
     * Génère une liste de placeholders nommés.
     */
    private function placeholders(array $values, string $prefix = 'p'): string
    {
        $parts = [];
        foreach (array_values($values) as $i => $v) {
            $parts[] = ":{$prefix}{$i}";
        }
        return implode(', ', $parts);
    }

    /**
     * Génère un tableau de paramètres nommés.
     */
    private function paramMap(array $values, string $prefix = 'p'): array
    {
        $params = [];
        foreach (array_values($values) as $i => $v) {
            $params[":{$prefix}{$i}"] = $v;
        }
        return $params;
    }

    /**
     * Compte les lignes d'une table pour un ensemble d'identifiants.
     */
    private function countRows(string $table, string $column, array $ids): int
    {
        if (empty($ids)) return 0;
        $ph = $this->placeholders($ids);
        $params = $this->paramMap($ids);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` IN ($ph)");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Compte via jointure (table A -> rapport_etudiants -> num_etu).
     */
    private function countRowsJoined(string $table, string $joinTable, string $joinCol, string $filterCol, array $ids): int
    {
        if (empty($ids)) return 0;
        $ph = $this->placeholders($ids);
        $params = $this->paramMap($ids);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM `$table` t
             JOIN `$joinTable` j ON j.`$joinCol` = t.`$joinCol`
             WHERE j.`$filterCol` IN ($ph)"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Compte les lignes d'une table où une colonne est dans une liste.
     */
    private function countRowsIn(string $table, string $column, array $values): int
    {
        if (empty($values)) return 0;
        $ph = $this->placeholders($values);
        $params = $this->paramMap($values);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$column` IN ($ph)");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Filtre les soutenanceIds pour ne garder que les valeurs strictement numériques.
     * Utilisé pour les requêtes sur evaluer.num_jury (INT) car num_soutenance est VARCHAR.
     */
    private function numericSoutenanceIds(array $soutenanceIds): array
    {
        return array_values(array_filter($soutenanceIds, static function ($v) {
            $s = (string) $v;
            return $s !== '' && ctype_digit($s);
        }));
    }

    /**
     * Compte les lignes avec OR entre deux colonnes.
     */
    private function countRowsInOr(string $table, string $col1, array $vals1, string $col2, array $vals2): int
    {
        $conditions = [];
        $params = [];
        if (!empty($vals1)) {
            $ph = $this->placeholders($vals1, 'a');
            $conditions[] = "`$col1` IN ($ph)";
            $params = array_merge($params, $this->paramMap($vals1, 'a'));
        }
        if (!empty($vals2)) {
            $ph = $this->placeholders($vals2, 'b');
            $conditions[] = "`$col2` IN ($ph)";
            $params = array_merge($params, $this->paramMap($vals2, 'b'));
        }
        if (empty($conditions)) return 0;

        $where = implode(' OR ', $conditions);
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE $where");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Compte les lignes où col1 IN (vals1) ET col2 IS NOT NULL, mais col_soutenance NOT IN (excluded).
     */
    private function countRowsNotIn(string $table, string $col, array $ids, string $excludeCol, array $excludeIds): int
    {
        if (empty($ids)) return 0;
        $ph = $this->placeholders($ids);
        $params = $this->paramMap($ids);
        $sql = "SELECT COUNT(*) FROM `$table` WHERE `$col` IN ($ph)";
        if (!empty($excludeIds)) {
            $ph2 = $this->placeholders($excludeIds, 'ex');
            $sql .= " AND `$excludeCol` NOT IN ($ph2)";
            $params = array_merge($params, $this->paramMap($excludeIds, 'ex'));
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Compte les lignes où une colonne est dans une liste ET une autre n'est pas NULL.
     */
    private function countRowsInNotNull(string $table, string $notNullCol, string $inCol, array $values): int
    {
        if (empty($values)) return 0;
        $ph = $this->placeholders($values);
        $params = $this->paramMap($values);
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM `$table` WHERE `$inCol` IN ($ph) AND `$notNullCol` IS NOT NULL"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Supprime les lignes d'une table pour un ensemble d'identifiants.
     */
    private function deleteRows(string $table, string $column, array $ids): int
    {
        if (empty($ids)) return 0;
        $ph = $this->placeholders($ids);
        $params = $this->paramMap($ids);
        $stmt = $this->pdo->prepare("DELETE FROM `$table` WHERE `$column` IN ($ph)");
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Collecte une colonne d'une table filtrée par identifiants.
     */
    private function collectColumn(string $table, string $column, string $filterCol, array $ids): array
    {
        if (empty($ids)) return [];
        $ph = $this->placeholders($ids);
        $params = $this->paramMap($ids);
        $stmt = $this->pdo->prepare("SELECT DISTINCT `$column` FROM `$table` WHERE `$filterCol` IN ($ph)");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }
}
