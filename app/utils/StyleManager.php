<?php
/**
 * STYLEMANAGER - GESTIONNAIRE CENTRALISÉ DU DESIGN SYSTEM
 *
 * Système professionnel 3 couleurs harmonisées:
 * - BLEU: Couleur principale (remplace le vert)
 * - GRIS: Couleur neutre (arrière-plans doux)
 * - ORANGE: Couleur d'accent
 *
 * Mode sombre/clair automatique + basculement manuel
 * Tout harmonisé au pixel près pour un design professionnel
 *
 * @version 2.0
 * @author Design System Team
 * @compatibility Tailwind CSS v4+
 */
class StyleManager {

    /**
     * Configuration des couleurs du design system
     * Système 3 couleurs professionnel complet
     */
    private static $colorSystem = [
        // COULEUR PRINCIPALE - BLEU PROFESSIONNEL
        'primary' => [
            '50'  => '#eff6ff',
            '100' => '#dbeafe',
            '200' => '#bfdbfe',
            '300' => '#93c5fd',
            '400' => '#60a5fa',
            '500' => '#3b82f6',
            '600' => '#2563eb',  // Couleur principale
            '700' => '#1d4ed8',
            '800' => '#1e40af',
            '900' => '#1e3a8a'
        ],

        // COULEUR NEUTRE - GRIS DOUX
        'neutral' => [
            '50'  => '#f8fafc',
            '100' => '#f1f5f9',
            '200' => '#e2e8f0',
            '300' => '#cbd5e1',
            '400' => '#94a3b8',
            '500' => '#64748b',
            '600' => '#475569',  // Gris principal
            '700' => '#334155',
            '800' => '#1e293b',
            '900' => '#0f172a'
        ],

        // COULEUR D'ACCENT - ORANGE PROFESSIONNEL
        'accent' => [
            '50'  => '#fff7ed',
            '100' => '#ffedd5',
            '200' => '#fed7aa',
            '300' => '#fdba74',
            '400' => '#fb923c',
            '500' => '#f97316',  // Orange principal
            '600' => '#ea580c',
            '700' => '#c2410c',
            '800' => '#9a3412',
            '900' => '#7c2d12'
        ]
    ];

    /**
     * États sémantiques harmonisés avec le système
     */
    private static $semanticColors = [
        'success' => [
            'light' => '#10b981',
            'background' => '#d1fae5',
            'text' => '#047857'
        ],
        'warning' => [
            'light' => '#f59e0b',
            'background' => '#fef3c7',
            'text' => '#92400e'
        ],
        'error' => [
            'light' => '#ef4444',
            'background' => '#fee2e2',
            'text' => '#991b1b'
        ],
        'info' => [
            'light' => '#3b82f6',
            'background' => '#dbeafe',
            'text' => '#1d4ed8'
        ]
    ];

    /**
     * Génère les variables CSS complètes pour le système
     */
    public static function generateCSSVariables() {
        $css = ":root {\n";

        // Couleurs système
        foreach (self::$colorSystem as $colorName => $shades) {
            foreach ($shades as $shade => $value) {
                $css .= "  --color-{$colorName}-{$shade}: {$value};\n";
            }
        }

        // Couleurs sémantiques
        foreach (self::$semanticColors as $name => $variants) {
            foreach ($variants as $variant => $value) {
                $css .= "  --color-{$name}-{$variant}: {$value};\n";
            }
        }

        // Couleurs fonctionnelles mode clair
        $css .= "  
  /* Couleurs fonctionnelles - Mode clair */
  --color-bg-primary: #ffffff;
  --color-bg-secondary: #f8fafc;
  --color-bg-tertiary: #f1f5f9;
  --color-surface: #ffffff;
  --color-surface-hover: #f8fafc;
  --color-surface-active: #f1f5f9;
  
  --color-text-primary: #0f172a;
  --color-text-secondary: #475569;
  --color-text-tertiary: #64748b;
  --color-text-quaternary: #94a3b8;
  --color-text-inverse: #ffffff;
  
  --color-border-primary: #e2e8f0;
  --color-border-secondary: #cbd5e1;
  --color-border-focus: #3b82f6;
";

        $css .= "}\n";

        // Mode sombre automatique
        $css .= "
@media (prefers-color-scheme: dark) {
  :root {
    --color-bg-primary: #1e293b;
    --color-bg-secondary: #0f172a;
    --color-bg-tertiary: #334155;
    --color-surface: #1e293b;
    --color-surface-hover: #334155;
    --color-surface-active: #475569;
    
    --color-text-primary: #f8fafc;
    --color-text-secondary: #cbd5e1;
    --color-text-tertiary: #94a3b8;
    --color-text-quaternary: #64748b;
    --color-text-inverse: #0f172a;
    
    --color-border-primary: #334155;
    --color-border-secondary: #475569;
  }
}
";

        return $css;
    }

