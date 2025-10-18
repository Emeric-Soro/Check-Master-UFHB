<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réclamations</title>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center font-poppins">

    <div class="container mx-auto py-10">
        <?php if (isset($_SESSION['message'])): ?>
        <div class="bg-accent/10 border border-accent text-accent px-4 py-3 rounded-lg relative mb-6 text-center">
            <strong class="font-bold">
                <?php if ($_SESSION['message']['type'] === 'success'): ?>Succès !<?php else: ?>Erreur !<?php endif; ?>
            </strong> <?= htmlspecialchars($_SESSION['message']['text']) ?>
        </div>
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <h1 class="text-3xl font-bold text-center text-primary mb-10 font-montserrat">Gestion des Réclamations</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($cardReclamation as $card): ?>
            <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 card-hover">
                <div class="flex items-center justify-center w-14 h-14 <?php echo htmlspecialchars($card['bg_color']); ?> rounded-full mb-4">
                    <?php if (!empty($card['icon'])): ?>
                    <i class="<?php echo htmlspecialchars($card['icon']); ?> <?php echo htmlspecialchars($card['text_color']); ?> text-2xl"></i>
                    <?php endif ?>
                </div>
                <h2 class="text-xl font-semibold mb-4 text-gray-900"><?php echo htmlspecialchars($card['title']); ?></h2>
                <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($card['description']); ?></p>
                <a href="<?php echo htmlspecialchars($card['link']); ?>"
                    class="inline-block bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-light transition-colors duration-300 font-semibold">
                    <?php echo htmlspecialchars($card['title_link']); ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</body>

</html>