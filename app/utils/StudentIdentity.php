<?php
/**
 * StudentIdentity — Helper centralisé pour la résolution d'identifiant étudiant.
 *
 * Dans le schéma actuel, un étudiant peut être identifié par :
 *   - num_ident_etud (varchar(25), UNIQUE, nullable) → identifiant MESRS / CNE
 *   - num_carte_etud (varchar(25), PK, NOT NULL)     → numéro de carte étudiant
 *
 * RÈGLE : TOUT affichage ou recherche doit PRIVILÉGIER num_ident_etud.
 *          utiliser num_carte_etud UNIQUEMENT en FALLBACK.
 */

class StudentIdentity
{
    /**
     * Retourne l'identifiant étudiant à afficher : num_ident_etud en priorité,
     * num_carte_etud en fallback.
     *
     * @param object|array|null $student  Ligne étudiant (objet ou tableau associatif)
     * @return string    Identifiant à afficher, ou chaîne vide si aucun
     */
    public static function getDisplayId(object|array|null $student): string
    {
        if ($student === null) {
            return '';
        }

        if (is_object($student)) {
            return !empty($student->num_ident_etud)
                ? trim((string) $student->num_ident_etud)
                : trim((string) ($student->num_carte_etud ?? ''));
        }

        // Tableau associatif
        return !empty($student['num_ident_etud'])
            ? trim((string) $student['num_ident_etud'])
            : trim((string) ($student['num_carte_etud'] ?? ''));
    }

    /**
     * Génère une clause SQL WHERE pour chercher un étudiant par l'un ou l'autre
     * identifiant.
     *
     * @param string $alias  Alias de la table etudiants (ex: 'e', 'et')
     * @return string  Clause SQL : "(e.num_ident_etud = ? OR e.num_carte_etud = ?)"
     */
    public static function searchWhereClause(string $alias = 'e'): string
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
        return "({$alias}.num_ident_etud = ? OR {$alias}.num_carte_etud = ?)";
    }

    /**
     * Génère l'expression SELECT pour l'identifiant à afficher.
     *
     * @param string $alias  Alias de la table etudiants
     * @return string  Expression SQL : "COALESCE(e.num_ident_etud, e.num_carte_etud) AS display_id"
     */
    public static function displayIdExpr(string $alias = 'e'): string
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
        return "COALESCE({$alias}.num_ident_etud, {$alias}.num_carte_etud) AS display_id";
    }

    /**
     * Génère une condition de jointure qui fonctionne avec les deux identifiants.
     * Utile quand la table distante stocke l'identifiant sous une colonne
     * unique (ex: re.num_etu = e.num_carte_etud) mais qu'on veut aussi matcher
     * par num_ident_etud.
     *
     * @param string $etudiantAlias  Alias de la table etudiants
     * @param string $remoteColumn   Colonne de la table distante (ex: 're.num_etu')
     * @return string  Condition de jointure
     */
    public static function joinCondition(string $etudiantAlias = 'e', string $remoteColumn = 're.num_etu'): string
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', $etudiantAlias);
        return "({$remoteColumn} = {$alias}.num_carte_etud OR {$remoteColumn} = {$alias}.num_ident_etud)";
    }

    /**
     * Extrait proprement l'identifiant depuis un tableau de données
     * pour les opérations CRUD (INSERT, UPDATE, DELETE).
     *
     * @param array $data  Données du formulaire
     * @return string  Identifiant à utiliser comme num_etu
     */
    public static function resolveFromInput(array $data): string
    {
        return trim((string) ($data['num_ident_etud'] ?? $data['identifiant_mesrs'] ?? $data['num_etu'] ?? $data['num_carte_etud'] ?? ''));
    }
}
