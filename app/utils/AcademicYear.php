<?php

use CheckMaster\Core\Messages;

class AcademicYear
{
    private const ALL_QUERY_VALUE = 'all';
    private const ALL_LABEL = 'Toutes les années';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function fetchAll(\PDO $db): array
    {
        static $cache = [];

        $cacheKey = spl_object_hash($db);
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $today = date('Y-m-d');
        $stmt = $db->query('SELECT id_annee_acad, date_deb, date_fin FROM annee_academique ORDER BY date_deb DESC');

        $years = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $start = (string) ($row['date_deb'] ?? '');
            $end = (string) ($row['date_fin'] ?? '');
            $label = self::formatLabel($start, $end, (string) ($row['id_annee_acad'] ?? ''));

            $years[] = [
                'id' => (int) ($row['id_annee_acad'] ?? 0),
                'label' => $label,
                'date_deb' => $start,
                'date_fin' => $end,
                'is_active' => ($start !== '' && $end !== '' && $today >= $start && $today <= $end),
            ];
        }

        $cache[$cacheKey] = $years;
        return $years;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getActive(\PDO $db): ?array
    {
        $years = self::fetchAll($db);
        foreach ($years as $year) {
            if (!empty($year['is_active'])) {
                return $year;
            }
        }

        return $years[0] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getById(\PDO $db, ?int $id): ?array
    {
        if ($id === null || $id <= 0) {
            return null;
        }

        foreach (self::fetchAll($db) as $year) {
            if ((int) ($year['id'] ?? 0) === $id) {
                return $year;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function getByLabel(\PDO $db, string $label): ?array
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }

        foreach (self::fetchAll($db) as $year) {
            if ((string) ($year['label'] ?? '') === $label) {
                return $year;
            }
        }

        return null;
    }

    public static function getAllQueryValue(): string
    {
        return self::ALL_QUERY_VALUE;
    }

    public static function getAllLabel(): string
    {
        return self::ALL_LABEL;
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $session
     * @return array{
     *     years: array<int, array<string, mixed>>,
     *     selected: array<string, mixed>|null,
     *     active: array<string, mixed>|null,
     *     writable: array<string, mixed>|null,
     *     all_selected: bool
     * }
     */
    public static function bootstrapSession(\PDO $db, array $query, array &$session): array
    {
        $years = self::fetchAll($db);
        $active = self::getActive($db);

        $requestedIdRaw = isset($query['global_annee_id'])
            ? trim((string) $query['global_annee_id'])
            : null;
        $requestedAll = self::isAllSelectionValue($requestedIdRaw);
        $requestedId = !$requestedAll && $requestedIdRaw !== null && is_numeric($requestedIdRaw)
            ? (int) $requestedIdRaw
            : null;
        $requestedLabel = isset($query['global_annee'])
            ? trim((string) $query['global_annee'])
            : '';

        $selected = null;
        $allSelected = $requestedAll;

        if (!$allSelected) {
            $selected = self::getById($db, $requestedId);
        }
        if (!$allSelected && $selected === null && $requestedLabel !== '') {
            if (self::isAllSelectionValue($requestedLabel)) {
                $allSelected = true;
            }
        }
        if (!$allSelected && $selected === null && $requestedLabel !== '') {
            $selected = self::getByLabel($db, $requestedLabel);
        }
        if (!$allSelected && $selected === null) {
            $sessionId = isset($session['global_annee_id']) && is_numeric($session['global_annee_id'])
                ? (int) $session['global_annee_id']
                : null;
            $selected = self::getById($db, $sessionId);
        }
        if (
            !$allSelected
            && !empty($session['global_annee_is_all_selected'])
            && !array_key_exists('global_annee_id', $query)
            && $requestedLabel === ''
        ) {
            $allSelected = true;
        }
        if (!$allSelected && $selected === null && !empty($session['global_annee_selected'])) {
            if (self::isAllSelectionValue((string) $session['global_annee_selected'])) {
                $allSelected = true;
            }
        }
        if (!$allSelected && $selected === null && !empty($session['global_annee_selected'])) {
            $selected = self::getByLabel($db, (string) $session['global_annee_selected']);
        }
        if (!$allSelected && $selected === null) {
            $selected = $active ?: ($years[0] ?? null);
        }

        $selectedContext = $allSelected
            ? [
                'id' => null,
                'label' => self::getAllLabel(),
                'date_deb' => null,
                'date_fin' => null,
                'is_active' => false,
                'is_all' => true,
            ]
            : $selected;
        $writable = $active ?: ($years[0] ?? null);
        $writeAllowed = !empty($writable) && (
            $allSelected
            || (!empty($selected) && (int) ($selected['id'] ?? 0) === (int) ($writable['id'] ?? 0))
        );

        $session['global_annee_id'] = $allSelected ? null : ($selected['id'] ?? null);
        $session['global_annee_selected'] = $selectedContext['label'] ?? '';
        $session['global_annee_active_id'] = $active['id'] ?? null;
        $session['global_annee_active_label'] = $active['label'] ?? '';
        $session['global_annee_is_all_selected'] = $allSelected;
        $session['global_annee_is_active_selected'] = $writeAllowed;
        $session['global_annee_write_allowed'] = $writeAllowed;
        $session['global_annee_writable_id'] = $writable['id'] ?? null;
        $session['global_annee_writable_label'] = $writable['label'] ?? '';

        return [
            'years' => $years,
            'selected' => $selectedContext,
            'active' => $active,
            'writable' => $writable,
            'all_selected' => $allSelected,
        ];
    }

    public static function getSelectedIdFromSession(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE || self::isAllSelectedFromSession()) {
            return null;
        }

        if (!isset($_SESSION['global_annee_id'])) {
            return null;
        }

        return is_numeric($_SESSION['global_annee_id']) ? (int) $_SESSION['global_annee_id'] : null;
    }

    public static function getSelectedLabelFromSession(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        return trim((string) ($_SESSION['global_annee_selected'] ?? ''));
    }

    public static function getActiveIdFromSession(): ?int
    {
        if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['global_annee_active_id'])) {
            return null;
        }

        return is_numeric($_SESSION['global_annee_active_id']) ? (int) $_SESSION['global_annee_active_id'] : null;
    }

