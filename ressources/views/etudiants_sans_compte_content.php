<?php
/**
 * P2.7 — Étudiants sans compte utilisateur
 * Slug: etudiants_sans_compte | Permission: gestion_utilisateurs
 *
 * Affiche la liste des étudiants sans compte avec cases à cocher
 * et un bouton pour créer les comptes en masse (AJAX).
 */
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Utilisateur.php';

$utilisateurModel = new Utilisateur(Database::getConnection());
$etudiants = $utilisateurModel->getEtudiantsInscritsNonUtilisateurs();
$isHubContext = ((string) ($_GET['page'] ?? '') === 'suivi_scolarite')
    || (((string) ($_GET['page'] ?? '') === 'parametres_generaux') && ((string) ($_GET['action'] ?? '') === 'suivi_scolarite'));
$accountsActionUrl = $isHubContext
    ? '?page=parametres_generaux&action=suivi_scolarite&tab=etudiants_sans_compte&sub_action=creer_comptes_masse'
    : '?page=etudiants_sans_compte&action=creer_comptes_masse';

// Traitement AJAX pour la création en masse
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && (isset($_GET['sub_action']) && $_GET['sub_action'] === 'creer_comptes_masse' || isset($_GET['action']) && $_GET['action'] === 'creer_comptes_masse')) {
    try {
        cm_csrf_verify($_POST['csrf_token'] ?? '');
    } catch (Exception $e) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Session expirée.']);
        exit;
    }

    if (!canCreate('gestion_utilisateurs')) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé.']);
        exit;
    }

    $selected = $_POST['selected'] ?? [];
    if (empty($selected) || !is_array($selected)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Aucun étudiant sélectionné.']);
        exit;
    }

    $typeUtilisateur = (int) ($_POST['id_type_utilisateur'] ?? 0);
    $groupeUtilisateur = (int) ($_POST['id_groupe_utilisateur'] ?? 0);
    $niveauAcces = (int) ($_POST['id_niveau_acces'] ?? 0);

    if ($typeUtilisateur <= 0 || $groupeUtilisateur <= 0) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Veuillez spécifier le type et le groupe utilisateur.']);
        exit;
    }

    $utilisateurs = [];
    $errors = [];

    foreach ($selected as $numEtu) {
        $etu = null;
        foreach ($etudiants as $e) {
            if (($e->num_etu ?? '') === $numEtu || ($e->num_carte_etud ?? '') === $numEtu) {
                $etu = $e;
                break;
            }
        }
        if (!$etu) {
            $errors[] = "Étudiant $numEtu non trouvé";
            continue;
        }

        if (!$utilisateurModel->isEtudiantInscrit($etu->num_etu ?? $numEtu)) {
            $errors[] = "Étudiant $numEtu : non inscrit";
            continue;
        }

        $login = $etu->email_etu ?? '';
        if (empty($login)) {
            $errors[] = "Étudiant $numEtu : email manquant";
            continue;
        }

        if ($utilisateurModel->isLoginUsed($login)) {
            $errors[] = "Étudiant $numEtu : login déjà utilisé";
            continue;
        }

        $utilisateurs[] = [
            'nom'       => ($etu->nom_etu ?? '') . ' ' . ($etu->prenom_etu ?? ''),
            'login'     => $login,
            'id_type'   => $typeUtilisateur,
            'id_groupe' => $groupeUtilisateur,
            'id_niveau' => $niveauAcces,
            'statut'    => 'Actif',
        ];
    }

    if (empty($utilisateurs)) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Aucun compte à créer.',
            'errors'  => $errors,
        ]);
        exit;
    }

    try {
        $result = $utilisateurModel->ajouterUtilisateursEnMasse($utilisateurs);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => count($result) . ' compte(s) créé(s) avec succès.',
            'count'   => count($result),
            'errors'  => $errors,
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Erreur : ' . $e->getMessage(),
        ]);
        exit;
    }
}

