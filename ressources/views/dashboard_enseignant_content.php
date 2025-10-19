<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Enseignant.php';
require_once __DIR__ . '/../../app/models/NiveauEtude.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/models/Ue.php';
require_once __DIR__ . '/../../app/models/Ecue.php';

$pdo = Database::getConnection();
$enseignantModel = new Enseignant($pdo);
$niveauEtudeModel = new NiveauEtude($pdo);
$etudiantModel = new Etudiant($pdo);
$ueModel = new Ue($pdo);
$ecueModel = new Ecue($pdo);
$enseignant = $enseignantModel->getEnseignantByLogin($_SESSION['login_utilisateur']);
$enseignantId = $enseignant->id_enseignant;
if (!$enseignantId) {
    die("Enseignant non trouvé ou non connecté.");
}

$total_etudiants = $GLOBALS['total_etudiants'] ?? 0;
$total_ues = $GLOBALS['total_ues'] ?? 0;
$total_ecues = $GLOBALS['total_ecues'] ?? 0;
$mes_cours = $GLOBALS['mes_cours'] ?? [];
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-primary">Bonjour, Professeur!</h1>
                    <p class="text-base-content/60 mt-1">Bienvenue à nouveau sur votre tableau de bord.</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p class="text-sm text-base-content/60"><?php echo date('d/m/Y'); ?></p>
                        <p class="text-sm text-base-content/40"><?php echo date('H:i'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Card: Étudiants -->
        <div class="card bg-gradient-to-br from-primary to-primary-light text-white shadow-lg hover:shadow-xl transition-all">
            <div class="card-body">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="text-sm font-medium opacity-80">Étudiants (vos UE/ECUE)</div>
                        <div class="stat-value text-3xl mt-2"><?php echo $GLOBALS['total_etudiants'] ?? 0; ?></div>
                        <div class="text-xs opacity-70 mt-1">Étudiants suivant vos UE/ECUE</div>
                    </div>
                    <div class="bg-white/20 p-4 rounded-xl">
                        <i class="fas fa-users text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: UE -->
        <div class="card bg-gradient-to-br from-secondary to-warning text-white shadow-lg hover:shadow-xl transition-all">
            <div class="card-body">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="text-sm font-medium opacity-80">UE prises en charge</div>
                        <div class="stat-value text-3xl mt-2"><?php echo $GLOBALS['total_ues'] ?? 0; ?></div>
                        <div class="text-xs opacity-70 mt-1">Total UE</div>
                    </div>
                    <div class="bg-white/20 p-4 rounded-xl">
                        <i class="fas fa-book text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card: ECUE -->
        <div class="card bg-gradient-to-br from-accent to-green-400 text-white shadow-lg hover:shadow-xl transition-all">
            <div class="card-body">
                <div class="flex justify-between items-center">
                    <div>
                        <div class="text-sm font-medium opacity-80">ECUE pris en charge</div>
                        <div class="stat-value text-3xl mt-2"><?php echo $GLOBALS['total_ecues'] ?? 0; ?></div>
                        <div class="text-xs opacity-70 mt-1">Total ECUE</div>
                    </div>
                    <div class="bg-white/20 p-4 rounded-xl">
                        <i class="fas fa-layer-group text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mes cours -->
    <div class="card bg-base-100 shadow-lg">
        <div class="card-body">
            <div class="flex items-center gap-2 mb-4 pb-4 border-b border-base-300">
                <i class="fas fa-book text-primary text-xl"></i>
                <h2 class="card-title text-primary">Mes Cours</h2>
            </div>
            <?php if (!empty($mes_cours)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($mes_cours as $cours): ?>
                        <div class="card bg-base-200 shadow-sm hover:shadow-md transition-all">
                            <div class="card-body p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-semibold text-base-content"><?php echo htmlspecialchars($cours['nom']); ?></h3>
                                    <span class="badge badge-primary badge-sm">
                                        <?php echo htmlspecialchars($cours['niveau']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-base-content/60">
                                    <i class="fas fa-users text-xs"></i>
                                    <span><?php echo $cours['nombre_etudiants']; ?> étudiants inscrits</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <i class="fas fa-chalkboard-teacher text-6xl text-base-content/20 mb-4"></i>
                    <p class="text-base-content/60">Aucun cours assigné</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Données pour les graphiques (à remplacer par les vraies données PHP)
    const evaluationsData = <?php echo json_encode($GLOBALS['evaluations_par_mois'] ?? []); ?>;
    const typesEvaluationsData = <?php echo json_encode($GLOBALS['types_evaluations'] ?? []); ?>;
    const etudiantsNiveauData = <?php echo json_encode($GLOBALS['etudiants_par_niveau'] ?? []); ?>;
    const distributionNotesData = <?php echo json_encode($GLOBALS['distribution_notes'] ?? []); ?>;
</script>