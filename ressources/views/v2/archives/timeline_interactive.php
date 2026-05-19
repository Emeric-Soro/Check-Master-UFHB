<?php
/**
 * Timeline Interactive du Parcours Etudiant
 * Etapes: Inscription -> Stage -> Depot Rapport -> Validation -> Candidature
 *         -> Programmation -> Soutenance -> CR -> Resultat
 * Icones FontAwesome par etape
 * Statut colore: vert (fait), orange (en cours), gris (a venir)
 * Animation CSS au scroll
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../../app/utils/permissions_helper.php';

if (!canView('archives_etudiants')) {
    $_SESSION['error_message'] = "Acces refuse.";
    header('Location: ?page=dashboard');
    exit;
}

$data = $GLOBALS['timeline_data'] ?? ['matricule' => '', 'evenements' => []];
$error = $GLOBALS['timeline_error'] ?? '';
$matricule = (string) ($data['matricule'] ?? '');
$evenements = $data['evenements'] ?? [];

// Definir les etapes du parcours avec leurs icones et couleurs
$etapesParcours = [
    'inscription' => [
        'label' => 'Inscription',
        'icon' => 'fa-user-graduate',
        'color' => '#3b82f6',
    ],
    'stage' => [
        'label' => 'Stage',
        'icon' => 'fa-briefcase',
        'color' => '#8b5cf6',
    ],
    'depot_rapport' => [
        'label' => 'Depot Rapport',
        'icon' => 'fa-file-upload',
        'color' => '#10b981',
    ],
    'validation' => [
        'label' => 'Validation',
        'icon' => 'fa-check-circle',
        'color' => '#059669',
    ],
    'candidature' => [
        'label' => 'Candidature',
        'icon' => 'fa-envelope',
        'color' => '#f59e0b',
    ],
    'programmation' => [
        'label' => 'Programmation',
        'icon' => 'fa-calendar-check',
        'color' => '#f97316',
    ],
    'soutenance' => [
        'label' => 'Soutenance',
        'icon' => 'fa-chalkboard-teacher',
        'color' => '#ef4444',
    ],
    'compte_rendu' => [
        'label' => 'Compte Rendu',
        'icon' => 'fa-file-alt',
        'color' => '#ec4899',
    ],
    'resultat' => [
        'label' => 'Resultat',
        'icon' => 'fa-trophy',
        'color' => '#14b8a6',
    ],
];

// Determiner le statut de chaque etape
$etapeStatus = [];
$foundActive = false;
foreach (array_keys($etapesParcours) as $etapeKey) {
    $found = false;
    foreach ($evenements as $evt) {
        if (($evt['type'] ?? '') === $etapeKey) {
            $found = true;
            break;
        }
    }
    if (!$foundActive && $found) {
        $etapeStatus[$etapeKey] = 'done'; // vert
    } elseif (!$foundActive && !$found) {
        $etapeStatus[$etapeKey] = 'pending'; // gris
        if (!in_array($etapeKey, ['resultat'], true)) {
            $foundActive = true;
        }
    } else {
        $etapeStatus[$etapeKey] = $found ? 'done' : 'pending';
    }
}

// Si aucune etape faite, la premiere est "en cours"
$hasDone = in_array('done', $etapeStatus, true);
if (!$hasDone && !empty($etapeStatus)) {
    $firstKey = array_key_first($etapeStatus);
    $etapeStatus[$firstKey] = 'current';
}
?>
<div class="cm-timeline-interactive cm-prd3-screen">
    <div class="cm-page-header cm-mb-4">
        <h1 class="cm-page-title">
            <i class="fas fa-stream cm-text-primary"></i>
            Timeline Parcours Etudiant
        </h1>
        <p class="cm-page-subtitle">Parcours chronologique interactif</p>
    </div>

    <?php if ($matricule === ''): ?>
        <div class="cm-crud-wrapper">
            <div class="cm-pole-superieur">
                <form method="GET" class="cm-form cm-grid-3">
                    <input type="hidden" name="page" value="timeline_parcours_etudiant">
                    <?php
                    cm_component('form/input-text', [
                        'name' => 'num_etu',
                        'label' => 'Matricule etudiant',
                        'value' => $matricule,
                        'placeholder' => 'Ex: 2024-1234',
                        'required' => true,
                    ]);
                    ?>
                    <div class="cm-form-group cm-flex-end">
                        <button type="submit" class="cm-btn is-primary is-sm cm-mt-6">
                            <i class="fas fa-search"></i> Voir le parcours
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php elseif ($error !== ''): ?>
        <div class="cm-alert is-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php else: ?>
        <!-- En-tete etudiant -->
        <div class="cm-card cm-p-4 cm-mb-4">
            <div class="cm-flex cm-items-center cm-gap-3">
                <i class="fas fa-user-circle cm-text-3xl cm-text-primary"></i>
                <div>
                    <h2 class="cm-text-lg cm-text-semibold">Etudiant: <?php echo htmlspecialchars($matricule, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p class="cm-text-muted"><?php echo count($evenements); ?> evenement(s) enregistre(s)</p>
                </div>
                <a href="?page=archives_etudiants" class="cm-btn is-light is-sm cm-ml-auto">
                    <i class="fas fa-arrow-left"></i> Archives
                </a>
            </div>
        </div>

        <!-- Timeline horizontale (etapes resume) -->
        <div class="cm-card cm-p-4 cm-mb-4 cm-timeline-steps-wrapper">
            <div class="cm-timeline-steps">
                <?php foreach ($etapesParcours as $key => $etape):
                    $status = $etapeStatus[$key] ?? 'pending';
                    $isDone = $status === 'done';
                    $isCurrent = $status === 'current';
                ?>
                    <div class="cm-timeline-step <?php echo $isDone ? 'is-done' : ($isCurrent ? 'is-current' : 'is-pending'); ?>"
                         data-etape="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="cm-step-icon" style="<?php echo $isDone ? 'background: ' . $etape['color'] . ';' : ''; ?>">
                            <i class="fas <?php echo $etape['icon']; ?>"></i>
                        </div>
                        <div class="cm-step-label"><?php echo htmlspecialchars($etape['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="cm-step-status">
                            <?php if ($isDone): ?>
                                <span class="cm-badge is-success">Fait</span>
                            <?php elseif ($isCurrent): ?>
                                <span class="cm-badge is-warning">En cours</span>
                            <?php else: ?>
                                <span class="cm-badge is-light">A venir</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Timeline verticale detaillee -->
        <div class="cm-card">
            <div class="cm-card-header cm-p-3 cm-bg-light">
                <h3 class="cm-text-semibold">
                    <i class="fas fa-list"></i>
                    Evenements detailles
                </h3>
            </div>
            <div class="cm-p-4">
                <?php if (empty($evenements)): ?>
                    <div class="cm-alert is-info">Aucun evenement enregistre pour cet etudiant.</div>
                <?php else: ?>
                    <div class="cm-timeline-vertical-detailed">
                        <?php foreach ($evenements as $index => $evt):
                            $type = (string) ($evt['type'] ?? '');
                            $etape = $etapesParcours[$type] ?? ['label' => $type, 'icon' => 'fa-circle', 'color' => '#6b7280'];
                            $date = (string) ($evt['date'] ?? '');
                            $titre = (string) ($evt['titre'] ?? '');
                            $description = (string) ($evt['description'] ?? '');
                            $isLast = $index === count($evenements) - 1;
                        ?>
                            <div class="cm-timeline-event cm-animate-on-scroll"
                                 data-type="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>"
                                 style="--event-color: <?php echo $etape['color']; ?>; animation-delay: <?php echo $index * 0.1; ?>s;">
                                <div class="cm-event-dot" style="background: <?php echo $etape['color']; ?>;">
                                    <i class="fas <?php echo $etape['icon']; ?>"></i>
                                </div>
                                <div class="cm-event-line" style="<?php echo $isLast ? 'display: none;' : ''; ?>"></div>
                                <div class="cm-event-card">
                                    <div class="cm-event-date">
                                        <i class="fas fa-clock"></i>
                                        <?php echo $date !== '' ? date('d/m/Y H:i', strtotime($date)) : 'Date inconnue'; ?>
                                    </div>
                                    <h4 class="cm-event-title"><?php echo htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <?php if ($description !== ''): ?>
                                        <p class="cm-event-description"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* ===== Timeline Interactive Styles ===== */

