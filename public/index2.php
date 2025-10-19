<?php



session_start();

?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CheckMaster| Plateforme de Gestion de la commission de Validation des soutenances</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="shortcut icon" href="./images/logo_cm_sbg.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/alpine.min.js" defer></script>
</head>

<body class="font-sans antialiased text-base-content bg-base-200">
<!-- Navigation avec DaisyUI -->
<div class="navbar bg-base-100 shadow-sm fixed w-full z-50">
    <div class="navbar-start">
        <div class="dropdown">
            <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                <i class="fas fa-bars text-xl"></i>
            </div>
            <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
                <li><a href="#features">Fonctionnalités</a></li>
                <li><a href="page_connexion.php" class="text-primary">Commencer</a></li>
            </ul>
        </div>
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 flex items-center justify-center">
                <img src="./images/logo_cm_sbg.png" alt="logo">
            </div>
            <span class="font-bold text-xl text-primary">CheckMaster</span>
        </div>
    </div>
    
    <div class="navbar-end hidden lg:flex">
        <ul class="menu menu-horizontal px-1">
            <li><a href="#features">Fonctionnalités</a></li>
            <li>
                <a href="page_connexion.php" class="btn btn-primary">Commencer</a>
            </li>
        </ul>
    </div>
</div>

<!-- Hero Section avec DaisyUI -->
<section class="hero min-h-screen pt-20">
    <div class="hero-content flex-col lg:flex-row-reverse">
        <div class="lg:w-1/2 flex justify-center">
            <div class="relative w-full max-w-xl">
                <img src="./images/undraw_professor_d7zn.svg" class="w-full floating" alt="Professeur">
                <div class="absolute -bottom-6 -left-6 w-32 h-32 rounded-full bg-primary/10 blur-2xl"></div>
                <div class="absolute -top-6 -right-6 w-24 h-24 rounded-full bg-primary/10 blur-2xl"></div>
            </div>
        </div>
        <div class="lg:w-1/2">
            <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-6">
                Révolutionnez la <span class="text-primary">Gestion de la commission de soutenances</span>
            </h1>
            <p class="text-xl mb-8 max-w-lg">
                CheckMaster est une plateforme qui facilite et automatise le
                processus de validation des rapports de stage et mémoires,
                garantissant une gestion efficace et transparente pour les étudiants et la commission. 🚀
            </p>
            <a href="page_connexion.php" class="btn btn-primary btn-lg">
                <i class="fas fa-rocket mr-2"></i> Commencer
            </a>
        </div>
    </div>
</section>

<!-- Features avec DaisyUI cards -->
<section class="py-20" id="features">
    <div class="container mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold mb-4">Fonctionnalités clés</h2>
            <p class="text-base-content/70 max-w-2xl mx-auto">Tout ce dont vous avez besoin pour rationaliser l'ensemble
                des rapports de stage des étudiants, de la soumission à la soutenance.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Feature 1 -->
            <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="card-body">
                    <div class="w-14 h-14 rounded-lg bg-gradient-to-br from-primary to-primary-light flex items-center justify-center mb-6">
                        <i class="fas fa-file-upload text-white text-xl"></i>
                    </div>
                    <h3 class="card-title">Soumission et suivi des documents</h3>
                    <p class="text-base-content/70">Permettre aux étudiants de déposer leurs rapports et suivre leur
                        statut de validation à
                    chaque étape du processus via notre interface conviviale.</p>
                    <ul class="space-y-2 text-base-content/70">
                        <li class="flex items-center"><i class="fas fa-check-circle text-primary mr-2"></i> Validation
                            du format</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-primary mr-2"></i>
                            Vérification des notes</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-primary mr-2"></i> Suivi des
                            dossiers</li>
                    </ul>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="card-body">
                    <div class="w-14 h-14 rounded-lg bg-gradient-to-br from-primary to-primary-light flex items-center justify-center mb-6">
                        <i class="fas fa-robot text-white text-xl"></i>
                    </div>
                    <h3 class="card-title">workflows de validation</h3>
                    <p class="text-base-content/70">Automatiser le processus avec un suivi clair des différentes étapes
                        (réception, examen, corrections éventuelles, approbation finale)
                        pour éviter les oublis et garantir la conformité.</p>
                    <ul class="space-y-2 text-base-content/70">
                        <li class="flex items-center"><i class="fas fa-check-circle text-primary mr-2"></i>
                            Analyse des rapports</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-primary mr-2"></i>
                            Soumission au comité</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-primary mr-2"></i>
                            Approbation finale
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="card-body">
                    <div class="w-14 h-14 rounded-lg bg-gradient-to-br from-primary to-primary-light flex items-center justify-center mb-6">
                        <i class="fas fa-users text-white text-xl"></i>
                    </div>
                    <h3 class="card-title">Collaboration du Comité</h3>
                    <p class="text-base-content/70">Facilitez la communication et la coordination entre les membres du
                        comité tout au long du processus.</p>
                    <ul class="space-y-2 text-base-content/70">
                        <li class="flex items-center"><i class="fas fa-check-circle text-primary mr-2"></i> Feedback
                            annoté</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-primary mr-2"></i>
                            Discussions organisées</li>
                        <li class="flex items-center"><i class="fas fa-check-circle text-primary mr-2"></i>
                            Planification des réunions</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>


<!-- Footer -->
<footer class="footer footer-center p-10 bg-primary/5 text-primary">
    <div>
        <p>© 2025 CheckMaster. Tous droits réservés.</p>
    </div>
</footer>

<script>
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