<?php
/**
 * Fiche Étudiant Archive
 */
$etudiant = $data['etudiant'] ?? null;
$parcours = $data['parcours'] ?? [];
$soutenances = $data['soutenances'] ?? [];
$documents = $data['documents'] ?? [];
$reclamations = $data['reclamations'] ?? [];

if (!$etudiant) {
    echo '<div class="cm-alert cm-alert-danger">Étudiant non trouvé</div>';
    return;
}

$onglet = $_GET['onglet'] ?? 'infos';
?>
<div class="cm-fiche-etudiant">
    <!-- En-tête -->
    <div class="cm-page-header cm-mb-4">
        <div class="cm-flex cm-justify-between cm-items-start">
            <div>
                <h1 class="cm-page-title">
                    <?php echo htmlspecialchars($etudiant->nom_etu . ' ' . $etudiant->prenom_etu); ?>
                </h1>
                <p class="cm-page-subtitle">
                    <code><?php echo htmlspecialchars($etudiant->num_carte_etud); ?></code> • 
                    <?php echo htmlspecialchars($etudiant->promotion_etu); ?>
                </p>
            </div>
            <div class="cm-flex cm-gap-2">
                <a href="?page=parcours_etudiant&id=<?php echo urlencode($etudiant->num_carte_etud); ?>" 
                   class="cm-btn cm-btn-outline">
                    <i class="fas fa-route"></i> Parcours
                </a>
                <a href="?page=archives_etudiants" class="cm-btn cm-btn-outline">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>
    </div>

    <!-- Onglets -->
    <div class="cm-tabs cm-mb-4">
        <a href="?page=fiche_etudiant_archive&id=<?php echo $etudiant->num_carte_etud; ?>&onglet=infos" 
           class="cm-tab <?php echo $onglet === 'infos' ? 'cm-active' : ''; ?>">Informations</a>
        <a href="?page=fiche_etudiant_archive&id=<?php echo $etudiant->num_carte_etud; ?>&onglet=parcours" 
           class="cm-tab <?php echo $onglet === 'parcours' ? 'cm-active' : ''; ?>">Parcours</a>
        <a href="?page=fiche_etudiant_archive&id=<?php echo $etudiant->num_carte_etud; ?>&onglet=soutenance" 
           class="cm-tab <?php echo $onglet === 'soutenance' ? 'cm-active' : ''; ?>">Soutenance</a>
        <a href="?page=fiche_etudiant_archive&id=<?php echo $etudiant->num_carte_etud; ?>&onglet=documents" 
           class="cm-tab <?php echo $onglet === 'documents' ? 'cm-active' : ''; ?>">Documents</a>
        <a href="?page=fiche_etudiant_archive&id=<?php echo $etudiant->num_carte_etud; ?>&onglet=reclamations" 
           class="cm-tab <?php echo $onglet === 'reclamations' ? 'cm-active' : ''; ?>">Réclamations</a>
    </div>

    <!-- Contenu onglet -->
    <div class="cm-tab-content">
        <?php switch ($onglet): 
            case 'infos': ?>
                <div class="cm-card">
                    <div class="cm-card-header">
                        <h3 class="cm-card-title">Informations Personnelles</h3>
                    </div>
                    <div class="cm-card-body">
                        <div class="cm-grid-2 cm-gap-4">
                            <div>
                                <p class="cm-mb-2"><strong>Matricule:</strong> <?php echo htmlspecialchars($etudiant->num_carte_etud); ?></p>
                                <p class="cm-mb-2"><strong>Nom:</strong> <?php echo htmlspecialchars($etudiant->nom_etu); ?></p>
                                <p class="cm-mb-2"><strong>Prénom:</strong> <?php echo htmlspecialchars($etudiant->prenom_etu); ?></p>
                                <p class="cm-mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($etudiant->email_etu); ?></p>
                            </div>
                            <div>
                                <p class="cm-mb-2"><strong>Date naissance:</strong> <?php echo $etudiant->date_naiss_etu ?? '-'; ?></p>
                                <p class="cm-mb-2"><strong>Genre:</strong> <?php echo htmlspecialchars($etudiant->libelle_genre ?? '-'); ?></p>
                                <p class="cm-mb-2"><strong>Promotion:</strong> <?php echo htmlspecialchars($etudiant->promotion_etu); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php break;

            case 'parcours': ?>
                <div class="cm-card">
                    <div class="cm-card-header">
                        <h3 class="cm-card-title">Parcours Académique</h3>
                    </div>
                    <div class="cm-card-body">
                        <?php if (empty($parcours)): ?>
                            <p class="cm-text-muted">Aucune inscription trouvée</p>
                        <?php else: ?>
                            <table class="cm-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Niveau</th>
                                        <th>Statut</th>
                                        <th>Moyenne M1</th>
                                        <th>Moyenne M2</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($parcours as $p): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($p->date_inscription)); ?></td>
                                            <td><?php echo htmlspecialchars($p->lib_niv_etude); ?></td>
                                            <td><?php echo htmlspecialchars($p->statut_inscription); ?></td>
                                            <td><?php echo $p->moyenne_M1 ?? '-'; ?></td>
                                            <td><?php echo $p->moyenne_M2 ?? '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                <?php break;

            case 'soutenance': ?>
                <div class="cm-card">
                    <div class="cm-card-header">
                        <h3 class="cm-card-title">Soutenance</h3>
                    </div>
                    <div class="cm-card-body">
                        <?php if (empty($soutenances)): ?>
                            <p class="cm-text-muted">Aucune soutenance programmée</p>
                        <?php else: ?>
                            <?php foreach ($soutenances as $s): ?>
                                <div class="cm-mb-3 cm-p-3 cm-bg-light cm-rounded">
                                    <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($s->date_soutenance)); ?> à <?php echo $s->heure_soutenance; ?></p>
                                    <p><strong>Salle:</strong> <?php echo htmlspecialchars($s->lib_salle ?? '-'); ?></p>
                                    <p><strong>Thème:</strong> <?php echo htmlspecialchars($s->theme_soutenance); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php break;

            case 'documents': ?>
                <div class="cm-card">
                    <div class="cm-card-header">
                        <h3 class="cm-card-title">Documents</h3>
                    </div>
                    <div class="cm-card-body">
                        <?php if (empty($documents)): ?>
                            <p class="cm-text-muted">Aucun document</p>
                        <?php else: ?>
                            <div class="cm-list-group">
                                <?php foreach ($documents as $d): ?>
                                    <div class="cm-list-item cm-flex cm-justify-between cm-items-center">
                                        <div>
                                            <i class="fas fa-file-pdf cm-text-danger"></i>
                                            <strong><?php echo htmlspecialchars($d->titre); ?></strong>
                                            <small class="cm-text-muted">(<?php echo $d->type; ?>)</small>
                                        </div>
                                        <a href="?page=telecharger_document&type=<?php echo $d->type; ?>&id=<?php echo $d->id; ?>" 
                                           class="cm-btn cm-btn-sm cm-btn-outline">
                                            <i class="fas fa-download"></i> Télécharger
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php break;

            case 'reclamations': ?>
                <div class="cm-card">
                    <div class="cm-card-header">
                        <h3 class="cm-card-title">Réclamations</h3>
                    </div>
                    <div class="cm-card-body">
                        <?php if (empty($reclamations)): ?>
                            <p class="cm-text-muted">Aucune réclamation</p>
                        <?php else: ?>
                            <?php foreach ($reclamations as $r): ?>
                                <div class="cm-mb-3 cm-p-3 cm-border cm-rounded">
                                    <div class="cm-flex cm-justify-between">
                                        <strong><?php echo htmlspecialchars($r->objet_reclamation); ?></strong>
                                        <span class="cm-badge cm-badge-<?php echo $r->libelle_statut_reclamation === 'Résolue' ? 'success' : 'warning'; ?>">
                                            <?php echo htmlspecialchars($r->libelle_statut_reclamation); ?>
                                        </span>
                                    </div>
                                    <p class="cm-text-muted cm-mt-1">
                                        <?php echo date('d/m/Y', strtotime($r->date_creation)); ?>
                                    </p>
                                    <p><?php echo htmlspecialchars($r->description); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php break;
        endswitch; ?>
    </div>
</div>
