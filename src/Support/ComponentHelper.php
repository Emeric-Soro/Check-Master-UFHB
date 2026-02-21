<?php
/**
 * ComponentHelper - Renders component partials
 * Usage in views: $c->form('field', ['name' => 'email'])
 */
class ComponentHelper
{
    private $viewsBasePath;
    
    public function __construct(?string $viewsBasePath = null)
    {
        // Default to ressources/views/components/
        $this->viewsBasePath = $viewsBasePath ?? dirname(__DIR__, 2) . '/ressources/views/components/';
    }
    
    /**
     * Render a component with parameters
     */
    public function render(string $component, array $params = []): string
    {
        $file = $this->viewsBasePath . $component . '.php';
        if (!file_exists($file)) {
            return "<!-- Component not found: {$component} -->";
        }
        extract($params);
        ob_start();
        include $file;
        return ob_get_clean();
    }
    
    // Category shortcuts
    public function form(string $component, array $params = []): string {
        return $this->render('form/' . $component, $params);
    }
    public function table(string $component, array $params = []): string {
        return $this->render('table/' . $component, $params);
    }
    public function panel(string $component, array $params = []): string {
        return $this->render('panel/' . $component, $params);
    }
    public function nav(string $component, array $params = []): string {
        return $this->render('nav/' . $component, $params);
    }
    public function widget(string $component, array $params = []): string {
        return $this->render('widget/' . $component, $params);
    }
    public function feedback(string $component, array $params = []): string {
        return $this->render('feedback/' . $component, $params);
    }
    public function special(string $component, array $params = []): string {
        return $this->render('special/' . $component, $params);
    }
    public function layout(string $component, array $params = []): string {
        return $this->render('layout/' . $component, $params);
    }
}
