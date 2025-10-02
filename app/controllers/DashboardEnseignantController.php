<?php


// Récupérer
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/Enseignant.php";
require_once __DIR__ . "/../models/Etudiant.php";
require_once __DIR__ . "/../models/Ue.php";
require_once __DIR__ . "/../models/Ecue.php";
require_once __DIR__ . "/../models/NiveauEtude.php";

/**
 * Contrôleur du tableau de bord enseignant
 * 
 * Ce contrôleur gère l'affichage des statistiques et des données du tableau de bord enseignant :
 * - Statistiques des cours enseignés
 * - Statistiques des évaluations
 * - Statistiques des étudiants encadrés
 * - Activités récentes
 * - Calendrier et échéances
 */
class DashboardEnseignantController
{
    /** @var Enseignant */
    private $enseignant;


    /** @var Etudiant */
    private $etudiant;

    /** @var Ue */
    private $ue;

    /** @var Ecue */
    private $ecue;

    /** @var NiveauEtude */
    private $niveauEtude;


    /** @var string */
    private $baseViewPath;

    /**
     * Constructeur du contrôleur
     * Initialise les modèles et les chemins nécessaires
     */
    public function __construct()
    {
        $this->baseViewPath = __DIR__ . '/../../ressources/views/';
        $this->enseignant = new Enseignant(Database::getConnection());
        $this->etudiant = new Etudiant(Database::getConnection());
        $this->ue = new Ue(Database::getConnection());
        $this->ecue = new Ecue(Database::getConnection());
        $this->niveauEtude = new NiveauEtude(Database::getConnection());
    }

    /**
     * Point d'entrée principal du contrôleur
     * Affiche le tableau de bord enseignant avec toutes les statistiques
     */
    public function index()
    {
        // Récupération des statistiques globales
        $this->getGlobalStats();
        
        
        
       
    }

    /**
     * Récupère les statistiques globales
     */
    private function getGlobalStats()
    {
        $enseignantId = $this->enseignant->getEnseignantByLogin($_SESSION['login_utilisateur'])->id_enseignant;

        // UE et ECUE pris en charge par l'enseignant
        $ues = $this->ue->getUesByEnseignant($enseignantId);
        $ecues = $this->ecue->getEcuesByEnseignant($enseignantId);

        // Récupérer tous les niveaux concernés par les UE/ECUE pris en charge
        $niveauIds = [];
        foreach ($ues as $ue) {
            if (isset($ue->id_niveau_etude) && !in_array($ue->id_niveau_etude, $niveauIds)) {
                $niveauIds[] = $ue->id_niveau_etude;
            }
        }
        foreach ($ecues as $ecue) {
            if (isset($ecue->id_niveau_etude) && !in_array($ecue->id_niveau_etude, $niveauIds)) {
                $niveauIds[] = $ecue->id_niveau_etude;
            }
        }

        // Étudiants inscrits dans ces niveaux (sans doublons)
        $etudiants = $this->etudiant->getAllListeEtudiants();
        $etudiantsSuivantCours = [];
        foreach ($etudiants as $etudiant) {
            if (in_array($etudiant->id_niv_etude, $niveauIds)) {
                $etudiantsSuivantCours[$etudiant->num_etu] = $etudiant; // clé = num_etu pour éviter les doublons
            }
        }

        // Statistiques
        $GLOBALS['total_etudiants'] = count($etudiantsSuivantCours);
        $GLOBALS['total_ues'] = count($ues);
        $GLOBALS['total_ecues'] = count($ecues);

        // Pour affichage des cours
        $mes_cours = [];
        foreach ($ues as $ue) {
            $mes_cours[] = [
                'nom' => $ue->lib_ue,
                'niveau' => $ue->lib_niv_etude,
                'nombre_etudiants' => array_reduce($etudiantsSuivantCours, function($carry, $etu) use ($ue) {
                    return $carry + ((isset($ue->id_niveau_etude) && $etu->id_niv_etude == $ue->id_niveau_etude) ? 1 : 0);
                }, 0)
            ];
        }
        foreach ($ecues as $ecue) {
            $mes_cours[] = [
                'nom' => $ecue->lib_ecue,
                'niveau' => $ecue->lib_niv_etude,
                'nombre_etudiants' => array_reduce($etudiantsSuivantCours, function($carry, $etu) use ($ecue) {
                    return $carry + ((isset($ecue->id_niveau_etude) && $etu->id_niv_etude == $ecue->id_niveau_etude) ? 1 : 0);
                }, 0)
            ];
        }
       
        $GLOBALS['mes_cours'] = $mes_cours;
    }


} 