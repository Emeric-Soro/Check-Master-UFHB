<?php
// Initialisation des variables avec des valeurs par défaut
$etudiantsInscrits = isset($GLOBALS['etudiantsInscrits']) ? $GLOBALS['etudiantsInscrits'] : [];
$listeAllEtudiant = $GLOBALS['listeAllEtudiant'];
$allVersement = $GLOBALS['listeVersement'];

// Configuration de la pagination
$items_par_page = 10; // Nombre d'éléments par page
$page_actuelle = isset($_GET['page_versements']) ? (int)$_GET['page_versements'] : 1;
$total_items = count($allVersement);
$total_pages = ceil($total_items / $items_par_page);
$page_actuelle = max(1, min($page_actuelle, $total_pages)); // S'assurer que la page est valide

// Calculer l'index de début et de fin pour la pagination
$debut = ($page_actuelle - 1) * $items_par_page;
$versements_pages = array_slice($allVersement, $debut, $items_par_page);

// Calcul des statistiques
$totalEtudiants = count($etudiantsInscrits);
$complete = 0;
$partial = 0;

foreach ($etudiantsInscrits as $etudiant) {
    $reste_a_payer = isset($etudiant['reste_a_payer']) ? floatval($etudiant['reste_a_payer']) : 0;

    if ($reste_a_payer <= 0) {
        $complete++;
    } else {
        $partial++;
    }
}

$pourcentageComplete = $totalEtudiants > 0 ? round(($complete / $totalEtudiants) * 100) : 0;
$pourcentagePartial = $totalEtudiants > 0 ? round(($partial / $totalEtudiants) * 100) : 0;
$pourcentagePending = count($listeAllEtudiant) > 0 ? round(($totalEtudiants / count($listeAllEtudiant)) * 100) : 0;
?>

<!-- Système de notification -->
<?php if (isset($GLOBALS['messageSuccess']) && !empty($GLOBALS['messageSuccess'])): ?>
<div id="successNotification" class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <span><?= htmlspecialchars($GLOBALS['messageSuccess']) ?></span>
    <button onclick="this.parentElement.remove()" class="alert-close">
        <i class="fas fa-times"></i>
    </button>
</div>
<?php endif; ?>

<?php if (isset($GLOBALS['messageErreur']) && !empty($GLOBALS['messageErreur'])): ?>
<div id="errorNotification" class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i>
    <span><?= htmlspecialchars($GLOBALS['messageErreur']) ?></span>
    <button onclick="this.parentElement.remove()" class="alert-close">
        <i class="fas fa-times"></i>
    </button>
</div>
<?php endif; ?>

