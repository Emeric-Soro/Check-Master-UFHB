<?php
/**
 * FormFieldStandards - Configurations standardisées de champs prêtes à l'emploi.
 *
 * Ces configurations garantissent la cohérence visuelle dans tous les formulaires.
 *
 * Usage:
 *   require_once __DIR__ . '/FormFieldStandards.php';
 *   $fields = [
 *       FormFieldStandards::dateNaissance(),
 *       FormFieldStandards::nom(),
 *       FormFieldStandards::email(),
 *   ];
 *   cm_component('form/form-grid', ['cols' => 3, 'fields' => $fields]);
 */
class FormFieldStandards
{
    /** Date de naissance (TOUJOURS compacte: 140px max) */
    public static function dateNaissance(array $overrides = []): array
    {
        return array_merge([
            'name' => 'date_naiss',
            'label' => 'Date de naissance',
            'type' => 'date',
            'size' => 'date',
        ], $overrides);
    }

    /** Année académique (TOUJOURS compacte: 120px max) */
    public static function anneeAcademique(array $overrides = []): array
    {
        return array_merge([
            'name' => 'annee_acad',
            'label' => 'Année académique',
            'type' => 'select',
            'size' => 'sm',
        ], $overrides);
    }

    /** Nom (taille moyenne: 180px) */
    public static function nom(array $overrides = []): array
    {
        return array_merge([
            'name' => 'nom',
            'label' => 'Nom',
            'type' => 'text',
            'size' => 'md',
        ], $overrides);
    }

    /** Prénom (taille moyenne: 180px) */
    public static function prenom(array $overrides = []): array
    {
        return array_merge([
            'name' => 'prenom',
            'label' => 'Prénom',
            'type' => 'text',
            'size' => 'md',
        ], $overrides);
    }

    /** Email (toujours plus large: 280px) */
    public static function email(array $overrides = []): array
    {
        return array_merge([
            'name' => 'email',
            'label' => 'Email',
            'type' => 'email',
            'size' => 'lg',
        ], $overrides);
    }

    /** Téléphone (compact: 120px) */
    public static function telephone(array $overrides = []): array
    {
        return array_merge([
            'name' => 'telephone',
            'label' => 'Téléphone',
            'type' => 'text',
            'size' => 'sm',
        ], $overrides);
    }

    /** Montant/Numéro (très compact: 120px) */
    public static function montant(array $overrides = []): array
    {
        return array_merge([
            'name' => 'montant',
            'label' => 'Montant',
            'type' => 'number',
            'size' => 'sm',
        ], $overrides);
    }

    /** Numéro étudiant (compact: 120px) */
    public static function numeroEtudiant(array $overrides = []): array
    {
        return array_merge([
            'name' => 'num_etu',
            'label' => 'N° Étudiant',
            'type' => 'text',
            'size' => 'sm',
        ], $overrides);
    }

    /** Description/Commentaire (pleine largeur) */
    public static function description(array $overrides = []): array
    {
        return array_merge([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
            'size' => 'full',
            'rows' => 3,
        ], $overrides);
    }

    /**
     * Champ select générique avec taille adaptative.
     */
    public static function select(string $name, string $label, array $options = [], array $overrides = []): array
    {
        return array_merge([
            'name' => $name,
            'label' => $label,
            'type' => 'select',
            'size' => 'md',
            'options' => $options,
        ], $overrides);
    }
}
