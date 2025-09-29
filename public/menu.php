<?php




require_once __DIR__ . '/../app/utils/StyleManager.php';
class MenuView {
    public function afficherMenu($traitements, $currentMenuSlug) {
        // Tri des traitements par ordre_traitement
        usort($traitements, function($a, $b) {
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
            $linkBaseClasses = "flex items-center px-2 py-3 text-sm font-medium rounded-md group";
            $activeClasses = "text-white bg-primary-500";
            $inactiveClasses = "text-gray-700 hover:text-gray-900 hover:bg-gray-100";
            $iconBaseClasses = "mr-3";
            $iconActiveClasses = "text-white";
            $iconInactiveClasses = "text-gray-400 group-hover:text-gray-500";

            // Blocage du lien gestion_rapports si la candidature n'est pas validée
            if ($traitement['lib_traitement'] === 'gestion_rapports' && $statut !== 'Validée') {
                $html .= '<span class="' . $linkBaseClasses . ' text-gray-400 cursor-not-allowed" style="pointer-events:none;">';
                $html .= '<i class="fas fa-lock ' . $iconBaseClasses . ' ' . $iconInactiveClasses . '"></i>';
                $html .= htmlspecialchars($traitement['label_traitement']);
                $html .= '</span>';
            } else {
                $html .= '<a href="?page=' . htmlspecialchars($traitement['lib_traitement']) . '" class="' . $linkBaseClasses . ' ' . ($isActive ? $activeClasses : $inactiveClasses) . '" >';
                $html .= '<i class="fas ' . htmlspecialchars($traitement['icone_traitement']) . ' ' . $iconBaseClasses . ' ' . ($isActive ? $iconActiveClasses : $iconInactiveClasses) . '"></i>';
                $html .= htmlspecialchars($traitement['label_traitement']);
                $html .= '</a>';
            }
        }
        return $html;
    }
}