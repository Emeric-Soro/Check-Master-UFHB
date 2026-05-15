<?php
require 'app/config/database.php';
$pdo = Database::getConnection();

$sql = "SELECT COUNT(*) FROM rapport_etudiants r 
        INNER JOIN etudiants e ON (r.num_etu = e.num_carte_etud OR r.num_etu = e.num_ident_etud) 
        WHERE r.statut_rapport = 'valider' 
        AND NOT EXISTS ( 
            SELECT 1 FROM inscriptions i 
            WHERE i.num_carte_etud = e.num_ident_etud 
            AND i.solde = 0.00 
        )";
echo "REMAINING: " . $pdo->query($sql)->fetchColumn() . "\n";
