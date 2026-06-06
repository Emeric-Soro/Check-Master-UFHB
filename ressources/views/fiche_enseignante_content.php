<?php
// ─── Données depuis le contrôleur ───
$viewMode = (string) ($_GET['view'] ?? 'liste');
$listeEnseignants = is_array($GLOBALS['liste_enseignants'] ?? null) ? $GLOBALS['liste_enseignants'] : [];
$search = (string) ($GLOBALS['search_enseignant'] ?? '');
$pagination = is_array($GLOBALS['pagination'] ?? null) ? $GLOBALS['pagination'] : [];
$ficheData = $GLOBALS['fiche_data'] ?? null;
?>

<?php if ($viewMode === 'fiche' && $ficheData !== null): ?>
    <?php
    $identite = $ficheData['identite'] ?? [];
    $gradeActuel = $ficheData['grade_actuel'] ?? [];
    $historiqueGrades = $ficheData['historique_grades'] ?? [];
    $fonctions = $ficheData['fonctions'] ?? [];
    $typeEnsLib = (string) ($ficheData['type_enseignant_lib'] ?? '');
    $jurys = $ficheData['jurys'] ?? [];
    $encadrements = $ficheData['encadrements'] ?? [];
    $stats = $ficheData['stats'] ?? [];
    $compteUser = $ficheData['compte_utilisateur'] ?? null;
    $idEns = (string) ($identite['id_enseignant'] ?? '');
    $nomComplet = trim((string) ($identite['nom_enseignant'] ?? '') . ' ' . ($identite['prenom_enseignant'] ?? ''));
    $mail = (string) ($identite['mail_enseignant'] ?? '');
    $tel = (string) ($identite['tel_enseignant'] ?? '');
    $libSpecialite = (string) ($identite['lib_specialite'] ?? '');
    $gradeLib = (string) ($gradeActuel['lib_grade'] ?? '');
    $gradeDate = (string) ($gradeActuel['date_grade'] ?? '');

    // Formater les dates
    if (!function_exists('fmtDate')) {
        function fmtDate(?string $d): string {
            if (empty($d)) return '-';
            $ts = strtotime($d);
            return $ts ? date('d/m/Y', $ts) : $d;
        }
    }
    if (!function_exists('fmtDateTime')) {
        function fmtDateTime(?string $d): string {
            if (empty($d)) return '-';
            $ts = strtotime($d);
            return $ts ? date('d/m/Y H:i', $ts) : $d;
        }
    }
    ?>

    <section class="cm-prd3-crud-screen cm-prd6-admin-screen">
        <!-- Lien retour -->
        <div class="mb-4">
            <a href="?page=fiche_enseignante" class="cm-btn is-light is-sm" data-cm-ajax-link="true">
                <i class="fas fa-arrow-left mr-2"></i> Retour à la liste
            </a>
        </div>

        <!-- ─── En-tête Identité ─── -->
        <div class="cm-card cm-card--highlight mb-6">
            <div class="cm-card__body" style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
                <div style="width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <span style="color: #fff; font-size: 1.75rem; font-weight: 700; letter-spacing: 0.05em;">
                        <?= htmlspecialchars(mb_substr($identite['nom_enseignant'] ?? '?', 0, 1), ENT_QUOTES, 'UTF-8') . htmlspecialchars(mb_substr($identite['prenom_enseignant'] ?? '?', 0, 1), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <h2 style="margin: 0 0 0.25rem; font-size: 1.5rem; font-weight: 700; color: #111827;">
                        <?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem 1.5rem; color: #6b7280; font-size: 0.875rem;">
                        <span><i class="fas fa-id-card mr-1"></i> <?= htmlspecialchars($idEns, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($mail !== ''): ?>
                        <span><i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($mail, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if ($tel !== ''): ?>
                        <span><i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($tel, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if ($gradeLib !== ''): ?>
                        <span><i class="fas fa-medal mr-1"></i> <?= htmlspecialchars($gradeLib, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if ($libSpecialite !== ''): ?>
                        <span><i class="fas fa-flask mr-1"></i> <?= htmlspecialchars($libSpecialite, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Statistiques (widgets) ─── -->
        <div class="cm-grid-4 mb-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem;">
            <?php cm_component('dashboard/stat-widget', [
                'value' => (string) ($stats['nb_soutenances'] ?? '0'),
                'label' => 'Soutenances',
                'icon' => 'fa-gavel',
                'color' => 'primary',
                'ajax' => false,
            ]); ?>
            <?php cm_component('dashboard/stat-widget', [
                'value' => (string) ($stats['nb_presidences'] ?? '0'),
                'label' => 'Presidences',
                'icon' => 'fa-user-tie',
                'color' => 'success',
                'ajax' => false,
            ]); ?>
            <?php cm_component('dashboard/stat-widget', [
                'value' => (string) ($stats['nb_encadrements'] ?? ($stats['nb_encadrements_rapports'] ?? '0')),
                'label' => 'Encadrements',
                'icon' => 'fa-chalkboard',
                'color' => 'info',
                'ajax' => false,
            ]); ?>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format((float) ($stats['note_moyenne'] ?? 0), 2),
                'label' => 'Note moyenne',
                'icon' => 'fa-star',
                'color' => 'warning',
                'ajax' => false,
            ]); ?>
        </div>

        <!-- ─── Grade actuel + Type enseignant ─── -->
        <div class="cm-grid-2 mb-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
            <div class="cm-card">
                <div class="cm-card__header">
                    <h3 class="cm-card__title"><i class="fas fa-medal mr-2"></i>Grade actuel</h3>
                </div>
                <div class="cm-card__body">
                    <?php if ($gradeLib !== ''): ?>
                    <p style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #111827;">
                        <?= htmlspecialchars($gradeLib, ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($gradeDate !== ''): ?>
                        <span style="font-weight: 400; font-size: 0.875rem; color: #6b7280;">
                            (depuis le <?= htmlspecialchars(fmtDate($gradeDate), ENT_QUOTES, 'UTF-8') ?>)
                        </span>
                        <?php endif; ?>
                    </p>
                    <?php else: ?>
                    <p style="color: #9ca3af; margin: 0;">Aucun grade renseigne</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cm-card">
                <div class="cm-card__header">
                    <h3 class="cm-card__title"><i class="fas fa-tag mr-2"></i>Type enseignant</h3>
                </div>
                <div class="cm-card__body">
                    <?php if ($typeEnsLib !== ''): ?>
                    <?php cm_component('ui/badge', ['text' => $typeEnsLib, 'type' => 'info']); ?>
                    <?php else: ?>
                    <p style="color: #9ca3af; margin: 0;">Non defini</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ─── Historique Grades ─── -->
        <?php if (!empty($historiqueGrades)): ?>
        <div class="cm-card mb-6">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-history mr-2"></i>Historique des grades</h3>
            </div>
            <div class="cm-card__body">
                <?php
                $gradesColumns = [
                    ['key' => 'lib_grade', 'label' => 'Grade'],
                    ['key' => 'date_grade', 'label' => 'Date d\'obtention', 'align' => 'center'],
                ];
                $gradesRows = [];
                foreach ($historiqueGrades as $hg) {
                    $gradesRows[] = [
                        'lib_grade' => (string) ($hg['lib_grade'] ?? ''),
                        'date_grade' => fmtDate($hg['date_grade'] ?? ''),
                    ];
                }
                cm_component('crud/data-table', [
                    'id' => 'historiqueGradesTable',
                    'columns' => $gradesColumns,
                    'rows' => $gradesRows,
                    'empty_title' => 'Aucun historique',
                    'empty_message' => 'Aucun grade historique.',
                ]);
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ─── Fonctions ─── -->
        <?php if (!empty($fonctions)): ?>
        <div class="cm-card mb-6">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-briefcase mr-2"></i>Fonctions occupees</h3>
            </div>
            <div class="cm-card__body">
                <?php
                $foncColumns = [
                    ['key' => 'lib_fonction', 'label' => 'Fonction'],
                    ['key' => 'date_occupation', 'label' => 'Date d\'occupation', 'align' => 'center'],
                ];
                $foncRows = [];
                foreach ($fonctions as $f) {
                    $foncRows[] = [
                        'lib_fonction' => (string) ($f['lib_fonction'] ?? ''),
                        'date_occupation' => fmtDate($f['date_occupation'] ?? ''),
                    ];
                }
                cm_component('crud/data-table', [
                    'id' => 'fonctionsTable',
                    'columns' => $foncColumns,
                    'rows' => $foncRows,
                    'empty_title' => 'Aucune fonction',
                    'empty_message' => 'Aucune fonction renseignee.',
                ]);
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ─── Jurys (tableau cliquable) ─── -->
        <?php if (!empty($jurys)): ?>
        <div class="cm-card mb-6">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-gavel mr-2"></i>Participations aux jurys</h3>
            </div>
            <div class="cm-card__body">
                <?php
                $juryColumns = [
                    ['key' => 'date_soutenance', 'label' => 'Date', 'align' => 'center'],
                    ['key' => 'num_etud', 'label' => 'Etudiant'],
                    ['key' => 'theme_soutenance', 'label' => 'Theme'],
                    ['key' => 'lib_role', 'label' => 'Role', 'align' => 'center'],
                    ['key' => 'note_attribuee', 'label' => 'Note', 'align' => 'center'],
                ];
                $juryRows = [];
                foreach ($jurys as $j) {
                    $juryRows[] = [
                        'num_soutenance' => (string) ($j['num_soutenance'] ?? ''),
                        'date_soutenance' => fmtDate($j['date_soutenance'] ?? ''),
                        'num_etud' => (string) ($j['num_etud'] ?? ''),
                        'theme_soutenance' => (string) ($j['theme_soutenance'] ?? ''),
                        'lib_role' => (string) ($j['lib_role'] ?? ''),
                        'note_attribuee' => $j['note_attribuee'] !== null && $j['note_attribuee'] !== '' ? (string) $j['note_attribuee'] : '—',
                    ];
                }
                cm_component('crud/data-table', [
                    'id' => 'jurysTable',
                    'columns' => $juryColumns,
                    'rows' => $juryRows,
                    'clickable' => true,
                    'row_link' => '?page=fiche_soutenance&id={num_soutenance}',
                    'row_key' => 'num_soutenance',
                    'empty_title' => 'Aucune participation',
                    'empty_message' => 'Cet enseignant n\'a participe a aucun jury.',
                ]);
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ─── Encadrements ─── -->
        <?php if (!empty($encadrements)): ?>
        <div class="cm-card mb-6">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-chalkboard mr-2"></i>Encadrements</h3>
            </div>
            <div class="cm-card__body">
                <?php
                $encColumns = [
                    ['key' => 'date_redaction_rapport', 'label' => 'Date', 'align' => 'center'],
                    ['key' => 'nom_etudiant', 'label' => 'Etudiant'],
                    ['key' => 'theme_rapport', 'label' => 'Theme'],
                    ['key' => 'role', 'label' => 'Role', 'align' => 'center'],
                    ['key' => 'statut_rapport', 'label' => 'Statut', 'align' => 'center'],
                ];
                $encRows = [];
                foreach ($encadrements as $e) {
                    $statutBadge = 'info';
                    $statut = (string) ($e['statut_rapport'] ?? '');
                    if ($statut === 'valider') {
                        $statutBadge = 'success';
                    } elseif ($statut === 'rejeter') {
                        $statutBadge = 'danger';
                    } elseif ($statut === 'en_attente') {
                        $statutBadge = 'warning';
                    }
                    $roleBadge = 'light';
                    $role = (string) ($e['role'] ?? '');
                    if ($role === 'directeur') {
                        $roleBadge = 'primary';
                    } elseif ($role === 'encadrant') {
                        $roleBadge = 'info';
                    }
                    $encRows[] = [
                        'id_rapport' => (string) ($e['id_rapport'] ?? ''),
                        'date_redaction_rapport' => fmtDateTime($e['date_redaction_rapport'] ?? ''),
                        'nom_etudiant' => (string) ($e['nom_etudiant'] ?? ''),
                        'theme_rapport' => (string) ($e['theme_rapport'] ?? ''),
                        'role' => $role,
                        'statut_rapport' => $statut,
                    ];
                }
                cm_component('crud/data-table', [
                    'id' => 'encadrementsTable',
                    'columns' => $encColumns,
                    'rows' => $encRows,
                    'clickable' => true,
                    'row_link' => '?page=documents&action=view&id={id_rapport}',
                    'row_key' => 'id_rapport',
                    'empty_title' => 'Aucun encadrement',
                    'empty_message' => 'Cet enseignant n\'a encadre aucun rapport.',
                ]);
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ─── Compte utilisateur ─── -->
        <?php if ($compteUser !== null): ?>
        <div class="cm-card mb-6">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-user-circle mr-2"></i>Compte utilisateur</h3>
            </div>
            <div class="cm-card__body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Login</span>
                        <p style="margin: 0.25rem 0 0; font-weight: 600;"><?= htmlspecialchars((string) ($compteUser['login_utilisateur'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Statut</span>
                        <p style="margin: 0.25rem 0 0;">
                            <?php
                            $statutUser = (string) ($compteUser['statut_utilisateur'] ?? 'Inactif');
                            $badgeType = $statutUser === 'Actif' ? 'success' : 'danger';
                            cm_component('ui/badge', ['text' => $statutUser, 'type' => $badgeType]);
                            ?>
                        </p>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">ID Utilisateur</span>
                        <p style="margin: 0.25rem 0 0; font-weight: 600;">#<?= htmlspecialchars((string) ($compteUser['id_utilisateur'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <span style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em;">Groupe</span>
                        <p style="margin: 0.25rem 0 0; font-weight: 600;">GU #<?= htmlspecialchars((string) ($compteUser['id_GU'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </div>
        </div>
                <?php endif; ?>
    </section>

<?php else: ?>
    <!-- ═══════════════ LISTE DES ENSEIGNANTS ═══════════════ -->
    <section class="cm-prd3-crud-screen cm-prd6-admin-screen">
        <div class="cm-page-header mb-4">
            <h2 class="cm-page-title"><i class="fas fa-chalkboard-user mr-2"></i>Fiche Enseignante</h2>
            <p class="cm-page-subtitle">Consultez la fiche detaillee de chaque enseignant.</p>
        </div>

        <!-- Recherche -->
        <form method="GET" action="" class="mb-4" data-cm-ajax-form="true" id="ficheEnseignantSearch">
            <input type="hidden" name="page" value="fiche_enseignante">
            <div style="display: flex; gap: 0.5rem; max-width: 400px;">
                <input type="text" name="search" class="cm-form-control" style="flex: 1;"
                       placeholder="Rechercher (nom, prenom, mail, grade...)"
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="cm-btn is-primary is-sm">
                    <i class="fas fa-search"></i>
                </button>
                <?php if ($search !== ''): ?>
                <a href="?page=fiche_enseignante" class="cm-btn is-light is-sm" data-cm-ajax-link="true">
                    <i class="fas fa-times"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Tableau des enseignants -->
        <?php
        $ensColumns = [
            ['key' => 'matricule_enseignant', 'label' => 'Matricule'],
            ['key' => 'nom_enseignant', 'label' => 'Nom'],
            ['key' => 'prenom_enseignant', 'label' => 'Prenom'],
            ['key' => 'lib_grade', 'label' => 'Grade', 'align' => 'center'],
            ['key' => 'lib_specialite', 'label' => 'Specialite'],
            ['key' => 'mail_enseignant', 'label' => 'Email'],
        ];
        $ensRows = [];
        foreach ($listeEnseignants as $ens) {
            $ensRows[] = [
                'matricule_enseignant' => (string) ($ens['matricule_enseignant'] ?? $ens['id_enseignant'] ?? ''),
                'id_enseignant' => (string) ($ens['id_enseignant'] ?? ''),
                'nom_enseignant' => (string) ($ens['nom_enseignant'] ?? ''),
                'prenom_enseignant' => (string) ($ens['prenom_enseignant'] ?? ''),
                'lib_grade' => (string) ($ens['lib_grade'] ?? '-'),
                'lib_specialite' => (string) ($ens['lib_specialite'] ?? '-'),
                'mail_enseignant' => (string) ($ens['mail_enseignant'] ?? '-'),
            ];
        }

        cm_component('crud/data-table', [
            'id' => 'listeEnseignantsTable',
            'columns' => $ensColumns,
            'rows' => $ensRows,
            'clickable' => true,
            'row_link' => '?page=fiche_enseignante&view=fiche&id={id_enseignant}',
            'row_key' => 'id_enseignant',
            'empty_title' => 'Aucun enseignant',
            'empty_message' => 'Aucun enseignant trouve.',
        ]);
        ?>

        <!-- Pagination -->
        <?php if (!empty($pagination) && $pagination['total'] > $pagination['per_page']): ?>
        <div class="mt-4">
            <?php
            $baseUrl = '?page=fiche_enseignante' . ($search !== '' ? '&search=' . urlencode($search) : '');
            cm_component('crud/pagination', [
                'pagination' => $pagination,
                'base_url' => $baseUrl,
                'param_name' => 'p',
            ]);
            ?>
        </div>
        <?php endif; ?>
    </section>

    <!-- Script AJAX pour la recherche -->
    <script>
    (function() {
        var form = document.getElementById('ficheEnseignantSearch');
        if (form && window.CM && window.CM.ajax && typeof window.CM.ajax.submit === 'function') {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var url = form.getAttribute('action') || window.location.pathname;
                var params = new URLSearchParams(new FormData(form));
                window.CM.ajax.load(url + '?' + params.toString());
            });
        }
    })();
    </script>
<?php endif; ?>
