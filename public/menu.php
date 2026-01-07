<?php

class MenuView
{
    /**
     * Afficher le menu hiérarchique avec catégories et fonctionnalités
     * @param array $menuHierarchique - Structure [['categorie' => obj, 'fonctionnalites' => [obj]]]
     * @param string $currentPage - Page actuelle pour highlight
     * @return string HTML du menu
     */
    public function afficherMenuHierarchique($menuHierarchique, $currentPage = '')
    {
        $html = '';

        // Récupérer le statut de la candidature si étudiant
        $statut = null;
        if (isset($_SESSION['id_GU']) && $_SESSION['id_GU'] == 13 && isset($_SESSION['num_etu'])) {
            require_once __DIR__ . '/../app/models/CandidatureSoutenance.php';
            $statut = CandidatureSoutenance::getStatutByEtudiant($_SESSION['num_etu']);
        }

        foreach ($menuHierarchique as $index => $item) {
            $categorie = $item['categorie'];
            $fonctionnalites = $item['fonctionnalites'];

            // ID unique pour le collapse
            $collapseId = 'collapse-' . $categorie->code_categorie;

            // Vérifier si une fonctionnalité de cette catégorie est active
            $categorieHasActive = false;
            foreach ($fonctionnalites as $fonc) {
                if ($this->isPageActive($currentPage, $fonc->url_fonctionnalite)) {
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
                $isActive = $this->isPageActive($currentPage, $fonc->url_fonctionnalite);
                $linkBaseClasses = "flex items-center px-4 py-2 text-sm font-medium rounded-lg group transition-all duration-200";
                $activeClasses = "bg-white text-primary shadow-md";
                $inactiveClasses = "text-white/70 hover:text-white ";
                $iconBaseClasses = "mr-3 text-base w-5 text-center";
                $iconActiveClasses = "text-primary";
                $iconInactiveClasses = "text-white/50 group-hover:text-white";

                // Blocage pour gestion_rapports si candidature non validée
                $isLocked = (strpos($fonc->url_fonctionnalite, 'gestion_rapports') !== false && $statut !== 'Validée');

                if ($isLocked) {
                    $html .= '<span class="' . $linkBaseClasses . ' text-white/40 cursor-not-allowed" title="Accessible après validation de la candidature">';
                    $html .= '<i class="fas fa-lock ' . $iconBaseClasses . ' text-white/40"></i>';
                    $html .= htmlspecialchars($fonc->label_fonctionnalite);
                    $html .= '</span>';
                } else {
                    $html .= '<a href="' . htmlspecialchars($fonc->url_fonctionnalite) . '" class="' . $linkBaseClasses . ' ' . ($isActive ? $activeClasses : $inactiveClasses) . '">';
                    $html .= '<i class="fas ' . htmlspecialchars($fonc->icone_fonctionnalite) . ' ' . $iconBaseClasses . ' ' . ($isActive ? $iconActiveClasses : $iconInactiveClasses) . '"></i>';
                    $html .= '<span>' . htmlspecialchars($fonc->label_fonctionnalite) . '</span>';
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
        // Extraire le paramètre page de l'URL
        $query = parse_url($url, PHP_URL_QUERY);
        $pageName = '';
        if ($query) {
            parse_str($query, $params);
            $pageName = $params['page'] ?? '';
        }

        return $currentPage === $pageName ||
            (isset($_GET['page']) && $_GET['page'] === $pageName);
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

    /**
     * Ancien afficheur de menu (compatibilité rétroactive)
     * @deprecated Utiliser afficherMenuHierarchique() à la place
     */
    public function afficherMenu($traitements, $currentMenuSlug)
    {
        // Tri des traitements par ordre_traitement
        usort($traitements, function ($a, $b) {
            return $a['ordre_traitement'] - $b['ordre_traitement'];
        });
        // Génération du menu
        $html = '';
        // Récupérer le statut de la candidature si étudiant
        $statut = null;
        if (isset($_SESSION['id_GU']) && $_SESSION['id_GU'] == 13 && isset($_SESSION['num_etu'])) { // 13 = groupe étudiant
            require_once __DIR__ . '/../app/models/CandidatureSoutenance.php';
            $statut = CandidatureSoutenance::getStatutByEtudiant($_SESSION['num_etu']);
        }
        foreach ($traitements as $traitement) {
            $isActive = ($currentMenuSlug === $traitement['lib_traitement']);
            $linkBaseClasses = "flex items-center px-4 py-3 text-sm font-medium rounded-lg group transition-all duration-200";
            // Styles inspirés de l'image
            $activeClasses = "bg-white text-primary shadow-md";
            $inactiveClasses = "text-white/80 hover:text-white ";
            $iconBaseClasses = "mr-3 text-lg w-6 text-center";
            $iconActiveClasses = "text-primary";
            $iconInactiveClasses = "text-white/60 group-hover:text-white";

            // Blocage du lien gestion_rapports si la candidature n'est pas validée
            if ($traitement['lib_traitement'] === 'gestion_rapports' && $statut !== 'Validée') {
                $html .= '<span class="' . $linkBaseClasses . ' text-white/40 cursor-not-allowed" title="Accessible après validation de la candidature">';
                $html .= '<i class="fas fa-lock ' . $iconBaseClasses . ' ' . $iconInactiveClasses . ' text-white/40"></i>';
                $html .= htmlspecialchars($traitement['label_traitement']);
                $html .= '</span>';
            } else {
                $html .= '<a href="?page=' . htmlspecialchars($traitement['lib_traitement']) . '" class="' . $linkBaseClasses . ' ' . ($isActive ? $activeClasses : $inactiveClasses) . '" >';
                $html .= '<i class="fas ' . htmlspecialchars($traitement['icone_traitement']) . ' ' . $iconBaseClasses . ' ' . ($isActive ? $iconActiveClasses : $iconInactiveClasses) . '"></i>';
                $html .= '<span>' . htmlspecialchars($traitement['label_traitement']) . '</span>';
                $html .= '</a>';
            }
        }
        return $html;
    }
}