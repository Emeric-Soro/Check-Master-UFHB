<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/models/NiveauEtude.php';
$pdo = Database::getConnection();
$etudiantModel = new Etudiant($pdo);
$niveauModel = new NiveauEtude($pdo);
$niveaux = $niveauModel->getAllNiveauxEtudes();

// Paramètres de pagination
$itemsPerPage = 10;
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filtres
$niveauFiltre = isset($_GET['niveau']) ? $_GET['niveau'] : '';
$searchFiltre = isset($_GET['search']) ? $_GET['search'] : '';

// Récupération des étudiants avec filtres
$etudiants = $etudiantModel->getAllListeEtudiants();

// Application des filtres
if ($niveauFiltre) {
    $etudiants = array_filter($etudiants, function ($e) use ($niveauFiltre) {
        return isset($e->id_niv_etude) && $e->id_niv_etude == $niveauFiltre;
    });
}

if ($searchFiltre) {
    $etudiants = array_filter($etudiants, function ($e) use ($searchFiltre) {
        $search = strtolower($searchFiltre);
        return strpos(strtolower($e->nom_etu ?? ''), $search) !== false ||
            strpos(strtolower($e->prenom_etu ?? ''), $search) !== false ||
            strpos(strtolower($e->email_etu ?? ''), $search) !== false ||
            strpos(strtolower($e->lib_niv_etude ?? ''), $search) !== false;
    });
}

