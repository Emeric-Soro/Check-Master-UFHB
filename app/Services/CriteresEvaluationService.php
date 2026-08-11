<?php
namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CritereEvaluation.php';

use CritereEvaluation;
use App\Support\DatabaseService;
use Exception;

class CriteresEvaluationService
{
    private $db;
    private DatabaseService $dbService;
    private $critereModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->dbService = new DatabaseService($db);
        $this->critereModel = new CritereEvaluation($db);
    }

    private function normalizeCritereTokens(string $libelle): array
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $libelle);
        if ($ascii === false) {
            $ascii = $libelle;
        }

        $ascii = strtoupper((string) $ascii);
        $ascii = preg_replace('/[^A-Z0-9]+/', ' ', $ascii);
        $ascii = trim((string) preg_replace('/\s+/', ' ', (string) $ascii));

        return [
            'words' => $ascii === '' ? [] : preg_split('/\s+/', $ascii),
            'compact' => str_replace(' ', '', $ascii),
        ];
    }

    private function addCritereCandidate(array &$candidates, string $candidate): void
    {
        $candidate = strtoupper((string) preg_replace('/[^A-Z0-9]+/', '', $candidate));
        if ($candidate === '') {
            return;
        }
        if (strlen($candidate) === 1) {
            $candidate .= 'X';
        }
        $candidate = substr($candidate, 0, 2);
        if (!in_array($candidate, $candidates, true)) {
            $candidates[] = $candidate;
        }
    }

    private function critereCodeExists(string $codeCritere, ?string $excludeId = null): bool
    {
        $codeCritere = trim(strtoupper($codeCritere));
        if ($codeCritere === '') {
            return false;
        }

        $critere = $this->critereModel->getCritereByCode($codeCritere);
        if (!$critere) {
            return false;
        }

        if ($excludeId !== null && (string) ($critere->id_critere ?? '') === $excludeId) {
            return false;
        }

        return true;
    }

    private function generateCritereCode(string $libelle, ?string $excludeId = null): string
    {
        $tokens = $this->normalizeCritereTokens($libelle);
        $words = is_array($tokens['words']) ? $tokens['words'] : [];
        $compact = (string) ($tokens['compact'] ?? '');
        $candidates = [];

        if (count($words) >= 2) {
            $this->addCritereCandidate($candidates, substr((string) $words[0], 0, 1) . substr((string) $words[1], 0, 1));
        }
        if ($compact !== '') {
            $this->addCritereCandidate($candidates, substr($compact, 0, 2));
        }
        foreach ($words as $word) {
            $this->addCritereCandidate($candidates, substr((string) $word, 0, 2));
        }
        for ($i = 0, $len = strlen($compact); $i < max($len - 1, 0); $i++) {
            $this->addCritereCandidate($candidates, substr($compact, $i, 2));
        }

        foreach ($candidates as $candidate) {
            if (!$this->critereCodeExists($candidate, $excludeId)) {
                return $candidate;
            }
        }

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $alphabetLength = strlen($alphabet);
        for ($i = 0; $i < $alphabetLength; $i++) {
            for ($j = 0; $j < $alphabetLength; $j++) {
                $candidate = $alphabet[$i] . $alphabet[$j];
                if (!$this->critereCodeExists($candidate, $excludeId)) {
                    return $candidate;
                }
            }
        }

        throw new Exception("Impossible de générer un code critère disponible.");
    }

    /**
     * Récupérer toutes les années académiques
     * @return array
     */
    public function getAnneesAcademiques(): array
    {
        return $this->dbService->select(
            "SELECT id_annee_acad as id, CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) as lib FROM annee_academique ORDER BY id_annee_acad DESC"
        );
    }

    /**
     * Récupérer tous les critères d'évaluation avec leurs barèmes
     * @return array
     */
    public function getCriteres(): array
    {
        $results = $this->dbService->select("
            SELECT 
                ce.id_critere as id,
                ce.lib_critere as libelle,
                bc.id_annee_acad as annee_id,
                CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) as annee_lib,
                bc.bareme
            FROM critere_evaluation ce
            LEFT JOIN bareme_critere bc ON ce.id_critere = bc.id_critere
            LEFT JOIN annee_academique aa ON bc.id_annee_acad = aa.id_annee_acad
            ORDER BY ce.id_critere, bc.id_annee_acad DESC
        ");

        // Grouper les résultats par critère
        $criteresData = [];
        foreach ($results as $row) {
            $critereId = $row['id'];

            if (!isset($criteresData[$critereId])) {
                $criteresData[$critereId] = [
                    'id' => $critereId,
                    'libelle' => $row['libelle'],
                    'baremes' => []
                ];
            }

            // Ajouter le barème s'il existe
            if ($row['annee_id'] && $row['bareme'] !== null) {
                $criteresData[$critereId]['baremes'][] = [
                    'annee_id' => $row['annee_id'],
                    'annee_lib' => $row['annee_lib'],
                    'bareme' => (int) $row['bareme']
                ];
            }
        }

        return array_values($criteresData);
    }

    /**
     * Créer un nouveau critère d'évaluation avec ses barèmes
     * @param array $input Données du critère (libelle, baremes)
     * @return string ID du critère créé
     * @throws Exception
     */
    public function createCritere(array $input): string
    {
        if (!isset($input['libelle']) || empty(trim($input['libelle']))) {
            throw new Exception('Le libellé du critère est requis');
        }

        if (!isset($input['baremes']) || empty($input['baremes'])) {
            throw new Exception('Au moins un barème est requis');
        }

        // Validation des barèmes - vérifier que le total par année ne dépasse pas 20
        $this->validateBaremesTotaux($input['baremes'], null);

        try {
            return $this->dbService->transaction(function () use ($input) {
                $libelle = trim((string) $input['libelle']);
                $codeCritere = $this->generateCritereCode($libelle);

                if (!$this->critereModel->creerCritere($codeCritere, $libelle)) {
                    throw new Exception('Erreur lors de la création du critère');
                }

                $critere = $this->critereModel->getCritereByCode($codeCritere);
                $critereId = (string) ($critere->id_critere ?? $codeCritere);
                if ($critereId === '') {
                    throw new Exception("Impossible de récupérer l'identifiant du critère créé");
                }

                // Insérer les barèmes
                foreach ($input['baremes'] as $bareme) {
                    if (
                        isset($bareme['annee_id'], $bareme['bareme']) &&
                        $bareme['annee_id'] !== '' &&
                        $bareme['bareme'] !== ''
                    ) {
                        $this->dbService->execute(
                            'INSERT INTO bareme_critere (id_critere, id_annee_acad, bareme) VALUES (:critere, :annee, :bareme)',
                            [
                                ':critere' => $critereId,
                                ':annee' => $bareme['annee_id'],
                                ':bareme' => (int) $bareme['bareme'],
                            ]
                        );
                    }
                }

                return $critereId;
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Mettre à jour un critère d'évaluation avec ses barèmes
     * @param array $input Données du critère (id, libelle, baremes)
     * @throws Exception
     */
    public function updateCritere(array $input): void
    {
        if (!isset($input['id']) || empty($input['id'])) {
            throw new Exception('ID du critère requis');
        }

        if (!isset($input['libelle']) || empty(trim($input['libelle']))) {
            throw new Exception('Le libellé du critère est requis');
        }

        if (!isset($input['baremes']) || empty($input['baremes'])) {
            throw new Exception('Au moins un barème est requis');
        }

        // Validation des barèmes - vérifier que le total par année ne dépasse pas 20 (en excluant le critère actuel)
        $this->validateBaremesTotaux($input['baremes'], $input['id']);

        try {
            $this->dbService->transaction(function () use ($input) {
                $critereId = trim((string) $input['id']);
                $libelle = trim((string) $input['libelle']);
                $codeCritere = $this->generateCritereCode($libelle, $critereId);

                if (!$this->critereModel->modifierCritere($critereId, $codeCritere, $libelle)) {
                    throw new Exception('Erreur lors de la modification du critère');
                }

                // Supprimer les anciens barèmes
                $this->dbService->execute('DELETE FROM bareme_critere WHERE id_critere = :critere', [':critere' => $critereId]);

                // Insérer les nouveaux barèmes
                foreach ($input['baremes'] as $bareme) {
                    if (
                        isset($bareme['annee_id'], $bareme['bareme']) &&
                        $bareme['annee_id'] !== '' &&
                        $bareme['bareme'] !== ''
                    ) {
                        $this->dbService->execute(
                            'INSERT INTO bareme_critere (id_critere, id_annee_acad, bareme) VALUES (:critere, :annee, :bareme)',
                            [
                                ':critere' => $critereId,
                                ':annee' => $bareme['annee_id'],
                                ':bareme' => (int) $bareme['bareme'],
                            ]
                        );
                    }
                }
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Supprimer un critère d'évaluation et ses barèmes
     * @param array $input Données contenant l'id du critère
     * @throws Exception
     */
    public function deleteCritere(array $input): void
    {
        if (!isset($input['id']) || empty($input['id'])) {
            throw new Exception('ID du critère requis');
        }

        try {
            $this->dbService->transaction(function () use ($input) {
                $critereId = trim((string) $input['id']);

                // Vérifier si le critère existe
                $critere = $this->critereModel->getCritereById($critereId);
                if (!$critere) {
                    throw new Exception('Critère non trouvé');
                }

                // Supprimer les barèmes associés
                $this->dbService->execute('DELETE FROM bareme_critere WHERE id_critere = :critere', [':critere' => $critereId]);

                // Supprimer le critère via le modèle
                if (!$this->critereModel->supprimerCritere($critereId)) {
                    throw new Exception('Erreur lors de la suppression du critère');
                }
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Valider que les totaux des barèmes par année ne dépassent pas 20 points
     * @param array $nouveauxBaremes Nouveaux barèmes à valider
     * @param string|null $critereIdExclure ID du critère à exclure (pour les modifications)
     * @throws Exception
     */
    private function validateBaremesTotaux(array $nouveauxBaremes, ?string $critereIdExclure = null): void
    {
        // Récupérer les totaux actuels par année (en excluant le critère en cours de modification si applicable)
        $sqlExclusion = $critereIdExclure ? " AND bc.id_critere != :exclure" : "";
        $sql = "
            SELECT 
                aa.id_annee_acad,
                CONCAT(aa.date_deb, ' - ', aa.date_fin) as lib_annee,
                COALESCE(SUM(bc.bareme), 0) as total_actuel
            FROM annee_academique aa
            LEFT JOIN bareme_critere bc ON aa.id_annee_acad = bc.id_annee_acad{$sqlExclusion}
            GROUP BY aa.id_annee_acad, aa.date_deb, aa.date_fin
            ORDER BY aa.date_deb DESC
        ";

        $params = $critereIdExclure ? [':exclure' => $critereIdExclure] : [];
        $totauxActuels = $this->dbService->select($sql, $params);

        // Créer un tableau des totaux actuels indexé par année
        $totauxParAnnee = [];
        foreach ($totauxActuels as $total) {
            $totauxParAnnee[$total['id_annee_acad']] = [
                'total' => (float) $total['total_actuel'],
                'lib_annee' => $total['lib_annee']
            ];
        }

        // Calculer les nouveaux totaux avec les barèmes proposés
        foreach ($nouveauxBaremes as $bareme) {
            if (
                isset($bareme['annee_id'], $bareme['bareme']) &&
                $bareme['annee_id'] !== '' &&
                $bareme['bareme'] !== ''
            ) {
                $anneeId = $bareme['annee_id'];
                $points = (float) $bareme['bareme'];

                if (!isset($totauxParAnnee[$anneeId])) {
                    // Récupérer les infos de l'année si pas encore présente
                    $anneeInfo = $this->dbService->selectOne(
                        "SELECT CONCAT(date_deb, ' - ', date_fin) as lib_annee FROM annee_academique WHERE id_annee_acad = :id",
                        [':id' => $anneeId]
                    );

                    $totauxParAnnee[$anneeId] = [
                        'total' => 0,
                        'lib_annee' => $anneeInfo ? $anneeInfo['lib_annee'] : "Année ID $anneeId"
                    ];
                }

                $totauxParAnnee[$anneeId]['total'] += $points;
            }
        }

        // Vérifier les limites
        foreach ($totauxParAnnee as $anneeId => $data) {
            if ($data['total'] > 20) {
                throw new Exception("Le total des points pour l'année {$data['lib_annee']} dépasse 20 points ({$data['total']} points). Veuillez ajuster les barèmes.");
            }
        }
    }
}