$rows = [];
foreach ($etudiants as $e) {
    $rows[] = [
        'num_etu'    => $e->num_etu ?? $e->num_carte_etud ?? '',
        'nom_etu'    => $e->nom_etu ?? '',
        'prenom_etu' => $e->prenom_etu ?? '',
        'email_etu'  => $e->email_etu ?? '',
    ];
}
?>
<section class="cm-screen-scrollable">
    <div class="cm-crud-wrapper">

        <?php cm_component('crud/form-pole', [
            'title' => 'Étudiants sans compte utilisateur',
            'icon'  => 'fa-user-plus',
            'content' => '<p class="cm-text-muted">Sélectionnez les étudiants pour lesquels créer un compte utilisateur. Utilisez les paramètres ci-dessous pour définir le type et le groupe.</p>',
        ]); ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $_SESSION['success']]); ?>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <form id="cmCreerComptesForm" method="POST" action="<?= htmlspecialchars($accountsActionUrl, ENT_QUOTES, 'UTF-8') ?>" data-cm-ajax-form="true">
            <?php cm_component('form/csrf-token'); ?>

            <div class="cm-grid-3 cm-mb-md">
                <?php
                // Types d'utilisateurs
                $typeOptions = ['' => '-- Sélectionner --'];
                try {
                    $stmt = Database::getConnection()->query("SELECT id_type_utilisateur, lib_type_utilisateur FROM type_utilisateur ORDER BY lib_type_utilisateur");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $typeOptions[$row['id_type_utilisateur']] = $row['lib_type_utilisateur'];
                    }
                } catch (Exception $e) {}
                cm_component('form/select', [
                    'name' => 'id_type_utilisateur',
                    'label' => 'Type utilisateur',
                    'options' => $typeOptions,
                    'required' => true,
                ]);

                // Groupes d'utilisateurs
                $groupeOptions = ['' => '-- Sélectionner --'];
                try {
                    $stmt = Database::getConnection()->query("SELECT id_GU, lib_GU FROM groupe_utilisateur ORDER BY lib_GU");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $groupeOptions[$row['id_GU']] = $row['lib_GU'];
                    }
                } catch (Exception $e) {}
                cm_component('form/select', [
                    'name' => 'id_groupe_utilisateur',
                    'label' => 'Groupe utilisateur',
                    'options' => $groupeOptions,
                    'required' => true,
                ]);

                // Niveaux d'accès
                $niveauOptions = ['' => '-- Sélectionner --'];
                try {
                    $stmt = Database::getConnection()->query("SELECT id_niveau_acces_donnees, lib_niveau_acces_donnees FROM niveau_acces_donnees ORDER BY lib_niveau_acces_donnees");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $niveauOptions[$row['id_niveau_acces_donnees']] = $row['lib_niveau_acces_donnees'];
                    }
                } catch (Exception $e) {}
                cm_component('form/select', [
                    'name' => 'id_niveau_acces',
                    'label' => 'Niveau accès',
                    'options' => $niveauOptions,
                ]);
                ?>
            </div>

            <div class="cm-pole-inferieur">
                <?php
                cm_component('crud/data-table', [
                    'id'         => 'cmEtudiantsSansCompteTable',
                    'selectable' => true,
                    'row_key'    => 'num_etu',
                    'columns'    => [
                        cm_column('num_etu',    'Matricule',    ['align' => 'center']),
                        cm_column('nom_etu',    'Nom'),
                        cm_column('prenom_etu', 'Prénom'),
                        cm_column('email_etu',  'Email'),
                    ],
                    'rows'          => $rows,
                    'empty_title'   => 'Tous les étudiants ont un compte',
                    'empty_message' => 'Aucun étudiant sans compte utilisateur trouvé.',
                ]);
                ?>

                <div class="cm-flex cm-flex-gap-md cm-mt-md">
                    <button type="submit" class="cm-btn is-primary" id="cmBtnCreerComptes" disabled>
                        <i class="fas fa-user-plus"></i>
                        <span>Créer les comptes sélectionnés</span>
                    </button>
                    <span class="cm-text-muted cm-text-sm cm-flex-center" id="cmSelectedCount">0 sélectionné(s)</span>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('cmEtudiantsSansCompteTable');
    const btn = document.getElementById('cmBtnCreerComptes');
    const countSpan = document.getElementById('cmSelectedCount');
    const form = document.getElementById('cmCreerComptesForm');

    if (!table || !btn || !countSpan || !form) return;

    function updateButtonState() {
        const checkboxes = table.querySelectorAll('.cm-table-check-row:checked');
        const count = checkboxes.length;
        btn.disabled = count === 0;
        countSpan.textContent = count + ' sélectionné(s)';

        // Synchroniser les hidden inputs
        const existingHidden = form.querySelectorAll('input[name="selected[]"]');
        existingHidden.forEach(el => el.remove());

        checkboxes.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected[]';
            input.value = cb.value;
            form.appendChild(input);
        });
    }

    // Check-all
    const checkAll = table.querySelector('.cm-table-check-all');
    if (checkAll) {
        checkAll.addEventListener('change', function() {
            const cbs = table.querySelectorAll('.cm-table-check-row');
            cbs.forEach(cb => cb.checked = this.checked);
            updateButtonState();
        });
    }

    // Row checkboxes
    table.addEventListener('change', function(e) {
        if (e.target.classList.contains('cm-table-check-row')) {
            updateButtonState();
        }
    });

    // AJAX submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création en cours...';

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Erreur : ' + data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Créer les comptes sélectionnés';
            }
        })
        .catch(err => {
            alert('Erreur réseau : ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Créer les comptes sélectionnés';
        });
    });
});
</script>
