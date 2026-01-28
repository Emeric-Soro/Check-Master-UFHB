<?php
/**
 * CheckMaster Premium - Page de Démonstration des Composants
 * 
 * Cette page montre tous les composants disponibles dans le Design System.
 * Elle sert de documentation visuelle et de référence pour les développeurs.
 */

// Les composants sont déjà chargés via layout_premium.php
?>

<div class="space-y-8">
    
    <!-- En-tête de page -->
    <?php echo renderPageHeader(
        'Design System Premium',
        renderButton('Documentation', 'outline', true, 'onclick="alert(\'Voir AGENTS.md\')"', '', 'fa-book'),
        'Bibliothèque de composants CheckMaster inspirée de Shadcn/UI'
    ); ?>

    <!-- Section: Stats Cards -->
    <section>
        <h2 class="text-xl font-bold mb-4">📊 Stats Cards (KPI)</h2>
        
        <?php 
        echo renderStatsGrid([
            [
                'label' => 'Total Étudiants',
                'value' => '1,234',
                'icon' => 'users',
                'type' => 'primary',
                'trend' => ['value' => '+12%', 'direction' => 'up']
            ],
            [
                'label' => 'Rapports Validés',
                'value' => '89',
                'icon' => 'check-circle',
                'type' => 'success',
                'trend' => ['value' => '+5', 'direction' => 'up']
            ],
            [
                'label' => 'En attente',
                'value' => '23',
                'icon' => 'clock',
                'type' => 'warning'
            ],
            [
                'label' => 'Rejetés',
                'value' => '7',
                'icon' => 'times-circle',
                'type' => 'danger',
                'trend' => ['value' => '-2', 'direction' => 'down']
            ]
        ]);
        ?>
    </section>

    <!-- Section: Buttons -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">🔘 Boutons</h3>
        </div>
        <div class="card-content space-y-4">
            <div class="flex flex-wrap gap-3">
                <?php
                echo renderButton('Primary', 'primary');
                echo renderButton('Secondary', 'secondary');
                echo renderButton('Outline', 'outline');
                echo renderButton('Ghost', 'ghost');
                echo renderButton('Danger', 'danger');
                echo renderButton('Success', 'success');
                echo renderButton('Warning', 'warning');
                ?>
            </div>
            
            <div class="flex flex-wrap gap-3">
                <?php
                echo renderButton('Petit', 'primary', true, '', 'sm');
                echo renderButton('Normal', 'primary');
                echo renderButton('Grand', 'primary', true, '', 'lg');
                ?>
            </div>
            
            <div class="flex flex-wrap gap-3">
                <?php
                echo renderButton('Avec Icône', 'primary', true, '', '', 'fa-plus');
                echo renderButton('Télécharger', 'outline', true, '', '', 'fa-download');
                echo renderButton('', 'primary', true, '', '', 'fa-search', true); // Icon only
                ?>
            </div>
        </div>
    </section>

    <!-- Section: Badges -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">🏷️ Badges</h3>
        </div>
        <div class="card-content">
            <div class="flex flex-wrap gap-3">
                <?php
                echo renderBadge('valide');
                echo renderBadge('rejete');
                echo renderBadge('en_cours');
                echo renderBadge('attente');
                echo renderBadge('archive');
                echo renderBadge('tres_bien');
                echo renderBadge('bien');
                echo renderBadge('passable');
                echo renderBadgeCount(42);
                ?>
            </div>
        </div>
    </section>

    <!-- Section: Alerts -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">⚠️ Alertes</h3>
        </div>
        <div class="card-content space-y-4">
            <?php
            echo renderAlert('Opération réussie ! Les données ont été enregistrées.', 'success', 'Succès');
            echo renderAlert('Attention, certains champs sont manquants.', 'warning');
            echo renderAlert('Une erreur est survenue lors du traitement.', 'danger', 'Erreur');
            echo renderAlert('Information : La maintenance est prévue ce weekend.', 'info');
            ?>
        </div>
    </section>

    <!-- Section: Form Inputs -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">📝 Formulaires</h3>
        </div>
        <div class="card-content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <?php
                    echo renderInput('nom', 'Nom complet', 'Jean Dupont', 'text', ['required' => true, 'placeholder' => 'Entrez votre nom']);
                    echo renderInput('email', 'Email', '', 'email', ['placeholder' => 'exemple@email.com', 'hint' => 'Nous ne partagerons jamais votre email.']);
                    echo renderInput('search', 'Recherche', '', 'text', ['icon' => 'fa-search', 'placeholder' => 'Rechercher...']);
                    ?>
                </div>
                <div>
                    <?php
                    echo renderSelect('niveau', ['L1' => 'Licence 1', 'L2' => 'Licence 2', 'L3' => 'Licence 3', 'M1' => 'Master 1', 'M2' => 'Master 2'], 'Niveau d\'étude', 'L3', ['required' => true]);
                    echo renderTextArea('description', 'Description', 'Ceci est un exemple de texte...', ['rows' => 3, 'placeholder' => 'Décrivez votre projet...']);
                    echo renderCheckbox('newsletter', 'Recevoir les newsletters', true);
                    ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Section: Progress -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">📈 Progression</h3>
        </div>
        <div class="card-content space-y-6">
            <div>
                <h4 class="font-medium mb-3">Barres de progression</h4>
                <div class="space-y-4">
                    <?php
                    echo renderProgressLabeled('Rapports validés', 75, 100, 'success');
                    echo renderProgressLabeled('Notes saisies', 45, 100, 'primary');
                    echo renderProgressLabeled('En attente', 23, 100, 'warning');
                    ?>
                </div>
            </div>
            
            <div>
                <h4 class="font-medium mb-3">Étapes de workflow</h4>
                <?php
                echo renderStepProgress(['Dépôt', 'Validation COM', 'Commission', 'Soutenance'], 2);
                ?>
            </div>
        </div>
    </section>

    <!-- Section: Data Table -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">📋 Tableau de Données</h3>
            <?php echo renderSearchBar('Rechercher un étudiant...'); ?>
        </div>
        <div class="card-content">
            <?php
            $sampleData = [
                [
                    'id' => 1,
                    'matricule' => 'ETU001',
                    'nom' => 'Koné Amadou',
                    'niveau' => 'M2',
                    'statut' => 'valide',
                    '_render' => ['statut' => renderBadge('valide')]
                ],
                [
                    'id' => 2,
                    'matricule' => 'ETU002',
                    'nom' => 'Touré Fatou',
                    'niveau' => 'M2',
                    'statut' => 'en_cours',
                    '_render' => ['statut' => renderBadge('en_cours')]
                ],
                [
                    'id' => 3,
                    'matricule' => 'ETU003',
                    'nom' => 'Diallo Ibrahim',
                    'niveau' => 'L3',
                    'statut' => 'attente',
                    '_render' => ['statut' => renderBadge('attente')]
                ],
                [
                    'id' => 4,
                    'matricule' => 'ETU004',
                    'nom' => 'Bamba Aissatou',
                    'niveau' => 'M1',
                    'statut' => 'rejete',
                    '_render' => ['statut' => renderBadge('rejete')]
                ]
            ];
            
            echo renderDataTable(
                [
                    'matricule' => 'Matricule',
                    'nom' => 'Nom Complet',
                    'niveau' => 'Niveau',
                    'statut' => 'Statut'
                ],
                $sampleData,
                [
                    'actions' => ['view', 'edit', 'delete'],
                    'searchable' => false,
                    'actionCallback' => function($action, $id) {
                        return "?page=demo&action=$action&id=$id";
                    }
                ]
            );
            
            echo renderPaginationInfo(1, 5, 47, 10, '?page=demo&p={page}');
            ?>
        </div>
    </section>

    <!-- Section: Tabs -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">📑 Onglets</h3>
        </div>
        <div class="card-content">
            <?php
            $tabs = [
                ['id' => 'tab1', 'label' => 'Général', 'icon' => 'fa-info-circle'],
                ['id' => 'tab2', 'label' => 'Notes', 'icon' => 'fa-chart-bar', 'count' => 12],
                ['id' => 'tab3', 'label' => 'Documents', 'icon' => 'fa-file-alt']
            ];
            
            $panels = [
                'tab1' => '<p>Contenu de l\'onglet Général. Ceci est un exemple de contenu.</p>',
                'tab2' => '<p>Tableau des notes et évaluations de l\'étudiant.</p>',
                'tab3' => '<p>Liste des documents uploadés par l\'étudiant.</p>'
            ];
            
            echo renderTabsWithPanels($tabs, $panels, 'tab1');
            ?>
        </div>
    </section>

    <!-- Section: Timeline -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">📅 Timeline / Workflow</h3>
        </div>
        <div class="card-content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium mb-3">Timeline verticale</h4>
                    <?php
                    echo renderTimeline([
                        ['title' => 'Dépôt du rapport', 'description' => 'Rapport soumis avec succès', 'date' => '15 Jan 2026', 'status' => 'complete'],
                        ['title' => 'Validation COM', 'description' => 'En cours de vérification', 'date' => '18 Jan 2026', 'status' => 'active'],
                        ['title' => 'Commission', 'description' => 'En attente', 'status' => 'pending'],
                        ['title' => 'Soutenance', 'description' => 'Date à définir', 'status' => 'pending']
                    ]);
                    ?>
                </div>
                <div>
                    <h4 class="font-medium mb-3">Workflow soutenance</h4>
                    <?php
                    echo renderSoutenanceWorkflow([
                        'depot' => 'complete',
                        'validation_com' => 'active',
                        'commission' => 'pending',
                        'soutenance' => 'pending'
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Section: Avatars -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">👤 Avatars</h3>
        </div>
        <div class="card-content">
            <div class="flex flex-wrap items-center gap-4">
                <?php
                echo renderAvatar('Jean Dupont', 'sm');
                echo renderAvatar('Marie Claire');
                echo renderAvatar('Pierre Martin', 'lg');
                echo renderAvatar('Sophie Laurent', 'xl');
                
                echo renderAvatarStatus('En ligne', 'online');
                echo renderAvatarStatus('Occupé', 'busy');
                echo renderAvatarStatus('Absent', 'away');
                
                echo renderAvatarGroup([
                    ['name' => 'User 1'],
                    ['name' => 'User 2'],
                    ['name' => 'User 3'],
                    ['name' => 'User 4'],
                    ['name' => 'User 5'],
                    ['name' => 'User 6']
                ], 4);
                ?>
            </div>
        </div>
    </section>

    <!-- Section: Skeleton Loading -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">⏳ États de Chargement (Skeleton)</h3>
        </div>
        <div class="card-content">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-medium mb-3">Texte</h4>
                    <?php echo renderSkeletonText(3); ?>
                </div>
                <div>
                    <h4 class="font-medium mb-3">Card</h4>
                    <?php echo renderSkeletonCard(); ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Section: Modals -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">💬 Modales</h3>
        </div>
        <div class="card-content">
            <div class="flex flex-wrap gap-3">
                <?php
                echo renderButton('Ouvrir Modale', 'primary', true, "onclick=\"CM.Modal.show('demo-modal')\"", '', 'fa-window-maximize');
                echo renderButton('Confirmation', 'warning', true, "onclick=\"CM.Modal.confirm({title:'Confirmation',message:'Voulez-vous continuer ?'}).then(ok => alert(ok ? 'Confirmé!' : 'Annulé'))\"", '', 'fa-question-circle');
                echo renderButton('Suppression', 'danger', true, "onclick=\"CM.Modal.confirm({title:'Supprimer',message:'Cette action est irréversible.',type:'danger',confirmText:'Supprimer'})\"", '', 'fa-trash');
                ?>
            </div>
            
            <?php
            echo renderModal(
                'demo-modal',
                'Exemple de Modale',
                '<p>Ceci est le contenu de la modale. Vous pouvez y mettre n\'importe quel HTML.</p>
                <div class="mt-4">' . renderInput('example', 'Champ exemple', '', 'text', ['placeholder' => 'Entrez quelque chose...']) . '</div>',
                renderButton('Annuler', 'secondary', true, "onclick=\"CM.Modal.hide('demo-modal')\"") . ' ' .
                renderButton('Enregistrer', 'primary')
            );
            ?>
        </div>
    </section>

    <!-- Section: Empty State -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">📭 État Vide</h3>
        </div>
        <div class="card-content">
            <?php
            echo renderEmptyState(
                'Aucun résultat trouvé',
                'fa-search',
                renderButton('Réinitialiser les filtres', 'outline', true, '', '', 'fa-redo')
            );
            ?>
        </div>
    </section>

    <!-- Section: Dropdown -->
    <section class="card">
        <div class="card-header">
            <h3 class="card-title">📂 Menus Déroulants</h3>
        </div>
        <div class="card-content">
            <div class="flex flex-wrap gap-4">
                <?php
                echo renderActionsDropdown([
                    ['label' => 'Voir détails', 'icon' => 'fa-eye', 'url' => '#'],
                    ['label' => 'Modifier', 'icon' => 'fa-edit', 'url' => '#'],
                    ['divider' => true],
                    ['label' => 'Supprimer', 'icon' => 'fa-trash', 'type' => 'danger', 'url' => '#']
                ]);
                
                echo renderExportDropdown('?page=demo');
                ?>
            </div>
        </div>
    </section>

</div>

<script>
// Animation au scroll
document.addEventListener('DOMContentLoaded', function() {
    const sections = document.querySelectorAll('section');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate__animated', 'animate__fadeInUp');
            }
        });
    }, { threshold: 0.1 });
    
    sections.forEach(section => observer.observe(section));
});
</script>
