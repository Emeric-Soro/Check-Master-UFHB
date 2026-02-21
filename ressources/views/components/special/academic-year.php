<?php
$yearData = $year ?? [];
$libelle = $yearData['libelle'] ?? '';
$dateDeb = $yearData['date_deb'] ?? '';
$dateFin = $yearData['date_fin'] ?? '';
$isActive = ($yearData['est_active'] ?? false) || ($yearData['active'] ?? false);
$format = $format ?? 'badge';
$showDates = $show_dates ?? false;
$showStatus = $show_status ?? true;
?>
<?php if ($format === 'card'): ?>
<div class="cm-academic-year card is-small">
    <div class="card-content py-2 px-3">
        <div class="is-flex is-align-items-center is-justify-content-space-between">
            <div class="is-flex is-align-items-center">
                <span class="icon has-text-primary mr-2">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                <span class="has-text-weight-medium"><?= htmlspecialchars($libelle) ?></span>
            </div>
            <?php if ($showStatus && $isActive): ?>
            <span class="tag is-success is-small">Active</span>
            <?php endif; ?>
        </div>
        <?php if ($showDates && !empty($dateDeb)): ?>
        <p class="is-size-7 has-text-grey mt-1">
            <?= date('d/m/Y', strtotime($dateDeb)) ?> - <?= !empty($dateFin) ? date('d/m/Y', strtotime($dateFin)) : '...' ?>
        </p>
        <?php endif; ?>
    </div>
</div>

<?php elseif ($format === 'inline'): ?>
<span class="cm-academic-year-inline">
    <span class="icon has-text-primary"><i class="fas fa-calendar-alt"></i></span>
    <span><?= htmlspecialchars($libelle) ?></span>
    <?php if ($showStatus && $isActive): ?>
    <span class="tag is-success is-rounded is-small ml-1">Active</span>
    <?php endif; ?>
</span>

<?php else: /* badge format */ ?>
<span class="tag is-primary is-light cm-academic-year-badge">
    <span class="icon"><i class="fas fa-calendar-alt"></i></span>
    <span><?= htmlspecialchars($libelle) ?></span>
    <?php if ($showStatus && $isActive): ?>
    <span class="tag is-success is-small ml-1">Active</span>
    <?php endif; ?>
</span>
<?php endif; ?>
