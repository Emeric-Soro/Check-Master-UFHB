<?php

/**
 * FormHelper - lightweight form utilities and field registry.
 */
class FormHelper
{
    /** @var array<string, array<string, array<string, mixed>>> */
    private array $registry = [
        'etudiant' => [
            'num_carte_etud' => ['type' => 'text', 'label' => 'Matricule', 'required' => true],
            'nom_etudiant' => ['type' => 'text', 'label' => 'Nom', 'required' => true],
            'prenom_etudiant' => ['type' => 'text', 'label' => 'Prenom', 'required' => true],
            'date_naiss_etudiant' => ['type' => 'date', 'label' => 'Date de naissance'],
            'mail_etudiant' => ['type' => 'email', 'label' => 'Email', 'required' => true],
        ],
    ];

    /**
     * @return array<string, mixed>|null
     */
    public function fieldConfig(string $entity, string $field): ?array
    {
        return $this->registry[$entity][$field] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function entityFields(string $entity): array
    {
        return $this->registry[$entity] ?? [];
    }
}

if (!function_exists('cm_csrf_token')) {
    /**
     * Return current CSRF token (CheckMaster Core token when available).
     */
    function cm_csrf_token(): string
    {
        if (class_exists('\CheckMaster\Core\Csrf')) {
            return (string) \CheckMaster\Core\Csrf::token();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['csrf_token'];
    }
}

if (!function_exists('cm_csrf_verify')) {
    /**
     * Validate CSRF token or throw.
     *
     * @throws RuntimeException
     */
    function cm_csrf_verify(string $submitted): void
    {
        $submitted = trim($submitted);
        if ($submitted === '') {
            throw new RuntimeException('Token CSRF manquant.');
        }

        if (class_exists('\CheckMaster\Core\Csrf')) {
            if (!\CheckMaster\Core\Csrf::validate($submitted)) {
                throw new RuntimeException('Token CSRF invalide.');
            }
            return;
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $token = (string) ($_SESSION['csrf_token'] ?? '');
        if ($token === '' || !hash_equals($token, $submitted)) {
            throw new RuntimeException('Token CSRF invalide.');
        }
    }
}

if (!function_exists('cm_old')) {
    /**
     * @param mixed $default
     * @return mixed
     */
    function cm_old(string $field, $default = '')
    {
        if (isset($_POST[$field])) {
            return $_POST[$field];
        }

        if (isset($_SESSION['old_input']) && is_array($_SESSION['old_input']) && array_key_exists($field, $_SESSION['old_input'])) {
            return $_SESSION['old_input'][$field];
        }

        if (isset($_SESSION['old']) && is_array($_SESSION['old']) && array_key_exists($field, $_SESSION['old'])) {
            return $_SESSION['old'][$field];
        }

        return $default;
    }
}

if (!function_exists('cm_error')) {
    /**
     * @param string $field
     */
    function cm_error(string $field): string
    {
        if (isset($_SESSION['validation_errors']) && is_array($_SESSION['validation_errors']) && isset($_SESSION['validation_errors'][$field])) {
            return (string) $_SESSION['validation_errors'][$field];
        }
        return '';
    }
}

// Backward-compatible aliases used by existing components.
if (!function_exists('cm_form_old_value')) {
    /**
     * @param mixed $default
     * @return mixed
     */
    function cm_form_old_value(string $name, $default = '')
    {
        return cm_old($name, $default);
    }
}

if (!function_exists('cm_form_field_error')) {
    function cm_form_field_error(string $name, string $default = ''): string
    {
        $message = cm_error($name);
        return $message !== '' ? $message : $default;
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
     * @param array<int|string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    function cm_form_normalize_options(array $options): array
    {
        $isList = function_exists('array_is_list')
            ? array_is_list($options)
            : (array_keys($options) === range(0, count($options) - 1));

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
                'value' => $isList ? (string) $option : (string) $key,
                'label' => (string) $option,
                'disabled' => false,
            ];
        }

        return $normalized;
    }
}
