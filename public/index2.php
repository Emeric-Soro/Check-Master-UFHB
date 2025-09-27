<?php

session_start();

?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soutenance Manager| Plateforme de Gestion de la commission de Validation des soutenances</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="shortcut icon" href="./images/dessin.svg" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body class="font-sans antialiased text-gray-800 bg-gray-50">
<!-- Navigation -->
<nav class="bg-white shadow-sm fixed w-full z-50">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10  flex items-center justify-center">
                <img src="./images/dessin.svg" alt="logo">
            </div>
            <span class="font-bold text-xl text-green-500">Soutenance Manager</span>
        </div>
        <div class="hidden md:flex items-center space-x-8">
            <a href="#features" class="nav-link text-gray-600 hover:text-gray-900 py-2">Fonctionnalités</a>
            <a href="page_connexion.php"
               class="px-6 py-2 rounded-md academic-gradient text-white font-medium hover:opacity-90 transition">Commencer</a>
        </div>
        <button class="md:hidden focus:outline-none">
            <i class="fas fa-bars text-xl text-gray-600"></i>
        </button>
    </div>
</nav>

<!-- Hero Section -->
<section class="pt-32 pb-20 px-6">
    <div class="container mx-auto flex flex-col lg:flex-row items-center">
        <div class="lg:w-1/2 mb-12 lg:mb-0">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-600 leading-tight mb-6">
                Révolutionnez la <span class="text-green-500">Gestion de la commission de soutenances</span>
            </h1>
            <p class="text-xl text-gray-600 mb-8 max-w-lg">
                Soutenance Manager est une plateforme qui facilite et automatise le
                processus de validation des rapports de stage et mémoires,
                garantissant une gestion efficace et transparente pour les étudiants et la commission. 🚀

            </p>
            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                <button
                        class="px-8 py-3 rounded-lg academic-gradient text-white font-semibold hover:opacity-90 transition flex items-center justify-center">
                    <a href="page_connexion.php"><i class="fas fa-rocket mr-2"></i> Commencer</a>

                </button>

            </div>

        </div>
        <div class="lg:w-1/2 flex justify-center">
            <div class="relative w-full max-w-xl">
                <img src="./images/undraw_professor_d7zn.svg" class="w-full floating">
                <div class="absolute -bottom-6 -left-6 w-32 h-32 rounded-full bg-green-100 opacity-50 -z-10"></div>
                <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full bg-green-100 opacity-50 -z-10"></div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="py-20" id="features">
    <div class="container mx-auto px-6">
        <div class="text-center mb-16">

            <h2 class="text-3xl font-bold text-gray-600 mb-4">Fonctionnalités clés</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Tout ce dont vous avez besoin pour rationaliser l'ensemble
                des rapports de stage des étudiants, de la soumission à la soutenance.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="bg-white p-8 rounded-xl shadow-md feature-card transition duration-300">
                <div class="w-14 h-14 rounded-lg academic-gradient flex items-center justify-center mb-6">
                    <i class="fas fa-file-upload text-white text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Soumission et suivi des documents</h3>
                <p class="text-gray-600 mb-4">Permettre aux étudiants de déposer leurs rapports et suivre leur
                    statut de validation à
                    chaque étape du processus via notre interface conviviale.</p>
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i> Validation
                        du format</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Vérification des notes</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i> Suivi des
                        dossiers</li>
                </ul>
            </div>

            <!-- Feature 2 -->
            <div class="bg-white p-8 rounded-xl shadow-md feature-card transition duration-300">
                <div class="w-14 h-14 rounded-lg academic-gradient flex items-center justify-center mb-6">
                    <i class="fas fa-robot text-white text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">workflows de validation</h3>
                <p class="text-gray-600 mb-4"> Automatiser le processus avec un suivi clair des différentes étapes
                    (réception, examen, corrections éventuelles, approbation finale)
                    pour éviter les oublis et garantir la conformité.</p>
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Analyse des rapports</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Soumission au comité</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Approbation finale
                    </li>
                </ul>
            </div>

            <!-- Feature 3 -->
            <div class="bg-white p-8 rounded-xl shadow-md feature-card transition duration-300">
                <div class="w-14 h-14 rounded-lg academic-gradient flex items-center justify-center mb-6">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-3">Collaboration du Comité</h3>
                <p class="text-gray-600 mb-4">Facilitez la communication et la coordination entre les membres du
                    comité tout au long du processus.</p>
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i> Feedback
                        annoté</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Discussions organisées</li>
                    <li class="flex items-center"><i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Planification des réunions</li>
                </ul>
            </div>
        </div>
    </div>
</section>



<!-- Footer -->
<footer class=" text-green-500 bg-green-100 shadow-sm">

    <div class=" pt-8 flex flex-col md:flex-row items-center justify-center pb-3">
        <p class="text-green-500  md:mb-0 items-center">© 2025 Soutenance Manager. Tous droits réservés.</p>

    </div>

</footer>

<script>
    // Mobile menu toggle
    document.querySelector('button.md\\:hidden').addEventListener('click', function() {
        const navLinks = document.querySelector('.hidden.md\\:flex');
        navLinks.classList.toggle('hidden');
        navLinks.classList.toggle('flex');
        navLinks.classList.toggle('flex-col');
        navLinks.classList.toggle('absolute');
        navLinks.classList.toggle('top-16');
        navLinks.classList.toggle('left-0');
        navLinks.classList.toggle('w-full');
        navLinks.classList.toggle('bg-white');
        navLinks.classList.toggle('px-6');
        navLinks.classList.toggle('py-4');
        navLinks.classList.toggle('space-y-4');
    });

    // Animation for feature cards on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fadeIn');
            }
        });
    }, {
        threshold: 0.1
    });

    document.querySelectorAll('.feature-card').forEach(card => {
        observer.observe(card);
    });
</script>
</body>

</html>