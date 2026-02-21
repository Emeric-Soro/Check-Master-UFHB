<?php
/**
 * Contenu d'une étape d'examen de candidature
 */
$etape = $etape ?? 1;
$data = $data ?? [];
?>

<div class="info-section has-background-light p-4 border-radius-lg mb-4">
    <h3 class="title is-5 mb-4">
        <?php
        switch ($etape) {
            case 1: echo '<i class="fas fa-money-check mr-2"></i>Vérification de la scolarité'; break;
            case 2: echo '<i class="fas fa-briefcase mr-2"></i>Vérification du stage'; break;
            case 3: echo '<i class="fas fa-graduation-cap mr-2"></i>Vérification du semestre'; break;
        }
        ?>
    </h3>

    <?php if ($etape == 1): ?>
        <div class="columns is-multiline">
            <div class="column is-6">
                <strong>Statut des paiements:</strong><br>
                <span class="tag is-info is-light"><?= htmlspecialchars($data['status'] ?? 'Inconnu') ?></span>
            </div>
            <div class="column is-6">
                <strong>Montant total:</strong><br>
                <span><?= htmlspecialchars($data['montant'] ?? '0') ?> FCFA</span>
            </div>
            <div class="column is-6">
                <strong>Montant payé:</strong><br>
                <span class="has-text-success font-bold"><?= htmlspecialchars($data['montant_paye'] ?? '0') ?> FCFA</span>
            </div>
            <div class="column is-6">
                <strong>Dernier paiement:</strong><br>
                <small><?= htmlspecialchars($data['dernierPaiement'] ?? 'Aucun') ?></small>
            </div>
        </div>
    <?php elseif ($etape == 2): ?>
        <div class="columns is-multiline">
            <div class="column is-12">
                <strong>Entreprise :</strong><br>
                <span><?= htmlspecialchars($data['entreprise'] ?? 'N/A') ?></span>
            </div>
            <div class="column is-12">
                <strong>Sujet :</strong><br>
                <span><?= htmlspecialchars($data['sujet'] ?? 'N/A') ?></span>
            </div>
            <div class="column is-6">
                <strong>Période :</strong><br>
                <small><?= htmlspecialchars($data['periode'] ?? 'N/A') ?></small>
            </div>
            <div class="column is-6">
                <strong>Encadrant :</strong><br>
                <span><?= htmlspecialchars($data['encadrant'] ?? 'N/A') ?></span>
            </div>
        </div>
    <?php elseif ($etape == 3): ?>
        <div class="columns is-multiline">
            <div class="column is-6">
                <strong>Semestre actuel:</strong><br>
                <span><?= htmlspecialchars($data['semestre'] ?? 'N/A') ?></span>
            </div>
            <div class="column is-6">
                <strong>Moyenne générale:</strong><br>
                <span class="tag is-success is-large"><?= htmlspecialchars($data['moyenne'] ?? '0') ?></span>
            </div>
            <div class="column is-12">
                <strong>Unités validées:</strong><br>
                <span class="has-text-info"><?= htmlspecialchars($data['unites'] ?? '0') ?></span>
            </div>
        </div>
        <?php if (empty($data['moyenne']) || $data['moyenne'] == '0'): ?>
            <div class="notification is-warning is-light mt-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Note :</strong> Aucune note n'a été trouvée pour cet étudiant.
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
