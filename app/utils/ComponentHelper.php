<?php

namespace CheckMaster\Support;

/**
 * ComponentHelper - Aide au rendu des composants réutilisables.
 */
class ComponentHelper
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR;
    }

    /**
     * Rend un composant générique.
     */
    public function render(string $path, array $params = []): string
    {
        $file = $this->basePath . str_replace('/', DIRECTORY_SEPARATOR, $path) . '.php';
        if (!is_file($file)) {
            return "<!-- Component not found: $path -->";
        }

        // Variable globale accessible dans le composant pour les inclusions récursives
        $c = $this;
        $component = function(string $p, array $pa = []) use ($c) {
            return $c->render($p, $pa);
        };

        ob_start();
        extract($params);
        include $file;
        return (string) ob_get_clean();
    }

    /**
     * Raccourci pour les composants de formulaire.
     */
    public function form(string $name, array $params = []): string
    {
        return $this->render("form/$name", $params);
    }

    /**
     * Raccourci pour les composants de tableau.
     */
    public function table(string $name, array $params = []): string
    {
        return $this->render("table/$name", $params);
    }

    /**
     * Raccourci pour les composants de panneau.
     */
    public function panel(string $name, array $params = []): string
    {
        return $this->render("panel/$name", $params);
    }

    /**
     * Raccourci pour les composants de navigation.
     */
    public function nav(string $name, array $params = []): string
    {
        return $this->render("nav/$name", $params);
    }

    /**
     * Raccourci pour les composants de widget.
     */
    public function widget(string $name, array $params = []): string
    {
        return $this->render("widget/$name", $params);
    }

    /**
     * Raccourci pour les composants de feedback.
     */
    public function feedback(string $name, array $params = []): string
    {
        return $this->render("feedback/$name", $params);
    }

    /**
     * Raccourci pour les composants de structure (layout).
     */
    public function layout(string $name, array $params = []): string
    {
        return $this->render("layout/$name", $params);
    }

    /**
     * Raccourci pour les composants spéciaux.
     */
    public function special(string $name, array $params = []): string
    {
        return $this->render("special/$name", $params);
    }
}

// ---------------------------------------------------------------------------
// Backward-compatible procedural helpers (used by layout.php and legacy code)
// ---------------------------------------------------------------------------

if (!function_exists('cm_component')) {
    /**
     * Render a reusable component from ressources/components.
     *
     * @param string $name   Component path without extension, ex: "form/input-text".
     * @param array<string, mixed> $params Variables exposed in component scope.
     */
    function cm_component(string $name, array $params = []): void
    {
        $normalized = trim(str_replace('\\', '/', $name), '/');
        if ($normalized === '' || strpos($normalized, '..') !== false) {
            return;
        }

        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'components';
        $file = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized) . '.php';

        if (!is_file($file)) {
            return;
        }

        extract($params, EXTR_SKIP);
        include $file;
    }
}

if (!function_exists('cm_render_component')) {
    /**
     * Render a component and return HTML.
     *
     * @param string $name
     * @param array<string, mixed> $params
     */
    function cm_render_component(string $name, array $params = []): string
    {
        ob_start();
        cm_component($name, $params);
        return (string) ob_get_clean();
    }
}

if (!function_exists('cm_asset')) {
    /**
     * Build a public asset URL relative to /public/layout.php context.
     */
    function cm_asset(string $path): string
    {
        $clean = ltrim(str_replace('\\', '/', $path), '/');
        if ($clean === '') {
            return 'assets';
        }
        return 'assets/' . $clean;
    }
}
