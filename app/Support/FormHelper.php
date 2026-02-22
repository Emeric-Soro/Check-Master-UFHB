<?php

namespace CheckMaster\Support;

/**
 * FormHelper - Registre centralisé des champs de formulaire par entité.
 */
class FormHelper
{
    private array $registry = [
        'etudiant' => [
            'num_carte_etud' => ['type' => 'text', 'label' => 'Matricule', 'size' => 'compresse', 'required' => true],
            'nom_etudiant'   => ['type' => 'text', 'label' => 'Nom', 'size' => 'standard', 'required' => true],
            'prenom_etudiant' => ['type' => 'text', 'label' => 'Prénom', 'size' => 'standard', 'required' => true],
            'date_naiss_etudiant' => ['type' => 'date', 'label' => 'Date de naissance', 'size' => 'standard'],
            'lieu_naiss_etudiant' => ['type' => 'text', 'label' => 'Lieu de naissance', 'size' => 'standard'],
            'sexe_etudiant'  => ['type' => 'select', 'label' => 'Genre', 'size' => 'compresse', 'options' => ['M' => 'Masculin', 'F' => 'Féminin']],
            'mail_etudiant'  => ['type' => 'email', 'label' => 'Email', 'size' => 'etendue', 'required' => true],
            'tel_etudiant'   => ['type' => 'tel', 'label' => 'Téléphone', 'size' => 'standard'],
        ],
        'enseignant' => [
            'nom_enseignant'    => ['type' => 'text', 'label' => 'Nom', 'size' => 'standard', 'required' => true],
            'prenom_enseignant' => ['type' => 'text', 'label' => 'Prénom', 'size' => 'standard', 'required' => true],
            'mail_enseignant'   => ['type' => 'email', 'label' => 'Email', 'size' => 'etendue', 'required' => true],
            'id_specialite'     => ['type' => 'select', 'label' => 'Spécialité', 'size' => 'standard'],
            'type_enseignant'   => ['type' => 'select', 'label' => 'Type', 'size' => 'standard', 'options' => [1 => 'Permanent', 2 => 'Vacataire']],
        ],
        'utilisateur' => [
            'nom_utilisateur'   => ['type' => 'text', 'label' => 'Nom Complet', 'size' => 'standard', 'required' => true],
            'login_utilisateur' => ['type' => 'text', 'label' => 'Login', 'size' => 'standard', 'required' => true],
            'statut_utilisateur' => ['type' => 'select', 'label' => 'Statut', 'size' => 'compresse', 'options' => [1 => 'Actif', 0 => 'Inactif']],
            'id_GU'             => ['type' => 'select', 'label' => 'Groupe', 'size' => 'standard'],
        ]
    ];

    /**
     * Récupère la configuration d'un champ.
     */
    public function fieldConfig(string $entity, string $field): ?array
    {
        return $this->registry[$entity][$field] ?? null;
    }

    /**
     * Récupère tous les champs pour une entité.
     */
    public function entityFields(string $entity): array
    {
        return $this->registry[$entity] ?? [];
    }

    /**
     * Construit les champs avec valeurs et erreurs.
     */
    public function buildFormFields(string $entity, array $values = [], array $errors = []): array
    {
        $fields = $this->entityFields($entity);
        foreach ($fields as $name => &$config) {
            $config['name'] = $name;
            $config['value'] = $values[$name] ?? '';
            $config['error'] = $errors[$name] ?? '';
        }
        return $fields;
    }
}

// ---------------------------------------------------------------------------
// Backward-compatible procedural helpers (used by legacy code)
// ---------------------------------------------------------------------------

if (!function_exists('cm_form_old_value')) {
    /**
     * @param mixed $default
     * @return mixed
     */
    function cm_form_old_value(string $name, $default = '')
    {
        if (isset($_POST[$name])) {
            return $_POST[$name];
        }

        if (isset($_SESSION['old']) && is_array($_SESSION['old']) && array_key_exists($name, $_SESSION['old'])) {
            return $_SESSION['old'][$name];
        }

        return $default;
    }
}

if (!function_exists('cm_form_field_error')) {
    function cm_form_field_error(string $name, string $default = ''): string
    {
        if (isset($_SESSION['errors']) && is_array($_SESSION['errors']) && isset($_SESSION['errors'][$name])) {
            return (string) $_SESSION['errors'][$name];
        }

        if (isset($GLOBALS['errors']) && is_array($GLOBALS['errors']) && isset($GLOBALS['errors'][$name])) {
            return (string) $GLOBALS['errors'][$name];
        }

        return $default;
    }
}

if (!function_exists('cm_form_attr_string')) {
    /**
     * Convert attrs array to safe HTML attributes.
     *
     * @param array<string, mixed> $attrs
     */
    function cm_form_attr_string(array $attrs): string
    {
        if (empty($attrs)) {
            return '';
        }

        $out = '';
        foreach ($attrs as $key => $value) {
            $attr = trim((string) $key);
            if ($attr === '') {
                continue;
            }

            if (is_bool($value)) {
                if ($value) {
                    $out .= ' ' . htmlspecialchars($attr, ENT_QUOTES, 'UTF-8');
                }
                continue;
            }

            if ($value === null) {
                continue;
            }

            $out .= ' ' . htmlspecialchars($attr, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
        }

        return $out;
    }
}

if (!function_exists('cm_form_normalize_options')) {
    /**
     * Normalize options to [['value'=>..., 'label'=>...], ...]
     *
     * @param array<int|string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    function cm_form_normalize_options(array $options): array
    {
        $normalized = [];
        foreach ($options as $key => $option) {
            if (is_array($option)) {
                $normalized[] = [
                    'value' => (string) ($option['value'] ?? $key),
                    'label' => (string) ($option['label'] ?? $option['text'] ?? $key),
                    'disabled' => !empty($option['disabled']),
                ];
                continue;
            }

            if (is_object($option)) {
                /** @var object $option */
                $normalized[] = [
                    'value' => (string) ($option->value ?? $key),
                    'label' => (string) ($option->label ?? $option->text ?? $key),
                    'disabled' => !empty($option->disabled),
                ];
                continue;
            }

            $normalized[] = [
                'value' => is_string($key) ? $key : (string) $option,
                'label' => (string) $option,
                'disabled' => false,
            ];
        }
        return $normalized;
    }
}
