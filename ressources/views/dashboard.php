<?php
/**
 * Vue unifiée des tableaux de bord
 * Remplace : dashboard_content.php, dashboard_scolarite_content.php,
 *            dashboard_secretaire_content.php, dashboard_commission_content.php,
 *            dashboard_enseignant_content.php
 *
 * La page affichée dépend de $_GET['page'] :
 *   dashboard              → Admin
 *   dashboard_scolarite    → Responsable Scolarité
 *   dashboard_secretaire   → Secrétariat
 *   dashboard_commission   → Commission de validation
 *   dashboard_enseignant   → Enseignant
 */

$currentPage = $_GET['page'] ?? 'dashboard';

// ── Scolarité ────────────────────────────────────────────────────────────────
if ($currentPage === 'dashboard_scolarite') {
    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/DashboardScolariteController.php';
    $dashboardController = new DashboardScolariteController();
    $dashboardData       = $dashboardController->getDashboardData();
    $stats               = $dashboardData['stats'];
    $inscriptionsParNiveau = $dashboardData['inscriptionsParNiveau'];
}

// ── Secrétariat ──────────────────────────────────────────────────────────────
if ($currentPage === 'dashboard_secretaire') {
    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/controllers/DashboardScolariteController.php';

    $dashboardController   = new DashboardScolariteController();
    $dashboardData         = $dashboardController->getDashboardData();
    $stats                 = $dashboardData['stats'];
    $inscriptionsParNiveau = $dashboardData['inscriptionsParNiveau'];

    $statsParNiveau = [];
    foreach ($inscriptionsParNiveau as $niveau) {
        $statsParNiveau[$niveau['niveau']] = $niveau['total'];
    }

    $totalInscriptions  = $stats['etudiants'];
    $paiementsComplets  = $stats['paiements_complets'];
    $pourcentageReussite = $totalInscriptions > 0
        ? round(($paiementsComplets / $totalInscriptions) * 100)
        : 0;

    $db = Database::getConnection();

    $stmtAct = $db->prepare(
        "SELECT i.date_inscription, e.nom_etu, e.prenom_etu, n.lib_niv_etude
         FROM inscriptions i
         JOIN etudiants e ON i.id_etudiant = e.num_carte_etud
         JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
         ORDER BY i.date_inscription DESC
         LIMIT 5"
    );
    $stmtAct->execute();
    $activitesRecentes = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

    $stmtRec = $db->prepare(
        "SELECT r.date_creation, e.nom_etu, e.prenom_etu,
                r.objet_reclamation AS type_reclamation, r.statut_reclamation
         FROM reclamations r
         LEFT JOIN etudiants e ON r.num_carte_etud = e.num_carte_etud
         ORDER BY r.date_creation DESC
         LIMIT 5"
    );
    $stmtRec->execute();
    $reclamationsRecentes = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

    $montantTotalPercu = $stats['montant_percu'];
    $montantEnAttente  = $stats['montant_attente'];
    $montantTotal      = $montantTotalPercu + $montantEnAttente;
    $pourcentagePercu  = $montantTotal > 0
        ? round(($montantTotalPercu / $montantTotal) * 100)
        : 0;

    $totalInscriptions = array_sum(array_column($inscriptionsParNiveau, 'total'));
}

// ── Commission ───────────────────────────────────────────────────────────────
if ($currentPage === 'dashboard_commission') {
    global $stats;
    $dashboardData   = $stats ?? [];
    $totalRapports   = $dashboardData['total_rapports']   ?? 0;
    $tauxValidation  = $dashboardData['taux_validation']  ?? 0;
    $tempsMoyen      = $dashboardData['temps_moyen']      ?? 0;
    $enAttente       = $dashboardData['en_attente']       ?? 0;
    $evolutionData   = $dashboardData['evolution_mensuelle'] ?? [];
    $repartitionData = $dashboardData['repartition_statuts'] ?? [];
    $activitesData   = $dashboardData['activites_recentes']  ?? [];
    $rapportsDetails = $dashboardData['rapports_details']    ?? [];

    $evolutionLabels   = [];
    $evolutionFinalises = [];
    $evolutionRejetes  = [];
    foreach ($evolutionData as $data) {
        $evolutionLabels[]    = date('M Y', strtotime($data['mois'] . '-01'));
        $evolutionFinalises[] = $data['finalises'];
        $evolutionRejetes[]   = $data['rejetes'];
    }

    $statusLabels = [];
    $statusData   = [];
    $statusColors = ['#10b981', '#0F4C75', '#f59e0b', '#6b7280', '#3282B8'];
    foreach ($repartitionData as $data) {
        $statusLabels[] = ucfirst($data['statut']);
        $statusData[]   = $data['nombre'];
    }
}

// ── Enseignant ───────────────────────────────────────────────────────────────
if ($currentPage === 'dashboard_enseignant') {
    require_once __DIR__ . '/../../app/config/database.php';
    require_once __DIR__ . '/../../app/models/Enseignant.php';
    require_once __DIR__ . '/../../app/models/NiveauEtude.php';
    require_once __DIR__ . '/../../app/models/Etudiant.php';

    $pdo             = Database::getConnection();
    $enseignantModel = new Enseignant($pdo);
    $enseignant      = $enseignantModel->getEnseignantByLogin($_SESSION['login_utilisateur']);
    $enseignantId    = $enseignant->id_enseignant ?? null;
    if (!$enseignantId) {
        die("Enseignant non trouvé ou non connecté.");
    }

    $total_etudiants = $GLOBALS['total_etudiants'] ?? 0;
    $total_ues       = $GLOBALS['total_ues']       ?? 0;
    $total_ecues     = $GLOBALS['total_ecues']     ?? 0;
    $mes_cours       = $GLOBALS['mes_cours']       ?? [];
}

