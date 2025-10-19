<?php
$stat_etudiants = $GLOBALS['stats_etudiants'] ?? ['total' => 0,'actifs' => 0,'inactifs' => 0,'taux_activite' => 0];
$stat_enseignants = $GLOBALS['stats_enseignants'] ?? ['total' => 0,'actifs' => 0,'inactifs' => 0,'taux_activite' => 0];
$stat_personnel = $GLOBALS['stats_personnel'] ?? ['total' => 0,'actifs' => 0,'inactifs' => 0,'taux_activite' => 0];
$stat_utilisateurs = $GLOBALS['stats_utilisateurs'] ?? ['total' => 0,'actifs' => 0,'inactifs' => 0,'taux_activite' => 0];
?>
<div x-data="dashboardData()" class="space-y-6">
    <!-- Date badge -->
    <div class="flex justify-start mb-6">
        <div class="badge badge-lg bg-accent text-white border-0 shadow-lg p-4 gap-2">
            <i class="fas fa-calendar-alt"></i>
            <div>
                <?php
                $date = new DateTime();
                $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                $mois = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
                $date_fr = $jours[$date->format('w')] . ' ' . $date->format('d') . ' ' . $mois[$date->format('n')-1] . ' ' . $date->format('Y');
                $heure = $date->format('H:i');
                ?>
                <div class="font-bold"><?php echo $date_fr; ?></div>
                <div class="text-xs opacity-90"><?php echo $heure; ?></div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Card: Étudiants -->
        <div class="card bg-gradient-to-br from-primary to-primary-light text-white shadow-lg hover:shadow-xl transition-all">
            <div class="card-body">
                <div class="flex justify-between items-center">
                    <div class="bg-white/20 w-12 h-12 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-graduate text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <div class="stat-value text-3xl"><?php echo $stat_etudiants['total']; ?></div>
                        <div class="stat-desc text-white/80">Étudiants</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Enseignants -->
        <div class="card bg-gradient-to-br from-secondary to-warning text-white shadow-lg hover:shadow-xl transition-all">
            <div class="card-body">
                <div class="flex justify-between items-center">
                    <div class="bg-white/20 w-12 h-12 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chalkboard text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <div class="stat-value text-3xl"><?php echo $stat_enseignants['total']; ?></div>
                        <div class="stat-desc text-white/80">Enseignants</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Personnel -->
        <div class="card bg-gradient-to-br from-error to-pink-400 text-white shadow-lg hover:shadow-xl transition-all">
            <div class="card-body">
                <div class="flex justify-between items-center">
                    <div class="bg-white/20 w-12 h-12 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-tie text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <div class="stat-value text-3xl"><?php echo $stat_personnel['total']; ?></div>
                        <div class="stat-desc text-white/80">Personnel Administratif</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: Utilisateurs -->
        <div class="card bg-gradient-to-br from-accent to-green-400 text-white shadow-lg hover:shadow-xl transition-all">
            <div class="card-body">
                <div class="flex justify-between items-center">
                    <div class="bg-white/20 w-12 h-12 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <div class="stat-value text-3xl"><?php echo $stat_utilisateurs['total']; ?></div>
                        <div class="stat-desc text-white/80">Utilisateurs</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Statistics Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Evolution Chart -->
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <div>
                            <h2 class="card-title text-primary">Évolution des utilisateurs</h2>
                            <p class="text-sm text-base-content/60">Sur les 6 derniers mois</p>
                        </div>
                        <div class="join join-horizontal">
                            <button @click="changeChartType('etudiants')" :class="{'btn-primary': chartType === 'etudiants', 'btn-ghost': chartType !== 'etudiants'}" class="join-item btn btn-sm">Étudiants</button>
                            <button @click="changeChartType('enseignants')" :class="{'btn-primary': chartType === 'enseignants', 'btn-ghost': chartType !== 'enseignants'}" class="join-item btn btn-sm">Enseignants</button>
                            <button @click="changeChartType('utilisateurs')" :class="{'btn-primary': chartType === 'utilisateurs', 'btn-ghost': chartType !== 'utilisateurs'}" class="join-item btn btn-sm">Utilisateurs</button>
                            <button @click="changeChartType('personnels')" :class="{'btn-primary': chartType === 'personnels', 'btn-ghost': chartType !== 'personnels'}" class="join-item btn btn-sm">Personnel</button>
                        </div>
                    </div>
                    <div class="h-80">
                        <canvas id="evolutionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Statistics Table -->
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    <h2 class="card-title text-primary mb-4">Statistiques détaillées</h2>
                    <div class="overflow-x-auto">
                        <table class="table table-zebra">
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
                                    <td><span class="badge badge-success"><?php echo $stat_etudiants['actifs']; ?></span></td>
                                    <td><span class="badge badge-error"><?php echo $stat_etudiants['inactifs']; ?></span></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <progress class="progress progress-primary w-20" value="<?php echo $stat_etudiants['taux_activite']; ?>" max="100"></progress>
                                            <span class="text-sm"><?php echo $stat_etudiants['taux_activite']; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Enseignants</td>
                                    <td><?php echo $stat_enseignants['total']; ?></td>
                                    <td><span class="badge badge-success"><?php echo $stat_enseignants['actifs']; ?></span></td>
                                    <td><span class="badge badge-error"><?php echo $stat_enseignants['inactifs']; ?></span></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <progress class="progress progress-primary w-20" value="<?php echo $stat_enseignants['taux_activite']; ?>" max="100"></progress>
                                            <span class="text-sm"><?php echo $stat_enseignants['taux_activite']; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Personnel Administratif</td>
                                    <td><?php echo $stat_personnel['total']; ?></td>
                                    <td><span class="badge badge-success"><?php echo $stat_personnel['actifs']; ?></span></td>
                                    <td><span class="badge badge-error"><?php echo $stat_personnel['inactifs']; ?></span></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <progress class="progress progress-primary w-20" value="<?php echo $stat_personnel['taux_activite']; ?>" max="100"></progress>
                                            <span class="text-sm"><?php echo $stat_personnel['taux_activite']; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="font-medium">Utilisateurs</td>
                                    <td><?php echo $stat_utilisateurs['total']; ?></td>
                                    <td><span class="badge badge-success"><?php echo $stat_utilisateurs['actifs']; ?></span></td>
                                    <td><span class="badge badge-error"><?php echo $stat_utilisateurs['inactifs']; ?></span></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <progress class="progress progress-primary w-20" value="<?php echo $stat_utilisateurs['taux_activite']; ?>" max="100"></progress>
                                            <span class="text-sm"><?php echo $stat_utilisateurs['taux_activite']; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar and Activities -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Calendar Card -->
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="card-title text-primary">Calendrier</h2>
                        <div class="join">
                            <button id="prevMonth" class="join-item btn btn-sm btn-ghost">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button id="nextMonth" class="join-item btn btn-sm btn-ghost">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="text-center mb-4">
                        <h3 id="currentMonth" class="text-md font-medium text-primary"></h3>
                    </div>
                    <div class="grid grid-cols-7 gap-1 text-center text-xs font-medium mb-2">
                        <div class="text-base-content/60">Lu</div>
                        <div class="text-base-content/60">Ma</div>
                        <div class="text-base-content/60">Me</div>
                        <div class="text-base-content/60">Je</div>
                        <div class="text-base-content/60">Ve</div>
                        <div class="text-base-content/60">Sa</div>
                        <div class="text-base-content/60">Di</div>
                    </div>
                    <div id="calendarDays" class="grid grid-cols-7 gap-1"></div>
                </div>
            </div>

            <!-- Recent Activities Card -->
            <div class="card bg-base-100 shadow-lg">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="card-title text-primary">Activités récentes</h2>
                        <button class="btn btn-ghost btn-sm btn-circle">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                    </div>
                    <div class="space-y-4">
                        <?php if (isset($GLOBALS['activites_recentes']) && !empty($GLOBALS['activites_recentes'])): ?>
                            <?php foreach ($GLOBALS['activites_recentes'] as $activite): ?>
                                <div class="flex items-start gap-3">
                                    <div class="avatar placeholder">
                                        <div class="w-10 h-10 rounded-full <?php echo $activite['type'] === 'utilisateur' ? 'bg-primary' : 'bg-accent'; ?>">
                                            <i class="fas <?php echo $activite['type'] === 'utilisateur' ? 'fa-user' : 'fa-cog'; ?> text-white text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium"><?php echo htmlspecialchars($activite['description']); ?></p>
                                        <p class="text-xs text-base-content/60"><?php echo htmlspecialchars($activite['date']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-history text-4xl text-base-content/20 mb-2"></i>
                                <p class="text-base-content/60">Aucune activité récente</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .calendar-day {
        @apply w-8 h-8 flex items-center justify-center text-sm rounded-lg cursor-pointer hover:bg-primary/10 transition-colors;
    }
    .calendar-day.other-month {
        @apply text-base-content/30;
    }
    .calendar-day.today {
        @apply bg-primary text-white font-bold;
    }
    .calendar-day.selected {
        @apply bg-accent text-white;
    }
</style>

<script>
function dashboardData() {
    return {
        chartType: 'utilisateurs',
        changeChartType(type) {
            this.chartType = type;
            updateEvolutionChart(type);
        }
    };
}

const chartData = {
    etudiants: { labels: ['Jan','Fév','Mar','Avr','Mai','Juin'], data: [120,150,180,200,220,250] },
    enseignants: { labels: ['Jan','Fév','Mar','Avr','Mai','Juin'], data: [20,25,30,35,40,45] },
    utilisateurs: { labels: ['Jan','Fév','Mar','Avr','Mai','Juin'], data: [140,175,210,235,260,295] },
    personnels: { labels: ['Jan','Fév','Mar','Avr','Mai','Juin'], data: [142,169,255,235,240,356] }
};

const evolutionCtx = document.getElementById('evolutionChart').getContext('2d');
let evolutionChart = new Chart(evolutionCtx, {
    type: 'line',
    data: {
        labels: chartData.utilisateurs.labels,
        datasets: [{ 
            label: 'Utilisateurs', 
            data: chartData.utilisateurs.data, 
            borderColor: '#1a5276', 
            backgroundColor: 'rgba(26,82,118,0.1)', 
            fill: true, 
            tension: 0.4,
            pointBackgroundColor: '#1a5276',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#1a5276'
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
                    font: { size: 12, family: 'Poppins' } 
                } 
            } 
        },
        scales: { 
            y: { 
                beginAtZero: true, 
                grid: { color: 'rgba(26,82,118,0.1)' }, 
                ticks: { color: '#1a5276' } 
            }, 
            x: { 
                grid: { color: 'rgba(26,82,118,0.1)' }, 
                ticks: { color: '#1a5276' } 
            } 
        }
    }
});

