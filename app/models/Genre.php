<?php

class Genre
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllGenres()
    {
        $query = "SELECT id_genre, libelle_genre FROM genre ORDER BY libelle_genre";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
