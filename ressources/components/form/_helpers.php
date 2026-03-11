<?php

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

if (!function_exists('cm_form_class_names')) {
    /**
     * @param mixed ...$parts
     */
    function cm_form_class_names(...$parts): string
    {
        $classes = [];

        $append = static function ($value) use (&$classes): void {
            if (!is_string($value) || trim($value) === '') {
                return;
            }

            foreach (preg_split('/\s+/', trim($value)) as $className) {
                if ($className === '' || in_array($className, $classes, true)) {
                    continue;
                }
                $classes[] = $className;
            }
        };

        foreach ($parts as $part) {
            if (is_array($part)) {
                foreach ($part as $item) {
                    $append($item);
                }
                continue;
            }

            $append($part);
        }

        return implode(' ', $classes);
    }
}

if (!function_exists('cm_form_group_class')) {
    /**
     * @param array<string, mixed> $options
     */
    function cm_form_group_class(bool $required, string $error, array $options = []): string
    {
        $classes = ['cm-form-group'];

        if ($required) {
            $classes[] = 'is-required';
        }
        if ($error !== '') {
            $classes[] = 'is-invalid';
        }
        if (!empty($options['readonly'])) {
            $classes[] = 'is-readonly';
        }
        if (!empty($options['disabled'])) {
            $classes[] = 'is-disabled';
        }
        if (!empty($options['dense'])) {
            $classes[] = 'is-dense';
        }

        $size = trim((string) ($options['size'] ?? ''));
        if ($size !== '') {
            $classes[] = 'is-' . $size;
        }

        if (!empty($options['group_class'])) {
            $classes[] = (string) $options['group_class'];
        }

        return cm_form_class_names($classes);
    }
}

if (!function_exists('cm_form_label_class')) {
    /**
     * @param array<string, mixed> $options
     */
    function cm_form_label_class(array $options = []): string
    {
        $classes = ['cm-form-label'];

        if (!empty($options['readonly'])) {
            $classes[] = 'is-readonly';
        }
        if (!empty($options['dense'])) {
            $classes[] = 'is-dense';
        }

        $size = trim((string) ($options['size'] ?? ''));
        if ($size !== '') {
            $classes[] = 'is-' . $size;
        }

        if (!empty($options['label_class'])) {
            $classes[] = (string) $options['label_class'];
        }

        return cm_form_class_names($classes);
    }
}

if (!function_exists('cm_form_control_class')) {
    /**
     * @param array<string, mixed> $options
     */
    function cm_form_control_class(string $baseClass = 'cm-form-control', array $options = []): string
    {
        $classes = [$baseClass];

        if (!empty($options['readonly'])) {
            $classes[] = 'is-readonly';
        }
        if (!empty($options['disabled'])) {
            $classes[] = 'is-disabled';
        }
        if (!empty($options['dense'])) {
            $classes[] = 'is-dense';
        }

        $size = trim((string) ($options['size'] ?? ''));
        if ($size !== '') {
            $classes[] = 'is-' . $size;
        }

        if (!empty($options['control_class'])) {
            $classes[] = (string) $options['control_class'];
        }

        return cm_form_class_names($classes);
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
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $normalized[] = [
                    'value' => (string) ($value['value'] ?? $key),
                    'label' => (string) ($value['label'] ?? $value['text'] ?? $key),
                    'disabled' => !empty($value['disabled']),
                ];
            } else {
                $normalized[] = [
                    'value' => $isList ? (string) $value : (string) $key,
                    'label' => (string) $value,
                    'disabled' => false,
                ];
            }
        }
        return $normalized;
    }
}
