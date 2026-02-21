<?php
/**
 * Layout Footer – migré vers le nouveau layout Bulma (ressources/views/layouts/app.php).
 *
 * Ce fichier est inclus par public/layout.php APRÈS header.php et variables.php.
 * Il capture le contenu bufferisé dans header.php, l'injecte dans le nouveau layout
 * via la variable $content, puis affiche le HTML final avec injection CSRF.
 *
 * ► Aucun contrôleur, aucune route, aucun modèle n'est modifié.
 */

// ────────────────────────────────────────────────────────────────────────────
// 1. Récupérer le contenu bufferisé (contenu de la page + variables.php)
// ────────────────────────────────────────────────────────────────────────────
$content = (string)ob_get_clean();

// ────────────────────────────────────────────────────────────────────────────
// 2. Inclure le nouveau layout qui produit le HTML complet
//    Le layout utilise les variables préparées dans header.php:
//    $menus, $page_title, $title, $user, $csrf_token, $flash_message,
//    $extra_css, $extra_js, $content, $c (ComponentHelper)
// ────────────────────────────────────────────────────────────────────────────

// Le layout app.php est dans ressources/views/layouts/
$layoutFile = __DIR__ . '/../../ressources/views/layouts/app.php';

include $layoutFile;

// ────────────────────────────────────────────────────────────────────────────
// 3. Injection CSRF automatique dans tous les formulaires POST (legacy)
// ────────────────────────────────────────────────────────────────────────────
/**
 * Injecte un champ CSRF dans les formulaires POST du legacy.
 * Objectif: sécuriser l'existant sans modifier toutes les vues d'un coup.
 */
function injectCsrfIntoPostForms(string $html): string
{
    $token = htmlspecialchars(\CheckMaster\Core\Csrf::token(), ENT_QUOTES, 'UTF-8');
    $field = '<input type="hidden" name="csrf_token" value="' . $token . '">';

    // Ajout juste après la balise <form ... method="post" ...>
    return preg_replace(
        '/(<form\b[^>]*\bmethod\s*=\s*(?:\"|\')?\s*post(?:\"|\')?\s*[^>]*>)/i',
        '$1' . $field,
        $html
    ) ?? $html;
}

$__out = ob_get_clean();
if ($__out !== false) {
    echo injectCsrfIntoPostForms($__out);
}
?>
