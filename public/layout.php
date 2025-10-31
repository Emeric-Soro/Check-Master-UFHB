<?php
/**
 * Layout Template - Pure Presentation Layer
 * This file should only contain HTML structure and display logic
 * All routing and business logic is handled by the Router in index.php
 */

// Ensure we have required variables from index.php
if (!isset($content)) {
    $content = '<div class="card p-6"><div class="text-danger">Erreur: Contenu non défini</div></div>';
}
if (!isset($currentPageLabel)) {
    $currentPageLabel = 'Tableau de bord';
}
if (!isset($menuHTML)) {
    $menuHTML = '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CheckMaster | <?php echo htmlspecialchars($currentPageLabel); ?></title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="shortcut icon" href="image/logo_cm_sbg.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2980b9',
                        'primary-lighter': '#3498db',
                        secondary: '#ff8c00',
                        accent: '#4caf50',
                        success: '#4caf50',
                        warning: '#f39c12',
                        danger: '#e74c3c',
                        'base-100': '#FFFFFF',
                        'base-200': '#F8FAFC',
                        'base-300': '#E2E8F0'
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                        'montserrat': ['Montserrat', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/fr.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.12.0/cdn.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .sidebar-logo { height: 56px; border-radius: 8px; }
        .topbar { height: 96px; padding: 0 1.5rem; background: var(--tw-bg-opacity, 1); }
        .card { background: white; border-radius: 12px; box-shadow: 0 8px 20px rgba(15, 20, 30, 0.06); }
    </style>
</head>
<body class="bg-base-200 font-poppins antialiased">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <div class="hidden md:flex md:flex-shrink-0">
        <div class="flex flex-col w-72 bg-primary text-white">
            <div class="flex items-center justify-center h-24 px-4">
                <div class="flex flex-col items-center text-center">
                    <img src="image/logo_cm_sbg.png" alt="Logo CheckMaster" class="sidebar-logo mb-2">
                    <span class="font-bold text-lg tracking-wide">CHECK MASTER</span>
                </div>
            </div>
            <div class="flex flex-col flex-grow px-4 py-4 overflow-y-auto">
                <div class="space-y-3 pb-3">
                    <?php echo $menuHTML; ?>
                </div>
                <div class="mt-auto px-4 py-3">
                    <form action="logout.php" method="POST" id="logoutForm" class="w-full">
                        <button type="submit" form="logoutForm" class="w-full flex items-center justify-center gap-3 px-4 py-3 rounded-lg bg-white/10 hover:bg-white/20 transition-colors">
                            <i class="fas fa-sign-out-alt text-white/80"></i>
                            <span class="text-sm">Déconnexion</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex flex-col flex-1 overflow-hidden">
        <!-- Top Bar -->
        <div class="flex items-center justify-between topbar bg-base-100 border-b border-base-300">
            <div class="flex items-center">
                <button id="mobileMenuButton" class="md:hidden text-primary/70 focus:outline-none mr-4">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
                <div>
                    <h1 class="text-2xl font-bold text-primary"><?php echo htmlspecialchars($currentPageLabel); ?></h1>
                </div>
            </div>
            <div class="flex items-center space-x-6">
                <div class="relative">
                    <i class="fas fa-bell text-primary/70 text-xl"></i>
                </div>
                <div class="w-px h-10 bg-base-300"></div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <span class="text-md font-bold text-primary block">Bienvenue, <?php echo htmlspecialchars($_SESSION['nom_utilisateur'] ?? 'Utilisateur'); ?></span>
                        <span class="text-sm text-primary/60 block"><?php echo htmlspecialchars($_SESSION['lib_GU'] ?? ''); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 p-6 overflow-y-auto">
            <?php echo $content; ?>
        </main>
    </div>
</div>

<!-- Mobile Menu Toggle Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const sidebar = document.querySelector('.hidden.md\\:flex.md\\:flex-shrink-0');
        if (mobileMenuButton && sidebar) {
            mobileMenuButton.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('absolute');
                sidebar.classList.toggle('z-20');
                sidebar.classList.toggle('h-full');
            });
        }
    });
</script>

<!-- Page-specific scripts -->
<script src="./js/suivi_reclamation.js"></script>
<script src="./js/historique_reclamation.js"></script>
</body>
</html>
