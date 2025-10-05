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







<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail Étudiant - Mes Résultats</title>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="mb-10 text-center animate-fade-in">
            <h1 class="text-4xl font-bold text-green-800 mb-2">Mon Portail Académique</h1>
            <p class="text-xl text-green-600">Consultez vos résultats et bulletins de notes</p>
            <div class="flex justify-center mt-4">
                <div class="bg-white rounded-full shadow-md px-6 py-2 inline-flex items-center">
                    <i class="fas fa-user-graduate text-indigo-500 mr-2"></i>
                    <span class="font-medium">Étudiant: <?php echo htmlspecialchars($etudiant->nom_etu . ' ' . $etudiant->prenom_etu); ?> | Numéro étudiant: <?php echo htmlspecialchars($etudiant->num_etu); ?></span>
                </div>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="card-gradient-1 rounded-xl shadow-lg p-6 flex items-center animate-fade-in">
                <div class="bg-blue-100 p-3 rounded-full mr-4">
                    <i class="fas fa-book text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-white text-sm">Moyenne Générale</p>
                    <h3 class="text-2xl font-bold text-white"><?php echo $moyenneGenerale !== null ? number_format($moyenneGenerale, 2) . '/20' : 'N/A'; ?></h3>
                </div>
            </div>

            <div class="card-gradient-2 rounded-xl shadow-lg p-6 flex items-center animate-fade-in">
                <div class="bg-green-100 p-3 rounded-full mr-4">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-white text-sm">Modules Validés</p>
                    <h3 class="text-2xl font-bold text-white"><?php echo $nbUeValide; ?></h3>
                </div>
            </div>

            <div class="card-gradient-3 rounded-xl shadow-lg p-6 flex items-center animate-fade-in">
                <div class="bg-amber-100 p-3 rounded-full mr-4">
                    <i class="fas fa-chart-line text-amber-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-white text-sm">Classement</p>
                    <h3 class="text-2xl font-bold text-white"><?php echo $classement !== null ? $classement . '/' . $totalEtudiants : 'N/A'; ?></h3>
                </div>
            </div>


        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden animate-fade-in">
            <!-- Toolbar -->
            <div class="bg-indigo-700 px-6 py-4 flex flex-wrap justify-between items-center">
                <h2 class="text-xl font-bold text-white">
                    <i class="fas fa-table mr-2"></i> Bulletin de Notes
                </h2>
                <div class="flex space-x-2 mt-2 sm:mt-0">
                   
                    <button id="pdfBtn"
                        class="bg-white text-indigo-700 px-4 py-2 rounded-lg hover:bg-indigo-50 transition flex items-center"
                        onclick="window.location.href='?action=export_pdf'">
                        <i class="fas fa-file-pdf mr-2"></i> PDF
                    </button>
                    <button id="exportBtn" onclick="exportCSV()"
                        class="bg-white text-indigo-700 px-4 py-2 rounded-lg hover:bg-indigo-50 transition flex items-center">
                        <i class="fas fa-file-excel mr-2"></i> Excel
                    </button>
                    <div class="relative">
                        <select id="semesterFilter"
                            class="appearance-none bg-white text-indigo-700 pl-4 pr-8 py-2 rounded-lg hover:bg-indigo-50 transition cursor-pointer">
                            <option value="all">Tous les semestres</option>
                            <?php if (!empty($semestres)) : ?>
                                <?php foreach ($semestres as $semestre) : ?>
                                    <option value="<?php echo htmlspecialchars($semestre->id_semestre); ?>">
                                        <?php echo htmlspecialchars($semestre->lib_semestre); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-3 text-indigo-700 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="gradesTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Module</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Professeur</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Crédits</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Note</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Appréciation</th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Semestre</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($notes)) : ?>
                            <?php foreach ($notes as $note) : ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?php echo htmlspecialchars($note->lib_ue); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">-</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?php echo htmlspecialchars($note->credit); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="grade-<?php echo $note->moyenne >= 16 ? 'A' : ($note->moyenne >= 14 ? 'B' : ($note->moyenne >= 12 ? 'C' : ($note->moyenne >= 10 ? 'D' : 'F'))); ?> px-3 py-1 rounded-full text-sm font-semibold"><?php echo htmlspecialchars($note->moyenne); ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500"><?php echo htmlspecialchars($note->commentaire); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">
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
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="text-center text-gray-500 py-8">Aucune note disponible.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>


        </div>


    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animation des éléments
            gsap.from(".animate-fade-in", {
                opacity: 0,
                y: 20,
                duration: 0.6,
                stagger: 0.1,
                ease: "power2.out"
            });

            // Filtrage par semestre
            const semesterFilter = document.getElementById('semesterFilter');
            semesterFilter.addEventListener('change', function() {
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

            // Bouton Imprimer
            document.getElementById('printBtn').addEventListener('click', function() {
                window.print();
            });

            // Bouton PDF (utilisant jsPDF et html2canvas)
            document.getElementById('pdfBtn').addEventListener('click', function() {
                const {
                    jsPDF
                } = window.jspdf;
                const element = document.querySelector('.bg-white.rounded-2xl');

                html2canvas(element).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdf = new jsPDF('p', 'mm', 'a4');
                    const imgProps = pdf.getImageProperties(imgData);
                    const pdfWidth = pdf.internal.pageSize.getWidth();
                    const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

                    pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                    pdf.save('bulletin-notes.pdf');
                });
            });

            

            // Effet hover sur les lignes du tableau
            const tableRows = document.querySelectorAll('#gradesTable tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', () => {
                    row.classList.add('bg-indigo-50');
                });
                row.addEventListener('mouseleave', () => {
                    row.classList.remove('bg-indigo-50');
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
            const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' }); // \uFEFF pour compat Excel
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'bulletin-notes.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>

</html>