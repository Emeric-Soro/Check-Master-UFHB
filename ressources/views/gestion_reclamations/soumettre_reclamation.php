<?php
// Include permissions helper
require_once __DIR__ . '/../../../app/utils/permissions_helper.php';
?>
<!-- Contenu de la page de soumission de réclamation -->
<!-- Include Quill rich text editor -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>

<style>
    .ql-toolbar.ql-snow {
        border-radius: 0.5rem 0.5rem 0 0;
        border-color: #d1d5db;
    }

    .ql-container.ql-snow {
        border-radius: 0 0 0.5rem 0.5rem;
        border-color: #d1d5db;
        min-height: 100px;
        font-family: inherit;
    }

    .ql-editor {
        min-height: 100px;
    }
</style>


<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <!-- Header -->
        <div class="bg-green-600 px-4 py-2">
            <h1 class="text-lg font-bold text-white flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i> Nouvelle Réclamation
            </h1>
        </div>

        <!-- Messages d'erreur -->
        <?php if (isset($_SESSION['message'])): ?>
            <div
                class="p-4 <?php echo $_SESSION['message']['type'] === 'success' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300'; ?> border">
                <i
                    class="fas <?php echo $_SESSION['message']['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['message']['text']); ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (!empty($erreurs)): ?>
            <div class="p-4 bg-red-100 text-red-800 border border-red-300">
                <h4 class="font-bold mb-2">Veuillez corriger les erreurs suivantes :</h4>
                <ul class="list-disc list-inside">
                    <?php foreach ($erreurs as $erreur): ?>
                        <li><?php echo htmlspecialchars($erreur); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Form Container -->
        <div class="p-4">
            <form method="POST" id="reclamationForm">
                <!-- Type et Objet sur la même ligne -->
                <div class="grid grid-cols-2 gap-4 mb-3">
                    <!-- Type de réclamation -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type de réclamation*</label>
                        <select name="type" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 <?php echo isset($erreurs['type']) ? 'border-red-500' : ''; ?>">
                            <option value="">Sélectionnez un type...</option>
                            <?php foreach ($typesReclamation as $key => $label): ?>
                                <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (isset($_POST['type']) && $_POST['type'] === $key) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($erreurs['type'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo htmlspecialchars($erreurs['type']); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Objet -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Objet*</label>
                        <input type="text" name="objet" required
                            value="<?php echo htmlspecialchars($_POST['objet'] ?? ''); ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 <?php echo isset($erreurs['titre']) ? 'border-red-500' : ''; ?>"
                            placeholder="Objet de votre réclamation" minlength="5">
                        <?php if (isset($erreurs['titre'])): ?>
                            <p class="mt-1 text-xs text-red-600"><?php echo htmlspecialchars($erreurs['titre']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Éditeur de texte riche -->
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description détaillée* <span
                            class="text-xs text-gray-500">(min. 60 caractères)</span></label>
                    <div id="editor"
                        class="bg-white <?php echo isset($erreurs['description']) ? 'border-red-500' : ''; ?>">
                        <?php echo htmlspecialchars($_POST['content'] ?? ''); ?>
                    </div>
                    <input type="hidden" name="content" id="content" required>
                    <?php if (isset($erreurs['description'])): ?>
                        <p class="mt-1 text-xs text-red-600"><?php echo htmlspecialchars($erreurs['description']); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Informations importantes -->
                <div class="mb-3 bg-blue-50 border border-blue-200 rounded-lg p-2">
                    <p class="text-xs text-blue-700">
                        <i class="fas fa-info-circle mr-1"></i>
                        <strong>Info :</strong> Votre réclamation sera traitée dans les meilleurs délais. Vous recevrez
                        une notification à chaque étape.
                    </p>
                </div>

                <!-- Boutons -->
                <div class="flex justify-end space-x-3">
                    <?php if (canCreate()): ?>
                        <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition flex items-center">
                            <i class="fas fa-paper-plane mr-2"></i> Soumettre la réclamation
                        </button>
                    <?php else: ?>
                        <span class="text-gray-500 text-sm italic">Vous n'avez pas la permission de soumettre une
                            réclamation.</span>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialiser l'éditeur de texte riche
        const quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{
                        'header': [1, 2, 3, false]
                    }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{
                        'color': []
                    }, {
                        'background': []
                    }],
                    [{
                        'list': 'ordered'
                    }, {
                        'list': 'bullet'
                    }],
                    ['link'],
                    ['clean']
                ]
            },
            placeholder: 'Décrivez votre réclamation de manière détaillée...'
        });

        // Gestion de la soumission du formulaire
        const form = document.getElementById('reclamationForm');
        form.addEventListener('submit', function (e) {
            // Récupérer le contenu HTML de l'éditeur
            const content = document.getElementById('content');
            content.value = quill.root.innerHTML;

            // Validation côté client
            if (quill.getText().trim().length < 60) {
                e.preventDefault();
                alert('La description doit contenir au moins 60 caractères.');
                return false;
            }
        });

        // Compteur de caractères
        const updateCharCount = () => {
            const textLength = quill.getText().trim().length;
            let counter = document.getElementById('charCounter');
            if (!counter) {
                counter = document.createElement('p');
                counter.id = 'charCounter';
                counter.className = 'mt-1 text-sm text-right';
                document.querySelector('#editor').parentNode.appendChild(counter);
            }
            counter.textContent = `${textLength} caractères`;
            counter.className = textLength >= 60 ?
                'mt-1 text-sm text-green-600 text-right' :
                'mt-1 text-sm text-gray-500 text-right';
        };

        quill.on('text-change', updateCharCount);
        updateCharCount();
    });
</script>