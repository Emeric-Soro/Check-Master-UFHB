<?php
/**
 * P2.10 — Portfolio Enseignant
 * Alias vers la Fiche Enseignante (P1.2)
 *
 * Le portfolio est un alias de la fiche enseignante.
 * On réutilise le même contenu que l'onglet Fiche enseignante.
 * @see ressources/views/fiche_enseignante_content.php
 */

// Forcer le mode liste pour le portfolio
$portfolioOriginalView = $_GET['view'] ?? 'liste';
$_GET['view'] = $portfolioOriginalView;

// Inclure le contrôleur si pas déjà fait (depuis le hub)
if (!isset($ensCtrl)) {
    if (!class_exists('FicheEnseignantController')) {
        require_once __DIR__ . '/../../app/controllers/FicheEnseignantController.php';
    }
    $ensCtrl = new FicheEnseignantController();
    if ((string) ($_GET['view'] ?? 'liste') === 'fiche' && isset($_GET['id']) && $_GET['id'] !== '') {
        $ensCtrl->fiche((string) $_GET['id']);
    } else {
        $ensCtrl->index();
    }
}

// Petit indicateur visuel pour signaler que Portfolio = Fiche enseignante
?>
<div class="cm-alert cm-alert--info cm-mb-sm" style="border-left: 3px solid #3b82f6; padding: 0.5rem 1rem; background: #eff6ff; border-radius: 4px; display: flex; align-items: center; gap: 0.5rem;">
    <i class="fas fa-info-circle" style="color: #3b82f6;"></i>
    <span style="font-size: 0.875rem; color: #1e40af;">
        Le portfolio correspond à la fiche détaillée de l'enseignant.
        Vous pouvez aussi utiliser l'onglet <strong>« Fiche enseignante »</strong> pour y accéder directement.
    </span>
    <a href="?page=parametres_generaux&action=enseignant_gestion&tab=fiche_enseignante&view=<?= htmlspecialchars(urlencode($portfolioOriginalView), ENT_QUOTES, 'UTF-8') ?>"
       class="cm-btn is-ghost is-xs" style="margin-left: auto; white-space: nowrap;">
        Aller à la fiche <i class="fas fa-arrow-right ml-1"></i>
    </a>
</div>
<?php
// Afficher le contenu de la fiche enseignante
include __DIR__ . DIRECTORY_SEPARATOR . 'fiche_enseignante_content.php';