// ── Admin ────────────────────────────────────────────────────────────────────
if ($currentPage === 'dashboard') {
    $stat_etudiants   = $GLOBALS['stats_etudiants']   ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
    $stat_enseignants = $GLOBALS['stats_enseignants'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
    $stat_personnel   = $GLOBALS['stats_personnel']   ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
    $stat_utilisateurs = $GLOBALS['stats_utilisateurs'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
}

// ── Helpers commission ───────────────────────────────────────────────────────
if (!function_exists('getTimeAgo')) {
    function getTimeAgo($date)
    {
        if (!$date) return 'N/A';
        $time = time() - strtotime($date);
        if ($time < 3600)  return floor($time / 60)   . 'min';
        if ($time < 86400) return floor($time / 3600) . 'h';
        return floor($time / 86400) . 'j';
    }
}
if (!function_exists('getStatusClass')) {
    function getStatusClass($status)
    {
        switch ($status) {
            case 'valider':    return 'bg-green-100 text-green-800';
            case 'rejeter':    return 'bg-red-100 text-red-800';
            case 'en_attente': return 'bg-blue-100 text-blue-800';
            case 'en_cours':   return 'bg-blue-100 text-blue-800';
            default:           return 'bg-gray-100 text-gray-800';
        }
    }
}
?>

<style>
:root {
    --blue: #0F4C75;
    --blue-light: #3282B8;
    --green: #10b981;
    --muted: #64748B;
    --warning: #f59e0b;
    --danger: #ef4444;
}
/* ── Commun ── */
.cm-card-hover { transition: all .3s ease; }
.cm-card-hover:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,.12); }
.cm-gradient-blue   { background: linear-gradient(135deg, var(--blue), var(--blue-light)) !important; }
.cm-gradient-green  { background: linear-gradient(135deg, var(--green), #059669) !important; }
.cm-gradient-orange { background: linear-gradient(135deg, #fb923c, #fdba74) !important; }
.cm-gradient-red    { background: linear-gradient(135deg, #ef4444, #f87171) !important; }
.cm-gradient-teal   { background: linear-gradient(135deg, #34d399, var(--green)) !important; }
.cm-gradient-yellow { background: linear-gradient(135deg, var(--blue), var(--green)) !important; }

/* ── Admin ── */
.calendar-grid    { display:grid; grid-template-columns:repeat(7,1fr); gap:.4rem; margin-top:.4rem; }
.calendar-header  { display:grid; grid-template-columns:repeat(7,1fr); gap:.4rem; margin-bottom:.4rem; }
.calendar-header div { text-align:center; font-size:.7rem; color:var(--blue); font-weight:500; }
.calendar-day     { text-align:center; padding:.3rem .2rem; border-radius:.4rem; cursor:pointer; font-size:.78rem; color:#374151; transition:background .2s; }
.calendar-day:hover { background:#dbeafe; }
.calendar-day.today    { background:var(--blue); color:#fff; font-weight:600; }
.calendar-day.selected { background:var(--blue-light); color:#fff; }
.calendar-day.other-month { color:#d1d5db; }
.stats-table th { background:#f3f4f6; color:var(--blue); font-weight:600; padding:.75rem 1rem; text-align:left; }
.stats-table td { padding:.75rem 1rem; border-bottom:1px solid #e5e7eb; }
.stats-table tr:hover { background:#f9fafb; }
.btn-tab { transition:all .3s ease; }
.btn-tab.active { background-color:var(--blue); color:#fff; }

/* ── Commission ── */
.fade-in { animation:fadeIn .3s ease-in; }
@keyframes fadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }
.stat-card-commission { transition:all .3s ease; }
.stat-card-commission:hover { transform:translateY(-2px); box-shadow:0 10px 25px rgba(0,0,0,.1); }
.chart-container { position:relative; height:300px; }
.metric-value {
    font-size:2.5rem; font-weight:700;
    background:linear-gradient(135deg, var(--blue), #155a84);
    -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}

/* ── Secrétariat ── */
.donut-chart-container {
    width:150px; height:150px; border-radius:50%;
    background:conic-gradient(#8b5cf6 0% 45%, #6366f1 45% 65%, #fcd34d 65% 85%, #f87171 85% 100%);
    position:relative;
}
.donut-chart-inner {
    position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
    width:90px; height:90px; background:#fff; border-radius:50%;
    display:flex; align-items:center; justify-content:center; flex-direction:column;
}
</style>

<?php /* ═══════════════════════════════════════════════════════════════════════
       ██  ADMIN
       ═══════════════════════════════════════════════════════════════════════ */
if ($currentPage === 'dashboard'): ?>

<div class="container mx-auto p-6">
    <!-- Date widget -->
    <div class="flex justify-between items-center mb-6">
        <div class="bg-green-500 rounded-xl shadow-lg p-4 text-white">
            <?php
            $date    = new DateTime();
            $jours   = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
            $moisArr = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
            $date_fr = $jours[$date->format('w')].' '.$date->format('d').' '.$moisArr[$date->format('n')-1].' '.$date->format('Y');
            ?>
            <h2 class="text-lg font-bold text-white"><?php echo $date_fr; ?></h2>
            <p class="text-sm text-white opacity-90"><?php echo $date->format('H:i'); ?></p>
        </div>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="cm-gradient-blue rounded-xl p-4 text-white shadow-lg cm-card-hover">
            <div class="flex justify-between items-center">
                <div class="bg-white bg-opacity-20 w-12 h-12 rounded-xl flex items-center justify-center">
                    <i class="fas fa-user-graduate text-white text-xl"></i>
                </div>
                <div class="text-right">
                    <h3 class="text-3xl font-bold"><?php echo $stat_etudiants['total']; ?></h3>
                    <p class="text-sm opacity-90">Étudiants</p>
                </div>
            </div>
        </div>
        <div class="cm-gradient-blue rounded-xl p-4 text-white shadow-lg cm-card-hover">
            <div class="flex justify-between items-center">
                <div class="bg-white bg-opacity-20 w-12 h-12 rounded-xl flex items-center justify-center">
                    <i class="fas fa-chalkboard text-white text-xl"></i>
                </div>
                <div class="text-right">
                    <h3 class="text-3xl font-bold"><?php echo $stat_enseignants['total']; ?></h3>
                    <p class="text-sm opacity-90">Enseignants</p>
                </div>
            </div>
        </div>
        <div class="cm-gradient-green rounded-xl p-4 text-white shadow-lg cm-card-hover">
            <div class="flex justify-between items-center">
                <div class="bg-white bg-opacity-20 w-12 h-12 rounded-xl flex items-center justify-center">
                    <i class="fas fa-user-tie text-white text-xl"></i>
                </div>
                <div class="text-right">
                    <h3 class="text-3xl font-bold"><?php echo $stat_personnel['total']; ?></h3>
                    <p class="text-sm opacity-90">Personnel Administratif</p>
                </div>
            </div>
        </div>
        <div class="cm-gradient-blue rounded-xl p-4 text-white shadow-lg cm-card-hover">
            <div class="flex justify-between items-center">
                <div class="bg-white bg-opacity-20 w-12 h-12 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
                <div class="text-right">
                    <h3 class="text-3xl font-bold"><?php echo $stat_utilisateurs['total']; ?></h3>
                    <p class="text-sm opacity-90">Utilisateurs</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart + Stats table + Calendrier + Activités -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <!-- Évolution chart -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 cm-card-hover">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-green-800">Évolution des utilisateurs</h2>
                        <p class="text-sm text-blue-600">Sur les 6 derniers mois</p>
                    </div>
                    <div class="flex space-x-1 text-xs bg-blue-100 rounded-lg p-1 flex-wrap gap-1">
                        <button class="btn-tab px-3 py-1 rounded-md" data-type="etudiants">ÉTUDIANTS</button>
                        <button class="btn-tab px-3 py-1 rounded-md" data-type="enseignants">ENSEIGNANTS</button>
                        <button class="btn-tab active px-3 py-1 rounded-md" data-type="utilisateurs">UTILISATEURS</button>
                        <button class="btn-tab px-3 py-1 rounded-md" data-type="personnels">PERSONNEL ADM.</button>
                    </div>
                </div>
                <div class="h-80"><canvas id="evolutionChartAdmin"></canvas></div>
            </div>

            <!-- Stats table -->
            <div class="bg-white rounded-xl shadow-lg p-6 cm-card-hover">
                <h2 class="text-lg font-bold text-green-800 mb-4">Statistiques détaillées</h2>
                <div class="overflow-x-auto">
                    <table class="stats-table w-full">
                        <thead>
                            <tr>
                                <th>Catégorie</th><th>Total</th><th>Actifs</th><th>Inactifs</th><th>Taux d'activité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Étudiants</td><td><?php echo $stat_etudiants['total']; ?></td><td><?php echo $stat_etudiants['actifs']; ?></td><td><?php echo $stat_etudiants['inactifs']; ?></td><td><?php echo $stat_etudiants['taux_activite']; ?>%</td></tr>
                            <tr><td>Enseignants</td><td><?php echo $stat_enseignants['total']; ?></td><td><?php echo $stat_enseignants['actifs']; ?></td><td><?php echo $stat_enseignants['inactifs']; ?></td><td><?php echo $stat_enseignants['taux_activite']; ?>%</td></tr>
                            <tr><td>Personnel Administratif</td><td><?php echo $stat_personnel['total']; ?></td><td><?php echo $stat_personnel['actifs']; ?></td><td><?php echo $stat_personnel['inactifs']; ?></td><td><?php echo $stat_personnel['taux_activite']; ?>%</td></tr>
                            <tr><td>Utilisateurs</td><td><?php echo $stat_utilisateurs['total']; ?></td><td><?php echo $stat_utilisateurs['actifs']; ?></td><td><?php echo $stat_utilisateurs['inactifs']; ?></td><td><?php echo $stat_utilisateurs['taux_activite']; ?>%</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1">
            <!-- Calendrier -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 cm-card-hover">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-lg font-bold text-green-800">Calendrier</h2>
                    <div class="flex space-x-2">
                        <button id="prevMonthBtn" class="w-8 h-8 rounded-md bg-blue-100 flex items-center justify-center text-blue-600 hover:bg-blue-200"><i class="fas fa-chevron-left text-xs"></i></button>
                        <button id="nextMonthBtn" class="w-8 h-8 rounded-md bg-blue-100 flex items-center justify-center text-blue-600 hover:bg-blue-200"><i class="fas fa-chevron-right text-xs"></i></button>
                    </div>
                </div>
                <div class="text-center mb-4"><h3 id="currentMonthLabel" class="text-md font-medium text-blue-600"></h3></div>
                <div class="calendar-header"><div>Lu</div><div>Ma</div><div>Me</div><div>Je</div><div>Ve</div><div>Sa</div><div>Di</div></div>
                <div id="calendarDaysGrid" class="calendar-grid"></div>
            </div>

            <!-- Activités récentes -->
            <div class="bg-white rounded-xl shadow-lg p-6 cm-card-hover">
                <h2 class="text-lg font-bold text-green-800 mb-4">Activités récentes</h2>
                <div class="space-y-4">
                    <?php if (!empty($GLOBALS['activites_recentes'])): ?>
                        <?php foreach ($GLOBALS['activites_recentes'] as $activite): ?>
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 rounded-full <?php echo $activite['type'] === 'utilisateur' ? 'bg-blue-100' : 'bg-green-100'; ?> flex items-center justify-center flex-shrink-0">
                                <i class="fas <?php echo $activite['type'] === 'utilisateur' ? 'fa-user text-blue-600' : 'fa-cog text-green-600'; ?> text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-blue-600"><?php echo htmlspecialchars($activite['description']); ?></p>
                                <p class="text-xs text-blue-600"><?php echo htmlspecialchars($activite['date']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8 text-blue-600">
                            <i class="fas fa-history text-4xl mb-2"></i>
                            <p>Aucune activité récente</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const chartData = {
        etudiants:  { labels:['Jan','Fév','Mar','Avr','Mai','Juin'], data:[120,150,180,200,220,250] },
        enseignants:{ labels:['Jan','Fév','Mar','Avr','Mai','Juin'], data:[20,25,30,35,40,45] },
        utilisateurs:{ labels:['Jan','Fév','Mar','Avr','Mai','Juin'], data:[140,175,210,235,260,295] },
        personnels:  { labels:['Jan','Fév','Mar','Avr','Mai','Juin'], data:[142,169,255,235,240,356] }
    };
    const ctx = document.getElementById('evolutionChartAdmin').getContext('2d');
    let chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.utilisateurs.labels,
            datasets: [{ label:'Utilisateurs', data:chartData.utilisateurs.data, borderColor:'#0F4C75', backgroundColor:'rgba(15,76,117,0.08)', fill:true, tension:0.4 }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ position:'top', labels:{ color:'#0F4C75', font:{ size:12 } } } },
            scales:{ y:{ beginAtZero:true }, x:{ grid:{ display:false } } }
        }
    });
    document.querySelectorAll('.btn-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.btn-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const type = btn.dataset.type;
            chart.data.labels = chartData[type].labels;
            chart.data.datasets[0].data  = chartData[type].data;
            chart.data.datasets[0].label = type.charAt(0).toUpperCase() + type.slice(1);
            chart.update();
        });
    });

    // Calendrier
    let curDate = new Date(), selDate = new Date();
    function renderCalendar() {
        const y = curDate.getFullYear(), m = curDate.getMonth();
        const monthNames = ["Janvier","Février","Mars","Avril","Mai","Juin","Juillet","Août","Septembre","Octobre","Novembre","Décembre"];
        document.getElementById('currentMonthLabel').textContent = monthNames[m] + ' ' + y;
        const firstDay   = new Date(y, m, 1);
        const totalDays  = new Date(y, m+1, 0).getDate();
        const startDay   = firstDay.getDay() || 7;
        const prevLast   = new Date(y, m, 0).getDate();
        const prevDays   = Array.from({length: startDay-1}, (_,i) => prevLast-i).reverse();
        const remain     = 42 - (prevDays.length + totalDays);
        const nextDays   = Array.from({length: remain}, (_,i) => i+1);
        const all = [
            ...prevDays.map(d => ({d, cur:false})),
            ...Array.from({length:totalDays}, (_,i) => ({d:i+1, cur:true})),
            ...nextDays.map(d => ({d, cur:false}))
        ];
        const now = new Date();
        document.getElementById('calendarDaysGrid').innerHTML = all.map(({d, cur}) => {
            const isToday = cur && d===now.getDate() && m===now.getMonth() && y===now.getFullYear();
            const isSel   = cur && d===selDate.getDate() && m===selDate.getMonth() && y===selDate.getFullYear();
            let cls = 'calendar-day';
            if (!cur)    cls += ' other-month';
            if (isToday) cls += ' today';
            if (isSel)   cls += ' selected';
            return `<div class="${cls}" data-y="${y}" data-m="${m}" data-d="${d}">${d}</div>`;
        }).join('');
        document.querySelectorAll('.calendar-day').forEach(el => {
            el.addEventListener('click', () => {
                selDate = new Date(+el.dataset.y, +el.dataset.m, +el.dataset.d);
                renderCalendar();
            });
        });
    }
    renderCalendar();
    document.getElementById('prevMonthBtn').addEventListener('click', () => { curDate.setMonth(curDate.getMonth()-1); renderCalendar(); });
    document.getElementById('nextMonthBtn').addEventListener('click', () => { curDate.setMonth(curDate.getMonth()+1); renderCalendar(); });
})();
</script>

<?php /* ═══════════════════════════════════════════════════════════════════════
       ██  SCOLARITÉ
       ═══════════════════════════════════════════════════════════════════════ */
elseif ($currentPage === 'dashboard_scolarite'): ?>

<div class="max-w-7xl mx-auto p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Tableau de bord Scolarité</h1>

    <!-- KPI cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm cm-card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Étudiants inscrits</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['etudiants']); ?></p>
                    <p class="text-xs text-gray-500">Total des inscriptions</p>
                </div>
                <div class="p-3 rounded-full bg-blue-100 text-blue-500"><i class="fas fa-users text-xl"></i></div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm cm-card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Réclamations en attente</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['reclamations_en_attente']); ?></p>
                    <p class="text-xs text-gray-500">À traiter</p>
                </div>
                <div class="p-3 rounded-full bg-blue-100 text-blue-500"><i class="fas fa-exclamation-triangle text-xl"></i></div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm cm-card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Paiements complets</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['paiements_complets']); ?></p>
                    <p class="text-xs text-gray-500">Validés</p>
                </div>
                <div class="p-3 rounded-full bg-green-100 text-green-500"><i class="fas fa-check-circle text-xl"></i></div>
            </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm cm-card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Paiements partiels</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['paiements_partiels']); ?></p>
                    <p class="text-xs text-gray-500">En attente: <?php echo number_format($stats['montant_attente'],0,',',' '); ?> FCFA</p>
                </div>
                <div class="p-3 rounded-full bg-blue-100 text-blue-500"><i class="fas fa-money-bill text-xl"></i></div>
            </div>
        </div>
    </div>

    <!-- Graphique + Stats réclamations/paiements -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-4 rounded-lg shadow-sm">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Inscriptions par niveau d'études</h2>
            <canvas id="inscriptionsChart" height="200"></canvas>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Statistiques des réclamations</h2>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="p-4 rounded-lg cm-gradient-yellow text-white">
                    <p class="text-sm font-medium">En attente</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_en_attente']); ?></p>
                </div>
                <div class="p-4 rounded-lg cm-gradient-green text-white">
                    <p class="text-sm font-medium">Résolues</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_resolues']); ?></p>
                </div>
                <div class="p-4 rounded-lg cm-gradient-red text-white">
                    <p class="text-sm font-medium">Rejetées</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_rejetees']); ?></p>
                </div>
                <div class="p-4 rounded-lg cm-gradient-blue text-white">
                    <p class="text-sm font-medium">Total</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['reclamations_total']); ?></p>
                </div>
            </div>
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Statistiques des paiements</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 rounded-lg cm-gradient-blue text-white">
                    <p class="text-sm font-medium">Complets</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['paiements_complets']); ?></p>
                </div>
                <div class="p-4 rounded-lg cm-gradient-red text-white">
                    <p class="text-sm font-medium">Partiels</p>
                    <p class="text-2xl font-bold"><?php echo number_format($stats['paiements_partiels']); ?></p>
                </div>
                <div class="p-4 rounded-lg cm-gradient-green text-white">
                    <p class="text-sm font-medium">Montant perçu</p>
                    <p class="text-lg font-bold"><?php echo number_format($stats['montant_percu'],0,',',' '); ?> F</p>
                </div>
                <div class="p-4 rounded-lg cm-gradient-yellow text-white">
                    <p class="text-sm font-medium">En attente</p>
                    <p class="text-lg font-bold"><?php echo number_format($stats['montant_attente'],0,',',' '); ?> F</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const ctx = document.getElementById('inscriptionsChart').getContext('2d');
    const niveauLabels = [], inscriptionsData = [];
    <?php foreach ($inscriptionsParNiveau as $niveau): ?>
        niveauLabels.push('<?php echo addslashes($niveau['niveau']); ?>');
        inscriptionsData.push(<?php echo intval($niveau['total']); ?>);
    <?php endforeach; ?>
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: niveauLabels,
            datasets: [{ label:"Nombre d'inscriptions", data:inscriptionsData,
                backgroundColor:['rgba(15,76,117,.5)','rgba(16,185,129,.5)','rgba(15,76,117,.5)','rgba(15,76,117,.5)','rgba(16,185,129,.5)'],
                borderColor:['#0F4C75','#10b981','#0F4C75','#0F4C75','#10b981'], borderWidth:1 }]
        },
        options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 } } } }
    });
})();
</script>