// Pagination
$totalItems = count($etudiants);
$totalPages = ceil($totalItems / $itemsPerPage);
$etudiants = array_slice($etudiants, $offset, $itemsPerPage);
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-folder-open"></i> Dossiers académiques des étudiants
        </h1>
    </div>

    <!-- Messages de succès/erreur en haut -->
    <?php
    if (isset($_GET['success']) && $_GET['success'] === '1') {
        echo '<div id="successMessage" class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Dossier enregistré avec succès !</span>
              </div>';
    } elseif (isset($_GET['success']) && $_GET['success'] === '0') {
        echo '<div id="errorMessage" class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <span>Erreur lors de l\'enregistrement du dossier.</span>
              </div>';
    }
    ?>

    <div class="card">
        <div class="card-header">
            <form method="get" class="form-grid">
                <input type="hidden" name="page" value="dossiers_academiques">
                <input type="text" name="search" id="searchInput"
                    placeholder="Rechercher par nom, email, niveau..."
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    class="form-control">
                <select name="niveau" class="form-control">
                    <option value="">Tous les niveaux</option>
                    <?php foreach ($niveaux as $niv): ?>
                        <option value="<?= htmlspecialchars($niv->id_niv_etude) ?>" <?= $niveauFiltre == $niv->id_niv_etude ? 'selected' : '' ?>>
                            <?= htmlspecialchars($niv->lib_niv_etude) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
            </form>
        </div>

        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>Niveau</th>
                        <th>Promotion</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody">
                    <?php
                    // Vérifier s'il y a des résultats totaux (pas juste sur la page courante)
                    $allEtudiants = $etudiantModel->getAllListeEtudiants();
                    if ($niveauFiltre) {
                        $allEtudiants = array_filter($allEtudiants, function ($e) use ($niveauFiltre) {
                            return isset($e->id_niv_etude) && $e->id_niv_etude == $niveauFiltre;
                        });
                    }
                    if ($searchFiltre) {
                        $allEtudiants = array_filter($allEtudiants, function ($e) use ($searchFiltre) {
                            $search = strtolower($searchFiltre);
                            return strpos(strtolower($e->nom_etu ?? ''), $search) !== false ||
                                strpos(strtolower($e->prenom_etu ?? ''), $search) !== false ||
                                strpos(strtolower($e->email_etu ?? ''), $search) !== false ||
                                strpos(strtolower($e->lib_niv_etude ?? ''), $search) !== false;
                        });
                    }

                    if (empty($allEtudiants)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class="fas fa-search"></i>
                                <p class="empty-state-title">Aucun étudiant trouvé</p>
                                <p class="empty-state-text">Aucun résultat ne correspond à vos critères de recherche</p>
                                <?php if (!empty($_GET['search']) || !empty($_GET['niveau'])): ?>
                                    <a href="?page=dossiers_academiques" class="btn btn-ghost">
                                        <i class="fas fa-times"></i> Effacer les filtres
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($etudiants as $etu): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($etu->nom_etu ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($etu->prenom_etu ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($etu->email_etu ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($etu->lib_niv_etude ?? ''); ?></td>
                                <td><?php
                                if (isset($etu->date_deb, $etu->date_fin)) {
                                    echo htmlspecialchars(date('Y', strtotime($etu->date_deb)) . '-' . date('Y', strtotime($etu->date_fin)));
                                }
                                ?></td>
                                <td class="text-center">
                                    <button class="btn btn-primary open-dossier-modal"
                                        data-num-etu="<?= htmlspecialchars($etu->num_etu) ?>"
                                        data-nom="<?= htmlspecialchars($etu->nom_etu . ' ' . $etu->prenom_etu) ?>">
                                        <i class="fas fa-eye"></i> Visualiser le dossier
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <div class="pagination-info">
                Affichage de <?= $offset + 1 ?> à <?= min($offset + $itemsPerPage, $totalItems) ?> sur
                <?= $totalItems ?> étudiants
            </div>
            <div class="pagination-controls">
                <?php if ($currentPage > 1): ?>
                    <a href="?page=dossiers_academiques&p=<?= $currentPage - 1 ?><?= !empty($searchFiltre) ? '&search=' . urlencode($searchFiltre) : '' ?><?= !empty($niveauFiltre) ? '&niveau=' . urlencode($niveauFiltre) : '' ?>"
                        class="btn btn-ghost">
                        <i class="fas fa-chevron-left"></i> Précédent
                    </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);

                if ($startPage > 1): ?>
                    <a href="?page=dossiers_academiques&p=1<?= !empty($searchFiltre) ? '&search=' . urlencode($searchFiltre) : '' ?><?= !empty($niveauFiltre) ? '&niveau=' . urlencode($niveauFiltre) : '' ?>"
                        class="btn btn-ghost">1</a>
                    <?php if ($startPage > 2): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="?page=dossiers_academiques&p=<?= $i ?><?= !empty($searchFiltre) ? '&search=' . urlencode($searchFiltre) : '' ?><?= !empty($niveauFiltre) ? '&niveau=' . urlencode($niveauFiltre) : '' ?>"
                        class="btn <?= $i == $currentPage ? 'btn-primary' : 'btn-ghost' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="?page=dossiers_academiques&p=<?= $totalPages ?><?= !empty($searchFiltre) ? '&search=' . urlencode($searchFiltre) : '' ?><?= !empty($niveauFiltre) ? '&niveau=' . urlencode($niveauFiltre) : '' ?>"
                        class="btn btn-ghost"><?= $totalPages ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page=dossiers_academiques&p=<?= $currentPage + 1 ?><?= !empty($searchFiltre) ? '&search=' . urlencode($searchFiltre) : '' ?><?= !empty($niveauFiltre) ? '&niveau=' . urlencode($niveauFiltre) : '' ?>"
                        class="btn btn-ghost">
                        Suivant <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal -->
<div id="dossierModal" class="modal">
    <div class="modal-content">
        <button id="closeModalBtn" class="modal-close">
            <i class="fas fa-times"></i>
        </button>
        <h2 class="modal-title">Dossier académique de <span id="modalNom"></span></h2>

        <!-- Indicateur de chargement -->
        <div id="loadingIndicator" class="skeleton" style="display: none;">
            <div class="skeleton-loader"></div>
            <span>Chargement des données...</span>
        </div>

        <form id="dossierForm" method="POST" action="?page=dossiers_academiques&action=enregistrer_dossier">
            <input type="hidden" name="num_etu" id="modalNumEtu">
            
            <!-- Informations personnelles -->
            <div class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-user"></i> Informations personnelles
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="adresse" id="modalAdresse" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="telephone" id="modalTelephone" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nationalité</label>
                        <input type="text" name="nationalite" id="modalNationalite" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Situation familiale</label>
                        <input type="text" name="situation_familiale" id="modalSituationFamiliale" class="form-control" disabled>
                    </div>
                </div>
            </div>

            <div class="separator"></div>

            <!-- Informations académiques -->
            <div class="form-section">
                <h3 class="form-section-title">
                    <i class="fas fa-graduation-cap"></i> Informations académiques
                </h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Dernier diplôme</label>
                        <input type="text" name="dernier_diplome" id="modalDernierDiplome" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Établissement d'origine</label>
                        <input type="text" name="etablissement_origine" id="modalEtablissementOrigine" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Année d'obtention du diplôme</label>
                        <input type="number" name="annee_obtention_diplome" id="modalAnneeObtentionDiplome" 
                            class="form-control" min="1900" max="2030" placeholder="Ex: 2023" disabled>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mention du diplôme</label>
                        <input type="text" name="mention_diplome" id="modalMentionDiplome" class="form-control" disabled>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <?php if (canEdit()): ?>
                <button type="button" id="editBtn" class="btn btn-secondary">
                    <i class="fas fa-edit"></i> Modifier
                </button>
                <button type="submit" id="saveBtn" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
    <script>
        // Masquer automatiquement les messages de succès/erreur après 5 secondes
        document.addEventListener('DOMContentLoaded', function () {
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');

            function hideMessage(element) {
                if (element) {
                    element.style.opacity = '0';
                    setTimeout(() => {
                        element.style.display = 'none';
                    }, 500);
                }
            }

            // Masquer le message de succès après 5 secondes
            if (successMessage) {
                setTimeout(() => hideMessage(successMessage), 5000);
            }

            // Masquer le message d'erreur après 5 secondes
            if (errorMessage) {
                setTimeout(() => hideMessage(errorMessage), 5000);
            }
        });

        // Filtrage JS côté client (pour la démo)
        const searchInput = document.getElementById('searchInput');
        const tableBody = document.getElementById('studentTableBody');
        const rows = Array.from(tableBody.getElementsByTagName('tr'));
        searchInput.addEventListener('input', function () {
            const value = this.value.toLowerCase();
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(value) ? '' : 'none';
            });
        });
        document.querySelectorAll('.open-dossier-modal').forEach(btn => {
            btn.addEventListener('click', function () {
                const numEtu = this.dataset.numEtu;
                const nom = this.dataset.nom;

                // Affiche la modal immédiatement
                document.getElementById('dossierModal').style.display = 'flex';
                document.getElementById('modalNom').textContent = nom;
                document.getElementById('modalNumEtu').value = numEtu;

                // Affiche l'indicateur de chargement
                document.getElementById('loadingIndicator').style.display = 'flex';
                document.getElementById('dossierForm').style.display = 'none';

                // Désactive les champs
                document.querySelectorAll('#dossierForm input, #dossierForm textarea').forEach(i => {
                    if (i.type !== 'hidden' && i.type !== 'file') i.disabled = true;
                });
                document.getElementById('saveBtn').style.display = 'none';
                document.getElementById('editBtn').style.display = 'inline-flex';
                // Cache les inputs file
                document.querySelectorAll('#dossierForm input[type=file]').forEach(i => i.style.display = 'none');

                // Charge les infos via AJAX avec timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 secondes de timeout

                fetch('?page=dossiers_academiques&action=get_dossier&num_etu=' + encodeURIComponent(
                    numEtu), {
                    signal: controller.signal
                })
                    .then(r => {
                        clearTimeout(timeoutId);
                        if (!r.ok) throw new Error('Erreur réseau');
                        return r.json();
                    })
                    .then(data => {
                        // Cache l'indicateur de chargement
                        document.getElementById('loadingIndicator').style.display = 'none';
                        document.getElementById('dossierForm').style.display = 'block';

                        // Remplit les champs avec les données existantes
                        document.getElementById('modalAdresse').value = data.adresse || '';
                        document.getElementById('modalTelephone').value = data.telephone || '';
                        document.getElementById('modalNationalite').value = data.nationalite || '';
                        document.getElementById('modalSituationFamiliale').value = data
                            .situation_familiale || '';
                        document.getElementById('modalDernierDiplome').value = data.dernier_diplome ||
                            '';
                        document.getElementById('modalEtablissementOrigine').value = data
                            .etablissement_origine || '';
                        document.getElementById('modalAnneeObtentionDiplome').value = data
                            .annee_obtention_diplome || '';
                        document.getElementById('modalMentionDiplome').value = data.mention_diplome ||
                            '';
                    })
                    .catch(error => {
                        clearTimeout(timeoutId);
                        console.log('Aucun dossier existant pour cet étudiant ou erreur de chargement');

                        // Cache l'indicateur de chargement
                        document.getElementById('loadingIndicator').style.display = 'none';
                        document.getElementById('dossierForm').style.display = 'block';

                        // Vide les champs si pas de dossier existant
                        document.getElementById('modalAdresse').value = '';
                        document.getElementById('modalTelephone').value = '';
                        document.getElementById('modalNationalite').value = '';
                        document.getElementById('modalSituationFamiliale').value = '';
                        document.getElementById('modalDernierDiplome').value = '';
                        document.getElementById('modalEtablissementOrigine').value = '';
                        document.getElementById('modalAnneeObtentionDiplome').value = '';
                        document.getElementById('modalMentionDiplome').value = '';
                    });
            });
        });

        document.getElementById('closeModalBtn').onclick = () => {
            document.getElementById('dossierModal').style.display = 'none';
        };

        document.getElementById('editBtn').onclick = function () {
            document.querySelectorAll('#dossierForm input, #dossierForm textarea').forEach(i => {
                if (i.type !== 'hidden') i.disabled = false;
            });
            // Affiche les inputs file
            document.querySelectorAll('#dossierForm input[type=file]').forEach(i => i.style.display = 'block');
            document.getElementById('saveBtn').style.display = 'inline-flex';
            this.style.display = 'none';
        };
    </script>