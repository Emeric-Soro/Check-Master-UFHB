<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';

$etudiantModel = new Etudiant(Database::getConnection());
$compte_rendu = $etudiantModel->getCompteRendu($_SESSION['num_etu']);
?>

<div class="cm-etu-screen">
    <section class="cm-etu-panel">
        <header class="cm-etu-panel__header">
            <div>
                <h2 class="cm-etu-panel__title"><i class="fas fa-file-signature" aria-hidden="true"></i> Mon Compte Rendu d'Évaluation</h2>
                <p class="cm-etu-panel__subtitle">Document officiel publié par la commission après évaluation de votre dossier.</p>
            </div>
        </header>

        <?php if (!$compte_rendu): ?>
            <div class="cm-etu-empty">
                <i class="fas fa-hourglass-half" aria-hidden="true"></i>
                <p>Aucun compte rendu n\'est disponible pour le moment.</p>
                <a href="?page=candidature_soutenance" class="cm-btn is-primary is-sm">Retour à ma candidature</a>
            </div>
        <?php else: ?>
            <?php
            $nomCr = (string) ($compte_rendu['nom_CR'] ?? 'Compte rendu');
            $dateCr = (string) ($compte_rendu['date_CR'] ?? '');
            $contenuCr = (string) ($compte_rendu['contenu_CR'] ?? '');
            $pdfPath = (string) ($compte_rendu['chemin_fichier_pdf'] ?? '');
            ?>

            <article class="cm-etu-doc-card">
                <header class="cm-etu-doc-card__header">
                    <div>
                        <h3><?= htmlspecialchars($nomCr, ENT_QUOTES, 'UTF-8') ?></h3>
                        <p>Publié le <?= $dateCr !== '' ? date('d/m/Y à H:i', strtotime($dateCr)) : 'Date indisponible' ?></p>
                    </div>
                    <?php if ($pdfPath !== ''): ?>
                        <a href="<?= htmlspecialchars($pdfPath, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="cm-btn is-info is-sm">
                            <i class="fas fa-download" aria-hidden="true"></i>
                            <span>Télécharger</span>
                        </a>
                    <?php endif; ?>
                </header>

                <div class="cm-etu-doc-card__body">
                    <?php if ($contenuCr !== ''): ?>
                        <p><?= nl2br(htmlspecialchars($contenuCr, ENT_QUOTES, 'UTF-8')) ?></p>
                    <?php else: ?>
                        <p>Le contenu texte n\'est pas disponible. Veuillez consulter le PDF.</p>
                    <?php endif; ?>
                </div>

                <footer class="cm-etu-doc-card__footer">
                    <span>Numéro étudiant : <strong><?= htmlspecialchars((string) ($compte_rendu['num_etu'] ?? $_SESSION['num_etu']), ENT_QUOTES, 'UTF-8') ?></strong></span>
                    <a href="?page=candidature_soutenance" class="cm-btn is-light is-sm">Retour à ma candidature</a>
                </footer>
            </article>

            <div class="cm-etu-note-box">
                <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
                <p>Note importante : ce compte rendu est un document officiel.</p>
            </div>
        <?php endif; ?>
    </section>
</div>