<div class="container">
    <!-- Header -->
    <div class="page-header">
        <div>
            <h1>Gestion des paiements</h1>
            <p>Suivi des paiements de scolarité</p>
        </div>
    </div>

    <!-- Payment status cards -->
    <div class="stats-grid">
        <div class="stat-card stat-card-success">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <p class="stat-card-label">Paiements complets</p>
                    <p class="stat-card-value"><?php echo $complete; ?></p>
                    <p class="stat-card-subtitle"><?php echo $pourcentageComplete; ?>% des étudiants</p>
                </div>
                <div class="stat-card-icon stat-card-icon-success">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="stat-card stat-card-warning">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <p class="stat-card-label">Paiements partiels</p>
                    <p class="stat-card-value"><?php echo $partial; ?></p>
                    <p class="stat-card-subtitle"><?php echo $pourcentagePartial; ?>% des étudiants</p>
                </div>
                <div class="stat-card-icon stat-card-icon-warning">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
            </div>
        </div>

        <div class="stat-card stat-card-danger">
            <div class="stat-card-content">
                <div class="stat-card-info">
                    <p class="stat-card-label">Etudiants inscrits</p>
                    <p class="stat-card-value"><?php echo $totalEtudiants; ?></p>
                    <p class="stat-card-subtitle"><?php echo $pourcentagePending; ?>% des étudiants</p>
                </div>
                <div class="stat-card-icon stat-card-icon-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment form -->
    <?php if (canCreate() || canEdit()): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <?php echo isset($GLOBALS['versementAModifier']) ? 'Mettre à jour un versement' : 'Enregistrer un versement'; ?>
            </h3>
        </div>
        <div class="card-body">
            <form id="versementsForm" method="POST"
                action="?page=gestion_scolarite<?php echo isset($GLOBALS['versementAModifier']) ? '&action=mettre_a_jour_versement' : '&action=enregistrer_versement'; ?>">
                <?php if (isset($GLOBALS['versementAModifier'])): ?>
                <input type="hidden" name="id_versement"
                    value="<?php echo $GLOBALS['versementAModifier']['id_versement']; ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="studentSelect" class="form-label">Étudiant <span class="text-danger">*</span></label>
                        <select id="studentSelect" name="id_etudiant" required class="select">
                            <option value="">Sélectionner un étudiant</option>
                            <?php foreach ($etudiantsInscrits as $etudiant): ?>
                            <option value="<?php echo $etudiant['id_etudiant']; ?>"
                                data-montant-total="<?php echo isset($GLOBALS['montantTotal']) ? $GLOBALS['montantTotal'] : (isset($etudiant['montant_scolarite']) ? $etudiant['montant_scolarite'] : 0); ?>"
                                data-montant-paye="<?php echo isset($GLOBALS['montantPaye']) ? $GLOBALS['montantPaye'] : (isset($etudiant['montant_paye']) ? $etudiant['montant_paye'] : 0); ?>"
                                data-reste-a-payer="<?php echo isset($GLOBALS['resteAPayer']) ? $GLOBALS['resteAPayer'] : (isset($etudiant['reste_a_payer']) ? $etudiant['reste_a_payer'] : 0); ?>"
                                <?php echo (isset($GLOBALS['versementAModifier']) && $GLOBALS['versementAModifier']['id_inscription'] == $etudiant['id_inscription']) ? 'selected' : ''; ?>>
                                <?php echo $etudiant['nom'] . ' ' . $etudiant['prenom']; ?> -
                                <?php echo $etudiant['nom_niveau']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="paymentAmount" class="form-label">Montant <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-prefix">FCFA</span>
                            <input type="number" id="paymentAmount" name="montant" required class="input"
                                placeholder="0"
                                value="<?php echo isset($GLOBALS['versementAModifier']) ? $GLOBALS['versementAModifier']['montant'] : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="paymentMethod" class="form-label">Méthode <span class="text-danger">*</span></label>
                        <select id="paymentMethod" name="methode_paiement" required class="select">
                            <option value="">Sélectionner une méthode de paiement</option>
                            <option value="Espèce"
                                <?php echo (isset($GLOBALS['versementAModifier']) && $GLOBALS['versementAModifier']['methode_paiement'] == 'Espèce') ? 'selected' : ''; ?>>
                                Espèce</option>
                            <option value="Carte bancaire"
                                <?php echo (isset($GLOBALS['versementAModifier']) && $GLOBALS['versementAModifier']['methode_paiement'] == 'Carte bancaire') ? 'selected' : ''; ?>>
                                Carte bancaire</option>
                            <option value="Virement"
                                <?php echo (isset($GLOBALS['versementAModifier']) && $GLOBALS['versementAModifier']['methode_paiement'] == 'Virement') ? 'selected' : ''; ?>>
                                Virement</option>
                            <option value="Chèque"
                                <?php echo (isset($GLOBALS['versementAModifier']) && $GLOBALS['versementAModifier']['methode_paiement'] == 'Chèque') ? 'selected' : ''; ?>>
                                Chèque</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" id="submitButton" class="btn btn-primary">
                        <?php echo isset($GLOBALS['versementAModifier']) ? 'Mettre à jour' : 'Enregistrer'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Liste des versements -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Liste des versements</h3>
        </div>
        <div class="filter-bar">
            <div class="search-box">
                <input type="text" id="searchVersements" placeholder="Rechercher un versement..." class="input">
            </div>
            <div class="filter-actions">
                <button type="button" onclick="exporterVersements()" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Exporter
                </button>
                <button type="button" onclick="imprimerListeVersements()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Imprimer
                </button>
            </div>
        </div>
        <form id="versementsForm" method="POST" action="?page=gestion_scolarite">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Montant versé</th>
                            <th>Date versement</th>
                            <th>Méthode</th>
                            <th>Type versement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($versements_pages)): ?>
                        <?php foreach ($versements_pages as $versement): ?>
                        <tr class="versement-row">
                            <td>
                                <div class="table-user">
                                    <div class="table-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="table-user-info">
                                        <span class="table-user-name">
                                            <?php echo htmlspecialchars($versement['nom_etudiant'] . ' ' . $versement['prenom_etudiant']); ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(number_format($versement['montant'] ?? 0, 0, ',', ' ')); ?>
                                FCFA
                            </td>
                            <td>
                                <?php echo htmlspecialchars(date('d/m/Y', strtotime($versement['date_versement'] ?? 'now'))); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($versement['methode_paiement'] ?? 'N/A'); ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($versement['type_versement'] ?? 'N/A'); ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <?php if ($versement['type_versement'] === 'Tranche'): ?>
                                    <?php if (canEdit()): ?>
                                    <a href="?page=gestion_scolarite&action=mettre_a_jour_versement&id=<?php echo $versement['id_versement']; ?>"
                                        class="btn-icon btn-icon-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <button
                                        onclick="imprimerRecu(<?php echo $versement['id_versement']; ?>)"
                                        class="btn-icon btn-icon-success">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="7" class="table-empty">
                                Aucun versement trouvé.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <div class="pagination-mobile">
                <?php if ($page_actuelle > 1): ?>
                <a href="?page=gestion_scolarite&page_versements=<?php echo $page_actuelle - 1; ?>"
                    class="pagination-btn">
                    Précédent
                </a>
                <?php endif; ?>
                <?php if ($page_actuelle < $total_pages): ?>
                <a href="?page=gestion_scolarite&page_versements=<?php echo $page_actuelle + 1; ?>"
                    class="pagination-btn">
                    Suivant
                </a>
                <?php endif; ?>
            </div>
            <div class="pagination-desktop">
                <div class="pagination-info">
                    <p>
                        Affichage de <span><?php echo $debut + 1; ?></span> à
                        <span><?php echo min($debut + $items_par_page, $total_items); ?></span>
                        sur
                        <span><?php echo $total_items; ?></span> versements
                    </p>
                </div>
                <div class="pagination-controls">
                    <?php if ($page_actuelle > 1): ?>
                    <a href="?page=gestion_scolarite&page_versements=<?php echo $page_actuelle - 1; ?>"
                        class="pagination-btn pagination-btn-prev">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>

                    <?php
                    $debut_pagination = max(1, $page_actuelle - 2);
                    $fin_pagination = min($total_pages, $page_actuelle + 2);

                    if ($debut_pagination > 1) {
                        echo '<a href="?page=gestion_scolarite&page_versements=1" class="pagination-btn">1</a>';
                        if ($debut_pagination > 2) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                    }

                    for ($i = $debut_pagination; $i <= $fin_pagination; $i++) {
                        $classes = $i === $page_actuelle 
                            ? 'pagination-btn pagination-btn-active'
                            : 'pagination-btn';
                        echo "<a href=\"?page=gestion_scolarite&page_versements={$i}\" class=\"{$classes}\">{$i}</a>";
                    }

                    if ($fin_pagination < $total_pages) {
                        if ($fin_pagination < $total_pages - 1) {
                            echo '<span class="pagination-ellipsis">...</span>';
                        }
                        echo "<a href=\"?page=gestion_scolarite&page_versements={$total_pages}\" class=\"pagination-btn\">{$total_pages}</a>";
                    }
                    ?>

                    <?php if ($page_actuelle < $total_pages): ?>
                    <a href="?page=gestion_scolarite&page_versements=<?php echo $page_actuelle + 1; ?>"
                        class="pagination-btn pagination-btn-next">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentSelect = document.getElementById('studentSelect');
    const paymentAmount = document.getElementById('paymentAmount');
    const searchInput = document.getElementById('searchVersements');
    const submitButton = document.getElementById('submitButton');
    const paymentMethod = document.getElementById('paymentMethod');




    // Mettre à jour le montant maximum possible
    studentSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            const resteAPayer = selectedOption.dataset.resteAPayer;
            if (resteAPayer == 0) {
                paymentAmount.disabled = true;
                paymentAmount.value = '';
                paymentAmount.placeholder = 'Paiement déjà soldé';
                submitButton.disabled = true;
                submitButton.classList.add('btn-disabled');
            } else {
                paymentAmount.disabled = false;
                paymentAmount.max = resteAPayer;
                paymentAmount.placeholder = `Montant maximum: ${resteAPayer} FCFA`;
                submitButton.disabled = false;
                submitButton.classList.remove('btn-disabled');
            }

        }
    });

    // Recherche d'étudiants
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('.versement-row');

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Validation du formulaire
    document.getElementById('versementsForm').addEventListener('submit', function(e) {
        const amount = parseFloat(paymentAmount.value);
        const selectedOption = studentSelect.options[studentSelect.selectedIndex];
        const resteAPayer = parseFloat(selectedOption.dataset.resteAPayer);

        if (amount > resteAPayer) {
            e.preventDefault();
            alert('Le montant ne peut pas dépasser le reste à payer.');
        }

    });

});



