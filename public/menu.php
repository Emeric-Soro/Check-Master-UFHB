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
            return (string) mb_convert_case($code, MB_CASE_TITLE, 'UTF-8');
        }
        return ucwords($code);
    }

    private function displayLabel($fonc): string
    {
        $label = isset($fonc->label_fonctionnalite) ? (string) $fonc->label_fonctionnalite : '';
        $lib = isset($fonc->lib_fonctionnalite) ? (string) $fonc->lib_fonctionnalite : '';
        $code = isset($fonc->code_fonctionnalite) ? (string) $fonc->code_fonctionnalite : '';

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

            // ID unique pour le collapse
            $collapseId = 'collapse-' . $categorie->code_categorie;

            // Vérifier si une fonctionnalité de cette catégorie est active
            $categorieHasActive = false;
            foreach ($fonctionnalites as $fonc) {
                if ($this->isItemActive($currentPage, $fonc)) {
                    $categorieHasActive = true;
                    break;
                }
            }

            // Header de catégorie (collapse)
            $html .= '<div class="mb-2">';
            $html .= '<button type="button" class="w-full flex items-center justify-between px-4 py-2 text-sm font-semibold text-white/90 hover:text-white  rounded-lg transition-all duration-200" ';
            $html .= 'onclick="toggleCategory(\'' . $collapseId . '\')" id="btn-' . $collapseId . '">';
            $html .= '<div class="flex items-center">';
            $html .= '<i class="' . htmlspecialchars($categorie->icone_categorie) . ' mr-3 text-base w-5 text-center"></i>';
            $html .= '<span>' . htmlspecialchars($categorie->lib_categorie) . '</span>';
            $html .= '</div>';
            $html .= '<i class="fas fa-chevron-down text-xs transition-transform duration-200" id="icon-' . $collapseId . '"></i>';
            $html .= '</button>';

            // Contenu de la catégorie (fonctionnalités)
            $displayStyle = ($index === 0 || $categorieHasActive) ? '' : 'display: none;'; // Premier et actif ouverts par défaut
            $html .= '<div id="' . $collapseId . '" class="mt-1 ml-4 space-y-1" style="' . $displayStyle . '">';

            foreach ($fonctionnalites as $fonc) {
                $children = isset($fonc->children) && is_array($fonc->children) ? $fonc->children : [];
                $hasChildren = !empty($children);
                $isActive = $this->isItemActive($currentPage, $fonc);
                $linkBaseClasses = "flex items-center px-4 py-2 text-sm font-medium rounded-lg group transition-all duration-200";
                $activeClasses = "bg-white text-primary shadow-md";
                $inactiveClasses = "text-white/70 hover:text-white ";
                $iconBaseClasses = "mr-3 text-base w-5 text-center";
                $iconActiveClasses = "text-primary";
                $iconInactiveClasses = "text-white/50 group-hover:text-white";



                $subId = 'sub-' . preg_replace('/[^a-zA-Z0-9_\-]/', '-', (string) ($fonc->code_fonctionnalite ?? uniqid()));
                if ($hasChildren) {
                    // Parent (sous-menu) + toggle
                    $html .= '<button type="button" class="' . $linkBaseClasses . ' ' . ($isActive ? $activeClasses : $inactiveClasses) . ' w-full justify-between" ';
                    $html .= 'onclick="toggleSubMenu(\'' . $subId . '\')" aria-controls="' . $subId . '">';
                    $html .= '<span class="flex items-center">';
                    $html .= '<i class="fas ' . htmlspecialchars((string) ($fonc->icone_fonctionnalite ?? 'fa-folder')) . ' ' . $iconBaseClasses . ' ' . ($isActive ? $iconActiveClasses : $iconInactiveClasses) . '"></i>';
                    $html .= '<span>' . htmlspecialchars($this->displayLabel($fonc)) . '</span>';
                    $html .= '</span>';
                    $html .= '<i class="fas fa-chevron-down text-xs transition-transform duration-200 ' . ($isActive ? 'rotate-180' : '') . '" id="icon-' . $subId . '"></i>';
                    $html .= '</button>';
                    $subDisplay = $isActive ? '' : 'display:none;';
                    $html .= '<div id="' . $subId . '" class="ml-6 mt-1 space-y-1" style="' . $subDisplay . '">';
                    foreach ($children as $child) {
                        $childActive = $this->isPageActiveExact($currentPage, (string) ($child->url_fonctionnalite ?? ''));
                        $html .= '<a href="' . htmlspecialchars((string) ($child->url_fonctionnalite ?? '#')) . '" class="' . $linkBaseClasses . ' ' . ($childActive ? $activeClasses : $inactiveClasses) . '">';
                        $html .= '<i class="fas ' . htmlspecialchars((string) ($child->icone_fonctionnalite ?? 'fa-circle')) . ' ' . $iconBaseClasses . ' ' . ($childActive ? $iconActiveClasses : $iconInactiveClasses) . '"></i>';
                        $html .= '<span>' . htmlspecialchars($this->displayLabel($child)) . '</span>';
                        $html .= '</a>';
                    }
                    $html .= '</div>';
                } else {
                    $html .= '<a href="' . htmlspecialchars((string) ($fonc->url_fonctionnalite ?? '#')) . '" class="' . $linkBaseClasses . ' ' . ($isActive ? $activeClasses : $inactiveClasses) . '">';
                    $html .= '<i class="fas ' . htmlspecialchars((string) ($fonc->icone_fonctionnalite ?? 'fa-circle')) . ' ' . $iconBaseClasses . ' ' . ($isActive ? $iconActiveClasses : $iconInactiveClasses) . '"></i>';
                    $html .= '<span>' . htmlspecialchars($this->displayLabel($fonc)) . '</span>';
                    $html .= '</a>';
                }
            }

            $html .= '</div>'; // Fin contenu catégorie
            $html .= '</div>'; // Fin bloc catégorie
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
        return $this->isPageActiveExact($currentPage, (string) $url);
    }

    private function extractPageAction(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);
        $pageName = '';
        $action = '';
        if ($query) {
            $params = [];
            parse_str($query, $params);
            $pageName = isset($params['page']) ? (string) $params['page'] : '';
            $action = isset($params['action']) ? (string) $params['action'] : '';
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

        $currentGetPage = isset($_GET['page']) ? (string) $_GET['page'] : '';
        $currentGetAction = isset($_GET['action']) ? (string) $_GET['action'] : '';

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
        if ($this->isPageActiveExact($currentPage, (string) ($fonc->url_fonctionnalite ?? ''))) {
            return true;
        }
        $children = isset($fonc->children) && is_array($fonc->children) ? $fonc->children : [];
        foreach ($children as $child) {
            if ($this->isPageActiveExact($currentPage, (string) ($child->url_fonctionnalite ?? ''))) {
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
            const content = document.getElementById(collapseId);
            const icon = document.getElementById("icon-" + collapseId);
            
            if (content.style.display === "none" || content.style.display === "") {
                content.style.display = "block";
                icon.style.transform = "rotate(180deg)";
            } else {
                content.style.display = "none";
                icon.style.transform = "rotate(0deg)";
            }
        }

        function toggleSubMenu(subId) {
            const content = document.getElementById(subId);
            const icon = document.getElementById("icon-" + subId);
            if (!content) return;
            if (content.style.display === "none" || content.style.display === "") {
                content.style.display = "block";
                if (icon) icon.style.transform = "rotate(180deg)";
            } else {
                content.style.display = "none";
                if (icon) icon.style.transform = "rotate(0deg)";
            }
        }
        
        // Auto-expand catégorie active au chargement
        document.addEventListener("DOMContentLoaded", function() {
            const activeLinks = document.querySelectorAll(".bg-white.text-primary");
            activeLinks.forEach(link => {
                const category = link.closest("[id^=\'collapse-\']");
                if (category) {
                    category.style.display = "block";
                    const categoryId = category.id;
                    const icon = document.getElementById("icon-" + categoryId);
                    if (icon) icon.style.transform = "rotate(180deg)";
                }
            });
        });
        </script>';
    }
}