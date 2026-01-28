<?php
/**
 * CheckMaster Premium - Dashboard Refactorisé
 * 
 * Version migree du dashboard utilisant le Design System Premium.
 * Ce fichier peut remplacer dashboard_content.php progressivement.
 * 
 * Données attendues (via $GLOBALS ou contrôleur):
 * - stats_etudiants, stats_enseignants, stats_personnel, stats_utilisateurs
 * - activites_recentes
 */

// Récupération des données (compatible avec l'ancien système)
$stat_etudiants = $GLOBALS['stats_etudiants'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
$stat_enseignants = $GLOBALS['stats_enseignants'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
$stat_personnel = $GLOBALS['stats_personnel'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
$stat_utilisateurs = $GLOBALS['stats_utilisateurs'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
$activites_recentes = $GLOBALS['activites_recentes'] ?? [];

// Date formatée en français
$date = new DateTime();
$jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
$mois = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$date_fr = $jours[$date->format('w')] . ' ' . $date->format('d') . ' ' . $mois[$date->format('n') - 1] . ' ' . $date->format('Y');
$heure = $date->format('H:i');
?>

<!-- En-tête avec date -->
<div class="flex justify-between items-center mb-6">
    <div class="bg-success rounded-xl shadow-lg p-4 text-white">
        <h2 class="text-lg font-bold"><?php echo $date_fr; ?></h2>
        <p class="text-sm opacity-90"><?php echo $heure; ?></p>
    </div>
</div>

<!-- Stats Cards - Utilisation du composant Premium -->
<?php 
echo renderStatsGrid([
    [
        'label' => 'Étudiants',
        'value' => number_format($stat_etudiants['total']),
        'icon' => 'user-graduate',
        'type' => 'primary',
        'trend' => $stat_etudiants['actifs'] > 0 
            ? ['value' => $stat_etudiants['actifs'] . ' actifs', 'direction' => 'up'] 
            : []
    ],
    [
        'label' => 'Enseignants',
        'value' => number_format($stat_enseignants['total']),
        'icon' => 'chalkboard-teacher',
        'type' => 'info',
        'trend' => $stat_enseignants['actifs'] > 0 
            ? ['value' => $stat_enseignants['actifs'] . ' actifs', 'direction' => 'up'] 
            : []
    ],
    [
        'label' => 'Personnel Administratif',
        'value' => number_format($stat_personnel['total']),
        'icon' => 'user-tie',
        'type' => 'success',
        'trend' => $stat_personnel['actifs'] > 0 
            ? ['value' => $stat_personnel['actifs'] . ' actifs', 'direction' => 'up'] 
            : []
    ],
    [
        'label' => 'Utilisateurs',
        'value' => number_format($stat_utilisateurs['total']),
        'icon' => 'users',
        'type' => 'warning',
        'trend' => $stat_utilisateurs['actifs'] > 0 
            ? ['value' => $stat_utilisateurs['actifs'] . ' actifs', 'direction' => 'up'] 
            : []
    ]
]);
?>

<!-- Contenu principal en grille -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Colonne principale (2/3) -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Graphique d'évolution -->
        <?php 
        $chartContent = '
            <div class="flex justify-end mb-4">
                <div class="flex gap-1 text-xs bg-muted-light rounded-lg p-1">
                    <button class="btn-tab px-3 py-1 rounded-md" data-type="etudiants">ÉTUDIANTS</button>
                    <button class="btn-tab px-3 py-1 rounded-md" data-type="enseignants">ENSEIGNANTS</button>
                    <button class="btn-tab active px-3 py-1 rounded-md" data-type="utilisateurs">UTILISATEURS</button>
                    <button class="btn-tab px-3 py-1 rounded-md" data-type="personnels">PERSONNEL</button>
                </div>
            </div>
            <div class="h-80">
                <canvas id="evolutionChart"></canvas>
            </div>
        ';
        
        echo renderFullCard(
            'Évolution des utilisateurs',
            $chartContent,
            '',
            '',
            'Sur les 6 derniers mois'
        );
        ?>
        
        <!-- Tableau des statistiques détaillées -->
        <?php
        $tableData = [
            [
                'id' => 1,
                'categorie' => 'Étudiants',
                'total' => $stat_etudiants['total'],
                'actifs' => $stat_etudiants['actifs'],
                'inactifs' => $stat_etudiants['inactifs'],
                'taux' => $stat_etudiants['taux_activite'] . '%',
                '_render' => [
                    'taux' => renderProgress($stat_etudiants['taux_activite'], 'success', '', true)
                ]
            ],
            [
                'id' => 2,
                'categorie' => 'Enseignants',
                'total' => $stat_enseignants['total'],
                'actifs' => $stat_enseignants['actifs'],
                'inactifs' => $stat_enseignants['inactifs'],
                'taux' => $stat_enseignants['taux_activite'] . '%',
                '_render' => [
                    'taux' => renderProgress($stat_enseignants['taux_activite'], 'primary', '', true)
                ]
            ],
            [
                'id' => 3,
                'categorie' => 'Personnel Administratif',
                'total' => $stat_personnel['total'],
                'actifs' => $stat_personnel['actifs'],
                'inactifs' => $stat_personnel['inactifs'],
                'taux' => $stat_personnel['taux_activite'] . '%',
                '_render' => [
                    'taux' => renderProgress($stat_personnel['taux_activite'], 'info', '', true)
                ]
            ],
            [
                'id' => 4,
                'categorie' => 'Utilisateurs',
                'total' => $stat_utilisateurs['total'],
                'actifs' => $stat_utilisateurs['actifs'],
                'inactifs' => $stat_utilisateurs['inactifs'],
                'taux' => $stat_utilisateurs['taux_activite'] . '%',
                '_render' => [
                    'taux' => renderProgress($stat_utilisateurs['taux_activite'], 'warning', '', true)
                ]
            ]
        ];
        
        $tableHtml = renderDataTable(
            [
                'categorie' => 'Catégorie',
                'total' => 'Total',
                'actifs' => 'Actifs',
                'inactifs' => 'Inactifs',
                'taux' => 'Taux d\'activité'
            ],
            $tableData,
            [
                'actions' => [],
                'emptyMessage' => 'Aucune donnée statistique disponible'
            ]
        );
        
        echo renderFullCard('Statistiques détaillées', $tableHtml);
        ?>
    </div>
    
    <!-- Colonne latérale (1/3) -->
    <div class="lg:col-span-1 space-y-6">
        
        <!-- Calendrier -->
        <?php
        $calendarContent = '
            <div class="flex justify-between items-center mb-4">
                <div class="flex gap-2">
                    <button id="prevMonth" class="btn btn-outline btn-sm">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button id="nextMonth" class="btn btn-outline btn-sm">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <div class="text-center mb-4">
                <h3 id="currentMonth" class="text-md font-medium text-primary"></h3>
            </div>
            
            <div class="grid grid-cols-7 gap-1 mb-2 text-center">
                <div class="text-xs font-medium text-muted">Lu</div>
                <div class="text-xs font-medium text-muted">Ma</div>
                <div class="text-xs font-medium text-muted">Me</div>
                <div class="text-xs font-medium text-muted">Je</div>
                <div class="text-xs font-medium text-muted">Ve</div>
                <div class="text-xs font-medium text-muted">Sa</div>
                <div class="text-xs font-medium text-muted">Di</div>
            </div>
            
            <div id="calendarDays" class="grid grid-cols-7 gap-1"></div>
        ';
        
        echo renderFullCard('Calendrier', $calendarContent);
        ?>
        
        <!-- Activités récentes -->
        <?php
        if (!empty($activites_recentes)) {
            $activitiesHtml = renderActivityTimeline(
                array_map(function($activite) {
                    return [
                        'description' => $activite['description'] ?? '',
                        'time' => $activite['date'] ?? '',
                        'icon' => ($activite['type'] ?? '') === 'utilisateur' ? 'fa-user' : 'fa-cog',
                        'type' => ($activite['type'] ?? '') === 'utilisateur' ? 'info' : 'success'
                    ];
                }, $activites_recentes)
            );
        } else {
            $activitiesHtml = renderEmptyState('Aucune activité récente', 'fa-history');
        }
        
        echo renderFullCard(
            'Activités récentes',
            $activitiesHtml,
            renderButton('Voir tout', 'ghost', true, '', 'sm', 'fa-external-link-alt')
        );
        ?>
    </div>
</div>

<!-- Scripts pour le graphique et le calendrier -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Styles pour les boutons d'onglets
    const style = document.createElement('style');
    style.textContent = `
        .btn-tab {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .btn-tab.active {
            background-color: var(--primary);
            color: white;
        }
        .btn-tab:hover:not(.active) {
            background-color: rgba(26, 82, 118, 0.1);
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .calendar-day:hover {
            background-color: var(--accent-light);
        }
        .calendar-day.other-month {
            color: var(--muted);
        }
        .calendar-day.today {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }
        .calendar-day.selected {
            border: 2px solid var(--primary);
        }
    `;
    document.head.appendChild(style);

    // Données du graphique
    const chartData = {
        etudiants: { labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'], data: [120, 150, 180, 200, 220, 250] },
        enseignants: { labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'], data: [20, 25, 30, 35, 40, 45] },
        utilisateurs: { labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'], data: [140, 175, 210, 235, 260, 295] },
        personnels: { labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'], data: [142, 169, 255, 235, 240, 356] }
    };

    // Création du graphique
    const evolutionCtx = document.getElementById('evolutionChart');
    if (evolutionCtx) {
        let evolutionChart = new Chart(evolutionCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartData.utilisateurs.labels,
                datasets: [{
                    label: 'Utilisateurs',
                    data: chartData.utilisateurs.data,
                    borderColor: '#1a5276',
                    backgroundColor: 'rgba(26, 82, 118, 0.08)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#1a5276'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#1a5276',
                            font: { size: 12, weight: '500' }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { color: '#6b7280' }
                    },
                    x: {
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { color: '#6b7280' }
                    }
                }
            }
        });

        // Changement d'onglets
        document.querySelectorAll('.btn-tab').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.btn-tab').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                const type = button.dataset.type;
                evolutionChart.data.labels = chartData[type].labels;
                evolutionChart.data.datasets[0].data = chartData[type].data;
                evolutionChart.data.datasets[0].label = type.charAt(0).toUpperCase() + type.slice(1);
                evolutionChart.update();
            });
        });
    }

    // Calendrier
    let currentDate = new Date();
    let selectedDate = new Date();

    function updateCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                           'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        
        const currentMonthEl = document.getElementById('currentMonth');
        if (currentMonthEl) {
            currentMonthEl.textContent = `${monthNames[month]} ${year}`;
        }
        
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const startingDay = firstDay.getDay() || 7;
        const totalDays = lastDay.getDate();
        const prevMonthLastDay = new Date(year, month, 0).getDate();
        
        const prevMonthDays = Array.from({ length: startingDay - 1 }, (_, i) => prevMonthLastDay - i).reverse();
        const remainingDays = 42 - (prevMonthDays.length + totalDays);
        const nextMonthDays = Array.from({ length: remainingDays }, (_, i) => i + 1);
        
        const allDays = [
            ...prevMonthDays.map(day => ({ day, isCurrentMonth: false })),
            ...Array.from({ length: totalDays }, (_, i) => ({ day: i + 1, isCurrentMonth: true })),
            ...nextMonthDays.map(day => ({ day, isCurrentMonth: false }))
        ];
        
        const calendarHTML = allDays.map(({ day, isCurrentMonth }) => {
            const isToday = isCurrentMonth && 
                           day === new Date().getDate() && 
                           month === new Date().getMonth() && 
                           year === new Date().getFullYear();
            const isSelected = isCurrentMonth && 
                              day === selectedDate.getDate() && 
                              month === selectedDate.getMonth() && 
                              year === selectedDate.getFullYear();
            
            let classes = 'calendar-day';
            if (!isCurrentMonth) classes += ' other-month';
            if (isToday) classes += ' today';
            if (isSelected && !isToday) classes += ' selected';
            
            return `<div class="${classes}" data-date="${year}-${month + 1}-${day}">${day}</div>`;
        }).join('');
        
        const calendarDaysEl = document.getElementById('calendarDays');
        if (calendarDaysEl) {
            calendarDaysEl.innerHTML = calendarHTML;
            
            document.querySelectorAll('.calendar-day').forEach(day => {
                day.addEventListener('click', () => {
                    const [y, m, d] = day.dataset.date.split('-').map(Number);
                    selectedDate = new Date(y, m - 1, d);
                    updateCalendar();
                });
            });
        }
    }

    // Initialisation du calendrier
    document.addEventListener('DOMContentLoaded', () => {
        updateCalendar();
        
        const prevBtn = document.getElementById('prevMonth');
        const nextBtn = document.getElementById('nextMonth');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                updateCalendar();
            });
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                updateCalendar();
            });
        }
    });
</script>
