<?php
$studentFile = $GLOBALS['studentFile'] ?? null;
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';

if (!$studentFile) {
    header('Location: ?page=admin_historique');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche Étudiant Archive</title>
    <style>
        .section-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0F4C75;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #0F4C75;
            box-shadow: 0 0 0 3px rgba(15, 76, 117, 0.1);
        }
        
        .form-input[readonly] {
            background-color: #f9fafb;
            color: #6b7280;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #0F4C75 0%, #3282B8 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Notifications -->
    <?php if ($messageSuccess): ?>
        <div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 animate-slide-in">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?php echo htmlspecialchars($messageSuccess); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($messageErreur): ?>
        <div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50 animate-slide-in">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span><?php echo htmlspecialchars($messageErreur); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <a href="?page=admin_historique" class="text-primary hover:text-primary-light mb-2 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Retour à l'historique
                </a>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-user-graduate text-primary mr-3"></i>
                    Fiche Étudiant Archive
                </h1>
            </div>
        </div>
        
        <form method="POST" action="?page=admin_historique&action=update_student">
            <input type="hidden" name="num_etu" value="<?php echo htmlspecialchars($studentFile['num_etu']); ?>">
            
            <!-- Personal Information -->
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-id-card mr-2"></i>
                    Informations Personnelles
                </h2>
                <div class="info-grid">
                    <div class="form-group">
                        <label class="form-label">Matricule</label>
                        <input type="text" class="form-input" value="<?php echo htmlspecialchars($studentFile['num_etu']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom</label>
                        <input type="text" name="nom_etu" class="form-input" value="<?php echo htmlspecialchars($studentFile['nom_etu']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prénoms</label>
                        <input type="text" name="prenom_etu" class="form-input" value="<?php echo htmlspecialchars($studentFile['prenom_etu']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email_etu" class="form-input" value="<?php echo htmlspecialchars($studentFile['email_etu']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Stage Information -->
            <?php if ($studentFile['stage']): ?>
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-briefcase mr-2"></i>
                    Informations de Stage
                </h2>
                <div class="info-grid">
                    <div class="form-group">
                        <label class="form-label">Entreprise</label>
                        <input type="text" class="form-input" value="<?php echo htmlspecialchars($studentFile['stage']['lib_entreprise'] ?? 'N/A'); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Maître de Stage</label>
                        <input type="text" class="form-input" value="<?php echo htmlspecialchars($studentFile['stage']['encadrant_entreprise'] ?? 'N/A'); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date Début</label>
                        <input type="text" class="form-input" value="<?php echo htmlspecialchars($studentFile['stage']['date_debut_stage'] ?? 'N/A'); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date Fin</label>
                        <input type="text" class="form-input" value="<?php echo htmlspecialchars($studentFile['stage']['date_fin_stage'] ?? 'N/A'); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Sujet de Stage</label>
                    <textarea class="form-input" rows="2" readonly><?php echo htmlspecialchars($studentFile['stage']['sujet_stage'] ?? 'N/A'); ?></textarea>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Theme/Report Information -->
            <?php if ($studentFile['rapport']): ?>
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-file-alt mr-2"></i>
                    Thème et Validation
                </h2>
                <div class="info-grid">
                    <div class="form-group col-span-2">
                        <label class="form-label">Thème</label>
                        <input type="text" name="theme_rapport" class="form-input" value="<?php echo htmlspecialchars($studentFile['rapport']['theme_rapport'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date de Validation Commission</label>
                        <input type="text" class="form-input" 
                               value="<?php echo !empty($studentFile['rapport']['date_validation']) ? htmlspecialchars(date('d/m/Y', strtotime($studentFile['rapport']['date_validation']))) : 'N/A'; ?>" 
                               readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Statut</label>
                        <select name="statut_rapport" class="form-input">
                            <option value="valider" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'valider' ? 'selected' : ''; ?>>Validé</option>
                            <option value="rejeter" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'rejeter' ? 'selected' : ''; ?>>Rejeté</option>
                            <option value="en_cours" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Observations</label>
                    <textarea class="form-input" rows="3" readonly><?php echo htmlspecialchars($studentFile['rapport']['commentaire_validation'] ?? 'Aucune observation'); ?></textarea>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Supervision/Encadrement -->
            <?php if ($studentFile['encadrement']): ?>
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>
                    Encadrement
                </h2>
                <div class="info-grid">
                    <?php if ($studentFile['encadrement']['encadrant']): ?>
                    <div class="form-group">
                        <label class="form-label">Encadreur Pédagogique</label>
                        <input type="text" class="form-input" 
                               value="<?php echo htmlspecialchars($studentFile['encadrement']['encadrant']['nom_enseignant'] . ' ' . $studentFile['encadrement']['encadrant']['prenom_enseignant']); ?>" 
                               readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Encadreur</label>
                        <input type="email" class="form-input" 
                               value="<?php echo htmlspecialchars($studentFile['encadrement']['encadrant']['mail_enseignant'] ?? ''); ?>" 
                               readonly>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($studentFile['encadrement']['directeur']): ?>
                    <div class="form-group">
                        <label class="form-label">Directeur de Mémoire</label>
                        <input type="text" class="form-input" 
                               value="<?php echo htmlspecialchars($studentFile['encadrement']['directeur']['nom_enseignant'] . ' ' . $studentFile['encadrement']['directeur']['prenom_enseignant']); ?>" 
                               readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Directeur</label>
                        <input type="email" class="form-input" 
                               value="<?php echo htmlspecialchars($studentFile['encadrement']['directeur']['mail_enseignant'] ?? ''); ?>" 
                               readonly>
                    </div>
                    <?php else: ?>
                    <div class="form-group col-span-2">
                        <label class="form-label">Directeur de Mémoire</label>
                        <input type="text" class="form-input" value="Non assigné (données antérieures)" readonly>
                        <p class="text-sm text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Le rôle de directeur de mémoire n'était pas encore en vigueur pour cette année
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Defense/Soutenance Information -->
            <?php if ($studentFile['soutenance']): ?>
            <div class="section-card">
                <h2 class="section-title">
                    <i class="fas fa-graduation-cap mr-2"></i>
                    Soutenance
                </h2>
                <div class="info-grid">
                    <div class="form-group">
                        <label class="form-label">Date de Soutenance</label>
                        <input type="text" class="form-input" 
                               value="<?php echo !empty($studentFile['soutenance']['date_soutenance']) ? htmlspecialchars(date('d/m/Y', strtotime($studentFile['soutenance']['date_soutenance']))) : 'N/A'; ?>" 
                               readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Heure</label>
                        <input type="text" class="form-input" 
                               value="<?php echo htmlspecialchars($studentFile['soutenance']['heure_soutenance'] ?? 'N/A'); ?>" 
                               readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Salle</label>
                        <input type="text" class="form-input" 
                               value="<?php echo htmlspecialchars($studentFile['soutenance']['lib_salle'] ?? 'N/A'); ?>" 
                               readonly>
                    </div>
                </div>
                
                <!-- Jury Members -->
                <?php if (!empty($studentFile['soutenance']['jury_members'])): ?>
                <div class="mt-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Composition du Jury</h3>
                    <div class="info-grid">
                        <?php 
                        $juryMembers = explode('|', $studentFile['soutenance']['jury_members']);
                        foreach ($juryMembers as $member): 
                            if (empty(trim($member))) continue;
                            list($name, $role) = explode(':', $member);
                        ?>
                        <div class="form-group">
                            <label class="form-label"><?php echo htmlspecialchars($role); ?></label>
                            <input type="text" class="form-input" value="<?php echo htmlspecialchars($name); ?>" readonly>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Notes -->
                <?php if (!empty($studentFile['soutenance']['notes'])): ?>
                <div class="mt-4">
                    <h3 class="font-semibold text-gray-900 mb-3">Notes et Évaluations</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Critère</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($studentFile['soutenance']['notes'] as $note): ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars($note['lib_critere'] ?? 'N/A'); ?></td>
                                    <td class="px-4 py-2 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($note['note']); ?>/20</td>
                                    <td class="px-4 py-2 text-sm text-gray-900"><?php echo htmlspecialchars(date('d/m/Y', strtotime($note['date_eval']))); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="flex justify-end gap-4 mt-6">
                <a href="?page=admin_historique" class="btn-secondary inline-block">
                    <i class="fas fa-times mr-2"></i>
                    Annuler
                </a>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
    
    <script>
        // Auto-hide notifications
        setTimeout(() => {
            document.querySelectorAll('.animate-slide-in').forEach(el => el.remove());
        }, 5000);
    </script>
</body>
</html>