// Fonction pour imprimer le reçu
// Supporte les appels rétrocompatibles : imprimerRecu(id) ou imprimerRecu(id, isVersement, idInscription)
function imprimerRecu(id, isVersement, idInscription) {
    // Si appelé avec un seul argument, on le considère comme un id_versement
    if (typeof isVersement === 'undefined') {
        isVersement = true;
    }

    if (!id) {
        alert('ID manquant pour l\'impression du reçu');
        return;
    }

    if (isVersement) {
        // id est un id_versement
        window.open(`?page=gestion_scolarite&action=imprimer_recu&id=${id}`, '_blank');
        return;
    }

    // id est un id_inscription : si idInscription est fourni, on l'utilise, sinon on utilise id
    var inscriptionId = idInscription || id;
    // Ouvrir la page qui imprimera le dernier versement pour cette inscription (serveur fera le fallback)
    window.open(`?page=gestion_scolarite&action=imprimer_recu&id=${inscriptionId}`, '_blank');
}

// Fonction pour exporter les versements
function exporterVersements() {
    const searchTerm = document.getElementById('searchVersements').value.toLowerCase();
    const rows = document.querySelectorAll('.versement-row');
    const selectedVersements = document.querySelectorAll('.versement-checkbox:checked');

    // Si aucun versement n'est sélectionné et qu'il y a une recherche, exporter les versements filtrés
    const versementsAExporter = selectedVersements.length > 0 ? selectedVersements :
        Array.from(rows).filter(row => {
            const text = row.textContent.toLowerCase();
            return searchTerm === '' || text.includes(searchTerm);
        });

    if (versementsAExporter.length === 0) {
        alert('Aucun versement à exporter.');
        return;
    }

    // Créer le contenu CSV
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Étudiant,Montant,Date,Méthode,Type\n";

    versementsAExporter.forEach(row => {
        const cells = row.querySelectorAll('td:not(:first-child):not(:last-child)');
        const rowData = Array.from(cells).map(cell => `"${cell.textContent.trim()}"`).join(',');
        csvContent += rowData + '\n';
    });

    // Télécharger le fichier
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', 'versements.csv');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Fonction pour imprimer la liste des versements
function imprimerListeVersements() {
    const searchTerm = document.getElementById('searchVersements').value.toLowerCase();
    const rows = document.querySelectorAll('.versement-row');
    const selectedVersements = document.querySelectorAll('.versement-checkbox:checked');

    // Si aucun versement n'est sélectionné et qu'il y a une recherche, imprimer les versements filtrés
    const versementsAImprimer = selectedVersements.length > 0 ? selectedVersements :
        Array.from(rows).filter(row => {
            const text = row.textContent.toLowerCase();
            return searchTerm === '' || text.includes(searchTerm);
        });

    if (versementsAImprimer.length === 0) {
        alert('Aucun versement à imprimer.');
        return;
    }

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Liste des versements</title>
            <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; }
                h2 { text-align: center; margin: 20px 0; }
                .info { text-align: center; margin: 10px 0; color: #666; }
                @media print {
                    body { margin: 0; padding: 20px; }
                    table { page-break-inside: auto; }
                    tr { page-break-inside: avoid; page-break-after: auto; }
                }
            </style>
        </head>
        <body>
            <h2>Liste des versements</h2>
            <div class="info">
                ${searchTerm ? `Filtre de recherche : "${searchTerm}"` : 'Liste complète des versements'}<br>
                Nombre de versements : ${versementsAImprimer.length}<br>
                Date d'impression : ${new Date().toLocaleDateString()}
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Étudiant</th>
                        <th>Montant</th>
                        <th>Date</th>
                        <th>Méthode</th>
                        <th>Type</th>
                    </tr>
                </thead>
                <tbody>
    `);

    versementsAImprimer.forEach(row => {
        const cells = row.querySelectorAll('td:not(:first-child):not(:last-child)');
        printWindow.document.write('<tr>');
        cells.forEach(cell => {
            printWindow.document.write(`<td>${cell.textContent.trim()}</td>`);
        });
        printWindow.document.write('</tr>');
    });

    printWindow.document.write(`
                </tbody>
            </table>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
    printWindow.focus();
    printWindow.close();
}

// Gérer les notifications
const successNotification = document.getElementById('successNotification');
const errorNotification = document.getElementById('errorNotification');

function removeNotification(notification) {
    if (notification) {
        notification.classList.add('alert-fade-out');
        setTimeout(() => notification.remove(), 500);
    }
}

if (successNotification) {
    setTimeout(() => removeNotification(successNotification), 5000);
}

if (errorNotification) {
    setTimeout(() => removeNotification(errorNotification), 5000);
}
</script>