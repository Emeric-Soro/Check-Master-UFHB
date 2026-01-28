<?php
// Chargement des composants
require_once __DIR__ . '/components/ui/stats-card.php';
require_once __DIR__ . '/components/ui/card.php';
require_once __DIR__ . '/components/ui/button.php';

$stat_etudiants = $GLOBALS['stats_etudiants'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
$stat_enseignants = $GLOBALS['stats_enseignants'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
$stat_personnel = $GLOBALS['stats_personnel'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
$stat_utilisateurs = $GLOBALS['stats_utilisateurs'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
?>

<div class="container p-lg">
    <!-- En-tête avec date -->
    <div class="flex justify-between items-center mb-lg">
        <div class="card p-lg bg-success text-white">
            <?php
            $date = new DateTime();
            $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
            $mois = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
            $date_fr = $jours[$date->format('w')] . ' ' . $date->format('d') . ' ' . $mois[$date->format('n') - 1] . ' ' . $date->format('Y');
            $heure = $date->format('H:i');
            ?>
            <h2 class="text-lg font-bold text-white"><?php echo $date_fr; ?></h2>
            <p class="text-sm text-white"><?php echo $heure; ?></p>
        </div>
    </div>

    <!-- Statistiques KPI -->
    <div class="stats-grid mb-lg">
        <?= renderStatsCard('Étudiants', $stat_etudiants['total'], 'user-graduate', 'primary') ?>
        <?= renderStatsCard('Enseignants', $stat_enseignants['total'], 'chalkboard', 'info') ?>
        <?= renderStatsCard('Personnels Administratifs', $stat_personnel['total'], 'user-tie', 'success') ?>
        <?= renderStatsCard('Utilisateurs', $stat_utilisateurs['total'], 'users', 'warning') ?>
    </div>

    <!-- Grille principale -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        <!-- Colonne gauche: Graphiques et statistiques -->
        <div class="lg:col-span-2">
            <!-- Graphique d'évolution -->
            <div class="card mb-lg">
                <div class="card-header">
                    <div>
                        <h2 class="card-title text-success">Évolution des utilisateurs</h2>
                        <p class="card-description text-primary">Sur les 6 derniers mois</p>
                    </div>
                    <div class="flex gap-xs bg-accent-light p-1 rounded-lg">
                        <button class="btn-tab px-3 py-2 text-xs rounded-md" data-type="etudiants">ÉTUDIANTS</button>
                        <button class="btn-tab px-3 py-2 text-xs rounded-md" data-type="enseignants">ENSEIGNANTS</button>
                        <button class="btn-tab active px-3 py-2 text-xs rounded-md" data-type="utilisateurs">UTILISATEURS</button>
                        <button class="btn-tab px-3 py-2 text-xs rounded-md" data-type="personnels">PERSONNEL ADMINISTRATIF</button>
                    </div>
                </div>
                <div class="card-content" style="height: 320px;">
                    <canvas id="evolutionChart"></canvas>
                </div>
            </div>

            <!-- Tableau statistiques détaillées -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title text-success">Statistiques détaillées</h2>
                </div>
                <div class="card-content">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Total</th>
                                    <th>Actifs</th>
                                    <th>Inactifs</th>
                                    <th>Taux d'activité</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="font-medium">Étudiants</td>
                                    <td><?php echo $stat_etudiants['total']; ?></td>
                                    <td><?php echo $stat_etudiants['actifs']; ?></td>
                                    <td><?php echo $stat_etudiants['inactifs']; ?></td>
                                    <td><?php echo $stat_etudiants['taux_activite']; ?>%</td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Enseignants</td>
                                    <td><?php echo $stat_enseignants['total']; ?></td>
                                    <td><?php echo $stat_enseignants['actifs']; ?></td>
                                    <td><?php echo $stat_enseignants['inactifs']; ?></td>
                                    <td><?php echo $stat_enseignants['taux_activite']; ?>%</td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Personnel Administratif</td>
                                    <td><?php echo $stat_personnel['total']; ?></td>
                                    <td><?php echo $stat_personnel['actifs']; ?></td>
                                    <td><?php echo $stat_personnel['inactifs']; ?></td>
                                    <td><?php echo $stat_personnel['taux_activite']; ?>%</td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Utilisateurs</td>
                                    <td><?php echo $stat_utilisateurs['total']; ?></td>
                                    <td><?php echo $stat_utilisateurs['actifs']; ?></td>
                                    <td><?php echo $stat_utilisateurs['inactifs']; ?></td>
                                    <td><?php echo $stat_utilisateurs['taux_activite']; ?>%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite: Calendrier et activités -->
        <div class="lg:col-span-1">
            <!-- Calendrier -->
            <div class="card mb-lg">
                <div class="card-header">
                    <h2 class="card-title text-success">Calendrier</h2>
                    <div class="flex gap-sm">
                        <button id="prevMonth" class="btn btn-ghost btn-sm btn-icon">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button id="nextMonth" class="btn btn-ghost btn-sm btn-icon">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="card-content">
                    <div class="text-center mb-md">
                        <h3 id="currentMonth" class="text-md font-medium text-primary"></h3>
                    </div>
                    
                    <div class="calendar-header">
                        <div>Lu</div>
                        <div>Ma</div>
                        <div>Me</div>
                        <div>Je</div>
                        <div>Ve</div>
                        <div>Sa</div>
                        <div>Di</div>
                    </div>
                    <div id="calendarDays" class="calendar-grid"></div>
                </div>
            </div>

            <!-- Activités récentes -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title text-success">Activités récentes</h2>
                    <button class="btn btn-ghost btn-sm btn-icon">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                </div>
                <div class="card-content">
                    <div class="flex flex-col gap-md">
                        <?php if (isset($GLOBALS['activites_recentes']) && !empty($GLOBALS['activites_recentes'])): ?>
                            <?php foreach ($GLOBALS['activites_recentes'] as $activite): ?>
                                <div class="flex items-start gap-md">
                                    <div class="flex-shrink-0">
                                        <div class="stat-card-icon <?php echo $activite['type'] === 'utilisateur' ? 'info' : 'success'; ?>" style="width: 2rem; height: 2rem;">
                                            <i class="fas <?php echo $activite['type'] === 'utilisateur' ? 'fa-user' : 'fa-cog'; ?> text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow">
                                        <p class="text-sm text-foreground">
                                            <?php echo htmlspecialchars($activite['description']); ?>
                                        </p>
                                        <p class="text-xs text-muted">
                                            <?php echo htmlspecialchars($activite['date']); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-xl text-muted">
                                <i class="fas fa-history text-3xl mb-md"></i>
                                <p>Aucune activité récente</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-tab {
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
    border-radius: var(--radius-md);
    border: none;
    background: transparent;
    color: var(--muted);
    cursor: pointer;
    transition: var(--transition);
    font-weight: 500;
}

.btn-tab:hover {
    background: rgba(255, 255, 255, 0.5);
    color: var(--primary);
}

.btn-tab.active {
    background: var(--primary);
    color: white;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.calendar-header div {
    text-align: center;
    font-size: 0.75rem;
    color: var(--primary);
    font-weight: 500;
}

.calendar-day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius);
    cursor: pointer;
    font-size: 0.875rem;
    transition: var(--transition);
}

.calendar-day:hover {
    background: var(--accent-light);
}

.calendar-day.other-month {
    color: var(--muted);
    opacity: 0.4;
}

.calendar-day.today {
    background: var(--accent);
    color: white;
    font-weight: 600;
}

.calendar-day.selected {
    background: var(--primary);
    color: white;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartData = {
        etudiants: { labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'], data: [120, 150, 180, 200, 220, 250] },
        enseignants: { labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'], data: [20, 25, 30, 35, 40, 45] },
        utilisateurs: { labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'], data: [140, 175, 210, 235, 260, 295] },
        personnels: { labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'], data: [142, 169, 255, 235, 240, 356] }
    };

    const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
    let evolutionChart = new Chart(evolutionCtx, {
        type: 'line',
        data: {
            labels: chartData.utilisateurs.labels,
            datasets: [{
                label: 'Utilisateurs',
                data: chartData.utilisateurs.data,
                borderColor: getComputedStyle(document.documentElement).getPropertyValue('--primary').trim() || '#1a5276',
                backgroundColor: 'rgba(26, 82, 118, 0.08)',
                fill: true,
                tension: 0.4,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: getComputedStyle(document.documentElement).getPropertyValue('--foreground').trim(),
                        font: { size: 12, family: 'Poppins' }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: { color: getComputedStyle(document.documentElement).getPropertyValue('--muted').trim() }
                },
                x: {
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    ticks: { color: getComputedStyle(document.documentElement).getPropertyValue('--muted').trim() }
                }
            }
        }
    });

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

    // Calendrier
    let currentDate = new Date();
    let selectedDate = new Date();

    function updateCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const monthNames = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"];
        document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;
        
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
            const isToday = isCurrentMonth && day === new Date().getDate() && month === new Date().getMonth() && year === new Date().getFullYear();
            const isSelected = isCurrentMonth && day === selectedDate.getDate() && month === selectedDate.getMonth() && year === selectedDate.getFullYear();
            
            let classes = 'calendar-day';
            if (!isCurrentMonth) classes += ' other-month';
            if (isToday) classes += ' today';
            if (isSelected) classes += ' selected';
            
            return `<div class="${classes}" data-date="${year}-${month + 1}-${day}">${day}</div>`;
        }).join('');
        
        document.getElementById('calendarDays').innerHTML = calendarHTML;
        
        document.querySelectorAll('.calendar-day').forEach(day => {
            day.addEventListener('click', () => {
                const [y, m, d] = day.dataset.date.split('-').map(Number);
                selectedDate = new Date(y, m - 1, d);
                updateCalendar();
            });
        });
    }

    updateCalendar();
    document.getElementById('prevMonth').addEventListener('click', () => { 
        currentDate.setMonth(currentDate.getMonth() - 1); 
        updateCalendar(); 
    });
    document.getElementById('nextMonth').addEventListener('click', () => { 
        currentDate.setMonth(currentDate.getMonth() + 1); 
        updateCalendar(); 
    });
</script>