    /**
     * Retourne les classes pour un bouton harmonisé
     */
    public static function getButtonClass($variant = 'primary', $size = 'md') {
        $baseClasses = [
            'inline-flex',
            'items-center',
            'justify-center',
            'border',
            'border-transparent',
            'font-medium',
            'focus:outline-none',
            'focus:ring-2',
            'focus:ring-offset-2',
            'disabled:opacity-50',
            'disabled:cursor-not-allowed'
        ];

        // Tailles
        $sizeClasses = [
            'xs' => ['px-2', 'py-1', 'text-xs', 'rounded'],
            'sm' => ['px-3', 'py-1.5', 'text-sm', 'rounded'],
            'md' => ['px-4', 'py-2', 'text-sm', 'rounded-md'],
            'lg' => ['px-6', 'py-3', 'text-base', 'rounded-md'],
            'xl' => ['px-8', 'py-4', 'text-lg', 'rounded-lg']
        ];

        // Variantes
        $variantClasses = [
            'primary' => ['bg-primary-600', 'text-white', 'hover:bg-primary-700', 'focus:ring-primary-500', 'shadow-sm', 'hover:shadow-md'],
            'secondary' => ['bg-neutral-100', 'text-neutral-900', 'hover:bg-neutral-200', 'focus:ring-neutral-500', 'border-neutral-300'],
            'accent' => ['bg-accent-500', 'text-white', 'hover:bg-accent-600', 'focus:ring-accent-500', 'shadow-sm'],
            'success' => ['bg-green-600', 'text-white', 'hover:bg-green-700', 'focus:ring-green-500'],
            'warning' => ['bg-yellow-500', 'text-yellow-900', 'hover:bg-yellow-600', 'focus:ring-yellow-500'],
            'error' => ['bg-red-600', 'text-white', 'hover:bg-red-700', 'focus:ring-red-500'],
            'outline' => ['bg-transparent', 'text-primary-600', 'border-primary-600', 'hover:bg-primary-50', 'focus:ring-primary-500'],
            'ghost' => ['bg-transparent', 'text-neutral-600', 'hover:bg-neutral-100', 'hover:text-neutral-900']
        ];

        $classes = array_merge(
            $baseClasses,
            $sizeClasses[$size] ?? $sizeClasses['md'],
            $variantClasses[$variant] ?? $variantClasses['primary']
        );

        return implode(' ', $classes);
    }

    /**
     * Retourne les classes pour une carte harmonisée
     */
    public static function getCardClass($elevated = false, $hover = true, $padding = 'md') {
        $baseClasses = [
            'bg-white',
            'border',
            'border-gray-200',
            'rounded-lg'
        ];

        if ($elevated) {
            $baseClasses[] = 'shadow-lg';
        } else {
            $baseClasses[] = 'shadow-sm';
        }

        if ($hover) {
            $baseClasses[] = 'hover:shadow-md';
            $baseClasses[] = 'transition-shadow';
            $baseClasses[] = 'duration-200';
        }

        // Padding options
        $paddingClasses = [
            'none' => [],
            'sm' => ['p-4'],
            'md' => ['p-6'],
            'lg' => ['p-8'],
            'xl' => ['p-10']
        ];

        $classes = array_merge($baseClasses, $paddingClasses[$padding] ?? $paddingClasses['md']);

        return implode(' ', $classes);
    }

    /**
     * Retourne les classes pour un badge de statut
     */
    public static function getBadgeClass($status = 'neutral', $size = 'md') {
        $baseClasses = [
            'inline-flex',
            'items-center',
            'font-medium',
            'rounded-full'
        ];

        // Tailles
        $sizeClasses = [
            'sm' => ['px-2', 'py-0.5', 'text-xs'],
            'md' => ['px-2.5', 'py-0.5', 'text-xs'],
            'lg' => ['px-3', 'py-1', 'text-sm']
        ];

        // Variantes de status
        $statusClasses = [
            'success' => ['bg-green-100', 'text-green-800'],
            'warning' => ['bg-yellow-100', 'text-yellow-800'],
            'error' => ['bg-red-100', 'text-red-800'],
            'info' => ['bg-blue-100', 'text-blue-800'],
            'neutral' => ['bg-gray-100', 'text-gray-800'],
            'primary' => ['bg-primary-100', 'text-primary-800']
        ];

        $classes = array_merge(
            $baseClasses,
            $sizeClasses[$size] ?? $sizeClasses['md'],
            $statusClasses[$status] ?? $statusClasses['neutral']
        );

        return implode(' ', $classes);
    }

