<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réclamations</title>
</head>

<body class="bg-gradient-to-r from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">

    <div class="container mx-auto py-10">
        <?php if (isset($_SESSION['message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6 text-center">
            <strong class="font-bold">
                <?php if ($_SESSION['message']['type'] === 'success'): ?>Succès !<?php else: ?>Erreur !<?php endif; ?>
            </strong> <?= htmlspecialchars($_SESSION['message']['text']) ?>
        </div>
        <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <h1 class="text-3xl font-bold text-center text-green-800 mb-10">Gestion des Réclamations</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($cardReclamation as $card): ?>
            <div
                class="bg-white p-6 rounded-lg shadow-lg hover:shadow-2xl transition-shadow duration-75 transform hover:-translate-y-2 card">
                <div
                    class="flex items-center justify-center w-14 h-14 <?php echo htmlspecialchars($card['bg_color']); ?> rounded-full mb-4">
                    <?php if (!empty($card['icon'])): ?>
                    <i
                        class="<?php echo htmlspecialchars($card['icon']); ?> <?php echo htmlspecialchars($card['text_color']); ?> text-2xl"></i>
                    <?php endif ?>
                </div>
                <h2 class="text-xl font-semibold mb-4 text-gray-800"><?php echo htmlspecialchars($card['title']); ?>
                </h2>
                <p class="text-gray-600 mb-6"><?php echo htmlspecialchars($card['description']); ?></p>
                <a href="<?php echo htmlspecialchars($card['link']); ?>"
                    class="inline-block <?php echo htmlspecialchars($card['bg_color']); ?> text-white px-6 py-2 rounded-lg hover:<?php echo htmlspecialchars($card['bg_color']); ?> transition-colors duration-300"><?php echo htmlspecialchars($card['title_link']); ?></a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // Animation au survol des cartes
        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                gsap.to(card, {
                    scale: 1.05,
                    duration: 0.1
                });
            });

            card.addEventListener('mouseleave', () => {
                gsap.to(card, {
                    scale: 1,
                    duration: 0.1
                });
            });
        });
    });
    </script>
</body>

</html>