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

<div class="container">
    <div class="page-header mb-lg">
        <div>
            <h1 class="text-lg font-bold">Bonjour, Professeur!</h1>
            <p class="text-sm text-muted mt-1">Bienvenue à nouveau sur votre tableau de bord.</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right">
                <p class="text-sm text-muted"><?php echo date('d/m/Y'); ?></p>
                <p class="text-sm text-muted"><?php echo date('H:i'); ?></p>
            </div>
        </div>
    </div>

    <div class="stats-grid mb-lg">
        <?php
        echo renderStatsCard('Étudiants (vos UE/ECUE)', $GLOBALS['total_etudiants'] ?? 0, 'users', 'primary', 'Étudiants suivant vos UE/ECUE (tous niveaux confondus)');
        echo renderStatsCard('UE prises en charge', $GLOBALS['total_ues'] ?? 0, 'book', 'warning', 'Total UE');
        echo renderStatsCard('ECUE pris en charge', $GLOBALS['total_ecues'] ?? 0, 'layer-group', 'success', 'Total ECUE');
        ?>
    </div>

    <div class="card">
        <div class="card-header border-b">
            <h2 class="card-title">
                <i class="fas fa-book text-primary mr-2"></i>
                Mes Cours
            </h2>
        </div>
        <div class="p-lg grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php if (!empty($mes_cours)): ?>
                <?php foreach ($mes_cours as $cours): ?>
                    <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="font-semibold"><?php echo htmlspecialchars($cours['nom']); ?></h3>
                            <?php echo renderBadge(htmlspecialchars($cours['niveau']), 'info'); ?>
                        </div>
                        <p class="text-sm text-muted"><?php echo $cours['nombre_etudiants']; ?> étudiants inscrits</p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-8">
                    <?php echo renderEmptyState('Aucun cours assigné', 'fa-chalkboard-teacher'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