<?php /* ═══════════════════════════════════════════════════════════════════════
       ██  SECRÉTARIAT
       ═══════════════════════════════════════════════════════════════════════ */
elseif ($currentPage === 'dashboard_secretaire'): ?>

<div class="max-w-7xl mx-auto p-6 md:p-8">
    <!-- En-tête -->
    <header class="flex items-center justify-between mb-8">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Tableau de bord Secrétariat</h1>
        <div class="flex items-center space-x-4 text-gray-600 text-sm">
            <span><?php echo date('d/m/Y'); ?></span>
            <span class="text-gray-400">|</span>
            <span><?php echo date('H:i'); ?></span>
        </div>
    </header>

    <!-- KPI cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4 cm-card-hover">
            <div class="bg-blue-100 p-3 rounded-full text-blue-600">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
            </div>
            <div>
                <div class="text-xl font-bold text-gray-900"><?php echo number_format($stats['etudiants']); ?></div>
                <div class="text-gray-500 text-sm">Étudiants Inscrits</div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4 cm-card-hover">
            <div class="bg-green-100 p-3 rounded-full text-green-600">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
            </div>
            <div>
                <div class="text-xl font-bold text-gray-900"><?php echo number_format($stats['paiements_complets']); ?></div>
                <div class="text-gray-500 text-sm">Étudiants Actifs</div>
            </div>
        </div>
        <div class="cm-gradient-red p-6 rounded-xl shadow-lg flex items-center space-x-4 text-white cm-card-hover">
            <div class="bg-white bg-opacity-30 p-3 rounded-full">
                <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            </div>
            <div>
                <div class="text-xl font-bold"><?php echo number_format($stats['reclamations_en_attente']); ?></div>
                <div class="text-sm text-white text-opacity-80">Réclamations en attente</div>
            </div>
        </div>
        <div class="cm-gradient-orange p-6 rounded-xl shadow-lg flex items-center space-x-4 text-white cm-card-hover">
            <div class="bg-white bg-opacity-30 p-3 rounded-full">
                <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path></svg>
            </div>
            <div>
                <div class="text-sm font-bold">
                    <?php
                    $niveauxAff = [];
                    foreach ($inscriptionsParNiveau as $n) $niveauxAff[] = $n['niveau'].': '.$n['total'];
                    echo implode(' | ', $niveauxAff);
                    ?>
                </div>
                <div class="text-sm text-white text-opacity-80">Répartition par Niveau</div>
            </div>
        </div>
    </div>

    <!-- Analytique + Activités -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl shadow-lg flex flex-col items-center">
            <h2 class="text-lg font-semibold text-gray-900 mb-4 text-center">Analytique des Étudiants</h2>
            <div class="donut-chart-container mb-6">
                <div class="donut-chart-inner">
                    <span class="text-2xl font-bold text-gray-900"><?php echo $pourcentageReussite; ?>%</span>
                    <span class="text-gray-500 text-xs">Paiements complets</span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm w-full max-w-xs">
                <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-violet-500 mr-2"></span>Paiements complets</div>
                <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-indigo-500 mr-2"></span>Paiements partiels</div>
                <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-amber-300 mr-2"></span>Réclamations</div>
                <div class="flex items-center"><span class="w-3 h-3 rounded-full bg-red-400 mr-2"></span>Total inscriptions</div>
            </div>
            <div class="mt-4 text-center">
                <div class="text-sm text-gray-600">Montant total perçu</div>
                <div class="text-lg font-bold text-green-600"><?php echo number_format($montantTotalPercu,0,',',' '); ?> FCFA</div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Activités du Secrétariat</h2>
            <div class="space-y-4">
                <?php if (!empty($activitesRecentes)): ?>
                    <?php foreach ($activitesRecentes as $act): ?>
                    <div class="flex items-start space-x-3">
                        <div class="w-10 h-10 flex-shrink-0 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L14 11.586V8a6 6 0 00-6-6zM12 15.5V14H8v1.5a2 2 0 104 0z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div>
                            <p class="text-gray-900 font-medium">Inscription traitée</p>
                            <p class="text-gray-600 text-sm">Dossier de <?php echo htmlspecialchars($act['prenom_etu'].' '.$act['nom_etu']); ?> (<?php echo htmlspecialchars($act['lib_niv_etude']); ?>)</p>
                            <span class="text-gray-400 text-xs"><?php echo date('d/m/Y H:i', strtotime($act['date_inscription'])); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-gray-500 py-4"><p>Aucune activité récente</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Suivi des réclamations -->
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Suivi des Réclamations</h2>
        <div class="flex mb-4 text-sm border-b border-gray-200">
            <button class="py-2 px-4 text-gray-700 font-medium border-b-2 border-indigo-500 -mb-px filter-btn-sec" data-target="sec-recentes">Récentes</button>
            <button class="py-2 px-4 text-gray-500 hover:text-gray-700 filter-btn-sec" data-target="sec-attente">En attente</button>
            <button class="py-2 px-4 text-gray-500 hover:text-gray-700 filter-btn-sec" data-target="sec-resolues">Résolues</button>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Étudiant</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                    </tr>
                </thead>
                <?php
                $mapSections = [
                    'sec-recentes' => $reclamationsRecentes,
                    'sec-attente'  => array_values(array_filter($reclamationsRecentes, fn($r) => $r['statut_reclamation'] === 'En attente')),
                    'sec-resolues' => array_values(array_filter($reclamationsRecentes, fn($r) => $r['statut_reclamation'] === 'Résolue')),
                ];
                foreach ($mapSections as $sectionId => $rows): ?>
                <tbody id="<?php echo $sectionId; ?>" class="rec-section <?php echo $sectionId === 'sec-recentes' ? '' : 'hidden'; ?> bg-white divide-y divide-gray-200">
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $rec): ?>
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo date('d/m/Y', strtotime($rec['date_creation'])); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($rec['nom_etu'].' '.$rec['prenom_etu']); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($rec['type_reclamation']); ?></td>
                            <td class="px-4 py-3">
                                <?php
                                $cls = match($rec['statut_reclamation']) {
                                    'En attente' => 'bg-yellow-100 text-yellow-800',
                                    'Résolue'    => 'bg-green-100 text-green-800',
                                    default      => 'bg-gray-100 text-gray-800',
                                };
                                ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $cls; ?>"><?php echo htmlspecialchars($rec['statut_reclamation']); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500">Aucune réclamation</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    const btns = document.querySelectorAll('.filter-btn-sec');
    btns.forEach(btn => {
        btn.addEventListener('click', () => {
            btns.forEach(b => { b.classList.remove('text-gray-700','font-medium','border-b-2','border-indigo-500','-mb-px'); b.classList.add('text-gray-500'); });
            btn.classList.add('text-gray-700','font-medium','border-b-2','border-indigo-500','-mb-px'); btn.classList.remove('text-gray-500');
            document.querySelectorAll('.rec-section').forEach(s => s.classList.add('hidden'));
            document.getElementById(btn.dataset.target).classList.remove('hidden');
        });
    });
})();
</script>

