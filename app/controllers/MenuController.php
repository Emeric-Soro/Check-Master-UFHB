<?php

include_once __DIR__ . '/../models/Traitement.php';
require_once __DIR__ . '/../utils/permissions.php';

class MenuController {


    public function genererMenu($idGroupe) {
        $traitement = new Traitement(Database::getConnection());

        return $traitement->getTraitementByGU($idGroupe);
    }
}