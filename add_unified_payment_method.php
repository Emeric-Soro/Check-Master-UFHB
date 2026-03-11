<?php
// Script pour ajouter la méthode enregistrerPaiement() au GestionScolariteController.php

$controllerFile = __DIR__ . '/app/controllers/GestionScolariteController.php';
$methodFile = __DIR__ . '/app/controllers/enregistrerPaiement_method.php';

// Lire le contenu du contrôleur
$controllerContent = file_get_contents($controllerFile);

// Lire la méthode à ajouter
$methodContent = file_get_contents($methodFile);

// Remplacer le dernier } par la méthode + }
$controllerContent = preg_replace('/\n\n}\s*$/', "\n\n" . $methodContent . "\n\n}\n", $controllerContent);

// Sauvegarder
file_put_contents($controllerFile, $controllerContent);

echo "✅ Méthode enregistrerPaiement() ajoutée avec succès au contrôleur !\n";
echo "📝 Fichiers modifiés :\n";
echo "   - app/controllers/GestionScolariteController.php\n";
echo "\n⚠️ N'oubliez pas d'ajouter le case dans le switch :\n";
echo "   case 'enregistrer_paiement':\n";
echo "       \$this->enregistrerPaiement();\n";
echo "       break;\n";
?>