<?php /* ═══════════════════════════════════════════════════════════════════════
       ██  COMMISSION
       ═══════════════════════════════════════════════════════════════════════ */
elseif ($currentPage === 'dashboard_commission'): ?>

<div class="max-w-7xl mx-auto p-6">
    <!-- KPI cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="stat-card-commission bg-white rounded-lg shadow p-6 fade-in">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Total Comptes Rendus</p><p class="metric-value"><?php echo $totalRapports; ?></p></div>
                <div class="p-3 rounded-full bg-blue-100"><i class="fas fa-file-alt text-blue-600 text-2xl"></i></div>
            </div>
        </div>
        <div class="stat-card-commission bg-white rounded-lg shadow p-6 fade-in">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Taux de Validation</p><p class="metric-value"><?php echo $tauxValidation; ?>%</p></div>
                <div class="p-3 rounded-full bg-green-100"><i class="fas fa-check-circle text-green-600 text-2xl"></i></div>
            </div>
        </div>
        <div class="stat-card-commission bg-white rounded-lg shadow p-6 fade-in">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">Temps Moyen</p><p class="metric-value"><?php echo $tempsMoyen; ?>j</p></div>
                <div class="p-3 rounded-full bg-blue-100"><i class="fas fa-clock text-blue-600 text-2xl"></i></div>
            </div>
        </div>
        <div class="stat-card-commission bg-white rounded-lg shadow p-6 fade-in">
            <div class="flex items-center justify-between">
                <div><p class="text-sm font-medium text-gray-600">En Attente</p><p class="metric-value"><?php echo $enAttente; ?></p></div>
                <div class="p-3 rounded-full bg-blue-100"><i class="fas fa-hourglass-half text-blue-600 text-2xl"></i></div>
            </div>
        </div>
    </div>

    <!-- Tableau détails rapports -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-8">
        <h3 class="text-gray-900 text-lg font-semibold mb-4"><i class="fas fa-list-alt text-green-500 mr-2"></i>Détails des Performances</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rapport</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Évaluateur</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Étudiant</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Temps</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($rapportsDetails)): ?>
                        <?php foreach ($rapportsDetails as $rapport): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $rapport['statut'] === 'valider' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>"><?php echo ucfirst($rapport['statut']); ?></span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo htmlspecialchars($rapport['titre']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo htmlspecialchars($rapport['prenom_enseignant'].' '.$rapport['nom_enseignant']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo htmlspecialchars($rapport['prenom_etudiant'].' '.$rapport['nom_etudiant']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo $rapport['temps_traitement'] ?? 0; ?> j</td>
                            <td class="px-4 py-2 text-sm font-medium">
                                <button class="text-blue-600 hover:text-blue-900 mr-2"><i class="fas fa-eye"></i></button>
                                <button class="text-green-600 hover:text-green-900"><i class="fas fa-download"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500"><i class="fas fa-table text-2xl mb-2 block"></i>Aucun rapport disponible</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Graphiques -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6 fade-in">
            <h3 class="text-lg font-semibold text-gray-800 mb-4"><i class="fas fa-chart-line text-blue-600 mr-2"></i>Évolution des Comptes Rendus</h3>
            <div class="chart-container"><canvas id="evolutionChartCom"></canvas></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 fade-in">
            <h3 class="text-lg font-semibold text-gray-800 mb-4"><i class="fas fa-chart-pie text-green-600 mr-2"></i>Répartition par Statut</h3>
            <div class="chart-container"><canvas id="statusChartCom"></canvas></div>
        </div>
    </div>

    <!-- Activités récentes -->
    <div class="bg-white rounded-lg shadow p-6 fade-in mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4"><i class="fas fa-history text-blue-600 mr-2"></i>Activité Récente</h3>
        <div class="space-y-3">
            <?php if (!empty($activitesData)): ?>
                <?php foreach ($activitesData as $act): ?>
                <div class="flex items-center space-x-3 p-2 hover:bg-gray-50 rounded-lg">
                    <div class="p-2 <?php echo $act['statut'] === 'valider' ? 'bg-green-100' : 'bg-red-100'; ?> rounded-full">
                        <i class="fas fa-<?php echo $act['statut'] === 'valider' ? 'check text-green-600' : 'times text-blue-600'; ?> text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium">Rapport <?php echo ucfirst($act['statut']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($act['titre']); ?> — <?php echo htmlspecialchars($act['prenom_etudiant'].' '.$act['nom_etudiant']); ?></p>
                        <p class="text-xs text-gray-400">Par <?php echo htmlspecialchars($act['prenom_enseignant'].' '.$act['nom_enseignant']); ?></p>
                    </div>
                    <span class="text-xs text-gray-400"><?php echo getTimeAgo($act['date_validation']); ?></span>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-gray-500 py-4"><i class="fas fa-history text-2xl mb-2 block"></i>Aucune activité récente</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tableau évaluations -->
    <div class="bg-white rounded-lg shadow p-6 fade-in">
        <h3 class="text-lg font-semibold text-gray-800 mb-4"><i class="fas fa-table text-gray-600 mr-2"></i>Détails des Évaluations</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Décision</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rapport</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Évaluateur</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Commentaire</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($dashboardData['evaluations_rapports'])): ?>
                        <?php foreach ($dashboardData['evaluations_rapports'] as $eval): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm">
                                <?php
                                $decCls = match($eval['decision_evaluation'] ?? '') {
                                    'valider'  => 'bg-green-100 text-green-800',
                                    'rejeter'  => 'bg-red-100 text-red-800',
                                    default    => 'bg-blue-100 text-blue-800',
                                };
                                ?>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $decCls; ?>"><?php echo ucfirst($eval['decision_evaluation']); ?></span>
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo htmlspecialchars($eval['nom_rapport'] ?? $eval['id_rapport']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo htmlspecialchars(($eval['prenom_enseignant'] ?? '').' '.($eval['nom_enseignant'] ?? $eval['id_evaluateur'])); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo htmlspecialchars($eval['commentaire']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-600"><?php echo $eval['date_evaluation']; ?></td>
                            <td class="px-4 py-2 text-sm font-medium">
                                <button class="text-blue-600 hover:text-blue-900 mr-2"><i class="fas fa-eye"></i></button>
                                <button class="text-green-600 hover:text-green-900"><i class="fas fa-download"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">Aucune évaluation</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    const evData   = { labels:<?php echo json_encode($evolutionLabels); ?>, datasets:[
        { label:'Finalisés', data:<?php echo json_encode($evolutionFinalises); ?>, borderColor:'#0F4C75', backgroundColor:'rgba(15,76,117,.08)', tension:.4, fill:true },
        { label:'Rejetés',   data:<?php echo json_encode($evolutionRejetes); ?>,  borderColor:'#10b981', backgroundColor:'rgba(16,185,129,.06)', tension:.4, fill:true }
    ]};
    const stData   = { labels:<?php echo json_encode($statusLabels); ?>, datasets:[{ data:<?php echo json_encode($statusData); ?>, backgroundColor:<?php echo json_encode($statusColors); ?>, borderWidth:0 }]};

    new Chart(document.getElementById('evolutionChartCom').getContext('2d'), { type:'line', data:evData,
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{ usePointStyle:true, padding:20 } } }, scales:{ y:{ beginAtZero:true }, x:{ grid:{ display:false } } } } });
    new Chart(document.getElementById('statusChartCom').getContext('2d'), { type:'doughnut', data:stData,
        options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom', labels:{ usePointStyle:true, padding:15 } } }, cutout:'60%' } });

    function animateMetrics() {
        document.querySelectorAll('.metric-value').forEach((el, i) => {
            const final = el.textContent;
            el.textContent = '0';
            const target = parseFloat(final);
            const hasPct = final.includes('%'), hasJ = final.includes('j');
            const inc = hasPct ? 1 : hasJ ? .1 : 1;
            let cur = 0;
            setTimeout(() => {
                const t = setInterval(() => {
                    cur += inc;
                    if (cur >= target) { cur = target; clearInterval(t); }
                    el.textContent = hasPct ? Math.round(cur)+'%' : hasJ ? cur.toFixed(1)+'j' : Math.round(cur);
                }, 50);
            }, i * 200);
        });
    }
    animateMetrics();
    setInterval(() => {}, 300000);
})();
</script>