function updateEvolutionChart(type) {
    evolutionChart.data.labels = chartData[type].labels;
    evolutionChart.data.datasets[0].data = chartData[type].data;
    evolutionChart.data.datasets[0].label = type.charAt(0).toUpperCase() + type.slice(1);
    evolutionChart.update();
}

// Calendar functionality
let currentDate = new Date();
let selectedDate = new Date();

function updateCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const monthNames = ["Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre"];
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
        ...prevMonthDays.map(day=>({day,isCurrentMonth:false})),
        ...Array.from({ length: totalDays }, (_, i) => ({ day: i + 1, isCurrentMonth: true })),
        ...nextMonthDays.map(day=>({day,isCurrentMonth:false}))
    ];
    const calendarHTML = allDays.map(({day,isCurrentMonth})=>{
        const isToday = isCurrentMonth && day === new Date().getDate() && month === new Date().getMonth() && year === new Date().getFullYear();
        const isSelected = isCurrentMonth && day === selectedDate.getDate() && month === selectedDate.getMonth() && year === selectedDate.getFullYear();
        let classes = 'calendar-day';
        if (!isCurrentMonth) classes += ' other-month';
        if (isToday) classes += ' today';
        if (isSelected) classes += ' selected';
        return `<div class="${classes}" data-date="${year}-${month+1}-${day}">${day}</div>`;
    }).join('');
    document.getElementById('calendarDays').innerHTML = calendarHTML;
    document.querySelectorAll('.calendar-day').forEach(day=>{
        day.addEventListener('click', ()=>{
            const [y,m,d] = day.dataset.date.split('-').map(Number);
            selectedDate = new Date(y,m-1,d);
            updateCalendar();
        });
    });
}

updateCalendar();
document.getElementById('prevMonth').addEventListener('click', ()=>{ currentDate.setMonth(currentDate.getMonth()-1); updateCalendar(); });
document.getElementById('nextMonth').addEventListener('click', ()=>{ currentDate.setMonth(currentDate.getMonth()+1); updateCalendar(); });
</script>