<?php
/**
 * Formulaire de versement de scolarité
 */
$etudiants = $etudiants ?? [];
$versement_edit = $versement_edit ?? null;
$action_url = '?page=gestion_scolarite' . ($versement_edit ? '&action=mettre_a_jour_versement' : '&action=enregistrer_versement');
?>

<form id="paymentForm" method="POST" action="<?= $action_url ?>">
    <?php if ($versement_edit): ?>
        <input type="hidden" name="id_versement" value="<?= htmlspecialchars($versement_edit['id_versement']) ?>">
    <?php endif; ?>

    <div class="field">
        <label class="label">Étudiant <span class="has-text-danger">*</span></label>
        <div class="control has-icons-left">
            <div class="select is-fullwidth">
                <select id="studentSelect" name="id_etudiant" required>
                    <option value="">Sélectionner un étudiant</option>
                    <?php foreach ($etudiants as $etudiant): ?>
                        <option value="<?= $etudiant['id_etudiant'] ?>"
                            data-montant-total="<?= htmlspecialchars($etudiant['montant_scolarite'] ?? 0) ?>"
                            data-montant-paye="<?= htmlspecialchars($etudiant['montant_paye'] ?? 0) ?>"
                            data-reste-a-payer="<?= htmlspecialchars($etudiant['reste_a_payer'] ?? 0) ?>"
                            <?= ($versement_edit && $versement_edit['id_inscription'] == $etudiant['id_inscription']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($etudiant['nom'] . ' ' . $etudiant['prenom']) ?> - <?= htmlspecialchars($etudiant['nom_niveau']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <span class="icon is-left"><i class="fas fa-user-graduate"></i></span>
        </div>
    </div>

    <div class="columns">
        <div class="column">
            <div class="field">
                <label class="label">Montant (FCFA) <span class="has-text-danger">*</span></label>
                <div class="control has-icons-left">
                    <input type="number" id="paymentAmount" name="montant" class="input" required
                        value="<?= htmlspecialchars($versement_edit['montant'] ?? '') ?>" placeholder="0">
                    <span class="icon is-left"><i class="fas fa-money-bill-wave"></i></span>
                </div>
            </div>
        </div>
        <div class="column">
            <div class="field">
                <label class="label">Méthode <span class="has-text-danger">*</span></label>
                <div class="control has-icons-left">
                    <div class="select is-fullwidth">
                        <select name="methode_paiement" required>
                            <option value="">Méthode...</option>
                            <?php 
                            $methodes = ['Espèce', 'Carte bancaire', 'Virement', 'Chèque'];
                            foreach($methodes as $m): 
                            ?>
                                <option value="<?= $m ?>" <?= ($versement_edit && $versement_edit['methode_paiement'] === $m) ? 'selected' : '' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <span class="icon is-left"><i class="fas fa-wallet"></i></span>
                </div>
            </div>
        </div>
    </div>

    <div class="field is-grouped is-grouped-right mt-5">
        <div class="control">
            <button type="submit" class="button is-primary">
                <span class="icon"><i class="fas fa-save"></i></span>
                <span><?= $versement_edit ? 'Mettre à jour' : 'Enregistrer le versement' ?></span>
            </button>
        </div>
    </div>
</form>
