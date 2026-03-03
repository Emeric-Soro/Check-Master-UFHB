<?php
$studentFile = $GLOBALS['studentFile'] ?? null;
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';

if (!$studentFile) {
    header('Location: ?page=admin_historique&error=not_found');
    exit;
}

function getMention($note) {
    if ($note === null) return null;
    if ($note >= 16) return ['text' => 'Très Bien', 'color' => 'text-purple-600', 'bg' => 'bg-purple-600'];
    if ($note >= 14) return ['text' => 'Bien', 'color' => 'text-blue-600', 'bg' => 'bg-blue-600'];
    if ($note >= 12) return ['text' => 'Assez Bien', 'color' => 'text-green-600', 'bg' => 'bg-green-600'];
    if ($note >= 10) return ['text' => 'Passable', 'color' => 'text-yellow-600', 'bg' => 'bg-yellow-500'];
    return ['text' => 'Ajourné', 'color' => 'text-red-600', 'bg' => 'bg-red-600'];
}

// --- Data Preparation ---
$soutenanceNote = $studentFile['soutenance']['note_soutenance'] ?? null;
$mention = getMention($soutenanceNote);

// Averages
$m1_avg = $studentFile['moyenne_m1'] ?? null;
$m2_s1_avg = $studentFile['moyenne_m2_s1'] ?? null;
$memo_avg = $soutenanceNote;

// Weighted average calculation based on documentation: (M1*2 + M2S1*3 + Memo*3) / 8
$coeff_m1 = 2;
$coeff_m2_s1 = 3;
$coeff_memo = 3;
$total_coeffs = $coeff_m1 + $coeff_m2_s1 + $coeff_memo; // 8

// Treat null grades as 0 for calculation purposes
$m1_calc = $m1_avg ?? 0;
$m2_s1_calc = $m2_s1_avg ?? 0;
$memo_calc = $memo_avg ?? 0;

$general_avg = ($m1_calc * $coeff_m1 + $m2_s1_calc * $coeff_m2_s1 + $memo_calc * $coeff_memo) / $total_coeffs;

// If all components were null, the average is null
if ($m1_avg === null && $m2_s1_avg === null && $memo_avg === null) {
    $general_avg = null;
}

$general_mention = getMention($general_avg);

// Timeline Data
$raw_timeline = [
    'Inscription' => ['date' => $studentFile['date_inscription'] ?? '2022-09-15', 'icon' => 'fa-user-check'],
    'Début de Stage' => ['date' => $studentFile['stage']['date_debut_stage'] ?? null, 'icon' => 'fa-briefcase'],
    'Validation Thème' => ['date' => $studentFile['rapport']['date_validation'] ?? null, 'icon' => 'fa-check-double'],
    'Soutenance' => ['date' => $studentFile['soutenance']['date_soutenance'] ?? null, 'icon' => 'fa-graduation-cap'],
    'Diplômé' => ['date' => ($soutenanceNote && $soutenanceNote >= 10) ? ($studentFile['soutenance']['date_soutenance'] ?? date('Y-m-d')) : null, 'icon' => 'fa-award']
];

$timeline_steps = [];
$last_valid_date_found = false;
// We reverse the array to find the last completed step from the end
$reversed_timeline = array_reverse($raw_timeline, true); 

foreach ($reversed_timeline as $title => $step) {
    $current_step = $step;
    if (!$last_valid_date_found && !empty($step['date'])) {
        $last_valid_date_found = true;
    }
    $current_step['is_completed'] = $last_valid_date_found;
    $timeline_steps[$title] = $current_step;
}
// Restore the original order
$timeline_steps = array_reverse($timeline_steps, true);

?>

