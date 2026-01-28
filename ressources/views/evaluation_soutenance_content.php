<?php
// Initialiser le contrôleur et récupérer les données
require_once __DIR__ . '/../../app/controllers/EvaluationSoutenanceController.php';
$controller = new EvaluationSoutenanceController();

// Traitement des actions POST
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'evaluer':
                $result = $controller->enregistrerEvaluation();
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'supprimer':
                $result = $controller->supprimerEvaluation();
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

$soutenances = $controller->getSoutenancesProgrammeesForView();
$criteres = $controller->getCriteresEvaluation();
$anneesAcademiques = $controller->getAnneesAcademiques();
$anneeAcademiqueCourante = $controller->getAnneeAcademiqueCourante();
?>

<!-- Messages de notification -->
<?php if (!empty($message)): ?>
    <div id="notification"
        class="fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg <?= $messageType === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' ?>">
        <div class="flex items-center">
            <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
            <span><?= htmlspecialchars($message) ?></span>
            <button onclick="closeNotification()" class="ml-4 text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
<?php endif; ?>

<!-- Section d'évaluation des soutenances -->
<div class="bg-white shadow rounded-lg p-6 mb-6 relative">
    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 px-3">
        <span class="text-lg font-medium text-purple-600" id="form-title">
            Évaluation des Soutenances
        </span>
    </div>

    <!-- Message d'information -->
    <?php if (empty($soutenances)): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Information</h3>
                    <div class="mt-1 text-sm text-blue-600">
                        Aucune soutenance programmée à évaluer.
                        <br>Consultez la page <strong>Planification Soutenance</strong> pour programmer des soutenances.
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulaire d'évaluation -->
    <form method="POST" class="space-y-4 mt-4" id="evaluationForm" onsubmit="return validerFormulaire()">
        <input type="hidden" name="action" value="evaluer" id="formAction">
        <input type="hidden" name="num_etu" value="" id="numEtu">

        <!-- Sélection année académique et soutenance -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Année académique *</label>
                <select name="id_annee_acad" id="anneeAcademique" required onchange="chargerCriteresAnnee(this)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Sélectionner une année</option>
                    <?php if (!empty($anneesAcademiques)): ?>
                        <?php foreach ($anneesAcademiques as $annee): ?>
                            <option value="<?= htmlspecialchars($annee['id_annee_acad']) ?>" <?= ($anneeAcademiqueCourante && $annee['id_annee_acad'] == $anneeAcademiqueCourante['id_annee_acad']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($annee['lib_annee']) ?>
                                (<?= date('d/m/Y', strtotime($annee['date_deb'])) ?> -
                                <?= date('d/m/Y', strtotime($annee['date_fin'])) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Soutenance à évaluer *</label>
                <select id="soutenance" required onchange="chargerInfosSoutenance(this)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                    <option value="">Sélectionner une soutenance</option>
                    <?php if (!empty($soutenances)): ?>
                        <?php foreach ($soutenances as $soutenance): ?>
                            <option value="<?= htmlspecialchars($soutenance['num_etu']) ?>"
                                data-programmation="<?= htmlspecialchars($soutenance['id_programmation']) ?>"
                                data-theme="<?= htmlspecialchars($soutenance['theme_soutenance']) ?>"
                                data-date="<?= htmlspecialchars($soutenance['date_soutenance']) ?>"
                                data-heure="<?= htmlspecialchars($soutenance['heure_soutenance']) ?>"
                                data-salle="<?= htmlspecialchars($soutenance['nom_salle'] ?? 'Non définie') ?>"
                                data-president="<?= htmlspecialchars($soutenance['president_nom'] ?? 'Non défini') ?>"
                                data-examinateur="<?= htmlspecialchars($soutenance['examinateur_nom'] ?? 'Non défini') ?>"
                                data-directeur="<?= htmlspecialchars($soutenance['directeur_nom'] ?? 'Non défini') ?>"
                                data-encadreur="<?= htmlspecialchars($soutenance['encadreur_nom'] ?? 'Non défini') ?>"
                                data-maitre="<?= htmlspecialchars($soutenance['maitre_stage_nom'] ?? 'Non défini') ?>"
                                data-evalue="<?= $soutenance['est_evalue'] ?>">
                                <?= htmlspecialchars($soutenance['nom_etudiant']) ?> -
                                <?= htmlspecialchars($soutenance['matricule_etudiant']) ?>
                                (<?= $soutenance['date_soutenance'] ? date('d/m/Y', strtotime($soutenance['date_soutenance'])) : 'Date non définie' ?>
                                à
                                <?= $soutenance['heure_soutenance'] ? date('H:i', strtotime($soutenance['heure_soutenance'])) : 'Heure non définie' ?>)
                                <?php if ($soutenance['est_evalue'] > 0): ?>
                                    - ✅ Évalué
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Informations de la soutenance sélectionnée -->
        <div id="infos-soutenance" class="bg-gray-50 rounded-lg p-4 hidden">
            <h4 class="text-lg font-medium text-gray-900 mb-3">Informations de la soutenance</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p><strong>Étudiant :</strong> <span id="info-etudiant">-</span></p>
                    <p><strong>Thème :</strong> <span id="info-theme">-</span></p>
                    <p><strong>Date :</strong> <span id="info-date">-</span></p>
                    <p><strong>Heure :</strong> <span id="info-heure">-</span></p>
                    <p><strong>Salle :</strong> <span id="info-salle">-</span></p>
                </div>
                <div>
                    <p><strong>Président :</strong> <span id="info-president">-</span></p>
                    <p><strong>Examinateur :</strong> <span id="info-examinateur">-</span></p>
                    <p><strong>Directeur :</strong> <span id="info-directeur">-</span></p>
                    <p><strong>Encadreur :</strong> <span id="info-encadreur">-</span></p>
                    <p><strong>Maître de stage :</strong> <span id="info-maitre">-</span></p>
                </div>
            </div>
        </div>

        <!-- Critères d'évaluation -->
        <div id="criteres-evaluation" class="space-y-4 hidden">
            <h4 class="text-lg font-medium text-gray-900">Évaluation par critères</h4>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-yellow-400 mr-2"></i>
                    <span class="text-sm text-yellow-800">
                        Le barème indique la note maximale possible pour chaque critère selon l'année académique. La
                        note finale sera calculée automatiquement.
                    </span>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="criteres-container">
                <?php foreach ($criteres as $critere): ?>
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700">
                            <?= htmlspecialchars($critere['lib_critere']) ?>
                            <span class="text-gray-500">(Note max: <?= $critere['bareme_max'] ?>)</span>
                        </label>
                        <input type="number" name="criteres[<?= $critere['id_critere'] ?>]"
                            id="critere_<?= $critere['id_critere'] ?>" data-bareme="<?= $critere['bareme_max'] ?>" min="0"
                            max="<?= $critere['bareme_max'] ?>" step="0.5" onchange="calculerSommeNotes()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Note totale -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Note totale (somme des notes)</label>
                    <input type="number" id="note_totale" readonly step="0.5"
                        class="w-full px-3 py-2 border border-gray-200 rounded-md bg-blue-50 text-blue-700 font-medium text-xl">
                </div>
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Mention</label>
                    <input type="text" id="mention" readonly
                        class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50 text-gray-700">
                </div>
            </div>

            <!-- Commentaire général -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Commentaire général</label>
                <textarea name="commentaire_general" id="commentaire_general" rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500"
                    placeholder="Observations générales sur la soutenance..."></textarea>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end space-x-3 pt-4" id="buttonContainer">
                <button type="button" onclick="resetForm()"
                    class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-6 rounded-lg shadow-md transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>Annuler
                </button>
                <button type="submit" id="submitBtn"
                    class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-6 rounded-lg shadow-md transition-colors duration-200">
                    <i class="fas fa-save mr-2"></i>Enregistrer Évaluation
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Section tableau des évaluations -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900">Évaluations enregistrées</h3>
        <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
            <i class="fas fa-graduation-cap mr-1"></i>
            <span><?= count(array_filter($soutenances, fn($s) => $s['est_evalue'] > 0)) ?></span> évaluation(s)
        </span>
    </div>

    <?php if (empty($soutenances)): ?>
        <div class="p-6 text-center text-gray-500">
            <i class="fas fa-clipboard-list text-4xl mb-2"></i>
            <p>Aucune soutenance à évaluer pour le moment</p>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thème
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date/Heure</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jury</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note
                            finale
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mention
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($soutenances as $soutenance): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($soutenance['nom_etudiant']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= htmlspecialchars($soutenance['matricule_etudiant']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate"
                                    title="<?= htmlspecialchars($soutenance['theme_soutenance']) ?>">
                                    <?= htmlspecialchars($soutenance['theme_soutenance']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= $soutenance['date_soutenance'] ? date('d/m/Y', strtotime($soutenance['date_soutenance'])) : '-' ?>
                                <br>
                                <?= $soutenance['heure_soutenance'] ? date('H:i', strtotime($soutenance['heure_soutenance'])) : '-' ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="space-y-1">
                                    <?php if ($soutenance['president_nom']): ?>
                                        <div class="text-xs"><strong>Président:</strong>
                                            <?= htmlspecialchars($soutenance['president_nom']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($soutenance['examinateur_nom']): ?>
                                        <div class="text-xs"><strong>Examinateur:</strong>
                                            <?= htmlspecialchars($soutenance['examinateur_nom']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($soutenance['est_evalue'] > 0 && $soutenance['note_finale']): ?>
                                    <div class="text-sm font-bold text-purple-600">
                                        <?= number_format($soutenance['note_finale'], 1) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-sm text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                if ($soutenance['est_evalue'] > 0 && $soutenance['note_finale']) {
                                    // La note finale sert directement de moyenne
                                    $noteTotale = $soutenance['note_finale'];

                                    if ($noteTotale >= 16) {
                                        $mention = 'Très Bien';
                                        $mentionClass = 'bg-green-100 text-green-800';
                                    } elseif ($noteTotale >= 14) {
                                        $mention = 'Bien';
                                        $mentionClass = 'bg-blue-100 text-blue-800';
                                    } elseif ($noteTotale >= 12) {
                                        $mention = 'Assez Bien';
                                        $mentionClass = 'bg-yellow-100 text-yellow-800';
                                    } elseif ($noteTotale >= 10) {
                                        $mention = 'Passable';
                                        $mentionClass = 'bg-orange-100 text-orange-800';
                                    } else {
                                        $mention = 'Insuffisant';
                                        $mentionClass = 'bg-red-100 text-red-800';
                                    }
                                    ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $mentionClass ?>">
                                        <?= $mention ?>
                                    </span>
                                <?php } else { ?>
                                    <span class="text-sm text-gray-400">-</span>
                                <?php } ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($soutenance['est_evalue'] > 0): ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Évalué
                                    </span>
                                <?php else: ?>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <i class="fas fa-clock mr-1"></i>
                                        En attente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button onclick="evaluerSoutenance('<?= $soutenance['num_etu'] ?>')"
                                    class="text-purple-600 hover:text-purple-900 mr-3"
                                    title="<?= $soutenance['est_evalue'] > 0 ? 'Modifier l\'évaluation' : 'Évaluer' ?>">
                                    <i class="fas <?= $soutenance['est_evalue'] > 0 ? 'fa-edit' : 'fa-clipboard-check' ?>"></i>
                                </button>
                                <?php if ($soutenance['est_evalue'] > 0): ?>
                                    <!-- Bouton pour imprimer les 3 annexes en un seul PDF -->
                                    <button onclick="ouvrirModalAnnexe3('<?= $soutenance['num_etu'] ?>')"
                                        class="text-blue-600 hover:text-blue-900 mr-3"
                                        title="Imprimer les 3 procès-verbaux (Annexes 1, 2 et 3)">
                                        <i class="fas fa-print"></i> Imprimer PV
                                    </button>

                                    <form method="POST" class="inline"
                                        onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette évaluation ?')">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="num_etu" value="<?= $soutenance['num_etu'] ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900"
                                            title="Supprimer l'évaluation">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal pour saisir la moyenne Master 1 et générer les 3 annexes -->
<div id="modalAnnexe3" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                <i class="fas fa-file-pdf text-blue-600 mr-2"></i>
                Génération des Procès-Verbaux
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                Un document PDF unique contenant les 3 annexes sera généré :
            </p>
            <ul class="text-xs text-gray-500 mb-4 ml-4 list-disc">
                <li>Annexe 1 : Soutenance de Mémoire</li>
                <li>Annexe 2 : PV Jury Standard</li>
                <li>Annexe 3 : PV Jury Formation Continue</li>
            </ul>
            <form id="formAnnexe3" target="_blank">
                <input type="hidden" id="annexe3_num_etu" name="num_etu">
                <input type="hidden" name="page" value="evaluation_soutenance">
                <input type="hidden" name="action" value="imprimer_pv">

                <div class="mb-4">
                    <label for="moyenne_master1" class="block text-sm font-medium text-gray-700 mb-2">
                        Moyenne Générale Master 1 <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="moyenne_master1" name="moyenne_master1" min="0" max="20" step="0.01"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Ex: 14.50">
                    <p class="mt-1 text-xs text-gray-500">Requise pour l'Annexe 3 (Formation Continue)</p>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="fermerModalAnnexe3()"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        <i class="fas fa-times mr-1"></i>Annuler
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <i class="fas fa-print mr-1"></i>Générer les 3 PV
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let isEditMode = false;
    let currentEtuNumber = null;

    // Fermer la notification
    function closeNotification() {
        const notification = document.getElementById('notification');
        if (notification) {
            notification.remove();
        }
    }

    // Auto-fermeture de la notification après 5 secondes
    setTimeout(closeNotification, 5000);

    // Charger les informations de la soutenance sélectionnée
    function chargerInfosSoutenance(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const infosDiv = document.getElementById('infos-soutenance');
        const criteresDiv = document.getElementById('criteres-evaluation');
        const numEtuInput = document.getElementById('numEtu');

        if (selectedOption && selectedOption.value) {
            const numEtu = selectedOption.value;
            numEtuInput.value = numEtu;

            // Afficher les informations
            document.getElementById('info-etudiant').textContent = selectedOption.textContent.split(' - ')[0];
            document.getElementById('info-theme').textContent = selectedOption.getAttribute('data-theme') || 'Non défini';
            document.getElementById('info-date').textContent = formatDate(selectedOption.getAttribute('data-date'));
            document.getElementById('info-heure').textContent = selectedOption.getAttribute('data-heure') || 'Non définie';
            document.getElementById('info-salle').textContent = selectedOption.getAttribute('data-salle') || 'Non définie';
            document.getElementById('info-president').textContent = selectedOption.getAttribute('data-president') || 'Non défini';
            document.getElementById('info-examinateur').textContent = selectedOption.getAttribute('data-examinateur') || 'Non défini';
            document.getElementById('info-directeur').textContent = selectedOption.getAttribute('data-directeur') || 'Non défini';
            document.getElementById('info-encadreur').textContent = selectedOption.getAttribute('data-encadreur') || 'Non défini';
            document.getElementById('info-maitre').textContent = selectedOption.getAttribute('data-maitre') || 'Non défini';

            infosDiv.classList.remove('hidden');
            criteresDiv.classList.remove('hidden');

            // Si déjà évalué, charger l'évaluation existante
            if (selectedOption.getAttribute('data-evalue') > 0) {
                chargerEvaluationExistante(numEtu);
            } else {
                resetEvaluation();
            }
        } else {
            infosDiv.classList.add('hidden');
            criteresDiv.classList.add('hidden');
            numEtuInput.value = '';
        }
    }

    // Charger une évaluation existante
    function chargerEvaluationExistante(numEtu) {
        isEditMode = true;
        currentEtuNumber = numEtu;

        document.getElementById('form-title').textContent = 'Modification Évaluation';
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="fas fa-edit mr-2"></i>Modifier Évaluation';
        submitBtn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
        submitBtn.classList.add('bg-yellow-600', 'hover:bg-yellow-700');

        // Appel AJAX pour récupérer les notes existantes
        fetch(`?page=evaluation_soutenance&action=getEvaluationExistante&num_etu=${numEtu}`)
            .then(response => response.json())
            .then(data => {
                console.log('Données reçues:', data); // Debug
                if (data && Array.isArray(data) && data.length > 0) {
                    // Pré-remplir les champs avec les notes existantes
                    data.forEach(evaluation => {
                        const inputId = `critere_${evaluation.id_critere}`;
                        const input = document.getElementById(inputId);
                        console.log(`Recherche input ${inputId}:`, input); // Debug
                        if (input) {
                            input.value = evaluation.note;
                            console.log(`Note ${evaluation.note} attribuée au critère ${evaluation.id_critere}`); // Debug
                        } else {
                            console.warn(`Input non trouvé pour le critère ${evaluation.id_critere}`); // Debug
                        }
                    });
                    // Recalculer la somme après avoir chargé les notes
                    calculerSommeNotes();
                } else {
                    console.log('Aucune évaluation existante trouvée'); // Debug
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement de l\'évaluation:', error);
            });
    }

    // Reset de l'évaluation
    function resetEvaluation() {
        isEditMode = false;
        currentEtuNumber = null;

        document.getElementById('form-title').textContent = 'Évaluation des Soutenances';
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Enregistrer Évaluation';
        submitBtn.classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
        submitBtn.classList.add('bg-purple-600', 'hover:bg-purple-700');
    }

    // Calculer la somme des notes (simple addition)
    // Valider que chaque note ne dépasse pas son barème
    function calculerSommeNotes() {
        const critereInputs = document.querySelectorAll('input[name^="criteres"]');
        let sommeNotes = 0;
        let isValid = true;

        critereInputs.forEach(input => {
            if (input.value && input.value !== '') {
                const note = parseFloat(input.value);
                const baremeMax = parseFloat(input.getAttribute('data-bareme')) || 20;

                // Valider que la note ne dépasse pas le barème
                if (note > baremeMax) {
                    input.classList.add('border-red-500', 'bg-red-50');
                    isValid = false;
                    return;
                } else {
                    input.classList.remove('border-red-500', 'bg-red-50');
                }

                sommeNotes += note;
            }
        });

        // Afficher la somme
        const noteTotaleInput = document.getElementById('note_totale');
        if (noteTotaleInput && isValid) {
            noteTotaleInput.value = sommeNotes.toFixed(1);

            // La somme des notes sert directement de base pour la mention
            calculerMention(sommeNotes);
        } else if (!isValid) {
            noteTotaleInput.value = '';
            document.getElementById('mention').value = 'Notes invalides';
        }
    }

    // Fonction supprimée - remplacée par calculerSommeNotes()

    // Charger les critères pour une année académique
    function chargerCriteresAnnee(selectElement) {
        const anneeId = selectElement.value;
        if (!anneeId) return;

        // Appel AJAX pour recharger les critères
        fetch(`?page=evaluation_soutenance&action=getCriteresParAnnee&id_annee_acad=${anneeId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mettreAJourCriteres(data.data);
                } else {
                    console.error('Erreur lors du chargement des critères:', data.message);
                }
            })
            .catch(error => {
                console.error('Erreur AJAX:', error);
            });
    }

    // Mettre à jour l'affichage des critères
    function mettreAJourCriteres(criteres) {
        const container = document.getElementById('criteres-container');
        if (!container) return;

        // Vider le conteneur
        container.innerHTML = '';

        // Ajouter les nouveaux critères
        criteres.forEach(critere => {
            const div = document.createElement('div');
            div.className = 'space-y-2';
            div.innerHTML = `
                <label class="block text-sm font-medium text-gray-700">
                    ${critere.lib_critere} 
                    <span class="text-gray-500">(Note max: ${critere.bareme_max})</span>
                </label>
                <input type="number" 
                    name="criteres[${critere.id_critere}]"
                    id="critere_${critere.id_critere}"
                    data-bareme="${critere.bareme_max}"
                    min="0" max="${critere.bareme_max}" step="0.5"
                    onchange="calculerSommeNotes()"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500">
            `;
            container.appendChild(div);
        });

        // Réinitialiser les notes
        document.getElementById('note_totale').value = '';
        document.getElementById('mention').value = '';
    }

    // Calculer la mention
    function calculerMention(note) {
        const mentionInput = document.getElementById('mention');

        if (note >= 16) {
            mentionInput.value = 'Très Bien';
        } else if (note >= 14) {
            mentionInput.value = 'Bien';
        } else if (note >= 12) {
            mentionInput.value = 'Assez Bien';
        } else if (note >= 10) {
            mentionInput.value = 'Passable';
        } else {
            mentionInput.value = 'Insuffisant';
        }
    }

    // Listener supprimé - plus de champ note_finale

    // Évaluer une soutenance depuis le tableau
    function evaluerSoutenance(numEtu) {
        const selectElement = document.getElementById('soutenance');
        selectElement.value = numEtu;
        chargerInfosSoutenance(selectElement);

        // Scroll vers le formulaire
        document.getElementById('evaluationForm').scrollIntoView({ behavior: 'smooth' });
    }

    // Reset du formulaire
    function resetForm() {
        document.getElementById('evaluationForm').reset();
        document.getElementById('infos-soutenance').classList.add('hidden');
        document.getElementById('criteres-evaluation').classList.add('hidden');
        document.getElementById('mention').value = '';
        resetEvaluation();
    }

    // Formater la date
    function formatDate(dateString) {
        if (!dateString) return 'Non définie';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR');
    }

    // Valider le formulaire avant soumission
    function validerFormulaire() {
        const critereInputs = document.querySelectorAll('input[name^="criteres"]');
        let hasError = false;
        let hasValue = false;

        // Supprimer les styles d'erreur existants
        critereInputs.forEach(input => {
            input.classList.remove('border-red-500', 'bg-red-50');
        });

        // Valider chaque critère
        critereInputs.forEach(input => {
            if (input.value && input.value !== '') {
                hasValue = true;
                const note = parseFloat(input.value);
                const baremeMax = parseFloat(input.getAttribute('data-bareme')) || 20;

                if (note < 0 || note > baremeMax) {
                    input.classList.add('border-red-500', 'bg-red-50');
                    hasError = true;
                }
            }
        });

        if (!hasValue) {
            alert('Vous devez saisir au moins une note pour un critère.');
            return false;
        }

        if (hasError) {
            alert('Certaines notes dépassent le barème autorisé. Veuillez corriger les champs en rouge.');
            return false;
        }

        return true;
    }

    // Fonctions pour le modal de génération des 3 PV
    function ouvrirModalAnnexe3(numEtu) {
        document.getElementById('annexe3_num_etu').value = numEtu;
        document.getElementById('modalAnnexe3').classList.remove('hidden');
        document.getElementById('moyenne_master1').value = '';
        document.getElementById('moyenne_master1').focus();
    }

    function fermerModalAnnexe3() {
        document.getElementById('modalAnnexe3').classList.add('hidden');
    }

    // Gérer la soumission du formulaire pour générer les 3 PV
    document.getElementById('formAnnexe3').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const params = new URLSearchParams(formData);
        window.open('?' + params.toString(), '_blank');
        fermerModalAnnexe3();
    });

    // Fermer le modal en cliquant en dehors
    document.getElementById('modalAnnexe3').addEventListener('click', function (e) {
        if (e.target === this) {
            fermerModalAnnexe3();
        }
    });
</script>