<?php
// Passerelle: le menu legacy est conservé dans /public/menu.php
// mais l'application se sert désormais via /public/app/layout.php.
// (Ce fichier reste ici car il est inclus par public/layout.php historique.)

class MenuView
{
    private function prettyLabelFromCode(string $code): string
    {
        $code = preg_replace('/^(SCOL|ETU|COM|ADM)_/u', '', $code);
        $code = str_replace('_', ' ', $code);
        $code = strtolower($code);
        if (function_exists('mb_convert_case')) {
            return (string)mb_convert_case($code, MB_CASE_TITLE, 'UTF-8');
        }
        return ucwords($code);
    }

    /**
     * Valider qu'une URL est sûre pour un attribut href (pas de javascript:, data:, vbscript:)
     */
    private function safeUrl(string $url): string
    {
        $trimmed = trim($url);
        if ($trimmed === '' || $trimmed === '#') {
            return '#';
        }
        // Autoriser uniquement les URLs relatives (?, /) ou les schémas http/https
        if (preg_match('/^(https?:\/\/|\/|\?|#)/i', $trimmed)) {
            return $trimmed;
        }
        return '#';
    }

    private function displayLabel($fonc): string
    {
        $label = isset($fonc->label_fonctionnalite) ? (string)$fonc->label_fonctionnalite : '';
        $lib = isset($fonc->lib_fonctionnalite) ? (string)$fonc->lib_fonctionnalite : '';
        $code = isset($fonc->code_fonctionnalite) ? (string)$fonc->code_fonctionnalite : '';

        $label = trim($label);
        $lib = trim($lib);
        $code = trim($code);

        if ($label !== '' && $label !== $code) {
            return $label;
        }
        if ($lib !== '' && $lib !== $code) {
            return $lib;
        }
        if ($code !== '') {
            return $this->prettyLabelFromCode($code);
        }
        return '';
    }

    /**
     * Afficher le menu hiérarchique avec catégories et fonctionnalités
     * @param array $menuHierarchique - Structure [['categorie' => obj, 'fonctionnalites' => [obj]]]
     * @param string $currentPage - Page actuelle pour highlight
     * @return string HTML du menu
     */
    public function afficherMenuHierarchique($menuHierarchique, $currentPage = '')
    {
        $html = '';

        foreach ($menuHierarchique as $index => $item) {
            $categorie = $item['categorie'];
            $fonctionnalites = $item['fonctionnalites'];

            $collapseId = 'collapse-' . $categorie->code_categorie;

            // Vérifier si une fonctionnalité de cette catégorie est active
            $categorieHasActive = false;
            foreach ($fonctionnalites as $fonc) {
                if ($this->isItemActive($currentPage, $fonc)) {
                    $categorieHasActive = true;
                    break;
                }
            }

            $isOpen = ($index === 0 || $categorieHasActive);
            $chevronClass = $isOpen ? 'is-open' : '';
            $displayStyle = $isOpen ? '' : 'display: none;';

            // Header de catégorie
            $html .= '<div class="cm-sidebar__category">';
            $html .= '<button type="button" class="cm-sidebar__cat-btn" ';
            $html .= 'onclick="toggleCategory(\'' . $collapseId . '\')" id="btn-' . $collapseId . '">';
            $html .= '<span class="cm-sidebar__cat-btn-left">';
            $html .= '<i class="' . htmlspecialchars($categorie->icone_categorie) . ' cm-sidebar__cat-icon"></i>';
            $html .= '<span>' . htmlspecialchars($categorie->lib_categorie) . '</span>';
            $html .= '</span>';
            $html .= '<i class="fas fa-chevron-down cm-sidebar__cat-chevron ' . $chevronClass . '" id="icon-' . $collapseId . '"></i>';
            $html .= '</button>';

            // Contenu de la catégorie (fonctionnalités)
            $html .= '<div id="' . $collapseId . '" class="cm-sidebar__cat-items" style="' . $displayStyle . '">';

            foreach ($fonctionnalites as $fonc) {
                $children = isset($fonc->children) && is_array($fonc->children) ? $fonc->children : [];
                $hasChildren = !empty($children);
                $isActive = $this->isItemActive($currentPage, $fonc);

                $subId = 'sub-' . preg_replace('/[^a-zA-Z0-9_\-]/', '-', (string)($fonc->code_fonctionnalite ?? uniqid()));

                if ($hasChildren) {
                    // Parent item with sub-menu
                    $activeClass = $isActive ? ' is-active-blue' : '';
                    $html .= '<button type="button" class="cm-sidebar__menu-link cm-sidebar__menu-parent' . $activeClass . '" ';
                    $html .= 'onclick="toggleSubMenu(\'' . $subId . '\')" aria-controls="' . $subId . '">';
                    $html .= '<span class="cm-sidebar__menu-parent-left">';
                    $html .= '<i class="fas ' . htmlspecialchars((string)($fonc->icone_fonctionnalite ?? 'fa-folder')) . ' cm-sidebar__menu-icon"></i>';
                    $html .= '<span>' . htmlspecialchars($this->displayLabel($fonc)) . '</span>';
                    $html .= '</span>';
                    $subChevronClass = $isActive ? 'is-open' : '';
                    $html .= '<i class="fas fa-chevron-down cm-sidebar__sub-chevron ' . $subChevronClass . '" id="icon-' . $subId . '"></i>';
                    $html .= '</button>';

                    $subDisplay = $isActive ? '' : 'display:none;';
                    $html .= '<div id="' . $subId . '" class="cm-sidebar__sub-items" style="' . $subDisplay . '">';
                    foreach ($children as $child) {
                        $childActive = $this->isPageActiveExact($currentPage, (string)($child->url_fonctionnalite ?? ''));
                        $childActiveClass = $childActive ? ' is-active-blue' : '';
                        $html .= '<a href="' . htmlspecialchars($this->safeUrl((string)($child->url_fonctionnalite ?? '#')), ENT_QUOTES, 'UTF-8') . '" class="cm-sidebar__menu-link' . $childActiveClass . '">';
                        $html .= '<i class="fas ' . htmlspecialchars((string)($child->icone_fonctionnalite ?? 'fa-circle')) . ' cm-sidebar__menu-icon"></i>';
                        $html .= '<span>' . htmlspecialchars($this->displayLabel($child)) . '</span>';
                        $html .= '</a>';
                    }
                    $html .= '</div>';
                }
                else {
                    // Simple menu link
                    $activeClass = $isActive ? ' is-active-blue' : '';
                    $html .= '<a href="' . htmlspecialchars($this->safeUrl((string)($fonc->url_fonctionnalite ?? '#')), ENT_QUOTES, 'UTF-8') . '" class="cm-sidebar__menu-link' . $activeClass . '">';
                    $html .= '<i class="fas ' . htmlspecialchars((string)($fonc->icone_fonctionnalite ?? 'fa-circle')) . ' cm-sidebar__menu-icon"></i>';
                    $html .= '<span>' . htmlspecialchars($this->displayLabel($fonc)) . '</span>';
                    $html .= '</a>';
                }
            }

            $html .= '</div>'; // Fin cm-sidebar__cat-items
            $html .= '</div>'; // Fin cm-sidebar__category
        }

        // Script JavaScript pour toggle
        $html .= $this->getToggleScript();

        return $html;
    }

