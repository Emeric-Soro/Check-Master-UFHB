<?php

/**
 * Model Permission
 * Gère les permissions granulaires du système
 * 
 * @author CheckMaster Team
 * @version 1. 0
 */
class Permission
{
    private $db;
    private static $cache = [];
    private static $cacheEnabled = true;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Vérifie si un groupe a une permission sur un traitement pour une action donnée
     * 
     * @param int $idGU ID du groupe utilisateur
     * @param string $libTraitement Libellé du traitement (page)
     * @param string $libAction Libellé de l'action
     * @param array $context Contexte optionnel pour les conditions avancées
     * @return bool
     */
    public function hasPermission(int $idGU, string $libTraitement, string $libAction, array $context = []): bool
    {
        $cacheKey = "{$idGU}:{$libTraitement}:{$libAction}";
        
        // Vérifier le cache (uniquement sans contexte)
        if (self::$cacheEnabled && isset(self::$cache[$cacheKey]) && empty($context)) {
            return self::$cache[$cacheKey];
        }

        try {
            $sql = "
                SELECT p.autorise, p.conditions, p.id_permission
                FROM permissions p
                JOIN traitement t ON p. id_traitement = t.id_traitement
                JOIN action a ON p.id_action = a.id_action
                WHERE p. id_GU = :id_GU 
                AND t.lib_traitement = :lib_traitement 
                AND a. lib_action = :lib_action
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_GU' => $idGU,
                ':lib_traitement' => $libTraitement,
                ':lib_action' => $libAction
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Si pas de permission définie ou non autorisée
            if (! $result || !$result['autorise']) {
                if (empty($context)) {
                    self::$cache[$cacheKey] = false;
                }
                return false;
            }

            // Vérifier les conditions JSON si présentes
            if ($result['conditions'] && ! empty($context)) {
                $conditions = json_decode($result['conditions'], true);
                if ($conditions && ! $this->evaluateJsonConditions($conditions, $context)) {
                    return false;
                }
            }

            // Vérifier les conditions avancées si contexte fourni
            if (! empty($context)) {
                if (!$this->evaluateAdvancedConditions($result['id_permission'], $context)) {
                    return false;
                }
            }

            // Mettre en cache si pas de contexte
            if (empty($context)) {
                self::$cache[$cacheKey] = true;
            }

            return true;

        } catch (PDOException $e) {
            error_log("Erreur Permission::hasPermission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un groupe a accès à un traitement (page)
     * Utilise la table rattacher existante
     * 
     * @param int $idGU ID du groupe utilisateur
     * @param string $libTraitement Libellé du traitement
     * @return bool
     */
    public function hasAccess(int $idGU, string $libTraitement): bool
    {
        try {
            $sql = "
                SELECT COUNT(*) 
                FROM rattacher r
                JOIN traitement t ON r.id_traitement = t.id_traitement
                WHERE r.id_GU = :id_GU AND t.lib_traitement = :lib_traitement
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_GU' => $idGU,
                ':lib_traitement' => $libTraitement
            ]);

            return $stmt->fetchColumn() > 0;

        } catch (PDOException $e) {
            error_log("Erreur Permission::hasAccess: " .  $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les permissions d'un groupe sous forme de matrice
     * 
     * @param int $idGU ID du groupe utilisateur
     * @return array Matrice des permissions
     */
    public function getPermissionsMatrice(int $idGU): array
    {
        try {
            $sql = "
                SELECT 
                    t.id_traitement,
                    t.lib_traitement,
                    t.label_traitement,
                    t.ordre_traitement,
                    a.id_action,
                    a.lib_action,
                    COALESCE(p.autorise, 0) as autorise,
                    p.conditions
                FROM traitement t
                CROSS JOIN action a
                LEFT JOIN permissions p ON 
                    p.id_traitement = t.id_traitement 
                    AND p. id_action = a.id_action 
                    AND p.id_GU = :id_GU
                ORDER BY t.ordre_traitement, t. label_traitement, a.lib_action
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_GU' => $idGU]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organiser en matrice
            $matrice = [];
            foreach ($rows as $row) {
                $traitementLib = $row['lib_traitement'];
                if (!isset($matrice[$traitementLib])) {
                    $matrice[$traitementLib] = [
                        'id_traitement' => $row['id_traitement'],
                        'label_traitement' => $row['label_traitement'],
                        'ordre_traitement' => $row['ordre_traitement'],
                        'actions' => []
                    ];
                }
                $matrice[$traitementLib]['actions'][$row['lib_action']] = [
                    'id_action' => $row['id_action'],
                    'autorise' => (bool)$row['autorise'],
                    'conditions' => $row['conditions'] ? json_decode($row['conditions'], true) : null
                ];
            }

            return $matrice;

        } catch (PDOException $e) {
            error_log("Erreur Permission::getPermissionsMatrice: " .  $e->getMessage());
            return [];
        }
    }

    /**
     * Définit une permission
     * 
     * @param int $idGU ID du groupe
     * @param int $idTraitement ID du traitement
     * @param int $idAction ID de l'action
     * @param bool $autorise Permission accordée ou non
     * @param array|null $conditions Conditions optionnelles
     * @param int|null $creePar ID de l'utilisateur créateur
     * @return bool
     */
    public function setPermission(
        int $idGU, 
        int $idTraitement, 
        int $idAction, 
        bool $autorise, 
        ?array $conditions = null, 
        ?int $creePar = null
    ): bool {
        try {
            $sql = "
                INSERT INTO permissions (id_GU, id_traitement, id_action, autorise, conditions, cree_par)
                VALUES (:id_GU, :id_traitement, :id_action, :autorise, :conditions, :cree_par)
                ON DUPLICATE KEY UPDATE 
                    autorise = VALUES(autorise),
                    conditions = VALUES(conditions),
                    date_modification = CURRENT_TIMESTAMP
            ";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':id_GU' => $idGU,
                ':id_traitement' => $idTraitement,
                ':id_action' => $idAction,
                ':autorise' => $autorise ?  1 : 0,
                ':conditions' => $conditions ? json_encode($conditions) : null,
                ':cree_par' => $creePar
            ]);

            // Invalider le cache pour ce groupe
            $this->clearCache($idGU);

            return $result;

        } catch (PDOException $e) {
            error_log("Erreur Permission::setPermission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour plusieurs permissions en lot
     * 
     * @param int $idGU ID du groupe
     * @param array $permissions Tableau de permissions
     * @param int|null $creePar ID de l'utilisateur créateur
     * @return bool
     */
    public function setPermissionsBatch(int $idGU, array $permissions, ? int $creePar = null): bool
    {
        try {
            $this->db->beginTransaction();

            foreach ($permissions as $perm) {
                $this->setPermission(
                    $idGU,
                    $perm['id_traitement'],
                    $perm['id_action'],
                    $perm['autorise'],
                    $perm['conditions'] ??  null,
                    $creePar
                );
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur Permission::setPermissionsBatch: " .  $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une permission
     * 
     * @param int $idGU ID du groupe
     * @param int $idTraitement ID du traitement
     * @param int $idAction ID de l'action
     * @return bool
     */
    public function deletePermission(int $idGU, int $idTraitement, int $idAction): bool
    {
        try {
            $sql = "DELETE FROM permissions WHERE id_GU = ?  AND id_traitement = ? AND id_action = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idGU, $idTraitement, $idAction]);
            
            $this->clearCache($idGU);
            return true;

        } catch (PDOException $e) {
            error_log("Erreur Permission::deletePermission: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les actions autorisées pour un groupe sur un traitement
     * 
     * @param int $idGU ID du groupe
     * @param string $libTraitement Libellé du traitement
     * @return array Liste des actions autorisées
     */
    public function getActionsAutorisees(int $idGU, string $libTraitement): array
    {
        try {
            $sql = "
                SELECT a.lib_action
                FROM permissions p
                JOIN traitement t ON p.id_traitement = t. id_traitement
                JOIN action a ON p.id_action = a.id_action
                WHERE p.id_GU = :id_GU 
                AND t. lib_traitement = :lib_traitement
                AND p.autorise = 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_GU' => $idGU,
                ':lib_traitement' => $libTraitement
            ]);

            return $stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch (PDOException $e) {
            error_log("Erreur Permission::getActionsAutorisees: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère toutes les actions disponibles
     * 
     * @return array
     */
    public function getAllActions(): array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM action ORDER BY id_action");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur Permission::getAllActions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les traitements
     * 
     * @return array
     */
    public function getAllTraitements(): array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM traitement ORDER BY ordre_traitement, label_traitement");
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur Permission::getAllTraitements: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Copie les permissions d'un groupe vers un autre
     * 
     * @param int $fromGU ID du groupe source
     * @param int $toGU ID du groupe destination
     * @param int|null $creePar ID de l'utilisateur
     * @return bool
     */
    public function copyPermissions(int $fromGU, int $toGU, ?int $creePar = null): bool
    {
        try {
            $this->db->beginTransaction();

            // Supprimer les permissions existantes du groupe cible
            $stmt = $this->db->prepare("DELETE FROM permissions WHERE id_GU = ?");
            $stmt->execute([$toGU]);

            // Copier les permissions
            $sql = "
                INSERT INTO permissions (id_GU, id_traitement, id_action, autorise, conditions, cree_par)
                SELECT :to_GU, id_traitement, id_action, autorise, conditions, :cree_par
                FROM permissions WHERE id_GU = :from_GU
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':to_GU' => $toGU,
                ':from_GU' => $fromGU,
                ':cree_par' => $creePar
            ]);

            $this->db->commit();
            $this->clearCache($toGU);
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur Permission::copyPermissions: " .  $e->getMessage());
            return false;
        }
    }

    /**
     * Initialise les permissions par défaut pour un groupe
     * Accorde toutes les permissions de consultation par défaut
     * 
     * @param int $idGU ID du groupe
     * @param int|null $creePar ID de l'utilisateur
     * @return bool
     */
    public function initDefaultPermissions(int $idGU, ? int $creePar = null): bool
    {
        try {
            // Récupérer l'ID de l'action "Consulter"
            $stmt = $this->db->prepare("SELECT id_action FROM action WHERE lib_action = 'Consulter'");
            $stmt->execute();
            $actionConsulter = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$actionConsulter) {
                return false;
            }

            // Récupérer tous les traitements
            $traitements = $this->getAllTraitements();

            $this->db->beginTransaction();

            foreach ($traitements as $traitement) {
                $this->setPermission(
                    $idGU,
                    $traitement->id_traitement,
                    $actionConsulter['id_action'],
                    true,
                    null,
                    $creePar
                );
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur Permission::initDefaultPermissions: " .  $e->getMessage());
            return false;
        }
    }

    /**
     * Évalue les conditions JSON
     * 
     * @param array $conditions Conditions à évaluer
     * @param array $context Contexte d'évaluation
     * @return bool
     */
    private function evaluateJsonConditions(array $conditions, array $context): bool
    {
        foreach ($conditions as $key => $value) {
            switch ($key) {
                case 'own_data':
                    // L'utilisateur ne peut accéder qu'à ses propres données
                    if ($value && isset($context['owner_id']) && isset($context['user_id'])) {
                        if ($context['owner_id'] != $context['user_id']) {
                            return false;
                        }
                    }
                    break;

                case 'time_range':
                    // Restriction horaire
                    $now = date('H:i');
                    if (isset($value['start']) && $now < $value['start']) return false;
                    if (isset($value['end']) && $now > $value['end']) return false;
                    break;

                case 'status_in':
                    // Restriction par statut
                    if (isset($context['status']) && ! in_array($context['status'], $value)) {
                        return false;
                    }
                    break;

                case 'max_per_day':
                    // Limite d'actions par jour
                    if (isset($context['action_count']) && $context['action_count'] >= $value) {
                        return false;
                    }
                    break;

                case 'niveau_etude':
                    // Restriction par niveau d'étude
                    if (isset($context['niveau_id']) && !in_array($context['niveau_id'], (array)$value)) {
                        return false;
                    }
                    break;
            }
        }
        return true;
    }

    /**
     * Évalue les conditions avancées depuis la table conditions_permissions
     * 
     * @param int $idPermission ID de la permission
     * @param array $context Contexte d'évaluation
     * @return bool
     */
    private function evaluateAdvancedConditions(int $idPermission, array $context): bool
    {
        try {
            $sql = "SELECT * FROM conditions_permissions WHERE id_permission = ?  ORDER BY id_condition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$idPermission]);
            $conditions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($conditions)) {
                return true;
            }

            $results = [];
            $currentLogic = 'AND';

            foreach ($conditions as $condition) {
                $fieldValue = $context[$condition['champ']] ?? null;
                $conditionValue = $condition['valeur'];

                $match = $this->evaluateOperator($fieldValue, $condition['operateur'], $conditionValue);

                $results[] = ['match' => $match, 'logic' => $currentLogic];
                $currentLogic = $condition['logique'];
            }

            // Évaluer le résultat final
            $finalResult = true;
            foreach ($results as $i => $r) {
                if ($i === 0) {
                    $finalResult = $r['match'];
                } else {
                    if ($r['logic'] === 'AND') {
                        $finalResult = $finalResult && $r['match'];
                    } else {
                        $finalResult = $finalResult || $r['match'];
                    }
                }
            }

            return $finalResult;

        } catch (PDOException $e) {
            error_log("Erreur Permission::evaluateAdvancedConditions: " . $e->getMessage());
            return true; // En cas d'erreur, on autorise par défaut
        }
    }

    /**
     * Évalue un opérateur de comparaison
     * 
     * @param mixed $fieldValue Valeur du champ
     * @param string $operator Opérateur
     * @param mixed $conditionValue Valeur de la condition
     * @return bool
     */
    private function evaluateOperator($fieldValue, string $operator, $conditionValue): bool
    {
        switch ($operator) {
            case '=':
                return $fieldValue == $conditionValue;
            case '!=':
                return $fieldValue != $conditionValue;
            case '>':
                return $fieldValue > $conditionValue;
            case '<':
                return $fieldValue < $conditionValue;
            case '>=':
                return $fieldValue >= $conditionValue;
            case '<=':
                return $fieldValue <= $conditionValue;
            case 'IN':
                $values = json_decode($conditionValue, true) ?: explode(',', $conditionValue);
                return in_array($fieldValue, array_map('trim', $values));
            case 'NOT IN':
                $values = json_decode($conditionValue, true) ?: explode(',', $conditionValue);
                return !in_array($fieldValue, array_map('trim', $values));
            case 'LIKE':
                return fnmatch($conditionValue, $fieldValue);
            default:
                return false;
        }
    }

    /**
     * Vide le cache des permissions
     * 
     * @param int|null $idGU ID du groupe (null pour tout vider)
     */
    public function clearCache(? int $idGU = null): void
    {
        if ($idGU === null) {
            self::$cache = [];
        } else {
            foreach (self::$cache as $key => $value) {
                if (strpos($key, "{$idGU}:") === 0) {
                    unset(self::$cache[$key]);
                }
            }
        }
    }

    /**
     * Active ou désactive le cache
     * 
     * @param bool $enabled
     */
    public static function setCacheEnabled(bool $enabled): void
    {
        self::$cacheEnabled = $enabled;
    }

    /**
     * Récupère les statistiques des permissions
     * 
     * @return array
     */
    public function getStatistiques(): array
    {
        try {
            $stats = [];

            // Total des permissions
            $stmt = $this->db->query("SELECT COUNT(*) FROM permissions");
            $stats['total_permissions'] = $stmt->fetchColumn();

            // Permissions par groupe
            $stmt = $this->db->query("
                SELECT gu.lib_GU, COUNT(p.id_permission) as nb_permissions,
                       SUM(CASE WHEN p.autorise = 1 THEN 1 ELSE 0 END) as nb_autorisees
                FROM groupe_utilisateur gu
                LEFT JOIN permissions p ON gu.id_GU = p.id_GU
                GROUP BY gu. id_GU, gu.lib_GU
                ORDER BY gu.lib_GU
            ");
            $stats['par_groupe'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Permissions par action
            $stmt = $this->db->query("
                SELECT a.lib_action, 
                       SUM(CASE WHEN p.autorise = 1 THEN 1 ELSE 0 END) as nb_autorisees,
                       SUM(CASE WHEN p.autorise = 0 THEN 1 ELSE 0 END) as nb_refusees
                FROM action a
                LEFT JOIN permissions p ON a.id_action = p.id_action
                GROUP BY a.id_action, a. lib_action
                ORDER BY a.lib_action
            ");
            $stats['par_action'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $stats;

        } catch (PDOException $e) {
            error_log("Erreur Permission::getStatistiques: " . $e->getMessage());
            return [];
        }
    }
}