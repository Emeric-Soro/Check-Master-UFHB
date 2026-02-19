<?php
/**
 * Script de diagnostic pour vérifier la session d'un étudiant
 * Usage: Placez ce fichier à la racine et accédez-y après connexion en tant qu'étudiant
 */

session_start();

echo "<h1>Diagnostic de session Étudiant</h1>";
echo "<h2>Variables de session</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Vérification des éléments critiques</h2>";
echo "<ul>";
echo "<li>id_utilisateur: " . (isset($_SESSION['id_utilisateur']) ? "✓ " . $_SESSION['id_utilisateur'] : "✗ ABSENT") . "</li>";
echo "<li>nom_utilisateur: " . (isset($_SESSION['nom_utilisateur']) ? "✓ " . $_SESSION['nom_utilisateur'] : "✗ ABSENT") . "</li>";
echo "<li>type_utilisateur: " . (isset($_SESSION['type_utilisateur']) ? "✓ " . $_SESSION['type_utilisateur'] : "✗ ABSENT") . "</li>";
echo "<li>id_GU: " . (isset($_SESSION['id_GU']) ? "✓ " . $_SESSION['id_GU'] : "✗ ABSENT") . "</li>";
echo "<li>lib_GU: " . (isset($_SESSION['lib_GU']) ? "✓ " . $_SESSION['lib_GU'] : "✗ ABSENT") . "</li>";
echo "<li>num_etu: " . (isset($_SESSION['num_etu']) ? "✓ " . $_SESSION['num_etu'] : "✗ ABSENT (PROBLÈME)") . "</li>";
echo "</ul>";

// Connexion à la base de données pour vérifier la configuration
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/models/Utilisateur.php';
require_once __DIR__ . '/../app/models/Etudiant.php';

if (isset($_SESSION['id_utilisateur'])) {
    $db = Database::getConnection();
    $utilisateur = new Utilisateur($db);

    echo "<h2>Informations depuis la base de données</h2>";

    // Vérifier le type d'utilisateur
    $type = $utilisateur->getLibelleTypeUtilisateur($_SESSION['id_utilisateur']);
    echo "<p>Type utilisateur (DB): <strong>" . htmlspecialchars($type ?? 'NULL') . "</strong></p>";

    // Vérifier la table type_utilisateur
    echo "<h3>Types d'utilisateurs disponibles</h3>";
    $stmt = $db->query("SELECT id_type_utilisateur, lib_type_utilisateur FROM type_utilisateur ORDER BY id_type_utilisateur");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Libellé</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>" . $row['id_type_utilisateur'] . "</td><td>" . htmlspecialchars($row['lib_type_utilisateur']) . "</td></tr>";
    }
    echo "</table>";

    // Vérifier l'utilisateur dans la base
    echo "<h3>Informations de l'utilisateur connecté</h3>";
    $query = "SELECT u.*, t.lib_type_utilisateur, gu.lib_groupe_util 
              FROM utilisateur u
              LEFT JOIN type_utilisateur t ON u.id_type_utilisateur = t.id_type_utilisateur
              LEFT JOIN groupe_utilisateur gu ON u.id_GU = gu.id_GU
              WHERE u.id_utilisateur = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $_SESSION['id_utilisateur']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";
    foreach ($user as $key => $value) {
        echo "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
    }
    echo "</table>";

    // Si c'est un étudiant, vérifier dans la table etudiants
    if ($type === 'Etudiant' || $type === 'Étudiant') {
        echo "<h3>Recherche dans la table etudiants</h3>";

        $etudiantModel = new Etudiant($db);
        $nomUtilisateur = $_SESSION['nom_utilisateur'] ?? '';
        $login = $_SESSION['login_utilisateur'] ?? '';

        echo "<p>Login utilisateur: <strong>" . htmlspecialchars($login) . "</strong></p>";
        echo "<p>Nom utilisateur: <strong>" . htmlspecialchars($nomUtilisateur) . "</strong></p>";

        // Méthode utilisée dans AuthController (passe maintenant le nom complet)
        $etudiant = $etudiantModel->getEtudiantByLogin($nomUtilisateur);

        if ($etudiant) {
            echo "<p>✓ Étudiant trouvé avec getEtudiantByLogin()</p>";
            echo "<pre>";
            print_r($etudiant);
            echo "</pre>";

            if (isset($etudiant->num_carte_etud)) {
                echo "<p><strong style='color: green;'>num_carte_etud trouvé: " . htmlspecialchars($etudiant->num_carte_etud) . "</strong></p>";
                echo "<p style='color: red;'>⚠️ Ce numéro DEVRAIT être dans \$_SESSION['num_etu'] mais il est absent!</p>";
                echo "<p style='color: blue;'>Action requise: Le problème vient du code de connexion dans AuthController.php</p>";
            } else {
                echo "<p style='color: red;'>✗ L'objet étudiant n'a pas de propriété num_carte_etud</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Aucun étudiant trouvé avec getEtudiantByLogin()</p>";

            // Recherche manuelle par nom
            echo "<h4>Recherche manuelle dans la table etudiants par nom</h4>";
            $parts = explode(' ', trim($nomUtilisateur), 2);
            if (count($parts) >= 2) {
                $query = "SELECT * FROM etudiants 
                          WHERE (UPPER(nom_etu) = UPPER(:part1) AND UPPER(prenom_etu) = UPPER(:part2))
                             OR (UPPER(prenom_etu) = UPPER(:part1) AND UPPER(nom_etu) = UPPER(:part2))
                          LIMIT 1";
                $stmt = $db->prepare($query);
                $stmt->execute([':part1' => $parts[0], ':part2' => $parts[1]]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    echo "<p>✓ Étudiant trouvé dans la table</p>";
                    echo "<pre>";
                    print_r($result);
                    echo "</pre>";
                } else {
                    echo "<p style='color: red;'>✗ Aucun étudiant trouvé avec le nom '" . htmlspecialchars($nomUtilisateur) . "'</p>";

                    // Afficher tous les étudiants pour debug
                    echo "<h4>Liste de tous les étudiants (10 premiers)</h4>";
                    $stmt = $db->query("SELECT num_carte_etud, nom_etu, prenom_etu, email_etu FROM etudiants LIMIT 10");
                    echo "<table border='1' cellpadding='5'>";
                    echo "<tr><th>num_carte_etud</th><th>nom_etu</th><th>prenom_etu</th><th>email_etu</th></tr>";
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['num_carte_etud']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['nom_etu']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['prenom_etu']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['email_etu'] ?? 'NULL') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "<p style='color: red;'>✗ Impossible de diviser le nom en deux parties</p>";
            }
        }
    }
}

echo "<hr>";
echo "<h2>Diagnostic terminé</h2>";
echo "<p><a href='public/layout.php?page=dashboard'>← Retour au dashboard</a></p>";