<div class="bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8 font-sans">
    <div class="max-w-5xl mx-auto space-y-8">
        <div class="mb-6">
            <a href="?page=admin_historique" class="text-sm font-medium text-gray-600 hover:text-blue-600 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour à la liste
            </a>
        </div>

        <?php if ($messageSuccess): ?>
            <div id="success-notice" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg flex justify-between items-center shadow-sm">
                <span><?php echo htmlspecialchars($messageSuccess); ?></span>
                <button onclick="document.getElementById('success-notice').style.display='none'" class="text-green-700">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
             <div class="flex flex-col sm:flex-row items-start gap-6">
                <div class="flex-grow">
                    <span class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($studentFile['nom_etu'] . ' ' . $studentFile['prenom_etu']); ?></span>
                    <p class="text-gray-500">Matricule: <span class="font-medium"><?php echo htmlspecialchars($studentFile['num_etu']); ?></span></p>
                    <p class="text-gray-500">Année académique: <span class="font-medium"><?php echo htmlspecialchars($studentFile['soutenance']['annee_academique'] ?? $studentFile['rapport']['annee_academique'] ?? $studentFile['annee_academique'] ?? 'N/A'); ?></span></p>
                    <?php if($mention): ?>
                    <div class="mt-2 text-lg font-bold <?php echo $mention['color']; ?>">
                        ✅ Diplômé - Mention <?php echo $mention['text']; ?> (<?php echo htmlspecialchars(number_format($soutenanceNote, 2)); ?>/20)
                    </div>
                    <?php else: ?>
                    <div class="mt-2 text-lg font-bold text-gray-500">
                        Status: <span class="font-medium"><?php echo htmlspecialchars($studentFile['rapport']['statut_rapport'] ?? 'En cours'); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="bg-white rounded-2xl shadow-sm p-6">

            <ol class="relative border-l border-gray-200">
                <?php foreach($timeline_steps as $title => $step): ?>
                <li class="mb-10 ml-6">
                    <?php if($step['is_completed']): ?>
                    <span class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -left-4 ring-8 ring-white">
                        <i class="fas <?php echo $step['icon']; ?> text-blue-600"></i>
                    </span>
                    <span class="flex items-center mb-1 text-base font-semibold text-gray-900"><?php echo $title; ?>
                        <span class="text-green-500 bg-green-100 text-sm font-medium mr-2 px-2.5 py-0.5 rounded ml-3">Terminé</span>
                    </span>
                    <?php
                        $timelineDate = $step['date'] ?? null;
                        $hasTimelineDate = $timelineDate && !str_starts_with($timelineDate, '0000-00-00');
                    ?>
                    <?php if($hasTimelineDate): ?>
                        <time class="block mb-2 text-sm font-normal leading-none text-gray-400"><?php echo htmlspecialchars(date('d F Y', strtotime($timelineDate))); ?></time>
                    <?php else: ?>
                        <time class="block mb-2 text-sm font-normal leading-none text-gray-400">Date non spécifiée</time>
                    <?php endif; ?>
                    <?php else: ?>
                     <span class="absolute flex items-center justify-center w-8 h-8 bg-gray-100 rounded-full -left-4 ring-8 ring-white">
                        <i class="fas fa-hourglass-half text-gray-500"></i>
                    </span>
                    <span class="flex items-center mb-1 text-base font-semibold text-gray-500"><?php echo $title; ?></span>
                    <time class="block mb-2 text-sm font-normal leading-none text-gray-400">En attente</time>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
        
        <!-- Dossier Académique (Read-only) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column -->
            <div class="space-y-8">
                <!-- Encadrement -->
                <?php if (!empty($studentFile['encadrement']['directeur']) || !empty($studentFile['encadrement']['encadrant'])): ?>
                <div class="bg-white rounded-2xl shadow-sm p-6">

                    <dl class="text-sm space-y-2">
                        <?php if(!empty($studentFile['encadrement']['directeur'])): ?>
                        <div class="flex"><dt class="font-medium text-gray-500 w-32">Directeur</dt><dd class="text-gray-900"><?php echo htmlspecialchars($studentFile['encadrement']['directeur']['nom_enseignant'] . ' ' . $studentFile['encadrement']['directeur']['prenom_enseignant']); ?></dd></div>
                        <?php endif; ?>
                        <?php if(!empty($studentFile['encadrement']['encadrant'])): ?>
                        <div class="flex"><dt class="font-medium text-gray-500 w-32">Encadrant</dt><dd class="text-gray-900"><?php echo htmlspecialchars($studentFile['encadrement']['encadrant']['nom_enseignant'] . ' ' . $studentFile['encadrement']['encadrant']['prenom_enseignant']); ?></dd></div>
                        <?php endif; ?>
                    </dl>
                </div>
                <?php endif; ?>
                 <!-- Informations de Stage -->
                <?php if (!empty($studentFile['stage'])): ?>
                <div class="bg-white rounded-2xl shadow-sm p-6">

                    <dl class="text-sm space-y-2">
                        <div class="flex"><dt class="font-medium text-gray-500 w-32">Entreprise</dt><dd class="text-gray-900"><?php echo htmlspecialchars($studentFile['stage']['lib_entreprise'] ?? 'N/A'); ?></dd></div>
                        <div class="flex"><dt class="font-medium text-gray-500 w-32">Maître de stage</dt><dd class="text-gray-900"><?php echo htmlspecialchars($studentFile['stage']['encadrant_entreprise'] ?? 'N/A'); ?></dd></div>
                        <div class="flex"><dt class="font-medium text-gray-500 w-32">Période</dt><dd class="text-gray-900">Du <?php echo htmlspecialchars($studentFile['stage']['date_debut_stage'] ?? 'N/A'); ?> au <?php echo htmlspecialchars($studentFile['stage']['date_fin_stage'] ?? 'N/A'); ?></dd></div>
                    </dl>
                </div>
                <?php endif; ?>
            </div>
            <!-- Right Column -->
            <div class="space-y-8">
                <!-- Soutenance -->
                <?php if (!empty($studentFile['soutenance'])): ?>
                <div class="bg-white rounded-2xl shadow-sm p-6">

                    <dl class="text-sm space-y-2">
                         <?php
                            $rawDateSout = $studentFile['soutenance']['date_soutenance'] ?? null;
                            $rawHeureSout = $studentFile['soutenance']['heure_soutenance'] ?? null;
                            $hasDateSout = $rawDateSout && !str_starts_with($rawDateSout, '0000-00-00');
                            $hasHeureSout = $rawHeureSout && stripos($rawHeureSout, '00:00:00') === false;
                         ?>
                         <div class="flex"><dt class="font-medium text-gray-500 w-32">Date</dt><dd class="text-gray-900"><?php echo $hasDateSout ? htmlspecialchars(date('d/m/Y', strtotime($rawDateSout))) : 'N/A'; ?></dd></div>
                         <div class="flex"><dt class="font-medium text-gray-500 w-32">Heure</dt><dd class="text-gray-900"><?php echo $hasHeureSout ? htmlspecialchars(date('H:i', strtotime($rawHeureSout))) : 'N/A'; ?></dd></div>
                         <div class="flex"><dt class="font-medium text-gray-500 w-32">Salle</dt><dd class="text-gray-900"><?php echo htmlspecialchars($studentFile['soutenance']['lib_salle'] ?? 'N/A'); ?></dd></div>
                    </dl>
                </div>
                <?php endif; ?>
                <!-- Jury Composition -->
                <?php if (!empty($studentFile['soutenance']['jury_members'])): ?>
                <div class="bg-white rounded-2xl shadow-sm p-6">

                    <dl class="text-sm space-y-2">
                        <?php
                        $juryMembers = explode('|', $studentFile['soutenance']['jury_members']);
                        foreach ($juryMembers as $member):
                            if (empty(trim($member))) continue;
                            $parts = explode(':', $member);
                            $name = trim($parts[0] ?? 'N/A');
                            $role = trim($parts[1] ?? 'N/A');
                        ?>
                        <div class="flex"><dt class="font-semibold text-gray-600 w-32 flex-shrink-0"><?php echo htmlspecialchars($role); ?>:</dt><dd class="text-gray-800"><?php echo htmlspecialchars($name); ?></dd></div>
                        <?php endforeach; ?>
                    </dl>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Relevé de notes -->
        <div class="bg-white rounded-2xl shadow-sm p-6">

            <div class="space-y-4">
                <div class="grid grid-cols-3 items-center gap-4">
                    <span class="text-sm font-medium text-gray-600">Moyenne Master 1 (Annexe 3)</span>
                    <span class="text-sm font-bold text-center"><?php echo $m1_avg ? number_format($m1_avg, 2) : 'N/A'; ?></span>
                    <div class="w-full bg-gray-200 rounded-full h-2.5"><div class="<?php echo getMention($m1_avg)['bg'] ?? 'bg-gray-300' ?> h-2.5 rounded-full" style="width: <?php echo $m1_avg ? ($m1_avg/20)*100 : 0; ?>%"></div></div>
                </div>
                 <div class="grid grid-cols-3 items-center gap-4">
                    <span class="text-sm font-medium text-gray-600">Moyenne Master 2 - S1 (Annexe 2)</span>
                    <span class="text-sm font-bold text-center"><?php echo $m2_s1_avg ? number_format($m2_s1_avg, 2) : 'N/A'; ?></span>
                    <div class="w-full bg-gray-200 rounded-full h-2.5"><div class="<?php echo getMention($m2_s1_avg)['bg'] ?? 'bg-gray-300' ?> h-2.5 rounded-full" style="width: <?php echo $m2_s1_avg ? ($m2_s1_avg/20)*100 : 0; ?>%"></div></div>
                </div>
                 <div class="grid grid-cols-3 items-center gap-4">
                    <span class="text-sm font-medium text-gray-600">Note du Mémoire / Soutenance</span>
                    <span class="text-sm font-bold text-center"><?php echo $memo_avg ? number_format($memo_avg, 2) : 'N/A'; ?></span>
                    <div class="w-full bg-gray-200 rounded-full h-2.5"><div class="<?php echo getMention($memo_avg)['bg'] ?? 'bg-gray-300' ?> h-2.5 rounded-full" style="width: <?php echo $memo_avg ? ($memo_avg/20)*100 : 0; ?>%"></div></div>
                </div>
                <div class="border-t pt-4 grid grid-cols-3 items-center gap-4">
                    <span class="text-sm font-bold text-gray-800">MOYENNE GÉNÉRALE</span>
                    <span class="text-lg font-bold text-center <?php echo $general_mention['color'] ?? 'text-gray-800' ?>"><?php echo $general_avg ? number_format($general_avg, 2) : 'N/A'; ?></span>
                     <div class="w-full bg-gray-200 rounded-full h-4"><div class="<?php echo $general_mention['bg'] ?? 'bg-gray-300' ?> h-4 rounded-full" style="width: <?php echo $general_avg ? ($general_avg/20)*100 : 0; ?>%"></div></div>
                </div>
            </div>
        </div>

        <!-- Notes Détaillées -->
        <?php if (!empty($studentFile['soutenance']['notes'])): ?>
        <div class="bg-white rounded-2xl shadow-sm p-6">

            <div class="cm-table-wrapper border rounded-lg">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">Critère d'Évaluation</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">Note</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($studentFile['soutenance']['notes'] as $note): ?>
                        <tr>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($note['lib_critere'] ?? 'N/A'); ?></td>
                            <td class="px-4 py-2 font-semibold"><?php echo htmlspecialchars($note['note']); ?>/20</td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars(date('d/m/Y', strtotime($note['date_eval']))); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Formulaire de modification -->
        <?php if (canEdit()): ?>
        <form method="POST" action="?page=admin_historique&action=update_student" class="bg-white rounded-2xl shadow-sm p-6">

            <input type="hidden" name="num_etu" value="<?php echo htmlspecialchars($studentFile['num_etu']); ?>">

            <div class="space-y-6">
                 <!-- Informations Personnelles -->
                <div class="space-y-4">

                    <div>
                        <label for="nom_etu" class="block text-sm font-medium text-gray-700">Nom & Prénoms</label>
                        <div class="mt-1 flex gap-2">
                             <input type="text" name="nom_etu" value="<?php echo htmlspecialchars($studentFile['nom_etu']); ?>" class="flex-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                             <input type="text" name="prenom_etu" value="<?php echo htmlspecialchars($studentFile['prenom_etu']); ?>" class="flex-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="email_etu" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email_etu" value="<?php echo htmlspecialchars($studentFile['email_etu']); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                    </div>
                </div>

                 <!-- Thème et validation -->
                <?php if (!empty($studentFile['rapport'])): ?>
                <div class="space-y-4 border-t border-gray-200 pt-6">

                     <div>
                        <label for="theme_rapport" class="block text-sm font-medium text-gray-700">Thème du mémoire</label>
                        <input type="text" name="theme_rapport" value="<?php echo htmlspecialchars($studentFile['rapport']['theme_rapport'] ?? ''); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                    </div>
                    <div>
                        <label for="statut_rapport" class="block text-sm font-medium text-gray-700">Statut de validation</label>
                        <select name="statut_rapport" id="statut_rapport" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm sm:text-sm">
                            <option value="valider" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'valider' ? 'selected' : ''; ?>>Validé</option>
                            <option value="rejeter" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'rejeter' ? 'selected' : ''; ?>>Rejeté</option>
                            <option value="en_cours" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-4 mt-8 pt-4 border-t border-gray-200">
                <a href="?page=admin_historique" class="bg-white hover:bg-gray-100 text-gray-700 font-medium py-2 px-4 border border-gray-300 rounded-lg shadow-sm">Annuler</a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm">
                    <i class="fas fa-save mr-2"></i>
                    Enregistrer les modifications
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

