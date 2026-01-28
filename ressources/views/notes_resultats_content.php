<?php

// Récupération des données depuis le contrôleur
$etudiant = $GLOBALS['etudiant'] ?? null;
$moyenneGenerale = $GLOBALS['moyenneGenerale'] ?? null;
$nbUeValide = $GLOBALS['nbUeValide'] ?? 0;
$classement = $GLOBALS['classement'] ?? null;
$totalEtudiants = $GLOBALS['totalEtudiants'] ?? 0;
$notes = $GLOBALS['notes'] ?? [];
$semestres = $GLOBALS['semestres'] ?? [];

?>

<div class="container">
        <!-- Header -->
        <header class="mb-10 text-center animate-fade-in">
            <h1 class="text-4xl font-bold text-green-800 mb-2">Mon Portail Académique</h1>
            <p class="text-xl text-green-600">Consultez vos résultats et bulletins de notes</p>
            <div class="flex justify-center mt-4">
                <div class="bg-white rounded-full shadow-md px-6 py-2 inline-flex items-center">
                    <i class="fas fa-user-graduate text-indigo-500 mr-2"></i>
                    <span class="font-medium">Étudiant:
                        <?php echo htmlspecialchars($etudiant->nom_etu . ' ' . $etudiant->prenom_etu); ?> | Numéro
                        étudiant: <?php echo htmlspecialchars($etudiant->num_etu); ?></span>
                </div>
            </div>
        </header>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stats-card stats-card-primary">
            <div class="stats-card-icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="stats-card-content">
                <p class="stats-card-label">Moyenne Générale</p>
                <h3 class="stats-card-value">
                    <?php echo $moyenneGenerale !== null ? number_format($moyenneGenerale, 2) . '/20' : 'N/A'; ?>
                </h3>
            </div>
        </div>

        <div class="stats-card stats-card-success">
            <div class="stats-card-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stats-card-content">
                <p class="stats-card-label">Modules Validés</p>
                <h3 class="stats-card-value"><?php echo $nbUeValide; ?></h3>
            </div>
        </div>

        <div class="stats-card stats-card-warning">
            <div class="stats-card-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stats-card-content">
                <p class="stats-card-label">Classement</p>
                <h3 class="stats-card-value">
                    <?php echo $classement !== null ? $classement . '/' . $totalEtudiants : 'N/A'; ?></h3>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="card">
        <!-- Toolbar -->
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-table"></i> Bulletin de Notes
            </h2>
            <div class="toolbar">
                <button id="pdfBtn" class="btn btn-secondary"
                    onclick="window.location.href='?action=export_pdf'">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button id="exportBtn" onclick="exportCSV()" class="btn btn-secondary">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <select id="semesterFilter" class="form-control">
                    <option value="all">Tous les semestres</option>
                    <?php if (!empty($semestres)): ?>
                        <?php foreach ($semestres as $semestre): ?>
                            <option value="<?php echo htmlspecialchars($semestre->id_semestre); ?>">
                                <?php echo htmlspecialchars($semestre->lib_semestre); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Table -->
        <div class="table-wrapper">
            <table class="table" id="gradesTable">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Professeur</th>
                        <th>Crédits</th>
                        <th>Note</th>
                        <th>Appréciation</th>
                        <th>Semestre</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($notes)): ?>
                        <?php foreach ($notes as $note): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($note->lib_ue); ?></td>
                                <td>-</td>
                                <td><?php echo htmlspecialchars($note->credit); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $note->moyenne >= 16 ? 'success' : ($note->moyenne >= 14 ? 'info' : ($note->moyenne >= 12 ? 'warning' : ($note->moyenne >= 10 ? 'muted' : 'danger'))); ?>">
                                        <?php echo htmlspecialchars($note->moyenne); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($note->commentaire); ?></td>
                                <td>
                                    <?php
                                    if (!empty($note->lib_semestre)) {
                                        echo htmlspecialchars($note->lib_semestre);
                                    } elseif (!empty($note->id_semestre)) {
                                        echo htmlspecialchars($note->id_semestre);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <p class="empty-state-text">Aucune note disponible.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Animation des éléments
        if (typeof gsap !== 'undefined') {
            gsap.from(".animate-fade-in", {
                opacity: 0,
                y: 20,
                duration: 0.6,
                stagger: 0.1,
                ease: "power2.out"
            });
        }

        // Filtrage par semestre
        const semesterFilter = document.getElementById('semesterFilter');
        semesterFilter.addEventListener('change', function () {
            const selectedSemester = this.value;
            const rows = document.querySelectorAll('#gradesTable tbody tr');

            rows.forEach(row => {
                const semesterCell = row.querySelector('td:nth-child(6)');
                if (selectedSemester === 'all' || semesterCell.textContent ===
                    selectedSemester) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Bouton Imprimer (si existe)
        const printBtn = document.getElementById('printBtn');
        if (printBtn) {
            printBtn.addEventListener('click', function () {
                window.print();
            });
        }

        // Bouton PDF (utilisant jsPDF et html2canvas)
        document.getElementById('pdfBtn').addEventListener('click', function () {
            if (typeof jspdf !== 'undefined' && typeof html2canvas !== 'undefined') {
                const {
                    jsPDF
                } = window.jspdf;
                const element = document.querySelector('.card');

                html2canvas(element).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    const imgProps = pdf.getImageProperties(imgData);
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

                    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                    pdf.save('bulletin-notes.pdf');
                });
            }
        });

        // Effet hover sur les lignes du tableau
        const tableRows = document.querySelectorAll('#gradesTable tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.classList.add('hover');
            });
            row.addEventListener('mouseleave', () => {
                row.classList.remove('hover');
            });
        });
    });

    function exportCSV() {
        const table = document.getElementById('gradesTable');
        let csv = [];

        // En-têtes
        const headers = Array.from(table.querySelectorAll('thead th')).map(th => `"${th.textContent.trim()}"`);
        csv.push(headers.join(';'));

        // Lignes visibles
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cells = Array.from(row.querySelectorAll('td'));
                const rowData = cells.map(td => `"${td.textContent.trim().replace(/"/g, '""')}"`);
                csv.push(rowData.join(';'));
            }
        });

        const csvContent = csv.join('\n');
        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'bulletin-notes.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
</script>