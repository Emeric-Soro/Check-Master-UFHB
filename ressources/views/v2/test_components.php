<?php
$sampleRows = [
    ['CI0111272399', 'Agnaramon', 'Boris Carnot', 'Masculin'],
    ['CI0111272417', 'Ahouana', 'Akichi Roche Wilfried', 'Masculin'],
];

ob_start();
?>
<?php cm_component('layout/page-header', [
    'title' => 'Catalogue composants',
    'subtitle' => 'Page de verification rapide des composants UI.',
    'annee' => date('Y') . '-' . (date('Y') + 1),
    'icon' => 'fa-puzzle-piece',
]); ?>

<div class="cm-grid-2 cm-mb-lg">
    <?php cm_component('dashboard/stat-widget', ['value' => '49', 'label' => 'Etudiants', 'icon' => 'fa-users', 'color' => 'info']); ?>
    <?php cm_component('dashboard/stat-widget', ['value' => '12', 'label' => 'Paiements', 'icon' => 'fa-credit-card', 'color' => 'success']); ?>
</div>

<div class="cm-grid-2">
    <div>
        <?php cm_component('form/input-text', ['name' => 'nom', 'id' => 'nom', 'label' => 'Nom', 'required' => true]); ?>
        <?php cm_component('form/input-email', ['name' => 'email', 'id' => 'email', 'label' => 'Email']); ?>
        <?php cm_component('form/select', ['name' => 'genre', 'id' => 'genre', 'label' => 'Genre', 'options' => ['M' => 'Masculin', 'F' => 'Feminin']]); ?>
        <?php cm_component('form/textarea', ['name' => 'message', 'id' => 'message', 'label' => 'Message']); ?>
    </div>
    <div>
        <?php cm_component('ui/alert-box', ['type' => 'info', 'message' => 'Composants restaures.']); ?>
        <?php cm_component('crud/data-table', [
            'headers' => ['N° Etud.', 'Nom', 'Prenom', 'Genre'],
            'rows' => $sampleRows,
        ]); ?>
        <?php cm_component('crud/pagination', [
            'pagination' => [
                'offset' => 0,
                'per_page' => 2,
                'total' => 49,
                'current' => 1,
                'last' => 25,
                'has_prev' => false,
                'has_next' => true,
                'pages' => [1, 2, 3, -1, 25],
            ],
            'base_url' => '?page=v2_test',
            'param_name' => 'p',
        ]); ?>
    </div>
</div>
<?php
$content = (string) ob_get_clean();

cm_component('layout/app-shell', [
    'page_title' => 'Test Components',
    'current_page' => 'v2_test',
    'content' => $content,
    'include_chart' => false,
]);
