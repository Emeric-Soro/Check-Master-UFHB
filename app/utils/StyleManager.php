<?php
/**
 * STYLEMANAGER FINAL - DESIGN SYSTEM COMPLET BASÉ SUR LE LOGO
 *
 * Système 3 couleurs du logo :
 * - TEAL PRINCIPAL (#0F766E) : Couleur principale du logo
 * - CHARCOAL SECONDAIRE (#1F2937) : Couleur sombre du logo
 * - GRIS NEUTRE (#6B7280) : Couleur grise d'accompagnement
 *
 * Couleurs d'état conservées : Vert/Rouge pour succès/erreur
 * Mode sombre automatique + basculement manuel
 *
 * @version 3.0 FINAL
 * @author Design System Team
 * @compatibility Tailwind CSS v4+
 */
class StyleManager {

    /**
     * Configuration des couleurs basée sur le logo
     */
    private static $colorSystem = [
        // COULEUR PRINCIPALE - TEAL DU LOGO
        'primary' => [
            '50'  => '#f0fdfa',
            '100' => '#ccfbf1',
            '200' => '#99f6e4',
            '300' => '#5eead4',
            '400' => '#2dd4bf',
            '500' => '#14b8a6',
            '600' => '#0F766E',  // Couleur exacte du logo
            '700' => '#0d9488',
            '800' => '#115e59',
            '900' => '#134e4a'
        ],

        // COULEUR SECONDAIRE - CHARCOAL DU LOGO
        'secondary' => [
            '50'  => '#f8fafc',
            '100' => '#f1f5f9',
            '200' => '#e2e8f0',
            '300' => '#cbd5e1',
            '400' => '#94a3b8',
            '500' => '#64748b',
            '600' => '#475569',
            '700' => '#334155',
            '800' => '#1e293b',
            '900' => '#1F2937'  // Couleur exacte du logo
        ],

        // COULEUR NEUTRE - GRIS DU LOGO
        'neutral' => [
            '50'  => '#f9fafb',
            '100' => '#f3f4f6',
            '200' => '#e5e7eb',
            '300' => '#d1d5db',
            '400' => '#9ca3af',
            '500' => '#6B7280',  // Couleur exacte du logo
            '600' => '#4b5563',
            '700' => '#374151',
            '800' => '#1f2937',
            '900' => '#111827'
        ]
    ];

    /**
     * Couleurs d'état sémantiques (conservées)
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
            'light' => '#0ea5e9',
            'background' => '#e0f2fe',
            'text' => '#0369a1'
        ]
    ];

    /**
     * Génère les variables CSS complètes avec les couleurs du logo
     */
    public static function generateCSSVariables() {
        $css = ":root {\n";

        // Couleurs système basées sur le logo
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
  --color-bg-secondary: #f9fafb;
  --color-bg-tertiary: #f3f4f6;
  --color-surface: #ffffff;
  --color-surface-hover: #f9fafb;
  --color-surface-active: #f3f4f6;
  
  --color-text-primary: #1F2937;
  --color-text-secondary: #374151;
  --color-text-tertiary: #6B7280;
  --color-text-quaternary: #9ca3af;
  --color-text-inverse: #ffffff;
  
  --color-border-primary: #e5e7eb;
  --color-border-secondary: #d1d5db;
  --color-border-focus: #0F766E;
";

        $css .= "}\n";

        // Mode sombre automatique
        $css .= "
@media (prefers-color-scheme: dark) {
  :root {
    --color-bg-primary: #1F2937;
    --color-bg-secondary: #111827;
    --color-bg-tertiary: #374151;
    --color-surface: #1F2937;
    --color-surface-hover: #374151;
    --color-surface-active: #4b5563;
    
    --color-text-primary: #f9fafb;
    --color-text-secondary: #e5e7eb;
    --color-text-tertiary: #9ca3af;
    --color-text-quaternary: #6B7280;
    --color-text-inverse: #1F2937;
    
    --color-border-primary: #374151;
    --color-border-secondary: #4b5563;
  }
}
";

        return $css;
    }

