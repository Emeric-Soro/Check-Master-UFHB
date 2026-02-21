<div class="cm-crud-wrapper">
    <!-- Pôle supérieur - Formulaire -->
    <section class="cm-pole-superieur">
        <div class="cm-pole-header">
            <h2 class="cm-pole-title">
                <i class="fas fa-<?= $form_mode === 'edition' ? 'edit' : 'plus-circle' ?>"></i>
                <?= htmlspecialchars($form_title ?? 'Formulaire') ?>
            </h2>
        </div>
        <div class="cm-pole-content">
            <?= $form_content ?? '' ?>
        </div>
    </section>
    
    <!-- Barre intermédiaire - Toolbar -->
    <section class="cm-barre-intermediaire">
        <?php 
        // Include toolbar if config provided
        if (!empty($toolbar_config)) {
            extract($toolbar_config);
            include dirname(__DIR__) . '/table/toolbar.php';
        }
        ?>
    </section>
    
    <!-- Pôle inférieur - Tableau -->
    <section class="cm-pole-inferieur">
        <div class="cm-pole-content cm-table-container">
            <?= $table_content ?? '' ?>
        </div>
    </section>
</div>

<?php if (!empty($panel_content)): ?>
<!-- Panneau latéral -->
<?= $panel_content ?>
<?php endif; ?>