    public static function getActiveLabelFromSession(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return '';
        }

        return trim((string) ($_SESSION['global_annee_active_label'] ?? ''));
    }

    public static function isAllSelectedFromSession(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        if (!empty($_SESSION['global_annee_is_all_selected'])) {
            return true;
        }

        return self::isAllSelectionValue($_SESSION['global_annee_selected'] ?? null);
    }

    public static function isWriteAllowedFromSession(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }

        if (array_key_exists('global_annee_write_allowed', $_SESSION)) {
            return !empty($_SESSION['global_annee_write_allowed']);
        }

        return !empty($_SESSION['global_annee_is_active_selected']);
    }

    public static function getWritableIdFromSession(): ?int
    {
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['global_annee_writable_id'])) {
            return is_numeric($_SESSION['global_annee_writable_id']) ? (int) $_SESSION['global_annee_writable_id'] : null;
        }

        $activeId = self::getActiveIdFromSession();
        if ($activeId !== null && $activeId > 0) {
            return $activeId;
        }

        return self::getSelectedIdFromSession();
    }

    public static function getWritableLabelFromSession(): string
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $label = trim((string) ($_SESSION['global_annee_writable_label'] ?? ''));
            if ($label !== '') {
                return $label;
            }
        }

        $activeLabel = self::getActiveLabelFromSession();
        if ($activeLabel !== '') {
            return $activeLabel;
        }

        return self::getSelectedLabelFromSession();
    }

    /**
     * @return array{success: bool, message: string, year: array<string, mixed>|null}
     */
    public static function ensureWritableYear(\PDO $db, $yearId, string $context = 'cette operation'): array
    {
        $active = self::getActive($db);
        if ($active === null) {
            return [
                'success' => false,
                'message' => Messages::get('business.no_active_year'),
                'year' => null,
            ];
        }

        $normalizedYearId = is_numeric($yearId) ? (int) $yearId : null;
        if ($normalizedYearId === null || $normalizedYearId <= 0) {
            $normalizedYearId = self::getSelectedIdFromSession();
        }
        if ($normalizedYearId === null || $normalizedYearId <= 0) {
            $normalizedYearId = self::getWritableIdFromSession();
        }
        if ($normalizedYearId === null || $normalizedYearId <= 0) {
            $normalizedYearId = (int) ($active['id'] ?? 0);
        }

        if ($normalizedYearId !== (int) ($active['id'] ?? 0)) {
            return [
                'success' => false,
                'message' => Messages::get('business.write_past_year', ['context' => $context, 'year' => $active['label']]),
                'year' => $active,
            ];
        }

        return [
            'success' => true,
            'message' => '',
            'year' => $active,
        ];
    }

    /**
     * @param array<int, mixed> $rows
     * @return array<int, mixed>
     */
    public static function filterRowsBySelectedYear(array $rows, string $field = 'id_annee_acad'): array
    {
        $selectedId = self::getSelectedIdFromSession();
        if ($selectedId === null || $selectedId <= 0) {
            return $rows;
        }

        return array_values(array_filter($rows, static function ($row) use ($field, $selectedId): bool {
            if (is_array($row)) {
                return isset($row[$field]) && (int) $row[$field] === $selectedId;
            }

            if (is_object($row)) {
                return isset($row->{$field}) && (int) $row->{$field} === $selectedId;
            }

            return false;
        }));
    }

    private static function formatLabel(string $dateDebut, string $dateFin, string $fallback): string
    {
        if ($dateDebut !== '' && $dateFin !== '') {
            return date('Y', strtotime($dateDebut)) . '-' . date('Y', strtotime($dateFin));
        }

        return $fallback;
    }

    private static function isAllSelectionValue($value): bool
    {
        if (!is_scalar($value)) {
            return false;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return false;
        }

        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
        if ($ascii !== false) {
            $normalized = $ascii;
        }

        $normalized = strtolower(trim($normalized));

        return in_array($normalized, [
            self::ALL_QUERY_VALUE,
            'toutes les annees',
            'toutes_les_annees',
        ], true);
    }
}
