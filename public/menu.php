<?php

class MenuView
{
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
            $inactiveClasses = "text-white/80 hover:text-white hover:bg-white/10";
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