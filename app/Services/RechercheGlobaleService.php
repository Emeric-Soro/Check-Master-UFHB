<?php
/**
 * RechercheGlobaleService
 *
 * Recherche fédérée dans plusieurs tables :
 *  - etudiants (nom, prénom, num_carte_etud, email)
 *  - enseignants (nom, prénom, id_enseignant, mail)
 *  - personnel_admin (nom, prénom)
 *  - utilisateur (login_utilisateur, email)
 *
 * Retourne les résultats groupés par catégorie.
 */

namespace CheckMaster\Services;

use PDO;

class RechercheGlobaleService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Recherche dans toutes les tables.
     *
     * @param string $query Terme de recherche
     * @param int    $limit Max résultats par catégorie
     * @return array Groupes : ['etudiants' => [...], 'enseignants' => [...], ...]
     */
    public function search(string $query, int $limit = 10): array
    {
        $results = [
            'etudiants'    => [],
            'enseignants'  => [],
            'personnel'    => [],
            'utilisateurs' => [],
        ];

        if (trim($query) === '') {
            return $results;
        }

        $like = '%' . $query . '%';

        // Étudiants
        try {
            $stmt = $this->db->prepare("
                SELECT num_carte_etud AS id, nom_etu, prenom_etu, email_etu, 'etudiant' AS type
                FROM etudiants
                WHERE nom_etu LIKE :q1
                   OR prenom_etu LIKE :q2
                   OR num_carte_etud LIKE :q3
                   OR num_ident_etud LIKE :q4
                   OR email_etu LIKE :q5
                LIMIT " . intval($limit)
            );
            $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like, ':q5' => $like]);
            $results['etudiants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('RechercheGlobale: etudiants - ' . $e->getMessage());
        }

        // Enseignants
        try {
            $stmt = $this->db->prepare("
                SELECT id_enseignant AS id, nom_enseignant, prenom_enseignant, mail_enseignant AS email, 'enseignant' AS type
                FROM enseignants
                WHERE nom_enseignant LIKE :q1
                   OR prenom_enseignant LIKE :q2
                   OR id_enseignant LIKE :q3
                   OR mail_enseignant LIKE :q4
                LIMIT " . intval($limit)
            );
            $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like]);
            $results['enseignants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('RechercheGlobale: enseignants - ' . $e->getMessage());
        }

        // Personnel administratif
        try {
            $stmt = $this->db->prepare("
                SELECT id_pers_admin AS id, nom_pers_admin, prenom_pers_admin, email_pers_admin AS email, 'personnel' AS type
                FROM personnel_admin
                WHERE nom_pers_admin LIKE :q1
                   OR prenom_pers_admin LIKE :q2
                   OR email_pers_admin LIKE :q3
                LIMIT " . intval($limit)
            );
            $stmt->execute([':q1' => $like, ':q2' => $like, ':q3' => $like]);
            $results['personnel'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('RechercheGlobale: personnel_admin - ' . $e->getMessage());
        }

        // Utilisateurs
        try {
            $stmt = $this->db->prepare("
                SELECT id_utilisateur AS id, login_utilisateur AS login, nom_utilisateur, login_utilisateur AS email, 'utilisateur' AS type
                FROM utilisateur
                WHERE login_utilisateur LIKE :q1
                   OR nom_utilisateur LIKE :q2
                LIMIT " . intval($limit)
            );
            $stmt->execute([':q1' => $like, ':q2' => $like]);
            $results['utilisateurs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('RechercheGlobale: utilisateur - ' . $e->getMessage());
        }

        return $results;
    }

    /**
     * Retourne les résultats au format JSON pour l'autocomplete AJAX.
     */
    public function searchAjax(string $query, int $limit = 5): array
    {
        $results = $this->search($query, $limit);
        $suggestions = [];

        $labels = [
            'etudiants'    => 'Étudiants',
            'enseignants'  => 'Enseignants',
            'personnel'    => 'Personnel administratif',
            'utilisateurs' => 'Utilisateurs',
        ];

        $icons = [
            'etudiants'    => 'fa-user-graduate',
            'enseignants'  => 'fa-chalkboard-teacher',
            'personnel'    => 'fa-users',
            'utilisateurs' => 'fa-user-circle',
        ];

        foreach ($results as $key => $items) {
            if (empty($items)) continue;

            $suggestions[] = [
                'type'  => 'category',
                'label' => $labels[$key] ?? $key,
                'icon'  => $icons[$key] ?? 'fa-circle',
            ];

            foreach ($items as $item) {
                $displayName = match ($key) {
                    'etudiants'   => ($item['nom_etu'] ?? '') . ' ' . ($item['prenom_etu'] ?? ''),
                    'enseignants' => ($item['nom_enseignant'] ?? '') . ' ' . ($item['prenom_enseignant'] ?? ''),
                    'personnel'   => ($item['nom_pers_admin'] ?? '') . ' ' . ($item['prenom_pers_admin'] ?? ''),
                    'utilisateurs' => $item['nom_utilisateur'] ?? $item['login'] ?? '',
                    default       => '',
                };

                $url = match ($key) {
                    'etudiants'   => '?page=fiche_etudiant_complete&id=' . urlencode((string) ($item['id'] ?? '')),
                    'enseignants' => '?page=fiche_enseignante&view=fiche&id=' . urlencode((string) ($item['id'] ?? '')),
                    'personnel'   => '?page=fiche_personnel_admin&id=' . urlencode((string) ($item['id'] ?? '')),
                    'utilisateurs' => '?page=gestion_utilisateurs&id=' . urlencode((string) ($item['id'] ?? '')),
                    default       => '#',
                };

                $suggestions[] = [
                    'type'  => 'item',
                    'label' => $displayName,
                    'sub'   => $item['id'] ?? $item['login'] ?? '',
                    'url'   => $url,
                    'category' => $key,
                ];
            }
        }

        return $suggestions;
    }
}
