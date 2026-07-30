<?php
require_once __DIR__ . '/../../app/utils/permissions_helper.php';

if (!empty($membresCommissionData['forbidden'])):
?>
    <div class="cm-prd3-screen">
        <div class="cm-crud-wrapper">
            <div style="padding: var(--cm-spacing-lg); border-radius: var(--cm-border-radius-lg); background: rgba(220, 38, 38, 0.1); border: 1px solid rgba(220, 38, 38, 0.2); color: #dc2626; font-weight: 600; display: flex; align-items: center; gap: 0.75rem;">
                <i class="fas fa-exclamation-triangle" style="font-size: 1.2rem;"></i>
                <span>Cette page est réservée à l’administrateur et au président de la commission.</span>
            </div>
        </div>
    </div>
<?php else: ?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <div class="cm-crud-wrapper" style="display: grid; gap: 1.25rem;">
        
        <!-- Pôle Inférieur : Barre d'outils & Tableau de composition -->
        <div class="cm-pole-inferieur">
            <form method="post" action="?page=membres_commission&action=sauvegarder_membres">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\CheckMaster\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
                
                <!-- Barre d'outils standardisée du projet -->
                <?php cm_toolbar([
                    'screen' => 'membres_commission',
                    'id_prefix' => 'cmMembres',
                    'search_value' => $_GET['search'] ?? '',
                    'limit' => 10,
                    'allowed_limits' => [5, 10, 25, 50],
                    'show_filters' => false,
                    'can_delete' => false,
                    'can_view' => true,
                ]); ?>

                <div class="cm-table-wrapper" style="margin-top: 1rem;">
                    <table class="cm-data-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th" style="padding: 0.85rem 1rem; background: var(--cm-table-header-bg); color: var(--cm-table-header-color); font-weight: var(--cm-font-weight-semibold); font-size: 0.85rem; text-align: left;">Utilisateur</th>
                                <th class="cm-data-table__th" style="padding: 0.85rem 1rem; background: var(--cm-table-header-bg); color: var(--cm-table-header-color); font-weight: var(--cm-font-weight-semibold); font-size: 0.85rem; text-align: left;">Groupe</th>
                                <th class="cm-data-table__th is-center" style="padding: 0.85rem 1rem; background: var(--cm-table-header-bg); color: var(--cm-table-header-color); font-weight: var(--cm-font-weight-semibold); font-size: 0.85rem; text-align: center; width: 140px;">Président</th>
                                <th class="cm-data-table__th is-center" style="padding: 0.85rem 1rem; background: var(--cm-table-header-bg); color: var(--cm-table-header-color); font-weight: var(--cm-font-weight-semibold); font-size: 0.85rem; text-align: center; width: 220px; vertical-align: middle;">
                                    <div style="display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                        <input type="checkbox" id="cmMembresCheckAll" class="cm-table-check-all" aria-label="Tout sélectionner" style="accent-color: #2563eb; width: 15px; height: 15px; cursor: pointer; margin: 0;">
                                        <span>Peut voter</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (($membresCommissionData['membres'] ?? []) as $membre): ?>
                                <tr class="cm-data-table__row" style="border-bottom: 1px solid var(--cm-table-border-color); transition: background-color var(--cm-transition-fast);">
                                    <td class="cm-data-table__td" style="padding: 0.85rem 1rem; font-size: 0.88rem;">
                                        <div style="font-weight: 700; color: var(--cm-primary-dark, #12395c); font-size: 0.93rem;">
                                            <?= htmlspecialchars((string) $membre['nom_utilisateur'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div style="color: var(--cm-text-muted, #5f7890); font-size: 0.78rem; display: inline-flex; align-items: center; gap: 0.35rem; margin-top: 0.15rem;">
                                            <i class="far fa-user" style="font-size: 0.75rem;"></i>
                                            <span><?= htmlspecialchars((string) $membre['login_utilisateur'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </div>
                                    </td>
                                    <td class="cm-data-table__td" style="padding: 0.85rem 1rem; font-size: 0.88rem; color: var(--cm-text-dark, #2b3a4a); font-weight: 500;">
                                        <?= htmlspecialchars((string) $membre['lib_GU'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="cm-data-table__td is-center" style="padding: 0.85rem 1rem; font-size: 0.88rem; text-align: center;">
                                        <?php if (!empty($membre['est_president'])): ?>
                                            <span style="display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.65rem; border-radius: 4px; background: rgba(16, 185, 129, 0.12); color: #10b981; font-weight: 700; font-size: 0.78rem; border: 1px solid rgba(16, 185, 129, 0.2);">
                                                <i class="fas fa-crown"></i> Président
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--cm-text-muted, #8b9ea2); font-size: 0.85rem; font-weight: 500;">Non</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cm-data-table__td is-center" style="padding: 0.85rem 1rem; font-size: 0.88rem; text-align: center; vertical-align: middle;">
                                        <?php $presidentNonAdmin = !empty($membre['est_president']) && (int) ($_SESSION['id_GU'] ?? 0) === 14; ?>
                                        <div style="display: inline-flex; align-items: center; justify-content: center;">
                                            <?php if ($presidentNonAdmin && !empty($membre['actif_votant'])): ?>
                                                <input type="hidden" name="membres_actifs[]" value="<?= (int) $membre['id_utilisateur'] ?>">
                                            <?php endif; ?>
                                            <label style="display: inline-flex; align-items: center; gap: 0.55rem; cursor: <?= $presidentNonAdmin ? 'not-allowed' : 'pointer' ?>; font-size: 0.85rem; font-weight: 600; padding: 0.35rem 0.75rem; border-radius: 6px; background: <?= $presidentNonAdmin ? 'rgba(0,0,0,0.03)' : (!empty($membre['actif_votant']) ? 'rgba(59, 130, 246, 0.08)' : 'rgba(0,0,0,0.02)') ?>; border: 1px solid <?= $presidentNonAdmin ? 'rgba(0,0,0,0.05)' : (!empty($membre['actif_votant']) ? 'rgba(59, 130, 246, 0.15)' : 'rgba(0,0,0,0.08)') ?>; color: <?= $presidentNonAdmin ? '#8b9ea2' : (!empty($membre['actif_votant']) ? '#2563eb' : '#4b5563') ?>; transition: all var(--cm-transition-fast);">
                                                <input type="checkbox" name="membres_actifs[]"
                                                       value="<?= (int) $membre['id_utilisateur'] ?>"
                                                       <?= !empty($membre['actif_votant']) ? 'checked' : '' ?>
                                                       <?= $presidentNonAdmin ? 'disabled' : '' ?>
                                                       style="accent-color: #2563eb; width: 15px; height: 15px; margin: 0; cursor: inherit;">
                                                <span><?= $presidentNonAdmin ? 'Géré par l’admin' : 'Votant actif' ?></span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Boutons d'actions en bas -->
                <div style="margin-top: 1.25rem; display: flex; justify-content: flex-end; gap: var(--cm-spacing-sm);">
                    <button type="submit" class="cm-btn is-primary" style="display: inline-flex; align-items: center; gap: 0.55rem; padding: 0.7rem 1.4rem; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(29, 78, 216, 0.15), 0 2px 4px -1px rgba(29, 78, 216, 0.1);">
                        <i class="fas fa-save" style="font-size: 0.95rem;"></i>
                        <span>Enregistrer la composition</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Gestion du "Cocher Tout" (Checkbox d'en-tête)
    var checkAll = document.getElementById('cmMembresCheckAll');
    if (checkAll) {
        checkAll.addEventListener('change', function () {
            var checkboxes = document.querySelectorAll('table.cm-data-table tbody input[type="checkbox"]:not(:disabled)');
            checkboxes.forEach(function (cb) {
                var row = cb.closest('tr');
                // Seulement cocher si la ligne est visible (non masquée par la recherche)
                if (row && row.style.display !== 'none') {
                    cb.checked = checkAll.checked;
                }
            });
        });
    }

    // Synchronisation du select all si des checkboxes sont modifiées manuellement
    document.addEventListener('change', function (e) {
        if (e.target && e.target.matches('table.cm-data-table tbody input[type="checkbox"]')) {
            var allCheckboxes = Array.from(document.querySelectorAll('table.cm-data-table tbody input[type="checkbox"]:not(:disabled)'));
            var checkedCheckboxes = allCheckboxes.filter(function (cb) { return cb.checked; });
            if (checkAll) {
                checkAll.checked = allCheckboxes.length > 0 && checkedCheckboxes.length === allCheckboxes.length;
            }
        }
    });
});
</script>
<?php endif; ?>
