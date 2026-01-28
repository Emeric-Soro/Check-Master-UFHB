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
    <div id="notification" class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-toast">
        <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
        <span><?= htmlspecialchars($message) ?></span>
        <button onclick="closeNotification()" class="alert-close">
            <i class="fas fa-times"></i>
        </button>
    </div>
<?php endif; ?>

<!-- Section d'évaluation des soutenances -->
<div class="card">
    <div class="card-header">
        <h2 id="form-title">Évaluation des Soutenances</h2>
    </div>

    <div class="card-body">
        <!-- Message d'information -->
        <?php if (empty($soutenances)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Information</strong>
                    <p>Aucune soutenance programmée à évaluer.<br>Consultez la page <strong>Planification Soutenance</strong> pour programmer des soutenances.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulaire d'évaluation -->
        <form method="POST" id="evaluationForm" onsubmit="return validerFormulaire()">
            <input type="hidden" name="action" value="evaluer" id="formAction">
            <input type="hidden" name="num_etu" value="" id="numEtu">

            <!-- Sélection année académique et soutenance -->
            <div class="filter-bar">
                <div class="form-group">
                    <label>Année académique *</label>
                    <select name="id_annee_acad" id="anneeAcademique" class="select" required onchange="chargerCriteresAnnee(this)">
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

                <div class="form-group">
                    <label>Soutenance à évaluer *</label>
                    <select id="soutenance" class="select" required onchange="chargerInfosSoutenance(this)">
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
            <div id="infos-soutenance" class="card hidden" style="margin-top: 1rem;">
                <div class="card-header">
                    <h4>Informations de la soutenance</h4>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
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
            </div>

            <!-- Critères d'évaluation -->
            <div id="criteres-evaluation" class="hidden" style="margin-top: 1rem;">
                <h4>Évaluation par critères</h4>
                <div class="alert alert-warning" style="margin: 1rem 0;">
                    <i class="fas fa-info-circle"></i>
                    <span>Le barème indique la note maximale possible pour chaque critère selon l'année académique. La note finale sera calculée automatiquement.</span>
                </div>
                <div class="filter-bar" id="criteres-container">
                    <?php foreach ($criteres as $critere): ?>
                        <div class="form-group">
                            <label>
                                <?= htmlspecialchars($critere['lib_critere']) ?>
                                <span style="color: var(--text-secondary);">(Note max: <?= $critere['bareme_max'] ?>)</span>
                            </label>
                            <input type="number" name="criteres[<?= $critere['id_critere'] ?>]"
                                id="critere_<?= $critere['id_critere'] ?>" class="input"
                                data-bareme="<?= $critere['bareme_max'] ?>" min="0"
                                max="<?= $critere['bareme_max'] ?>" step="0.5" onchange="calculerSommeNotes()">
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Note totale -->
                <div class="filter-bar" style="margin-top: 1.5rem;">
                    <div class="form-group">
                        <label>Note totale (somme des notes)</label>
                        <input type="number" id="note_totale" class="input" readonly step="0.5" style="background: var(--info-bg); color: var(--primary); font-weight: 600; font-size: 1.25rem;">
                    </div>
                    <div class="form-group">
                        <label>Mention</label>
                        <input type="text" id="mention" class="input" readonly style="background: var(--bg-secondary);">
                    </div>
                </div>

                <!-- Commentaire général -->
                <div class="form-group" style="margin-top: 1rem;">
                    <label>Commentaire général</label>
                    <textarea name="commentaire_general" id="commentaire_general" class="input" rows="4"
                        placeholder="Observations générales sur la soutenance..."></textarea>
                </div>

                <!-- Boutons d'action -->
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;" id="buttonContainer">
                    <button type="button" onclick="resetForm()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <?php if (canCreate() || canEdit()): ?>
                    <button type="submit" id="submitBtn" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer Évaluation
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Section tableau des évaluations -->
<div class="card">
    <div class="card-header">
        <h3>Évaluations enregistrées</h3>
        <span class="badge badge-info">
            <i class="fas fa-graduation-cap"></i>
            <?= count(array_filter($soutenances, fn($s) => $s['est_evalue'] > 0)) ?> évaluation(s)
        </span>
    </div>

    <?php if (empty($soutenances)): ?>
        <div class="card-body" style="text-align: center; color: var(--text-secondary);">
            <i class="fas fa-clipboard-list" style="font-size: 2.5rem; margin-bottom: 0.5rem;"></i>
            <p>Aucune soutenance à évaluer pour le moment</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Thème</th>
                        <th>Date/Heure</th>
                        <th>Jury</th>
                        <th>Note finale</th>
                        <th>Mention</th>
                        <th>Statut</th>
                        <?php if (canEdit() || canDelete()): ?>
                        <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($soutenances as $soutenance): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 500;">
                                    <?= htmlspecialchars($soutenance['nom_etudiant']) ?>
                                </div>
                                <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                    <?= htmlspecialchars($soutenance['matricule_etudiant']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="max-width: 20rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                    title="<?= htmlspecialchars($soutenance['theme_soutenance']) ?>">
                                    <?= htmlspecialchars($soutenance['theme_soutenance']) ?>
                                </div>
                            </td>
                            <td>
                                <?= $soutenance['date_soutenance'] ? date('d/m/Y', strtotime($soutenance['date_soutenance'])) : '-' ?>
                                <br>
                                <?= $soutenance['heure_soutenance'] ? date('H:i', strtotime($soutenance['heure_soutenance'])) : '-' ?>
                            </td>
                            <td>
                                <div style="font-size: 0.875rem;">
                                    <?php if ($soutenance['president_nom']): ?>
                                        <div><strong>Président:</strong> <?= htmlspecialchars($soutenance['president_nom']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($soutenance['examinateur_nom']): ?>
                                        <div><strong>Examinateur:</strong> <?= htmlspecialchars($soutenance['examinateur_nom']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($soutenance['est_evalue'] > 0 && $soutenance['note_finale']): ?>
                                    <div style="font-weight: 700; color: var(--primary);">
                                        <?= number_format($soutenance['note_finale'], 1) ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary);">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if ($soutenance['est_evalue'] > 0 && $soutenance['note_finale']) {
                                    $noteTotale = $soutenance['note_finale'];

                                    if ($noteTotale >= 16) {
                                        $mention = 'Très Bien';
                                        $mentionClass = 'badge-success';
                                    } elseif ($noteTotale >= 14) {
                                        $mention = 'Bien';
                                        $mentionClass = 'badge-info';
                                    } elseif ($noteTotale >= 12) {
                                        $mention = 'Assez Bien';
                                        $mentionClass = 'badge-warning';
                                    } elseif ($noteTotale >= 10) {
                                        $mention = 'Passable';
                                        $mentionClass = 'badge-warning';
                                    } else {
                                        $mention = 'Insuffisant';
                                        $mentionClass = 'badge-danger';
                                    }
                                    ?>
                                    <span class="badge <?= $mentionClass ?>">
                                        <?= $mention ?>
                                    </span>
                                <?php } else { ?>
                                    <span style="color: var(--text-secondary);">-</span>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if ($soutenance['est_evalue'] > 0): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check-circle"></i>
                                        Évalué
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock"></i>
                                        En attente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <?php if (canEdit() || canDelete()): ?>
                            <td>
                                <?php if (canEdit()): ?>
                                <button onclick="evaluerSoutenance('<?= $soutenance['num_etu'] ?>')" class="btn btn-primary btn-sm"
                                    title="<?= $soutenance['est_evalue'] > 0 ? 'Modifier l\'évaluation' : 'Évaluer' ?>">
                                    <i class="fas <?= $soutenance['est_evalue'] > 0 ? 'fa-edit' : 'fa-clipboard-check' ?>"></i>
                                </button>
                                <?php endif; ?>
                                <?php if ($soutenance['est_evalue'] > 0): ?>
                                    <button onclick="ouvrirModalAnnexe3('<?= $soutenance['num_etu'] ?>')" class="btn btn-info btn-sm"
                                        title="Imprimer les 3 procès-verbaux (Annexes 1, 2 et 3)">
                                        <i class="fas fa-print"></i>
                                    </button>

                                    <?php if (canDelete()): ?>
                                    <form method="POST" style="display: inline;"
                                        onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette évaluation ?')">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="num_etu" value="<?= $soutenance['num_etu'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Supprimer l'évaluation">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal pour saisir la moyenne Master 1 et générer les 3 annexes -->
<div id="modalAnnexe3" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>
                <i class="fas fa-file-pdf"></i>
                Génération des Procès-Verbaux
            </h3>
        </div>
        <div class="modal-body">
            <p style="margin-bottom: 1rem;">
                Un document PDF unique contenant les 3 annexes sera généré :
            </p>
            <ul style="font-size: 0.875rem; color: var(--text-secondary); margin: 0 0 1rem 1.5rem; list-style: disc;">
                <li>Annexe 1 : Soutenance de Mémoire</li>
                <li>Annexe 2 : PV Jury Standard</li>
                <li>Annexe 3 : PV Jury Formation Continue</li>
            </ul>
            <form id="formAnnexe3" target="_blank">
                <input type="hidden" id="annexe3_num_etu" name="num_etu">
                <input type="hidden" name="page" value="evaluation_soutenance">
                <input type="hidden" name="action" value="imprimer_pv">

                <div class="form-group">
                    <label>Moyenne Générale Master 1 <span style="color: var(--danger);">*</span></label>
                    <input type="number" id="moyenne_master1" name="moyenne_master1" class="input"
                        min="0" max="20" step="0.01" required placeholder="Ex: 14.50">
                    <small style="color: var(--text-secondary); font-size: 0.75rem;">Requise pour l'Annexe 3 (Formation Continue)</small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" onclick="fermerModalAnnexe3()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-print"></i> Générer les 3 PV
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let isEditMode = false;
    let currentEtuNumber = null;

    function closeNotification() {
        const notification = document.getElementById('notification');
        if (notification) {
            notification.remove();
        }
    }

    setTimeout(closeNotification, 5000);

    function chargerInfosSoutenance(selectElement) {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        const infosDiv = document.getElementById('infos-soutenance');
        const criteresDiv = document.getElementById('criteres-evaluation');
        const numEtuInput = document.getElementById('numEtu');

        if (selectedOption && selectedOption.value) {
            const numEtu = selectedOption.value;
            numEtuInput.value = numEtu;

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

    function chargerEvaluationExistante(numEtu) {
        isEditMode = true;
        currentEtuNumber = numEtu;

        document.getElementById('form-title').textContent = 'Modification Évaluation';
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="fas fa-edit"></i> Modifier Évaluation';
        submitBtn.classList.remove('btn-primary');
        submitBtn.classList.add('btn-warning');

        fetch(`?page=evaluation_soutenance&action=getEvaluationExistante&num_etu=${numEtu}`)
            .then(response => response.json())
            .then(data => {
                console.log('Données reçues:', data);
                if (data && Array.isArray(data) && data.length > 0) {
                    data.forEach(evaluation => {
                        const inputId = `critere_${evaluation.id_critere}`;
                        const input = document.getElementById(inputId);
                        console.log(`Recherche input ${inputId}:`, input);
                        if (input) {
                            input.value = evaluation.note;
                            console.log(`Note ${evaluation.note} attribuée au critère ${evaluation.id_critere}`);
                        } else {
                            console.warn(`Input non trouvé pour le critère ${evaluation.id_critere}`);
                        }
                    });
                    calculerSommeNotes();
                } else {
                    console.log('Aucune évaluation existante trouvée');
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement de l\'évaluation:', error);
            });
    }

    function resetEvaluation() {
        isEditMode = false;
        currentEtuNumber = null;

        document.getElementById('form-title').textContent = 'Évaluation des Soutenances';
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer Évaluation';
        submitBtn.classList.remove('btn-warning');
        submitBtn.classList.add('btn-primary');
    }

    function calculerSommeNotes() {
        const critereInputs = document.querySelectorAll('input[name^="criteres"]');
        let sommeNotes = 0;
        let isValid = true;

        critereInputs.forEach(input => {
            if (input.value && input.value !== '') {
                const note = parseFloat(input.value);
                const baremeMax = parseFloat(input.getAttribute('data-bareme')) || 20;

                if (note > baremeMax) {
                    input.style.borderColor = 'var(--danger)';
                    input.style.backgroundColor = 'var(--danger-bg)';
                    isValid = false;
                    return;
                } else {
                    input.style.borderColor = '';
                    input.style.backgroundColor = '';
                }

                sommeNotes += note;
            }
        });

        const noteTotaleInput = document.getElementById('note_totale');
        if (noteTotaleInput && isValid) {
            noteTotaleInput.value = sommeNotes.toFixed(1);
            calculerMention(sommeNotes);
        } else if (!isValid) {
            noteTotaleInput.value = '';
            document.getElementById('mention').value = 'Notes invalides';
        }
    }

    function chargerCriteresAnnee(selectElement) {
        const anneeId = selectElement.value;
        if (!anneeId) return;

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

    function mettreAJourCriteres(criteres) {
        const container = document.getElementById('criteres-container');
        if (!container) return;

        container.innerHTML = '';

        criteres.forEach(critere => {
            const div = document.createElement('div');
            div.className = 'form-group';
            div.innerHTML = `
                <label>
                    ${critere.lib_critere} 
                    <span style="color: var(--text-secondary);">(Note max: ${critere.bareme_max})</span>
                </label>
                <input type="number" 
                    name="criteres[${critere.id_critere}]"
                    id="critere_${critere.id_critere}"
                    class="input"
                    data-bareme="${critere.bareme_max}"
                    min="0" max="${critere.bareme_max}" step="0.5"
                    onchange="calculerSommeNotes()">
            `;
            container.appendChild(div);
        });

        document.getElementById('note_totale').value = '';
        document.getElementById('mention').value = '';
    }

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

    function evaluerSoutenance(numEtu) {
        const selectElement = document.getElementById('soutenance');
        selectElement.value = numEtu;
        chargerInfosSoutenance(selectElement);
        document.getElementById('evaluationForm').scrollIntoView({ behavior: 'smooth' });
    }

    function resetForm() {
        document.getElementById('evaluationForm').reset();
        document.getElementById('infos-soutenance').classList.add('hidden');
        document.getElementById('criteres-evaluation').classList.add('hidden');
        document.getElementById('mention').value = '';
        resetEvaluation();
    }

    function formatDate(dateString) {
        if (!dateString) return 'Non définie';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR');
    }

    function validerFormulaire() {
        const critereInputs = document.querySelectorAll('input[name^="criteres"]');
        let hasError = false;
        let hasValue = false;

        critereInputs.forEach(input => {
            input.style.borderColor = '';
            input.style.backgroundColor = '';
        });

        critereInputs.forEach(input => {
            if (input.value && input.value !== '') {
                hasValue = true;
                const note = parseFloat(input.value);
                const baremeMax = parseFloat(input.getAttribute('data-bareme')) || 20;

                if (note < 0 || note > baremeMax) {
                    input.style.borderColor = 'var(--danger)';
                    input.style.backgroundColor = 'var(--danger-bg)';
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

    function ouvrirModalAnnexe3(numEtu) {
        document.getElementById('annexe3_num_etu').value = numEtu;
        document.getElementById('modalAnnexe3').classList.remove('hidden');
        document.getElementById('moyenne_master1').value = '';
        document.getElementById('moyenne_master1').focus();
    }

    function fermerModalAnnexe3() {
        document.getElementById('modalAnnexe3').classList.add('hidden');
    }

    document.getElementById('formAnnexe3').addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const params = new URLSearchParams(formData);
        window.open('?' + params.toString(), '_blank');
        fermerModalAnnexe3();
    });

    document.getElementById('modalAnnexe3').addEventListener('click', function (e) {
        if (e.target === this) {
            fermerModalAnnexe3();
        }
    });
</script>
</script>