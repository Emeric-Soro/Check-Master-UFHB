<?php

namespace CheckMaster\Services;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CompteRendu.php';

use Exception;

class ArchivesCompteRenduService
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupère les archives paginées avec filtres.
     *
     * @return array{archives: array, totalArchives: int, totalPages: int}
     */
    public function getPaginatedArchives(?string $search, ?string $year, int $page = 1, int $limit = 10): array
    {
        $safePage = max(1, $page);
        $safeLimit = max(1, $limit);
        $offset = ($safePage - 1) * $safeLimit;

        $archives = \CompteRendu::getAllArchives($safeLimit, $offset, $search, $year);
        $allRows = \CompteRendu::getAllArchives(null, 0, $search, $year);
        $totalArchives = is_array($allRows) ? count($allRows) : 0;
        $totalPages = max(1, (int) ceil($totalArchives / $safeLimit));

        return [
            'archives' => is_array($archives) ? $archives : [],
            'totalArchives' => $totalArchives,
            'totalPages' => $totalPages,
        ];
    }

    public function getArchiveById($id): ?array
    {
        $result = \CompteRendu::getArchiveById($id);
        return is_array($result) ? $result : null;
    }

    public function deleteArchive($id): bool
    {
        return (bool) \CompteRendu::deleteArchive($id);
    }

    public function getStats(): array
    {
        $stats = \CompteRendu::getStatsArchives();
        return is_array($stats) ? $stats : [];
    }
}