/* Etapes horizontales */
.cm-timeline-steps-wrapper {
    overflow-x: auto;
}
.cm-timeline-steps {
    display: flex;
    gap: 1rem;
    min-width: max-content;
    padding: 1rem 0;
}
.cm-timeline-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    min-width: 90px;
    position: relative;
}
.cm-timeline-step::after {
    content: '';
    position: absolute;
    top: 24px;
    left: calc(50% + 25px);
    width: calc(100% - 50px);
    height: 3px;
    background: #e5e7eb;
    z-index: 0;
}
.cm-timeline-step:last-child::after {
    display: none;
}
.cm-timeline-step.is-done::after {
    background: #10b981;
}
.cm-timeline-step.is-current::after {
    background: linear-gradient(90deg, #f59e0b 50%, #e5e7eb 50%);
}
.cm-step-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    color: white;
    background: #e5e7eb;
    position: relative;
    z-index: 1;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.cm-timeline-step.is-done .cm-step-icon {
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}
.cm-timeline-step.is-current .cm-step-icon {
    background: #f59e0b !important;
    animation: pulse 2s infinite;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}
.cm-timeline-step.is-pending .cm-step-icon {
    opacity: 0.5;
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
.cm-step-label {
    font-size: 11px;
    font-weight: 600;
    text-align: center;
    color: #374151;
}
.cm-step-status .cm-badge {
    font-size: 9px;
    padding: 2px 6px;
}

/* Timeline verticale detaillee */
.cm-timeline-vertical-detailed {
    position: relative;
    padding-left: 40px;
}
.cm-timeline-event {
    position: relative;
    padding-bottom: 30px;
    padding-left: 30px;
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s ease forwards;
}
.cm-animate-on-scroll {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeInUp 0.5s ease forwards;
}
@keyframes fadeInUp {
    to { opacity: 1; transform: translateY(0); }
}
.cm-event-dot {
    position: absolute;
    left: -48px;
    top: 4px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    z-index: 2;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.cm-event-line {
    position: absolute;
    left: -31px;
    top: 40px;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
    z-index: 1;
}
.cm-event-card {
    background: #f9fafb;
    border-radius: 8px;
    padding: 12px 16px;
    border-left: 3px solid var(--event-color, #6b7280);
    transition: box-shadow 0.3s ease;
}
.cm-event-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.cm-event-date {
    font-size: 11px;
    color: #6b7280;
    margin-bottom: 4px;
}
.cm-event-date i {
    margin-right: 4px;
}
.cm-event-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
    color: #111827;
}
.cm-event-description {
    font-size: 13px;
    color: #6b7280;
    margin: 0;
}
</style>

<script>
(function() {
    'use strict';

    // Animation au scroll pour les evenements
    var events = document.querySelectorAll('.cm-timeline-event');
    if (events.length > 0) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.style.animationPlayState = 'running';
                }
            });
        }, { threshold: 0.1 });

        events.forEach(function(el) {
            observer.observe(el);
        });
    }

    // Gestion du clic sur les etapes horizontales
    var steps = document.querySelectorAll('.cm-timeline-step');
    steps.forEach(function(step) {
        step.addEventListener('click', function() {
            var etape = step.getAttribute('data-etape') || '';
            if (etape) {
                var eventEl = document.querySelector('.cm-timeline-event[data-type="' + etape + '"]');
                if (eventEl) {
                    eventEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
})();
</script>
