<?php
$backups = is_array($backups ?? null) ? $backups : (is_array($GLOBALS['backups'] ?? null) ? $GLOBALS['backups'] : []);


$rows = [];
foreach ($backups as $backup) {
    $type = (string) ($backup['type'] ?? 'Manuelle');
    $rows[] = [
        'filename' => (string) ($backup['filename'] ?? ''),
        'size' => (string) ($backup['size'] ?? '-'),
        'created_at' => (string) ($backup['created_at'] ?? '-'),
        'type' => ['label' => $type, 'type' => strcasecmp($type, 'Automatique') === 0 ? 'info' : 'warning'],
    ];
}
?>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <?php if (isset($_GET['success'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => 'Sauvegarde creee avec succes.']); ?>
    <?php endif; ?>
    <?php if (isset($_GET['restored'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => 'Restauration effectuee avec succes.']); ?>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => 'Sauvegarde supprimee avec succes.']); ?>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => 'Erreur operation sauvegarde/restauration: ' . (string) $_GET['error']]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <?php cm_toolbar([
            'screen' => 'sauvegarde_restauration',
            'id_prefix' => 'backupTop',
            'search_value' => $_GET['search'] ?? '',
            'limit' => 10,
            'can_delete' => canDelete(),
            'can_view' => canView(),
        ]); ?>
        <?php if (canCreate()): ?>
        <form method="POST" action="?page=sauvegarde_restauration&action=create" data-cm-ajax-form="true">
            <?php cm_component('form/csrf-token'); ?>
            <div class="cm-grid-2">
                <?php cm_component('form/input-text', [
                    'name' => 'backup_name',
                    'label' => 'Nom sauvegarde (optionnel)',
                    'placeholder' => 'Ex: avant_migration',
                ]); ?>
            </div>
            <?php cm_component('crud/form-actions', [
                'actions' => [[
                    'tag' => 'button',
                    'type' => 'submit',
                    'label' => 'Creer une sauvegarde',
                    'icon' => 'fa-database',
                    'class' => 'cm-btn is-success',
                ]],
            ]); ?>
        </form>
        <?php else: ?>
            <?php cm_component('ui/alert-box', ['type' => 'info', 'message' => 'Mode lecture: seules les actions de telechargement sont autorisees.']); ?>
        <?php endif; ?>
        <?php
        cm_component('crud/form-pole', [
            'title' => '',
            'icon' => 'fa-database',
            'content' => (string) ob_get_clean(),
        ]);
        ?>

        <?php cm_toolbar([
            'screen' => 'sauvegarde_restauration',
            'id_prefix' => 'backup',
            'search_value' => $_GET['search'] ?? '',
            'limit' => 10,
            'can_delete' => canDelete(),
            'can_view' => canView(),
        ]); ?>

        <div class="cm-pole-inferieur">
            <?php
            $actions = [];
            $actions[] = [
                'tag' => 'button',
                'type' => 'button',
                'label' => 'Telecharger',
                'icon' => 'fa-download',
                'class' => 'cm-btn-action is-info js-backup-download',
            ];
            if (canEdit()) {
                $actions[] = [
                    'tag' => 'button',
                    'type' => 'button',
                    'label' => 'Restaurer',
                    'icon' => 'fa-undo-alt',
                    'class' => 'cm-btn-action is-edit js-backup-restore',
                ];
                $actions[] = [
                    'tag' => 'button',
                    'type' => 'button',
                    'label' => 'Supprimer',
                    'icon' => 'fa-trash',
                    'class' => 'cm-btn-action is-delete js-backup-delete',
                ];
            }

            cm_component('crud/data-table', [
                'id' => 'cmBackupTable',
                'columns' => [
                    cm_column('filename', 'Nom fichier'),
                    cm_column('size', 'Taille'),
                    cm_column('created_at', 'Date creation'),
                    cm_column('type', 'Type', ['type' => 'badge', 'align' => 'center']),
                ],
                'rows' => $rows,
                'row_key' => 'filename',
                'selectable' => false,
                'actions' => $actions,
                'empty_title' => 'Aucune sauvegarde',
                'empty_message' => 'Aucun fichier SQL detecte.',
            ]);
            ?>
        </div>
    </div>
</section>

<?php if (canEdit()): ?>
<form id="cmBackupDeleteForm" method="POST" action="?page=sauvegarde_restauration&action=delete" class="cm-hidden" data-cm-ajax-form="true">
    <?php cm_component('form/csrf-token'); ?>
    <input type="hidden" name="filename" id="cmBackupDeleteFilename" value="">
</form>
<form id="cmBackupRestoreForm" method="POST" action="?page=sauvegarde_restauration&action=restore" class="cm-hidden" data-cm-ajax-form="true">
    <?php cm_component('form/csrf-token'); ?>
    <input type="hidden" name="filename" id="cmBackupRestoreFilename" value="">
</form>
<?php endif; ?>

<script>
(function () {
    const table = document.getElementById('cmBackupTable');
    if (!table) {
        return;
    }

    const isAdmin = <?= canEdit() ? 'true' : 'false' ?>;
    const deleteForm = document.getElementById('cmBackupDeleteForm');
    const restoreForm = document.getElementById('cmBackupRestoreForm');
    const deleteInput = document.getElementById('cmBackupDeleteFilename');
    const restoreInput = document.getElementById('cmBackupRestoreFilename');

    table.addEventListener('click', async function (event) {
        const target = event.target;
        const downloadBtn = target.closest('.js-backup-download');
        const restoreBtn = target.closest('.js-backup-restore');
        const deleteBtn = target.closest('.js-backup-delete');

        if (downloadBtn) {
            const filename = downloadBtn.getAttribute('data-row-id') || '';
            if (filename !== '') {
                window.location.href = '?page=sauvegarde_restauration&action=download&filename=' + encodeURIComponent(filename);
            }
            return;
        }

        if (!isAdmin) {
            return;
        }

        if (restoreBtn) {
            const filename = restoreBtn.getAttribute('data-row-id') || '';
            if (filename !== '' && restoreForm && restoreInput) {
                const restoreConfirmed = await window.CM.confirm({
                    title: 'Restauration',
                    message: 'Restaurer la base depuis ce fichier ?',
                    type: 'warning',
                    confirmText: 'Restaurer',
                });
                if (!restoreConfirmed) {
                    return;
                }
                restoreInput.value = filename;
                if (typeof restoreForm.requestSubmit === 'function') {
                    restoreForm.requestSubmit();
                } else {
                    restoreForm.submit();
                }
            }
            return;
        }

        if (deleteBtn) {
            const filename = deleteBtn.getAttribute('data-row-id') || '';
            if (filename !== '' && deleteForm && deleteInput) {
                const deleteConfirmed = await window.CM.confirm({
                    title: 'Suppression',
                    message: 'Supprimer definitivement ce fichier de sauvegarde ?',
                    type: 'danger',
                    confirmText: 'Supprimer',
                });
                if (!deleteConfirmed) {
                    return;
                }
                deleteInput.value = filename;
                if (typeof deleteForm.requestSubmit === 'function') {
                    deleteForm.requestSubmit();
                } else {
                    deleteForm.submit();
                }
            }
        }
    });
})();
</script>
