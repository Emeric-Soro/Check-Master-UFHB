<?php
require_once __DIR__ . '/../../../app/utils/FormHelper.php';

if (!function_exists('cm_form_old_value')) {
    /**
     * @param mixed $default
     * @return mixed
     */
    function cm_form_old_value(string $name, $default = '')
    {
        return $default;
    }
}

if (!function_exists('cm_form_field_error')) {
    function cm_form_field_error(string $name, string $default = ''): string
    {
        return $default;
    }
}

if (!function_exists('cm_form_attr_string')) {
    /**
     * @param array<string, mixed> $attrs
     */
    function cm_form_attr_string(array $attrs): string
    {
        $out = '';
        foreach ($attrs as $key => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $out .= ' ' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8');
                continue;
            }
            $out .= ' ' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8')
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
        $normalized = [];
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $normalized[] = [
                    'value' => (string) ($value['value'] ?? $key),
                    'label' => (string) ($value['label'] ?? $value['text'] ?? $key),
                    'disabled' => !empty($value['disabled']),
                ];
            } else {
                $normalized[] = [
                    'value' => is_string($key) ? $key : (string) $value,
                    'label' => (string) $value,
                    'disabled' => false,
                ];
            }
        }
        return $normalized;
    }
}