<?php /* ═══════════════════════════════════════════════════════════════════════
       ██  ENSEIGNANT
       ═══════════════════════════════════════════════════════════════════════ */
elseif ($currentPage === 'dashboard_enseignant'): ?>

<div class="container mx-auto px-4 max-w-screen-xl py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-gray-600 text-3xl font-bold">Bonjour, Professeur!</h1>
            <p class="text-gray-400 text-sm mt-1">Bienvenue à nouveau sur votre tableau de bord.</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500"><?php echo date('d/m/Y'); ?></p>
            <p class="text-sm text-gray-400"><?php echo date('H:i'); ?></p>
        </div>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="cm-card-hover cm-gradient-blue rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-white text-sm font-medium">Étudiants (vos cours)</h3>
                <i class="fas fa-users text-white text-xl opacity-80"></i>
            </div>
            <div class="text-white text-3xl font-bold mb-2"><?php echo $total_etudiants; ?></div>
            <div class="text-white text-xs opacity-80">Étudiants suivant vos cours</div>
        </div>
        <div class="cm-card-hover cm-gradient-orange rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-white text-sm font-medium">UE prises en charge</h3>
                <i class="fas fa-book text-white text-xl opacity-80"></i>
            </div>
            <div class="text-white text-3xl font-bold mb-2"><?php echo $total_ues; ?></div>
            <div class="text-white text-xs opacity-80">Total UE</div>
        </div>
        <div class="cm-card-hover cm-gradient-teal rounded-xl shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-white text-sm font-medium">ECUE pris en charge</h3>
                <i class="fas fa-layer-group text-white text-xl opacity-80"></i>
            </div>
            <div class="text-white text-3xl font-bold mb-2"><?php echo $total_ecues; ?></div>
            <div class="text-white text-xs opacity-80">Total ECUE</div>
        </div>
    </div>

    <!-- Mes cours -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-gray-900 text-xl font-bold mb-4 pb-3 border-b border-gray-200">
            <i class="fas fa-book text-blue-500 mr-2"></i>Mes Cours
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (!empty($mes_cours)): ?>
                <?php foreach ($mes_cours as $cours): ?>
                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-gray-900 font-semibold"><?php echo htmlspecialchars($cours['nom']); ?></h3>
                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full"><?php echo htmlspecialchars($cours['niveau']); ?></span>
                    </div>
                    <p class="text-gray-500 text-sm"><?php echo $cours['nombre_etudiants']; ?> étudiants inscrits</p>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center text-gray-500 py-8">
                    <i class="fas fa-chalkboard-teacher text-4xl mb-4 block"></i>
                    <p>Aucun cours assigné</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>
