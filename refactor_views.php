<?php
/**
 * Script de refactoring automatique des vues CheckMaster
 * Convertit les vues existantes vers le nouveau système de composants
 * 
 * Usage: php refactor_views.php [fichier|dossier]
 */

class ViewRefactorer
{
    private $patterns = [
        // Classes Tailwind -> Bulma
        'tailwind_to_bulma' => [
            '/class="([^"]*)bg-white([^"]*)"/' => 'class="$1has-background-white$2"',
            '/class="([^"]*)bg-gray-100([^"]*)"/' => 'class="$1has-background-light$2"',
            '/class="([^"]*)rounded-lg([^"]*)"/' => 'class="$1is-rounded$2"',
            '/class="([^"]*)shadow-md([^"]*)"/' => 'class="$1has-shadow$2"',
            '/class="([^"]*)text-gray-800([^"]*)"/' => 'class="$1$2"', // Supprimer
            '/class="([^"]*)text-blue-600([^"]*)"/' => 'class="$1has-text-primary$2"',
            '/class="([^"]*)font-semibold([^"]*)"/' => 'class="$1has-text-weight-semibold$2"',
            '/class="([^"]*)font-bold([^"]*)"/' => 'class="$1has-text-weight-bold$2"',
            '/class="([^"]*)text-xl([^"]*)"/' => 'class="$1is-size-4$2"',
            '/class="([^"]*)text-2xl([^"]*)"/' => 'class="$1is-size-3$2"',
            '/class="([^"]*)text-3xl([^"]*)"/' => 'class="$1is-size-2$2"',
            '/class="([^"]*)text-sm([^"]*)"/' => 'class="$1is-size-7$2"',
            '/class="([^"]*)text-xs([^"]*)"/' => 'class="$1is-size-7$2"',
            '/class="([^"]*)flex([^"]*)"/' => 'class="$1is-flex$2"',
            '/class="([^"]*)items-center([^"]*)"/' => 'class="$1is-align-items-center$2"',
            '/class="([^"]*)justify-center([^"]*)"/' => 'class="$1is-justify-content-center$2"',
            '/class="([^"]*)justify-between([^"]*)"/' => 'class="$1is-justify-content-space-between$2"',
            '/class="([^"]*)hidden([^"]*)"/' => 'class="$1is-hidden$2"',
            '/class="([^"]*)block([^"]*)"/' => 'class="$1$2"', // Supprimer (défaut)
            '/class="([^"]*)w-full([^"]*)"/' => 'class="$1is-fullwidth$2"',
            '/class="([^"]*)mb-4([^"]*)"/' => 'class="$1mb-4$2"',
            '/class="([^"]*)mb-6([^"]*)"/' => 'class="$1mb-5$2"',
            '/class="([^"]*)mt-4([^"]*)"/' => 'class="$1mt-4$2"',
            '/class="([^"]*)mt-6([^"]*)"/' => 'class="$1mt-5$2"',
            '/class="([^"]*)p-4([^"]*)"/' => 'class="$1p-4$2"',
            '/class="([^"]*)p-6([^"]*)"/' => 'class="$1p-5$2"',
            '/class="([^"]*)px-4([^"]*)"/' => 'class="$1px-4$2"',
            '/class="([^"]*)py-8([^"]*)"/' => 'class="$1py-5$2"',
            '/class="([^"]*)container mx-auto([^"]*)"/' => 'class="$1container$2"',
            '/class="([^"]*)grid grid-cols-2([^"]*)"/' => 'class="$1columns$2"',
            '/class="([^"]*)grid grid-cols-3([^"]*)"/' => 'class="$1columns is-multiline$2"',
        ],
        
        // Formulaires simples
        'forms' => [
            // Label + input basique -> composant field
            '/<label[^>]*>([^<]+)<\/label>\s*<input[^>]*type="text"[^>]*name="([^"]+)"[^>]*>/i' => 
                '<?= $c->form(\'field\', [\'name\' => \'$2\', \'label\' => \'$1\']) ?>',
        ],
    ];
    
    public function refactorFile($filePath)
    {
        if (!file_exists($filePath)) {
            echo "❌ Fichier non trouvé: $filePath\n";
            return false;
        }
        
        $content = file_get_contents($filePath);
        $originalContent = $content;
        
        // Identifier le type de page
        $pageType = $this->detectPageType($content);
        echo "📄 Type détecté: $pageType\n";
        
        // Appliquer les conversions CSS
        foreach ($this->patterns['tailwind_to_bulma'] as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        // Nettoyer les classes vides
        $content = preg_replace('/class="\s*"/', '', $content);
        $content = preg_replace('/class="([^"]*)\s+"/', 'class="$1"', $content);
        
        // Sauvegarder le backup
        $backupPath = $filePath . '.backup.' . date('YmdHis');
        file_put_contents($backupPath, $originalContent);
        
        // Sauvegarder le fichier refactorisé
        file_put_contents($filePath, $content);
        
        echo "✅ Refactorisé: $filePath\n";
        echo "💾 Backup: $backupPath\n";
        
        return true;
    }
    
    private function detectPageType($content)
    {
        if (strpos($content, 'data-table') !== false || 
            (strpos($content, '<table') !== false && strpos($content, '<form') !== false)) {
            return 'CRUD (liste + formulaire)';
        }
        if (strpos($content, 'dashboard') !== false || 
            preg_match('/stat|card|widget/i', $content)) {
            return 'Dashboard';
        }
        if (strpos($content, 'consultation') !== false ||
            (strpos($content, 'readonly') !== false && strpos($content, '<form') === false)) {
            return 'Consultation (lecture seule)';
        }
        if (strpos($content, 'editor') !== false || strpos($content, 'textarea') !== false) {
            return 'Éditeur';
        }
        return 'Standard';
    }
}

// Exécution
if ($argc < 2) {
    echo "Usage: php refactor_views.php [fichier|dossier]\n";
    echo "Exemples:\n";
    echo "  php refactor_views.php gestion_etudiants_content.php\n";
    echo "  php refactor_views.php ressources/views/\n";
    exit(1);
}

$refactorer = new ViewRefactorer();
$target = $argv[1];

if (is_file($target)) {
    $refactorer->refactorFile($target);
} elseif (is_dir($target)) {
    $files = glob($target . '/*_content.php');
    echo "🔍 " . count($files) . " fichiers trouvés\n\n";
    
    $count = 0;
    foreach ($files as $file) {
        if ($refactorer->refactorFile($file)) {
            $count++;
        }
        echo "\n";
    }
    
    echo "\n✅ $count fichiers refactorisés avec succès\n";
} else {
    echo "❌ Chemin invalide: $target\n";
    exit(1);
}
