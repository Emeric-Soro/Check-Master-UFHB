<?php
/**
 * ExportMasseDocumentsService
 *
 * Service d'export en masse de documents au format ZIP.
 * Tables : documents, rapport_etudiants, compte_rendu
 *
 * Permet de filtrer par année académique, filière et type de document.
 */

namespace CheckMaster\Services;

use PDO;
use ZipArchive;

class ExportMasseDocumentsService
{
    private PDO $db;
    private string $storagePath;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->storagePath = __DIR__ . '/../../storage/documents';
    }

    /**
     * Récupère la liste des types de documents disponibles.
     */
    public function getDocumentTypes(): array
    {
        try {
            $stmt = $this->db->query("SELECT DISTINCT type_document FROM documents WHERE type_document IS NOT NULL AND type_document <> '' ORDER BY type_document");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère les filières disponibles via les inscriptions.
     */
    public function getFilieres(?int $idAnnee = null): array
    {
        try {
            $sql = "SELECT DISTINCT f.id_filiere, f.lib_filiere
                    FROM inscriptions i
                    JOIN filiere f ON i.id_filiere = f.id_filiere";
            $params = [];
            if ($idAnnee !== null && $idAnnee > 0) {
                $sql .= " WHERE i.id_annee_acad = :id_annee";
                $params[':id_annee'] = $idAnnee;
            }
            $sql .= " ORDER BY f.lib_filiere";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère les années académiques.
     */
    public function getAnneesAcademiques(): array
    {
        try {
            $stmt = $this->db->query("SELECT id_annee_acad, CONCAT(YEAR(date_deb), '-', YEAR(date_fin)) AS label FROM annee_academique ORDER BY date_deb DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Compte les documents disponibles selon les critères.
     */
    public function countDocuments(?int $idAnnee = null, ?int $idFiliere = null, string $typeDoc = ''): int
    {
        $sql = "SELECT COUNT(*) FROM documents d";
        $joins = [];
        $where = [];
        $params = [];

        if ($idAnnee !== null && $idAnnee > 0) {
            $joins[] = "JOIN rapport_etudiants re ON d.id_rapport = re.id_rapport";
            $where[] = "re.id_annee_acad = :id_annee";
            $params[':id_annee'] = $idAnnee;
        }

        if ($idFiliere !== null && $idFiliere > 0) {
            $joins[] = "JOIN inscriptions i ON (re.num_etu = i.num_carte_etud OR re.num_etu = i.num_ident_etud)";
            $where[] = "i.id_filiere = :id_filiere";
            $params[':id_filiere'] = $idFiliere;
        }

        if ($typeDoc !== '') {
            $where[] = "d.type_document = :type_doc";
            $params[':type_doc'] = $typeDoc;
        }

        $sql .= ' ' . implode(' ', $joins);
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return 0;
        }
    }

    /**
     * Génère l'archive ZIP des documents selon les critères.
     *
     * @return array ['success' => bool, 'path' => string|null, 'message' => string]
     */
    public function generateZip(?int $idAnnee = null, ?int $idFiliere = null, string $typeDoc = ''): array
    {
        // Requête pour trouver les documents
        $sql = "SELECT d.id_document, d.nom_fichier, d.type_document, d.fichier_document,
                       re.num_etu, e.nom_etu, e.prenom_etu
                FROM documents d
                JOIN rapport_etudiants re ON d.id_rapport = re.id_rapport
                JOIN etudiants e ON (re.num_etu = e.num_carte_etud OR re.num_etu = e.num_ident_etud)";
        $where = [];
        $params = [];

        if ($idAnnee !== null && $idAnnee > 0) {
            $where[] = "re.id_annee_acad = :id_annee";
            $params[':id_annee'] = $idAnnee;
        }

        if ($idFiliere !== null && $idFiliere > 0) {
            $sql .= " JOIN inscriptions i ON (re.num_etu = i.num_carte_etud OR re.num_etu = i.num_ident_etud)";
            $where[] = "i.id_filiere = :id_filiere";
            $params[':id_filiere'] = $idFiliere;
        }

        if ($typeDoc !== '') {
            $where[] = "d.type_document = :type_doc";
            $params[':type_doc'] = $typeDoc;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            return ['success' => false, 'path' => null, 'message' => 'Erreur de requête : ' . $e->getMessage()];
        }

        if (empty($documents)) {
            return ['success' => false, 'path' => null, 'message' => 'Aucun document trouvé pour les critères sélectionnés.'];
        }

        // Créer le dossier temporaire
        $tempDir = $this->storagePath . '/exports';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filename = 'export_documents_' . date('Ymd_His') . '.zip';
        $filepath = $tempDir . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['success' => false, 'path' => null, 'message' => 'Impossible de créer l\'archive ZIP.'];
        }

        $added = 0;
        $errors = [];

        foreach ($documents as $doc) {
            $etudiantLabel = $doc['nom_etu'] . '_' . $doc['prenom_etu'] . '_' . $doc['num_etu'];
            $etudiantLabel = preg_replace('/[^a-zA-Z0-9_-]/', '_', $etudiantLabel);

            $nomFichier = $doc['nom_fichier'] ?? 'document_' . $doc['id_document'];
            $archiveName = $etudiantLabel . '/' . $nomFichier;

            // Le fichier peut être stocké en base (BLOB) ou sur disque
            $fichierContent = $doc['fichier_document'] ?? null;

            if (!empty($fichierContent)) {
                // Stocké en base de données (BLOB)
                $zip->addFromString($archiveName, is_resource($fichierContent) ? stream_get_contents($fichierContent) : $fichierContent);
                $added++;
            } else {
                // Stocké sur disque - chercher dans le storage
                $diskPath = $this->storagePath . '/documents/' . $doc['id_document'] . '_' . $nomFichier;
                if (file_exists($diskPath)) {
                    $zip->addFile($diskPath, $archiveName);
                    $added++;
                } else {
                    $errors[] = $doc['nom_fichier'] . ' (#' . $doc['id_document'] . ')';
                }
            }
        }

        $zip->close();

        if ($added === 0 && !empty($errors)) {
            return ['success' => false, 'path' => null, 'message' => 'Aucun fichier physique trouvé.'];
        }

        $msg = $added . ' document(s) ajouté(s) à l\'archive.';
        if (!empty($errors)) {
            $msg .= ' ' . count($errors) . ' fichier(s) manquant(s).';
        }

        return [
            'success' => true,
            'path'    => $filepath,
            'filename' => $filename,
            'message' => $msg,
            'count'   => $added,
        ];
    }
}