    /**
     * Retourne les classes pour un input de formulaire
     */
    public static function getInputClass($state = null, $size = 'md') {
        $baseClasses = [
            'block',
            'w-full',
            'border',
            'border-gray-300',
            'rounded-md',
            'shadow-sm',
            'focus:outline-none',
            'focus:ring-2',
            'focus:ring-primary-500',
            'focus:border-primary-500',
            'disabled:bg-gray-50',
            'disabled:text-gray-500'
        ];

        // Tailles
        $sizeClasses = [
            'sm' => ['px-2', 'py-1', 'text-sm'],
            'md' => ['px-3', 'py-2', 'text-sm'],
            'lg' => ['px-4', 'py-3', 'text-base']
        ];

        // États
        $stateClasses = [
            'error' => ['border-red-500', 'focus:ring-red-500', 'focus:border-red-500'],
            'success' => ['border-green-500', 'focus:ring-green-500', 'focus:border-green-500'],
            'warning' => ['border-yellow-500', 'focus:ring-yellow-500', 'focus:border-yellow-500']
        ];

        $classes = array_merge(
            $baseClasses,
            $sizeClasses[$size] ?? $sizeClasses['md']
        );

        if ($state && isset($stateClasses[$state])) {
            // Remplacer les classes de bordure par défaut
            $classes = array_filter($classes, function($class) {
                return !in_array($class, ['border-gray-300', 'focus:ring-primary-500', 'focus:border-primary-500']);
            });
            $classes = array_merge($classes, $stateClasses[$state]);
        }

        return implode(' ', $classes);
    }

    /**
     * Retourne les classes pour un label de formulaire
     */
    public static function getLabelClass($required = false) {
        $classes = [
            'block',
            'text-sm',
            'font-medium',
            'text-gray-700',
            'mb-2'
        ];

        $labelClass = implode(' ', $classes);

        if ($required) {
            return $labelClass . ' required-label';
        }

        return $labelClass;
    }

    /**
     * Retourne les classes pour la navigation
     */
    public static function getNavLinkClass($active = false) {
        $baseClasses = [
            'px-3',
            'py-2',
            'text-sm',
            'font-medium',
            'rounded-md',
            'transition-colors',
            'duration-200'
        ];

        if ($active) {
            $activeClasses = [
                'bg-primary-100',
                'text-primary-800'
            ];
            $classes = array_merge($baseClasses, $activeClasses);
        } else {
            $inactiveClasses = [
                'text-gray-600',
                'hover:bg-gray-100',
                'hover:text-gray-900'
            ];
            $classes = array_merge($baseClasses, $inactiveClasses);
        }

        return implode(' ', $classes);
    }

    /**
     * Retourne les classes pour un tableau harmonisé
     */
    public static function getTableClass($striped = false, $hover = true) {
        $classes = ['min-w-full', 'divide-y', 'divide-gray-200'];

        if ($striped) {
            $classes[] = 'table-striped';
        }

        if ($hover) {
            $classes[] = 'table-hover';
        }

        return implode(' ', $classes);
    }

    /**
     * Retourne les classes pour une notification
     */
    public static function getNotificationClass($type = 'info') {
        $baseClasses = [
            'flex',
            'items-start',
            'p-4',
            'rounded-lg',
            'shadow-lg',
            'max-w-sm',
            'border-l-4'
        ];

        $typeClasses = [
            'success' => ['bg-green-50', 'text-green-700', 'border-green-400'],
            'error' => ['bg-red-50', 'text-red-700', 'border-red-400'],
            'warning' => ['bg-yellow-50', 'text-yellow-700', 'border-yellow-400'],
            'info' => ['bg-blue-50', 'text-blue-700', 'border-blue-400']
        ];

        $classes = array_merge(
            $baseClasses,
            $typeClasses[$type] ?? $typeClasses['info']
        );

        return implode(' ', $classes);
    }

    /**
     * Génère le script JavaScript pour la gestion du thème
     */
    public static function getThemeToggleScript() {
        return '
<script>
// Gestionnaire de thème sombre/clair automatique
class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem("app-theme") || "auto";
        this.init();
    }
    
    init() {
        this.applyTheme();
        this.watchSystemTheme();
    }
    
    applyTheme() {
        const root = document.documentElement;
        
        if (this.theme === "dark") {
            root.classList.add("dark");
        } else if (this.theme === "light") {
            root.classList.remove("dark");
        } else {
            // Mode auto: suivre les préférences système
            const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
            if (prefersDark) {
                root.classList.add("dark");
            } else {
                root.classList.remove("dark");
            }
        }
        
        // Mettre à jour le bouton toggle si il existe
        this.updateToggleButton();
    }
    
    watchSystemTheme() {
        const mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
        mediaQuery.addEventListener("change", () => {
            if (this.theme === "auto") {
                this.applyTheme();
            }
        });
    }
    
