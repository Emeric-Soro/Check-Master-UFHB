<?php

session_start();


$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./css/output.css">
    <link rel="shortcut icon" href="./images/dessin.svg" type="image/x-icon">
    <title>Se connecter | Soutenance Manager</title>
</head>

<body>
    <div class="min-h-screen flex fle-col items-center justify-center">
        <div class="py-6 px-4">
            <div class="grid md:grid-cols-2 items-center gap-6 max-w-6xl w-full">
                <div
                    class="border border-slate-300 rounded-lg p-6 max-w-md shadow-[0_2px_22px_-4px_rgba(93,96,127,0.2)] max-md:mx-auto">
                    <form action="login.php" method="POST" class="space-y-6">

                        <div class="mb-12">
                            <div>
                                <a href="#"><img src="./images/dessin.svg" class="mx-auto block"
                                        style="width:20%" /></a>
                                <h3 class="text-green-500 text-3xl font-semibold">Se connecter</h3>
                            </div>
                            <p class="text-gray-800 text-sm mt-6 leading-relaxed">
                                Connectez-vous à votre compte et explorez un monde de possibilités. Votre voyage
                                commence ici.
                            </p>
                        </div>

                        <div>
                            <label class="text-green-500 text-sm font-medium mb-2 block">Login</label>
                            <div class="relative flex items-center">
                                <input name="login" type="email" required
                                    class="w-full text-sm text-slate-800 border border-slate-300 pl-4 pr-10 py-3 rounded-lg focus:outline-green-600"
                                    placeholder="Entrer votre login" />
                                <svg xmlns="http://www.w3.org/2000/svg" fill="#3c9e5f" stroke="#3c9e5f"
                                    class="w-[18px] h-[18px] absolute right-4" viewBox="0 0 24 24">
                                    <circle cx="10" cy="7" r="6" data-original="#000000"></circle>
                                    <path
                                        d="M14 15H6a5 5 0 0 0-5 5 3 3 0 0 0 3 3h12a3 3 0 0 0 3-3 5 5 0 0 0-5-5zm8-4h-2.59l.3-.29a1 1 0 0 0-1.42-1.42l-2 2a1 1 0 0 0 0 1.42l2 2a1 1 0 0 0 1.42 0 1 1 0 0 0 0-1.42l-.3-.29H22a1 1 0 0 0 0-2z"
                                        data-original="#000000"></path>
                                </svg>
                            </div>
                        </div>

                        <div>
                            <label class="text-green-500 text-sm font-medium mb-2 block">Mot de passe</label>
                            <div class="relative flex items-center">
                                <input name="password" type="password" required
                                    class="w-full text-sm text-slate-800 border border-slate-300 pl-4 pr-10 py-3 rounded-lg focus:outline-green-600"
                                    placeholder="Entrer votre mot de passe" />
                                <svg xmlns="http://www.w3.org/2000/svg" fill="#3c9e5f" stroke="#3c9e5f"
                                    class="w-[18px] h-[18px] absolute right-4 cursor-pointer" viewBox="0 0 128 128">
                                    <path
                                        d="M64 104C22.127 104 1.367 67.496.504 65.943a4 4 0 0 1 0-3.887C1.367 60.504 22.127 24 64 24s62.633 36.504 63.496 38.057a4 4 0 0 1 0 3.887C126.633 67.496 105.873 104 64 104zM8.707 63.994C13.465 71.205 32.146 96 64 96c31.955 0 50.553-24.775 55.293-31.994C114.535 56.795 95.854 32 64 32 32.045 32 13.447 56.775 8.707 63.994zM64 88c-13.234 0-24-10.766-24-24s10.766-24 24-24 24 10.766 24 24-10.766 24-24 24zm0-40c-8.822 0-16 7.178-16 16s7.178 16 16 16 16-7.178 16-16-7.178-16-16-16z"
                                        data-original="#000000"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="text-sm">
                                <a href="reset_password.php"
                                    class="text-green-500 underline hover:underline font-medium">
                                    Mot de passe oublié?
                                </a>
                            </div>
                        </div>
                        <?php if (isset($_SESSION['error'])) : ?>
                        <div id="errorMessage"
                            class=" bg-red-50 border-l-4 border-red-500 text-red-700 p-2 mb-4 rounded text-sm"
                            role="alert">
                            <?php echo htmlspecialchars($_SESSION['error']);?></div>
                        <?php endif?>
                        <div class="!mt-12">

                            <button type="submit"
                                class="w-full shadow-xl py-2.5 px-4 text-[15px] font-medium tracking-wide rounded-lg text-white bg-green-500 hover:bg-green-700 focus:outline-none">
                                Se connecter
                            </button>
                        </div>
                    </form>
                </div>

                <div class="max-md:mt-8">
                    <img src="./images/undraw_secure-login_m11a.svg"
                        class="w-full aspect-[71/50] max-md:w-4/5 mx-auto block object-cover" alt="login img" />
                </div>
            </div>
        </div>
    </div>

    <script>
    // Script pour faire disparaître le message après 3 secondes
    document.addEventListener('DOMContentLoaded', function() {
        const errorMessage = document.getElementById('errorMessage');

        // Animation de disparition progressive
        setTimeout(function() {
            // Ajouter une transition pour l'opacité
            errorMessage.style.transition = 'opacity 0.5s ease-out';
            errorMessage.style.opacity = '0';

            // Supprimer complètement l'élément après la transition
            setTimeout(function() {
                errorMessage.style.display = 'none';
            }, 500);
        }, 2000); // Disparaît après 3 secondes (3000ms)
    });
    </script>

    <?php 
    // Supprimer l'erreur de la session pour qu'elle ne réapparaisse pas lors du rechargement
    unset($_SESSION['error']); 
    ?>


</body>

</html>