    /**
     * Vérifier si une page est active
     */
    private function isPageActive($currentPage, $url)
    {
        return $this->isPageActiveExact($currentPage, (string)$url);
    }

    private function extractPageAction(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);
        $pageName = '';
        $action = '';
        if ($query) {
            $params = [];
            parse_str($query, $params);
            $pageName = isset($params['page']) ? (string)$params['page'] : '';
            $action = isset($params['action']) ? (string)$params['action'] : '';
        }
        return [$pageName, $action];
    }

    /**
     * Active si:
     * - url contient action => page + action doivent matcher
     * - sinon => page suffit
     */
    private function isPageActiveExact(string $currentPage, string $url): bool
    {
        [$pageName, $action] = $this->extractPageAction($url);
        if ($pageName === '') {
            return false;
        }

        $currentGetPage = isset($_GET['page']) ? (string)$_GET['page'] : '';
        $currentGetAction = isset($_GET['action']) ? (string)$_GET['action'] : '';

        $pageMatches = ($currentPage === $pageName) || ($currentGetPage === $pageName);
        if (!$pageMatches) {
            return false;
        }
        if ($action !== '') {
            return $currentGetAction === $action;
        }
        return true;
    }

    private function isItemActive(string $currentPage, $fonc): bool
    {
        if ($this->isPageActiveExact($currentPage, (string)($fonc->url_fonctionnalite ?? ''))) {
            return true;
        }
        $children = isset($fonc->children) && is_array($fonc->children) ? $fonc->children : [];
        foreach ($children as $child) {
            if ($this->isPageActiveExact($currentPage, (string)($child->url_fonctionnalite ?? ''))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Script JavaScript pour expand/collapse des catégories
     */
    private function getToggleScript()
    {
        return '<script>
        function toggleCategory(collapseId) {
            var content = document.getElementById(collapseId);
            var icon = document.getElementById("icon-" + collapseId);
            if (!content) return;

            if (content.style.display === "none") {
                content.style.display = "";
                if (icon) icon.classList.add("is-open");
            } else {
                content.style.display = "none";
                if (icon) icon.classList.remove("is-open");
            }
        }

        function toggleSubMenu(subId) {
            var content = document.getElementById(subId);
            var icon = document.getElementById("icon-" + subId);
            if (!content) return;

            if (content.style.display === "none") {
                content.style.display = "";
                if (icon) icon.classList.add("is-open");
            } else {
                content.style.display = "none";
                if (icon) icon.classList.remove("is-open");
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            var activeLinks = document.querySelectorAll(".cm-sidebar__menu-link.is-active-blue, .cm-sidebar__menu-link.is-active");
            activeLinks.forEach(function(link) {
                var category = link.closest("[id^=\'collapse-\']");
                if (category) {
                    category.style.display = "";
                    var icon = document.getElementById("icon-" + category.id);
                    if (icon) icon.classList.add("is-open");
                }
                var subMenu = link.closest(".cm-sidebar__sub-items");
                if (subMenu) {
                    subMenu.style.display = "";
                    var subIcon = document.getElementById("icon-" + subMenu.id);
                    if (subIcon) subIcon.classList.add("is-open");
                }
            });
        });
        </script>';
    }
}