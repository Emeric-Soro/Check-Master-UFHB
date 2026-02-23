<?php

/**
 * ComponentHelper - centralized rendering for reusable UI components.
 *
 * This file intentionally exposes global procedural helpers (`cm_component`,
 * `cm_render_component`, `cm_asset`) to match the legacy view layer.
 */
class ComponentHelper
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath
            ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR;
    }

    /**
     * Render a component and return HTML.
     *
     * @param string $path   Component path without extension.
     * @param array<string, mixed> $params
     */
    public function render(string $path, array $params = []): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');
        if ($normalized === '' || strpos($normalized, '..') !== false) {
            return '<!-- Invalid component path -->';
        }

        $file = $this->basePath . str_replace('/', DIRECTORY_SEPARATOR, $normalized) . '.php';
        if (!is_file($file)) {
            return "<!-- Component not found: {$normalized} -->";
        }

        ob_start();
        extract($params, EXTR_SKIP);
        include $file;
        return (string) ob_get_clean();
    }
}

if (!function_exists('cm_component')) {
    /**
     * Include a reusable component from ressources/components.
     *
     * @param string $name
     * @param array<string, mixed> $props
     *
     * @throws RuntimeException if component does not exist.
     */
    function cm_component(string $name, array $props = []): void
    {
        $normalized = trim(str_replace('\\', '/', $name), '/');
        if ($normalized === '' || strpos($normalized, '..') !== false) {
            throw new RuntimeException('[cm_component] Invalid component name: ' . $name);
        }

        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'components';
        $file = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized) . '.php';

        if (!is_file($file)) {
            throw new RuntimeException('[cm_component] Component not found: ' . $normalized . ' (' . $file . ')');
        }

        (static function (string $_file, array $_props): void {
            extract($_props, EXTR_SKIP);
            require $_file;
        })($file, $props);
    }
}

if (!function_exists('cm_render_component')) {
    /**
     * Render a component and return its HTML.
     *
     * @param string $name
     * @param array<string, mixed> $props
     */
    function cm_render_component(string $name, array $props = []): string
    {
        ob_start();
        cm_component($name, $props);
        return (string) ob_get_clean();
    }
}

if (!function_exists('cm_asset')) {
    /**
     * Build an asset URL with cache busting from public/assets.
     *
     * Works for both `/public/layout.php` and `/public/app/layout.php`.
     */
    function cm_asset(string $path): string
    {
        $clean = ltrim(str_replace('\\', '/', $path), '/');
        if ($clean === '') {
            return 'assets';
        }

        $full = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $clean);
        $version = is_file($full) ? (string) filemtime($full) : (string) time();

        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $prefix = strpos($script, '/app/') !== false ? '../' : '';

        return $prefix . 'assets/' . $clean . '?v=' . rawurlencode($version);
    }
}
