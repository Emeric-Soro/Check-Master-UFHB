<div class="container">
    <div class="page-header">
        <h1>Gestion des Étudiants</h1>
        <p class="page-subtitle">Gérez les profils et inscriptions des étudiants</p>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
        <?php if (canCreate()): ?>
        <!-- Carte pour ajouter un étudiant -->
        <a href="?page=gestion_etudiants&action=ajouter_des_etudiants" class="action-card">
            <div class="action-card-icon success">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="action-card-content">
                <h3 class="action-card-title">Ajouter un Étudiant</h3>
                <p class="action-card-description">
                    Créez un nouveau profil étudiant dans la base de données. Cette option permet d'ajouter
                    manuellement les informations d'un nouvel étudiant.
                </p>
                <div class="action-card-link">
                    Accéder à l'ajout d'étudiant
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>
        </a>

        <!-- Carte pour inscrire un étudiant -->
        <a href="?page=gestion_etudiants&action=inscrire_des_etudiants" class="action-card">
            <div class="action-card-icon primary">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="action-card-content">
                <h3 class="action-card-title">Inscrire un Étudiant</h3>
                <p class="action-card-description">
                    Inscrivez un étudiant à une formation ou un cours. Cette option permet de gérer les inscriptions
                    académiques des étudiants existants.
                </p>
                <div class="action-card-link">
                    Accéder aux inscriptions
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>
        </a>
        <?php endif; ?>
    </div>
</div>