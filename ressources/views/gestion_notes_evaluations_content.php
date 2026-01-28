<?php

// Initialiser les variables globales si elles n'existent pas
$students = $GLOBALS['listeEtudiants'] ?? [];
$niveauxEtude = $GLOBALS['niveauxEtude'] ?? [];
$selectedNiveau = $GLOBALS['selectedNiveau'] ?? null;
$selectedStudent = $GLOBALS['selectedStudent'] ?? null;
$studentGrades = $GLOBALS['studentGrades'] ?? [];

?>

<div class="container">
    <div id="alertContainer">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success" role="alert">
            <span><?php echo $_SESSION['success']; ?></span>
        </div>
        <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger" role="alert">
            <span><?php echo $_SESSION['error']; ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </div>

    <!-- Interface de saisie des notes (optimisée) -->
    <div id="notes" class="tab-content active">
        <!-- Header with student search -->
        <div class="card">
            <div class="page-header">
                <h2>Gestion des Notes</h2>
                <div class="filter-controls">
                    <!-- Niveau d'étude -->
                    <div class="form-group">
                        <select id="niveauSelect" class="select">
                            <option value="">Sélectionner un niveau</option>
                            <?php foreach ($GLOBALS['niveaux'] as $niveau): ?>
                            <option value="<?php echo htmlspecialchars($niveau->id_niv_etude); ?>"
                                <?php echo $GLOBALS['selectedNiveau'] == $niveau->id_niv_etude ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($niveau->lib_niv_etude); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Étudiant -->
                    <div class="form-group">
                        <select id="studentSelect" class="select">
                            <option value="">Sélectionner un étudiant</option>
                            <?php foreach ($GLOBALS['etudiants'] as $etudiant): ?>
                            <option value="<?php echo htmlspecialchars($etudiant->num_etu); ?>"
                                <?php echo isset($GLOBALS['selectedStudent']) && $GLOBALS['selectedStudent']->num_etu == $etudiant->num_etu ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($etudiant->nom_etu . ' ' . $etudiant->prenom_etu); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Message initial -->
            <?php if (empty($GLOBALS['selectedNiveau'])) { ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h4>Sélectionnez un niveau d'étude</h4>
                <p>Veuillez sélectionner un niveau d'étude pour afficher les semestres et les unités d'enseignement.</p>
            </div>
            <?php } ?>

            <!-- Informations de l'étudiant -->
            <?php if (!empty($GLOBALS['selectedStudent'])): ?>
            <div class="student-info-card">
                <div class="avatar avatar-lg">
                    <i class="fas fa-user"></i>
                </div>
                <div class="student-details">
                    <h3><?php echo htmlspecialchars($GLOBALS['selectedStudent']->nom_etu . ' ' . $GLOBALS['selectedStudent']->prenom_etu); ?></h3>
                    <p>Numéro d'étudiant: <?php echo htmlspecialchars($GLOBALS['selectedStudent']->num_etu); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Semestres et UE -->
            <div class="grades-section">
                <?php if (!empty($GLOBALS['selectedNiveau'])) { ?>

                <form id="saisiForm" action="?page=gestion_notes_evaluations<?php 
                        echo !empty($GLOBALS['selectedNiveau']) ? '&niveau=' . htmlspecialchars($GLOBALS['selectedNiveau']) : '';
                        echo !empty($GLOBALS['selectedStudent']) ? '&student=' . htmlspecialchars($GLOBALS['selectedStudent']->num_etu) : '';
                    ?>&action=enregistrer_notes" method="POST">
                    <?php 
                        $currentSemestre = null;
                        if (!empty($GLOBALS['studentUes'])) {
                            foreach ($GLOBALS['studentUes'] as $ue) {
                                if ($currentSemestre !== $ue->lib_semestre) {
                                    if ($currentSemestre !== null) {
                                        echo '</div></div>';
                                    }
                                    $currentSemestre = $ue->lib_semestre;
                                    
                                    // Calculer le total des crédits pour ce semestre
                                    $totalCreditsSemestre = 0;
                                    foreach ($GLOBALS['studentUes'] as $ueSemestre) {
                                        if ($ueSemestre->lib_semestre === $currentSemestre) {
                                            $totalCreditsSemestre += $ueSemestre->credit;
                                        }
                                    }
                                    ?>
                    <div class="semester-card">
                        <div class="semester-header">
                            <h3><?php echo htmlspecialchars($ue->lib_semestre); ?></h3>
                            <span class="semester-credits"><?php echo $totalCreditsSemestre; ?> crédits</span>
                        </div>
                        <div class="semester-content">
                            <?php
                            }
                            ?>
                            <div class="ue-item">
                                <?php
                                // Récupérer les ECUE de cette UE
                                $ecues = [];
                                if (!empty($GLOBALS['studentEcues'])) {
                                    foreach ($GLOBALS['studentEcues'] as $ecue) {
                                        if ($ecue->id_ue == $ue->id_ue) {
                                            $ecues[] = $ecue;
                                        }
                                    }
                                }
                                
                                if (!empty($ecues)) {
                                    // Affichage avec ECUE
                                    echo '<div class="ue-header">';
                                    echo '<h4>' . htmlspecialchars($ue->lib_ue) . '</h4>';
                                    echo '<span class="ue-credits">' . $ue->credit . ' crédits</span>';
                                    echo '</div>';
                                    
                                    echo '<div class="ecue-list">';
                                    echo '<h5 class="ecue-title">Éléments constitutifs (ECUE)</h5>';
                                    echo '<div class="ecue-items">';
                                    foreach ($ecues as $ecue) {
                                        echo '<div class="ecue-row">';
                                        echo '<div class="ecue-label">';
                                        echo '<h4>' . htmlspecialchars($ecue->lib_ecue) . '</h4>';
                                        echo '</div>';
                                        echo '<div class="note-input-wrapper">';
                                        echo '<input type="number" step="0.01" min="0" max="20" name="notes_ecue[' . $ecue->id_ecue . ']" value="';
                                        $note_ecue = null;
                                        if (!empty($GLOBALS['studentGrades'])) {
                                            foreach ($GLOBALS['studentGrades'] as $grade) {
                                                if ($grade->id_ecue == $ecue->id_ecue) {
                                                    $note_ecue = $grade->moyenne;
                                                    break;
                                                }
                                            }
                                        }
                                        echo $note_ecue !== null ? htmlspecialchars($note_ecue) : '';
                                        echo '" class="input input-note">';
                                        echo '</div>';
                                        echo '<div class="comment-input-wrapper">';
                                        echo '<input type="text" name="commentaires_ecue[' . $ecue->id_ecue . ']" value="';
                                        $commentaire_ecue = null;
                                        if (!empty($GLOBALS['studentGrades'])) {
                                            foreach ($GLOBALS['studentGrades'] as $grade) {
                                                if ($grade->id_ecue == $ecue->id_ecue) {
                                                    $commentaire_ecue = $grade->commentaire;
                                                    break;
                                                }
                                            }
                                        }
                                        echo $commentaire_ecue !== null ? htmlspecialchars($commentaire_ecue) : '';
                                        echo '" placeholder="Commentaire" class="input">';
                                        echo '</div>';
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                    // Affichage de la moyenne de l'UE (lecture seule)
                                    $moyenne_ue = null;
                                    $nb_ecue = 0;
                                    $somme = 0;
                                    foreach ($ecues as $ecue) {
                                        foreach ($GLOBALS['studentGrades'] as $grade) {
                                            if ($grade->id_ecue == $ecue->id_ecue && $grade->moyenne !== null) {
                                                $somme += $grade->moyenne;
                                                $nb_ecue++;
                                            }
                                        }
                                    }
                                    if ($nb_ecue > 0) {
                                        $moyenne_ue = round($somme / $nb_ecue, 2);
                                    }
                                    echo '<div class="ue-average">Moyenne UE : ' . ($moyenne_ue !== null ? $moyenne_ue : '-') . '</div>';
                                    echo '</div>';
                                } else {
                                    // Affichage sans ECUE - tout sur une ligne
                                    echo '<div class="ue-row">';
                                    echo '<div class="ue-label">';
                                    echo '<h4>' . htmlspecialchars($ue->lib_ue) . '</h4>';
                                    echo '</div>';
                                    echo '<div class="note-input-wrapper">';
                                    echo '<input type="number" step="0.01" min="0" max="20" name="notes[' . $ue->id_ue . ']" value="';
                                    $note = null;
                                    if (!empty($GLOBALS['studentGrades'])) {
                                        foreach ($GLOBALS['studentGrades'] as $grade) {
                                            if ($grade->id_ue == $ue->id_ue) {
                                                $note = $grade->moyenne;
                                                break;
                                            }
                                        }
                                    }
                                    echo $note !== null ? htmlspecialchars($note) : '';
                                    echo '" class="input input-note">';
                                    echo '</div>';
                                    echo '<div class="comment-input-wrapper">';
                                    echo '<input type="text" name="commentaires[' . $ue->id_ue . ']" value="';
                                    $commentaire = null;
                                    if (!empty($GLOBALS['studentGrades'])) {
                                        foreach ($GLOBALS['studentGrades'] as $grade) {
                                            if ($grade->id_ue == $ue->id_ue) {
                                                $commentaire = $grade->commentaire;
                                                break;
                                            }
                                        }
                                    }
                                    echo $commentaire !== null ? htmlspecialchars($commentaire) : '';
                                    echo '" placeholder="Commentaire" class="input">';
                                    echo '</div>';
                                    echo '<div class="ue-credits-inline">';
                                    echo '<span>' . $ue->credit . ' crédits</span>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            <?php
                        }
                        if ($currentSemestre !== null) {
                            echo '</div></div>';
                        }
                    ?>
                            <div class="form-actions">
                                <?php if (canCreate() || canEdit()): ?>
                                <button type="submit" name="btn_enregistrer_notes" class="btn btn-primary">
                                    Enregistrer les notes
                                </button>
                                <?php endif; ?>
                            </div>
                </form>
                <?php } ?>

                <?php } ?>
            </div>

            <!-- Résumé des notes -->
            <?php if (!empty($GLOBALS['selectedStudent'])): ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3 class="stat-label">Moyenne Générale</h3>
                    <p class="stat-value stat-value-primary">
                        <?php
                        $totalNotes = 0;
                        $totalCredits = 0;
                        foreach ($GLOBALS['studentGrades'] as $grade) {
                            $totalNotes += $grade->moyenne * $grade->credit;
                            $totalCredits += $grade->credit;
                        }
                        echo $totalCredits > 0 ? number_format($totalNotes / $totalCredits, 2) : '0.00';
                        ?>
                    </p>
                </div>
                <div class="stat-card">
                    <h3 class="stat-label">Moyenne UE majeures</h3>
                    <p class="stat-value stat-value-primary">
                        <?php
                        $sumMaj = $credMaj = 0;
                        foreach ($GLOBALS['studentGrades'] as $grade) {
                            if ($grade->credit > 3) {
                                $sumMaj += $grade->moyenne * $grade->credit;
                                $credMaj += $grade->credit;
                            }
                        }
                        $moyMaj = $credMaj ? round($sumMaj / $credMaj, 2) : '-';
                        echo $moyMaj;
                        ?>
                    </p>
                </div>
                <div class="stat-card">
                    <h3 class="stat-label">Moyenne UE mineures</h3>
                    <p class="stat-value stat-value-primary">
                        <?php
                        $sumMin = $credMin = 0;
                        foreach ($GLOBALS['studentGrades'] as $grade) {
                            if ($grade->credit <= 3) {
                                $sumMin += $grade->moyenne * $grade->credit;
                                $credMin += $grade->credit;
                            }
                        }
                        $moyMin = $credMin ? round($sumMin / $credMin, 2) : '-';
                        echo $moyMin;
                        ?>
                    </p>
                </div>
                <div class="stat-card">
                    <h3 class="stat-label">Crédits Attribués</h3>
                    <p class="stat-value stat-value-success">
                        <?php
                        // Validation du semestre selon les moyennes majeures/mineures
                        $semestreValide = ($moyMaj !== '-' && $moyMin !== '-' && $moyMaj >= 10 && $moyMin >= 10);
                        if ($semestreValide) {
                            echo $totalCredits;
                        } else {
                            // Sinon, somme des crédits des UE validées individuellement
                            $creditsValides = 0;
                            foreach ($GLOBALS['studentGrades'] as $grade) {
                                if ($grade->moyenne >= 10) {
                                    $creditsValides += $grade->credit;
                                }
                            }
                            echo $creditsValides;
                        }
                        ?>
                    </p>
                </div>
                <div class="stat-card">
                    <h3 class="stat-label">Validation Semestre</h3>
                    <p class="stat-value <?php echo $semestreValide ? 'stat-value-success' : 'stat-value-danger'; ?>">
                        <?php echo $semestreValide ? 'Validé' : 'Non validé'; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($GLOBALS['selectedStudent'])): ?>
            <div class="action-bar">
                <a href="?page=gestion_notes_evaluations&action=imprimer_releve&student=<?= urlencode($GLOBALS['selectedStudent']->num_etu) ?>&niveau=<?= urlencode($GLOBALS['selectedNiveau']) ?>"
                    target="_blank" class="btn btn-primary">
                    <i class="fa fa-file-pdf"></i> Imprimer le relevé de notes (PDF)
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
function toggleSemestreValidation(semestre) {
    fetch('<?php echo '?page=gestion_notes_evaluations'; ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                semestre: semestre,
                etudiant_id: '<?php echo $GLOBALS['selectedStudent']->num_etu; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Erreur lors de la validation du semestre');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la validation du semestre');
        });
}

document.getElementById('saisiForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('<?php echo '?page=gestion_notes_evaluations&action=enregistrer_notes'; ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Erreur lors de l\'enregistrement des notes');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'enregistrement des notes');
        });
});

document.addEventListener('DOMContentLoaded', function() {
    const niveauSelect = document.getElementById('niveauSelect');
    const studentSelect = document.getElementById('studentSelect');

    // Gestion des messages d'alerte
    const alerts = document.querySelectorAll('[role="alert"]');
    alerts.forEach(alert => {
        // Faire apparaître l'alerte
        setTimeout(() => {
            alert.classList.add('show');
        }, 100);

        // Faire disparaître l'alerte après 5 secondes
        setTimeout(() => {
            alert.classList.remove('show');
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });

    niveauSelect.addEventListener('change', function() {
        const niveauId = this.value;
        if (niveauId) {
            window.location.href = `?page=gestion_notes_evaluations&niveau=${niveauId}`;
        }
    });

    studentSelect.addEventListener('change', function() {
        const studentId = this.value;
        const niveauId = niveauSelect.value;
        if (studentId && niveauId) {
            window.location.href =
                `?page=gestion_notes_evaluations&niveau=${niveauId}&student=${studentId}`;
        }
    });
});
</script>