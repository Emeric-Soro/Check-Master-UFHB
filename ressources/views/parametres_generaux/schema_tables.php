<?php
$schemaTables = is_array($GLOBALS['schemaTables'] ?? null) ? $GLOBALS['schemaTables'] : [];
$selectedTable = (string) ($GLOBALS['schemaSelectedTable'] ?? '');
$selectedColumns = is_array($GLOBALS['schemaSelectedColumns'] ?? null) ? $GLOBALS['schemaSelectedColumns'] : [];
$messageErreur = (string) ($GLOBALS['messageErreur'] ?? '');

$tableOptions = [];
$tableRows = [];
foreach ($schemaTables as $table) {
    $tableName = (string) ($table['table_name'] ?? '');
    if ($tableName === '') {
        continue;
    }

    $tableOptions[$tableName] = $tableName . ' (' . (int) ($table['columns_count'] ?? 0) . ' col.)';
    $tableRows[] = [
        'table_name' => $tableName,
        'columns_count' => (int) ($table['columns_count'] ?? 0),
        'managed' => [
            'label' => !empty($table['is_managed']) ? 'Oui' : 'Non',
            'type' => !empty($table['is_managed']) ? 'success' : 'danger',
        ],
        'covered_count' => (int) ($table['covered_count'] ?? 0),
        'missing_count' => (int) ($table['missing_count'] ?? 0),
        'missing_columns_preview' => (string) ($table['missing_columns_preview'] ?? ''),
        'managed_action' => (string) ($table['managed_action'] ?? '-'),
    ];
}

$columnRows = [];
foreach ($selectedColumns as $column) {
    $fieldName = (string) ($column['Field'] ?? '');
    if ($fieldName === '') {
        continue;
    }

    $columnRows[] = [
        'field' => $fieldName,
        'type' => (string) ($column['Type'] ?? ''),
        'null' => (string) ($column['Null'] ?? ''),
        'key' => (string) ($column['Key'] ?? ''),
        'default' => (string) (($column['Default'] ?? '') === null ? 'NULL' : $column['Default']),
        'extra' => (string) ($column['Extra'] ?? ''),
        'covered' => [
            'label' => !empty($column['is_covered']) ? 'Présente' : 'Manquante',
            'type' => !empty($column['is_covered']) ? 'success' : 'warning',
        ],
    ];
}
?>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <?php if ($messageErreur !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <?php ob_start(); ?>
        <form method="GET" data-cm-ajax-form="true">
            <input type="hidden" name="page" value="parametres_specifiques">
            <input type="hidden" name="action" value="schema_tables">
            <div class="cm-grid-2">
                <?php cm_component('form/select', [
                    'name' => 'table',
                    'label' => 'Table à inspecter',
                    'options' => array_merge(['' => '-- Sélectionner --'], $tableOptions),
                    'selected' => $selectedTable,
                ]); ?>
                <?php cm_component('form/input-text', [
                    'name' => 'table_info',
                    'label' => 'Table sélectionnée',
                    'value' => $selectedTable,
                    'attrs' => ['readonly' => 'readonly'],
                ]); ?>
            </div>
            <?php
            cm_component('crud/form-actions', [
                'actions' => [
                    [
                        'tag' => 'a',
                        'href' => '?page=parametres_specifiques&action=schema_tables',
                        'label' => 'Réinitialiser',
                        'icon' => 'fa-rotate-left',
                        'class' => 'cm-btn is-light',
                    ],
                    [
                        'tag' => 'button',
                        'type' => 'submit',
                        'label' => 'Afficher',
                        'icon' => 'fa-search',
                        'class' => 'cm-btn is-primary',
                    ],
                ],
            ]);
            ?>
        </form>
        <?php
        cm_component('crud/form-pole', [
            'title' => 'Couverture paramètres vs base',
            'icon' => 'fa-database',
            'content' => (string) ob_get_clean(),
        ]);
        ?>

        <div class="cm-pole-inferieur">
            <?php cm_component('crud/data-table', [
                'id' => 'cmSchemaTablesCoverage',
                'columns' => [
                    cm_column('table_name', 'Table'),
                    cm_column('columns_count', 'Nb colonnes', ['align' => 'center']),
                    cm_column('managed', 'Écran existant', ['type' => 'badge', 'align' => 'center']),
                    cm_column('covered_count', 'Colonnes couvertes', ['align' => 'center']),
                    cm_column('missing_count', 'Colonnes manquantes', ['align' => 'center']),
                    cm_column('missing_columns_preview', 'Aperçu colonnes manquantes'),
                    cm_column('managed_action', 'Action paramètres'),
                ],
                'rows' => $tableRows,
                'row_key' => 'table_name',
                'selectable' => false,
                'empty_title' => 'Aucune table',
                'empty_message' => 'Impossible de charger les tables de la base.',
            ]); ?>

            <?php cm_component('crud/data-table', [
                'id' => 'cmSchemaColumnsDetail',
                'columns' => [
                    cm_column('field', 'Colonne'),
                    cm_column('type', 'Type SQL'),
                    cm_column('null', 'Null'),
                    cm_column('key', 'Clé'),
                    cm_column('default', 'Défaut'),
                    cm_column('extra', 'Extra'),
                    cm_column('covered', 'Dans l\'écran paramètre', ['type' => 'badge', 'align' => 'center']),
                ],
                'rows' => $columnRows,
                'row_key' => 'field',
                'selectable' => false,
                'empty_title' => 'Aucune colonne',
                'empty_message' => 'Sélectionnez une table pour voir ses colonnes.',
            ]); ?>
        </div>
    </div>
</section>
