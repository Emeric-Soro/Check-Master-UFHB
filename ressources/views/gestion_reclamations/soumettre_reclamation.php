<?php

$typesReclamation = is_array($typesReclamation ?? null) ? $typesReclamation : [];
$erreurs = is_array($erreurs ?? null) ? $erreurs : [];

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$oldType = (string) ($_POST['type'] ?? '');
$oldObjet = (string) ($_POST['objet'] ?? '');
$oldContent = (string) ($_POST['content'] ?? '');
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>

<div class="cm-etu-screen">
    <section class="cm-etu-panel">
        <header class="cm-etu-panel__header">
            <div>
                
                <p class="cm-etu-panel__subtitle">Soumettez une demande détaillée. Minimum 60 caractères.</p>
            </div>
        </header>

        <?php if (is_array($message)): ?>
            <?php cm_component('ui/alert-box', [
                'type' => ($message['type'] ?? '') === 'success' ? 'success' : 'danger',
                'message' => (string) ($message['text'] ?? ''),
            ]); ?>
        <?php endif; ?>

        <?php if (!empty($erreurs)): ?>
            <div class="cm-etu-validation-box">
                <strong>Veuillez corriger les erreurs suivantes :</strong>
                <ul>
                    <?php foreach ($erreurs as $erreur): ?>
                        <li><?= htmlspecialchars((string) $erreur, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" id="reclamationForm" class="cm-etu-form">
            <div class="cm-etu-grid cm-etu-grid--2">
                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="type">Type de réclamation <span class="cm-required-star">*</span></label>
                    <select id="type" name="type" class="cm-etu-select" required>
                        <option value="">Sélectionnez un type...</option>
                        <?php foreach ($typesReclamation as $key => $label): ?>
                            <option value="<?= htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8') ?>" <?= $oldType === (string) $key ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="objet">Objet <span class="cm-required-star">*</span></label>
                    <input
                        id="objet"
                        name="objet"
                        type="text"
                        class="cm-etu-input"
                        value="<?= htmlspecialchars($oldObjet, ENT_QUOTES, 'UTF-8') ?>"
                        minlength="5"
                        required
                        placeholder="Ex: Erreur sur la note de soutenance">
                </div>
            </div>

            <div class="cm-etu-field">
                <label class="cm-etu-label" for="editor">Description détaillée <span class="cm-required-star">*</span></label>
                <p class="cm-etu-help">Minimum 60 caractères</p>
                <div id="editor" class="cm-etu-quill-editor"></div>
                <input type="hidden" name="content" id="content" value="<?= htmlspecialchars($oldContent, ENT_QUOTES, 'UTF-8') ?>">
                <p id="charCounter" class="cm-etu-char-counter">0 caractères</p>
            </div>

            <div class="cm-etu-note-box is-info">
                <i class="fas fa-circle-info" aria-hidden="true"></i>
                <p>Votre réclamation sera traitée rapidement et vous serez notifié à chaque étape.</p>
            </div>

            <div class="cm-etu-actions">
                <?php if (canCreate()): ?>
                    <button type="submit" class="cm-btn is-success">
                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
                        <span>Soumettre la réclamation</span>
                    </button>
                <?php else: ?>
                    <span class="cm-etu-help">Vous n\'avez pas la permission de soumettre une réclamation.</span>
                <?php endif; ?>
            </div>
        </form>
    </section>
</div>

<script>
(function () {
    const editorContainer = document.getElementById('editor');
    const hiddenContent = document.getElementById('content');
    const charCounter = document.getElementById('charCounter');
    const form = document.getElementById('reclamationForm');

    if (!editorContainer || !hiddenContent || !charCounter || !form || typeof Quill === 'undefined') {
        return;
    }

    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],
                [{ header: [1, 2, 3, false] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link'],
                ['clean']
            ]
        },
        placeholder: 'Décrivez précisément votre réclamation...'
    });

    const initialContent = <?= json_encode($oldContent, JSON_UNESCAPED_UNICODE) ?>;
    if (initialContent && String(initialContent).trim() !== '') {
        quill.root.innerHTML = initialContent;
    }

    function updateCounter() {
        const textLength = quill.getText().trim().length;
        charCounter.textContent = textLength + ' caractères';
        charCounter.classList.toggle('is-valid', textLength >= 60);
        charCounter.classList.toggle('is-invalid', textLength < 60);
    }

    quill.on('text-change', function () {
        hiddenContent.value = quill.root.innerHTML;
        updateCounter();
    });

    form.addEventListener('submit', function (event) {
        hiddenContent.value = quill.root.innerHTML;
        const textLength = quill.getText().trim().length;
        if (textLength < 60) {
            event.preventDefault();
            alert('La description doit contenir au moins 60 caractères.');
            return;
        }
    });

    updateCounter();
})();
</script>