    /**
     * Retourne les classes pour un bouton harmonisé avec les couleurs du logo
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
            'disabled:cursor-not-allowed',
            'transition-fast'
        ];

        // Tailles
        $sizeClasses = [
            'xs' => ['px-2', 'py-1', 'text-xs', 'rounded'],
            'sm' => ['px-3', 'py-1.5', 'text-sm', 'rounded'],
            'md' => ['px-4', 'py-2', 'text-sm', 'rounded-md'],
            'lg' => ['px-6', 'py-3', 'text-base', 'rounded-md'],
            'xl' => ['px-8', 'py-4', 'text-lg', 'rounded-lg']
        ];

        // Variantes basées sur les couleurs du logo
        $variantClasses = [
            'primary' => ['bg-primary-600', 'text-white', 'hover:bg-primary-700', 'focus:ring-primary-500', 'shadow-sm', 'hover:shadow-md'],
            'secondary' => ['bg-secondary-100', 'text-secondary-900', 'hover:bg-secondary-200', 'focus:ring-secondary-500', 'border-neutral-300'],
            'neutral' => ['bg-neutral-100', 'text-neutral-900', 'hover:bg-neutral-200', 'focus:ring-neutral-500'],
            'success' => ['bg-green-600', 'text-white', 'hover:bg-green-700', 'focus:ring-green-500'],
            'warning' => ['bg-yellow-500', 'text-white', 'hover:bg-yellow-600', 'focus:ring-yellow-500'],
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

        // Variantes de status avec couleurs du logo
        $statusClasses = [
            'success' => ['bg-green-100', 'text-green-800'],
            'warning' => ['bg-yellow-100', 'text-yellow-800'],
            'error' => ['bg-red-100', 'text-red-800'],
            'info' => ['bg-blue-100', 'text-blue-800'],
            'neutral' => ['bg-neutral-100', 'text-neutral-800'],
            'primary' => ['bg-primary-100', 'text-primary-800'],
            'secondary' => ['bg-secondary-100', 'text-secondary-800']
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
            'disabled:text-gray-500',
            'transition-fast'
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
     * Retourne les classes pour la navigation avec couleurs du logo
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
                'text-neutral-600',
                'hover:bg-primary-50',
                'hover:text-primary-700'
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
     * Génère le script JavaScript pour la gestion du thème
     */
    public static function getThemeToggleScript() {
        return '
<script>
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
            const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
            if (prefersDark) {
                root.classList.add("dark");
            } else {
                root.classList.remove("dark");
            }
        }
        
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

const themeManager = new ThemeManager();
window.toggleTheme = () => themeManager.toggle();
window.setTheme = (theme) => themeManager.setTheme(theme);
</script>';
    }

    /**
     * Retourne le bouton de basculement de thème avec couleurs du logo
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
     * Génère les meta tags pour le thème avec couleurs du logo
     */
    public static function getThemeMetaTags() {
        return '
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#0F766E" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#1F2937" media="(prefers-color-scheme: dark)">
<meta name="msapplication-TileColor" content="#0F766E">
<meta name="msapplication-navbutton-color" content="#0F766E">';
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
                'primary' => 'Teal principal #0F766E - Couleur 1 du logo',
                'secondary' => 'Charcoal #1F2937 - Couleur 2 du logo',
                'neutral' => 'Gris neutre #6B7280 - Couleur 3 du logo'
            ],
            'features' => [
                'dark_mode' => 'Automatique basé sur les préférences système',
                'components' => 'Boutons, cartes, formulaires, badges, navigation',
                'responsive' => 'Grilles et espacements adaptatifs',
                'animations' => 'Transitions fluides et professionnelles',
                'logo_colors' => 'Couleurs extraites directement du logo'
            ],
            'version' => '3.0 FINAL',
            'compatibility' => 'Tailwind CSS v4+',
            'logo_source' => 'Couleurs basées sur logo_cm_sbg.jpg'
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
    background-color: #f9fafb;
}

.table-hover tbody tr:hover {
    background-color: #f3f4f6;
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

/* Dark mode support avec couleurs du logo */
@media (prefers-color-scheme: dark) {
    .bg-white { background-color: #1F2937 !important; }
    .text-gray-700 { color: #e5e7eb !important; }
    .text-gray-600 { color: #9ca3af !important; }
    .border-gray-200 { border-color: #374151 !important; }
    .border-gray-300 { border-color: #4b5563 !important; }
}
</style>';
    }
}
?>