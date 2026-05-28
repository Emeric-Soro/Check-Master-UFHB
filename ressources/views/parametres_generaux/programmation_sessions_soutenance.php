<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_specifiques');
$annees = is_array($GLOBALS['listeAnnees'] ?? null) ? $GLOBALS['listeAnnees'] : [];
$selectedYearId = (string) ($GLOBALS['selectedYearId'] ?? '');
$sessionForm = is_array($GLOBALS['sessionForm'] ?? null) ? $GLOBALS['sessionForm'] : [];
$tableRows = is_array($GLOBALS['sessionTableRows'] ?? null) ? $GLOBALS['sessionTableRows'] : [];
$messageSuccess = (string) ($GLOBALS['messageSuccess'] ?? '');
$messageErreur = (string) ($GLOBALS['messageErreur'] ?? '');

$formatYearLabel = static function ($annee): string {
    $dateDebut = (string) ($annee->date_deb ?? '');
    $dateFin = (string) ($annee->date_fin ?? '');
    if ($dateDebut !== '' && $dateFin !== '') {
        return date('Y', strtotime($dateDebut)) . '-' . date('Y', strtotime($dateFin));
    }

    $id = (string) ($annee->id_annee_acad ?? '');
    return $id !== '' ? $id : '-';
};

$yearOptions = [];
foreach ($annees as $annee) {
    $id = (string) ($annee->id_annee_acad ?? '');
    if ($id === '') {
        continue;
    }
    $yearOptions[$id] = $formatYearLabel($annee);
}

$sessionForm = array_replace([
    1 => ['date_debut' => ''],
    2 => ['date_debut' => ''],
    3 => ['date_debut' => ''],
], $sessionForm);
?>
<style>
    .cm-soutenance-sessions-grid,
    .cm-soutenance-sessions-dates {
        display: grid;
        grid-template-columns: minmax(220px, 1.2fr) repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .cm-soutenance-sessions-grid {
        align-items: end;
        margin-bottom: 0.75rem;
    }

    .cm-soutenance-sessions-dates {
        align-items: start;
    }

    .cm-soutenance-sessions-grid .cm-session-label {
        font-weight: 600;
        color: #223046;
        padding-bottom: 0.35rem;
    }

    .cm-soutenance-sessions-dates .cm-session-helper {
        color: #5d6b7a;
        font-weight: 600;
        padding-top: 0.55rem;
    }

    .cm-soutenance-sessions-dates .cm-session-block {
        display: grid;
        gap: 0.55rem;
    }

    @media (max-width: 1024px) {

        .cm-soutenance-sessions-grid,
        .cm-soutenance-sessions-dates {
            grid-template-columns: 1fr;
        }

        .cm-soutenance-sessions-grid .cm-session-label {
            padding-bottom: 0;
        }
    }
</style>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <?php if ($messageSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?>
    <?php endif; ?>
    <?php if ($messageErreur !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <?php ob_start(); ?>
        <form method="POST" class="cm-form" autocomplete="off">
            <?php cm_component('form/csrf-token'); ?>
            <input type="hidden" name="page" value="<?= htmlspecialchars($pageSlug, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="programmation_sessions_soutenance">

            <div class="cm-soutenance-sessions-grid">
                <?php cm_component('form/select', [
                    'name' => 'id_annee_acad',
                    'label' => 'Année académique',
                    'options' => $yearOptions,
                    'selected' => $selectedYearId,
                    'required' => true,
                ]); ?>
                <div class="cm-session-label">Session 1</div>
                <div class="cm-session-label">Session 2</div>
                <div class="cm-session-label">Session 3</div>
            </div>

            <div class="cm-soutenance-sessions-dates">
                <div class="cm-session-helper">Dates</div>
                <div class="cm-session-block">
                    <?php cm_component('form/input-date', [
                        'name' => 'session_1_debut',
                        'label' => 'Date',
                        'value' => (string) ($sessionForm[1]['date_debut'] ?? ''),
                    ]); ?>
                </div>
                <div class="cm-session-block">
                    <?php cm_component('form/input-date', [
                        'name' => 'session_2_debut',
                        'label' => 'Date',
                        'value' => (string) ($sessionForm[2]['date_debut'] ?? ''),
                    ]); ?>
                </div>
                <div class="cm-session-block">
                    <?php cm_component('form/input-date', [
                        'name' => 'session_3_debut',
                        'label' => 'Date',
                        'value' => (string) ($sessionForm[3]['date_debut'] ?? ''),
                    ]); ?>
                </div>
            </div>

            <div class="cm-form-hint" style="margin-top: 0.35rem;">
                Renseignez une date pour chaque session. Laissez vide pour retirer une session.
            </div>

            <?php
            cm_component('crud/form-actions', [
                'actions' => [
                    [
                        'tag' => 'button',
                        'type' => 'submit',
                        'label' => 'Afficher',
                        'icon' => 'fa-rotate-right',
                        'class' => 'cm-btn is-light is-sm',
                        'name' => 'load_programmation_sessions',
                        'value' => '1',
                    ],
                    [
                        'tag' => 'button',
                        'type' => 'submit',
                        'label' => 'Enregistrer',
                        'icon' => 'fa-save',
                        'class' => 'cm-btn is-primary is-sm',
                        'name' => 'submit_programmation_sessions',
                        'value' => '1',
                    ],
                ],
            ]);
            ?>
        </form>
        <?php
        cm_component('crud/form-pole', [
            'title' => 'Programmation approximative des sessions',
            'icon' => 'fa-calendar-days',
            'content' => (string) ob_get_clean(),
        ]);
        ?>

        <div class="cm-pole-inferieur">
            <?php cm_component('crud/data-table', [
                'id' => 'cmSessionsSoutenanceTable',
                'columns' => [
                    cm_column('annee_label', 'Année académique'),
                    cm_column('session_1', 'Session 1'),
                    cm_column('session_2', 'Session 2'),
                    cm_column('session_3', 'Session 3'),
                ],
                'rows' => $tableRows,
                'row_key' => 'annee_label',
                'selectable' => false,
                'empty_title' => 'Aucune programmation',
                'empty_message' => 'Aucune session approximative n\'est enregistrée.',
            ]); ?>
        </div>
    </div>
</section>