    setTheme(newTheme) {
        this.theme = newTheme;
        localStorage.setItem("app-theme", newTheme);
        this.applyTheme();
    }
    
    toggle() {
        const isDark = document.documentElement.classList.contains("dark");
        this.setTheme(isDark ? "light" : "dark");
    }
    
    updateToggleButton() {
        const toggleBtn = document.querySelector(".theme-toggle");
        if (!toggleBtn) return;
        
        const isDark = document.documentElement.classList.contains("dark");
        const sunIcon = toggleBtn.querySelector(".sun-icon");
        const moonIcon = toggleBtn.querySelector(".moon-icon");
        
        if (sunIcon && moonIcon) {
            if (isDark) {
                sunIcon.style.display = "block";
                moonIcon.style.display = "none";
            } else {
                sunIcon.style.display = "none"; 
                moonIcon.style.display = "block";
            }
        }
    }
}

// Initialiser le gestionnaire de thème
const themeManager = new ThemeManager();

// Fonctions globales
window.toggleTheme = () => themeManager.toggle();
window.setTheme = (theme) => themeManager.setTheme(theme);
</script>';
    }

    /**
     * Retourne le bouton de basculement de thème
     */
    public static function getThemeToggleButton() {
        $buttonClass = self::getButtonClass('ghost', 'sm');

        return '
<button onclick="toggleTheme()" class="' . $buttonClass . ' theme-toggle" title="Basculer le thème">
    <svg class="w-4 h-4 sun-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
    </svg>
    <svg class="w-4 h-4 moon-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
    </svg>
</button>';
    }

    /**
     * Génère les meta tags pour le thème
     */
    public static function getThemeMetaTags() {
        return '
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#2563eb" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1e293b" media="(prefers-color-scheme: dark)">
<meta name="msapplication-TileColor" content="#2563eb">
<meta name="msapplication-navbutton-color" content="#2563eb">';
    }

    /**
     * Retourne une grille responsive harmonisée
     */
    public static function getGridClass($cols = 3, $gap = 'md') {
        $gapClasses = [
            'sm' => 'gap-4',
            'md' => 'gap-6',
            'lg' => 'gap-8',
            'xl' => 'gap-10'
        ];

        $gridClasses = [
            1 => 'grid grid-cols-1',
            2 => 'grid grid-cols-1 md:grid-cols-2',
            3 => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
            4 => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
            5 => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
            6 => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6'
        ];

        $baseGrid = $gridClasses[$cols] ?? $gridClasses[3];
        $gapClass = $gapClasses[$gap] ?? $gapClasses['md'];

        return $baseGrid . ' ' . $gapClass;
    }

    /**
     * Retourne une couleur spécifique du système
     */
    public static function getColor($color, $shade = '600') {
        return self::$colorSystem[$color][$shade] ?? '#000000';
    }

    /**
     * Change la couleur principale dynamiquement
     */
    public static function setPrimaryColor($colors) {
        self::$colorSystem['primary'] = array_merge(self::$colorSystem['primary'], $colors);
    }

    /**
     * Retourne les informations du design system
     */
    public static function getSystemInfo() {
        return [
            'colors' => [
                'primary' => 'Bleu professionnel - Actions principales',
                'neutral' => 'Gris doux - Arrière-plans et textes',
                'accent' => 'Orange - Éléments d\'accent et CTA'
            ],
            'features' => [
                'dark_mode' => 'Automatique basé sur les préférences système',
                'components' => 'Boutons, cartes, formulaires, badges, navigation',
                'responsive' => 'Grilles et espacements adaptatifs',
                'animations' => 'Transitions fluides et professionnelles'
            ],
            'version' => '2.0',
            'compatibility' => 'Tailwind CSS v4+'
        ];
    }
}

// CSS supplémentaire pour les styles requis
if (!function_exists('getAdditionalCSS')) {
    function getAdditionalCSS() {
        return '
<style>
/* Styles supplémentaires pour les éléments requis */
.required-label::after {
    content: " *";
    color: #ef4444;
}

.table-striped tbody tr:nth-child(even) {
    background-color: #f8fafc;
}

.table-hover tbody tr:hover {
    background-color: #f1f5f9;
}

/* Animations fluides */
.transition-shadow {
    transition-property: box-shadow;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

.transition-colors {
    transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .bg-white { background-color: var(--color-surface) !important; }
    .text-gray-700 { color: var(--color-text-secondary) !important; }
    .text-gray-600 { color: var(--color-text-tertiary) !important; }
    .border-gray-200 { border-color: var(--color-border-primary) !important; }
    .border-gray-300 { border-color: var(--color-border-secondary) !important; }
}
</style>';
    }
}
?>
