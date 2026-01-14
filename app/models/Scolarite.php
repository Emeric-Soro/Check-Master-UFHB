<?php

namespace App\Models;

use PDO;
use Psr\Log\LoggerInterface;
use Exception;

class Scolarite
{
    private $pdo;
    private $logger;

    public function __construct(PDO $pdo, LoggerInterface $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    // Récupérer le montant de la scolarité pour un niveau d'études
    public function getMontantScolarite($id_niveau)
    {
        try {
            $query = "SELECT montant_scolarite FROM niveau_etude WHERE id_niv_etude= ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_niveau]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['montant_scolarite'] : null;
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du montant de la scolarité : " . $e->getMessage());
            return null;
        }
    }

    // Créer une inscription avec le premier versement
    public function creerInscription($id_etudiant, $id_niveau, $id_annee_acad, $montant_premier_versement, $nombre_tranches, $reste_a_payer, $methode_paiement)
    {
        $this->pdo->beginTransaction();
        try {
            // Créer l'inscription
            $query = "INSERT INTO inscriptions (id_etudiant, id_niveau, id_annee_acad, date_inscription, statut_inscription, nombre_tranche, reste_a_payer, montant_paye) 
                     VALUES (?, ?, ?, NOW(), 'En cours', ?, ?, ?)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_etudiant, $id_niveau, $id_annee_acad, $nombre_tranches, $reste_a_payer, $montant_premier_versement]);
            $id_inscription = $this->pdo->lastInsertId();

            // Enregistrer le premier versement
            $query = "INSERT INTO versements (id_inscription, montant, date_versement, type_versement, methode_paiement) 
                     VALUES (?, ?, NOW(), 'Premier versement', ?)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_inscription, $montant_premier_versement, $methode_paiement]);

            $this->pdo->commit();
            return $id_inscription;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Erreur lors de la création de l'inscription : " . $e->getMessage());
            throw $e;
        }
    }

    // Récupérer les étudiants non inscrits
    public function getEtudiantsNonInscrits()
    {
        try {
            $query = "SELECT num_etu, nom_etu, prenom_etu FROM etudiants WHERE num_etu NOT IN (SELECT id_etudiant FROM inscriptions)";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération des étudiants non inscrits : " . $e->getMessage());
            return [];
        }
    }

    // Récupérer les informations d'un étudiant
    public function getInfoEtudiant($numEtu)
    {
        try {
            $query = "SELECT num_etu, nom_etu, prenom_etu FROM etudiants WHERE num_etu = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$numEtu]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération des infos de l'étudiant : " . $e->getMessage());
            return null;
        }
    }

    // Récupérer tous les niveaux d'études
    public function getNiveauxEtudes()
    {
        try {
            $query = "SELECT id_niv_etude, lib_niv_etude,montant_scolarite, montant_inscription FROM niveau_etude";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération des niveaux d'études : " . $e->getMessage());
            return [];
        }
    }

    public function creerEcheance($id_inscription, $montant, $date_echeance)
    {
        try {
            $query = "INSERT INTO echeances (id_inscription, montant, date_echeance, statut_echeance) 
                     VALUES (?, ?, ?, 'En attente')";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$id_inscription, $montant, $date_echeance]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la création de l'échéance : " . $e->getMessage());
            return false;
        }
    }

    public function getEtudiantsInscrits()
    {
        try {
            // Retourne la liste des inscriptions avec le dernier versement et les montants calculés
            $query = "SELECT
                i.id_inscription,
                i.id_etudiant,
                e.num_etu as num_etu,
                e.nom_etu AS nom,
                e.prenom_etu AS prenom,

                n.lib_niv_etude AS nom_niveau,
                n.montant_scolarite,
                n.montant_inscription,

                a.date_deb,
                a.date_fin,

                i.date_inscription,
                i.statut_inscription,

                (SELECT id_versement FROM versements v2 WHERE v2.id_inscription = i.id_inscription ORDER BY v2.date_versement DESC LIMIT 1) AS last_versement_id,
                (SELECT montant FROM versements v3 WHERE v3.id_inscription = i.id_inscription ORDER BY v3.date_versement DESC LIMIT 1) AS last_versement_montant,
                (SELECT date_versement FROM versements v4 WHERE v4.id_inscription = i.id_inscription ORDER BY v4.date_versement DESC LIMIT 1) AS last_versement_date,
                (SELECT id_versement FROM versements v7 WHERE v7.id_inscription = i.id_inscription ORDER BY v7.date_versement ASC LIMIT 1) AS first_versement_id,
                (SELECT montant FROM versements v8 WHERE v8.id_inscription = i.id_inscription ORDER BY v8.date_versement ASC LIMIT 1) AS first_versement_montant,

                COALESCE((SELECT SUM(v5.montant) FROM versements v5 WHERE v5.id_inscription = i.id_inscription AND v5.date_versement <= NOW()), 0) AS montant_paye,
                GREATEST(n.montant_scolarite - COALESCE((SELECT SUM(v6.montant) FROM versements v6 WHERE v6.id_inscription = i.id_inscription AND v6.date_versement <= NOW()), 0), 0) AS reste_a_payer

            FROM inscriptions i
            JOIN etudiants e ON i.id_etudiant = e.num_etu
            JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
            JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad
            ORDER BY i.date_inscription DESC";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération des étudiants inscrits : " . $e->getMessage());
            return [];
        }
    }

    // Récupérer tous les étudiants
    public function getAllEtudiants()
    {
        try {
            $query = "SELECT * FROM etudiants ORDER BY nom_etu, prenom_etu";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les étudiants : " . $e->getMessage());
            return [];
        }
    }

    // Vérifier si un étudiant est déjà inscrit pour une année académique spécifique
    public function estEtudiantInscritPourAnnee($num_etu, $id_annee_acad)
    {
        try {
            $query = "SELECT COUNT(*) as count FROM inscriptions WHERE id_etudiant = ? AND id_annee_acad = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$num_etu, $id_annee_acad]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result && $result['count'] > 0);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la vérification de l'inscription : " . $e->getMessage());
            return false;
        }
    }

    // Modifier une inscription
    public function modifierInscription($id_inscription, $id_niveau, $id_annee_acad, $montant_premier_versement, $nombre_tranches, $methode_versement)
    {
        $this->pdo->beginTransaction();
        try {
            // Mettre à jour l'inscription
            $query = "UPDATE inscriptions SET id_niveau = ?, id_annee_acad = ?, nombre_tranche = ?  WHERE id_inscription = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_niveau, $id_annee_acad, $nombre_tranches, $id_inscription]);

            // Mettre à jour le premier versement
            $query = "UPDATE versements SET montant = ?, methode_paiement = ? WHERE id_inscription = ? AND type_versement = 'Premier versement'";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$montant_premier_versement, $methode_versement, $id_inscription]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Erreur lors de la modification de l'inscription : " . $e->getMessage());
            throw $e;
        }
    }

    // Supprimer les échéances d'une inscription
    public function supprimerEcheances($id_inscription)
    {
        try {
            $query = "DELETE FROM echeances WHERE id_inscription = ?";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$id_inscription]);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la suppression des échéances : " . $e->getMessage());
            return false;
        }
    }

    // Supprimer une inscription
    public function supprimerInscription($id_inscription)
    {
        $this->pdo->beginTransaction();
        try {
            // Supprimer d'abord les échéances
            $query = "DELETE FROM echeances WHERE id_inscription = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_inscription]);

            // Supprimer ensuite les versements
            $query = "DELETE FROM versements WHERE id_inscription = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_inscription]);

            // Enfin supprimer l'inscription
            $query = "DELETE FROM inscriptions WHERE id_inscription = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_inscription]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Erreur lors de la suppression de l'inscription : " . $e->getMessage());
            throw $e;
        }
    }

    // Récupérer une inscription par son ID
    public function getInscriptionById($id_inscription)
    {
        try {
            $query = "SELECT i.*, n.lib_niv_etude as nom_niveau, n.montant_scolarite as montant_total, 
                   a.date_deb, a.date_fin, v.montant as montant_premier_versement, v.methode_paiement as methode_paiement,
                   e.nom_etu as nom_etudiant, e.prenom_etu as prenom_etudiant,
                   COALESCE((SELECT SUM(v2.montant) FROM versements v2 WHERE v2.id_inscription = i.id_inscription AND v2.date_versement <= NOW()), 0) AS montant_paye,
                   GREATEST(n.montant_scolarite - COALESCE((SELECT SUM(v3.montant) FROM versements v3 WHERE v3.id_inscription = i.id_inscription AND v3.date_versement <= NOW()), 0), 0) AS reste_a_payer
               FROM inscriptions i 
               JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude 
               JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad 
               JOIN etudiants e ON i.id_etudiant = e.num_etu
               LEFT JOIN versements v ON i.id_inscription = v.id_inscription AND v.type_versement = 'Premier versement'
               WHERE i.id_inscription = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_inscription]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de l'inscription par ID : " . $e->getMessage());
            return null;
        }
    }

    // Récupérer tous les versements avec les informations associées
    public function getAllVersements()
    {
        try {
            $query = "SELECT v.*, e.nom_etu as nom_etudiant, e.prenom_etu as prenom_etudiant, i.id_inscription 
                      FROM versements v
                      JOIN inscriptions i ON v.id_inscription = i.id_inscription
                      JOIN etudiants e ON i.id_etudiant = e.num_etu
                      ORDER BY v.date_versement DESC";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de tous les versements : " . $e->getMessage());
            return [];
        }
    }

    // Récupérer un versement par son ID
    public function getVersementById($id_versement)
    {
        try {
            $query = "SELECT v.*, e.nom_etu as nom_etudiant, e.prenom_etu as prenom_etudiant, i.id_inscription 
                      FROM versements v
                      JOIN inscriptions i ON v.id_inscription = i.id_inscription
                      JOIN etudiants e ON i.id_etudiant = e.num_etu
                      WHERE v.id_versement = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_versement]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du versement par ID : " . $e->getMessage());
            return null;
        }
    }

    // Récupérer le dernier (ou le plus récent) versement pour une inscription donnée
    public function getLastVersementByInscription($id_inscription)
    {
        try {
            $query = "SELECT v.*, e.nom_etu as nom_etudiant, e.prenom_etu as prenom_etudiant, i.id_inscription
                      FROM versements v
                      JOIN inscriptions i ON v.id_inscription = i.id_inscription
                      JOIN etudiants e ON i.id_etudiant = e.num_etu
                      WHERE v.id_inscription = ?
                      ORDER BY v.date_versement DESC
                      LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_inscription]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération du dernier versement : " . $e->getMessage());
            return null;
        }
    }

    // Ajouter un nouveau versement
    public function addVersement($data)
    {
        try {
            // Insérer le versement
            $sql = "INSERT INTO versements (id_inscription, montant, methode_paiement, type_versement) 
                    VALUES (:id_inscription, :montant, :methode_paiement, 'Tranche')";
            $stmt = $this->pdo->prepare($sql);

            $result = $stmt->execute([
                'id_inscription' => $data['id_inscription'],
                'montant' => $data['montant'],
                'methode_paiement' => $data['methode_paiement']
            ]);

            if (!$result) {
                return false;
            } else {
                // Mettre à jour le montant payé dans l'inscription
                $sql = "UPDATE inscriptions i 
                        JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                        SET i.montant_paye = i.montant_paye + :montant,
                            i.reste_a_payer = n.montant_scolarite - i.montant_paye
                        WHERE i.id_inscription = :id_inscription";
                $stmt = $this->pdo->prepare($sql);

                $result = $stmt->execute([
                    'montant' => $data['montant'],
                    'id_inscription' => $data['id_inscription']
                ]);

                return true;
            }
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de l'ajout du versement : " . $e->getMessage());
            return false;
        }
    }

    // Modifier un versement existant
    public function updateVersement($id_versement, $data)
    {
        try {
            // Récupérer le versement actuel
            $query = "SELECT v.*, i.reste_a_payer, i.montant_paye 
                     FROM versements v 
                     JOIN inscriptions i ON v.id_inscription = i.id_inscription 
                     WHERE v.id_versement = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_versement]);
            $versement = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$versement) {
                throw new Exception("Versement non trouvé");
            }


            // Mettre à jour le versement
            $query = "UPDATE versements 
                     SET montant = ?, 
                         methode_paiement = ? 
                     WHERE id_versement = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([
                $data['montant'],
                $data['methode_paiement'],
                $id_versement
            ]);

            // Mettre à jour le montant payé dans l'inscription
            $sql = "UPDATE inscriptions i 
                        JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
                        SET i.montant_paye = i.montant_paye - :difference,
                            i.reste_a_payer = n.montant_scolarite - i.montant_paye
                        WHERE i.id_inscription = :id_inscription";
            $stmt = $this->pdo->prepare($sql);

            $stmt->execute([
                'difference' => $data['difference'],
                'id_inscription' => $versement['id_inscription']
            ]);


            return true;
        } catch (Exception $e) {
            $this->logger->error("Erreur lors de la mise à jour du versement : " . $e->getMessage());
            return false;
        }
    }


    // Récupérer l'ID de l'inscription par ID de l'étudiant
    public function getInscriptionByEtudiantId($id_etudiant)
    {
        try {
            $query = "SELECT i.*, n.lib_niv_etude,n.montant_inscription, n.montant_scolarite, a.date_deb, a.date_fin,
                            COALESCE((SELECT SUM(v2.montant) FROM versements v2 WHERE v2.id_inscription = i.id_inscription AND v2.date_versement <= NOW()), 0) AS montant_paye,
                            GREATEST(n.montant_scolarite - COALESCE((SELECT SUM(v3.montant) FROM versements v3 WHERE v3.id_inscription = i.id_inscription AND v3.date_versement <= NOW()), 0), 0) AS reste_a_payer
                     FROM inscriptions i 
                     JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude 
                     JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad 
                     WHERE i.id_etudiant = ?
                     ORDER BY i.date_inscription DESC 
                     LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$id_etudiant]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de l'inscription par ID étudiant : " . $e->getMessage());
            return null;
        }
    }

    // Récupérer les informations d'inscription d'un étudiant
    public function getInscriptionEtudiant($num_etu)
    {
        try {
            $query = "SELECT i.*, n.lib_niv_etude, n.montant_scolarite,n.montant_inscription, a.date_deb, a.date_fin,
                            COALESCE((SELECT SUM(v2.montant) FROM versements v2 WHERE v2.id_inscription = i.id_inscription AND v2.date_versement <= NOW()), 0) AS montant_paye,
                            GREATEST(n.montant_scolarite - COALESCE((SELECT SUM(v3.montant) FROM versements v3 WHERE v3.id_inscription = i.id_inscription AND v3.date_versement <= NOW()), 0), 0) AS reste_a_payer
                     FROM inscriptions i 
                     JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude 
                     JOIN annee_academique a ON i.id_annee_acad = a.id_annee_acad 
                     WHERE i.id_etudiant = ?
                     ORDER BY i.date_inscription DESC 
                     LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$num_etu]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de l'inscription étudiant : " . $e->getMessage());
            return null;
        }
    }

    // Récupérer les informations de scolarité d'un étudiant
    public function getScolariteEtudiant($num_etu)
    {
        try {
            $query = "SELECT i.*, n.montant_scolarite as montant_total, n.montant_inscription,
                   COALESCE((SELECT SUM(v2.montant) FROM versements v2 WHERE v2.id_inscription = i.id_inscription AND v2.date_versement <= NOW()), 0) AS montant_paye,
                   GREATEST(n.montant_scolarite - COALESCE((SELECT SUM(v3.montant) FROM versements v3 WHERE v3.id_inscription = i.id_inscription AND v3.date_versement <= NOW()), 0), 0) AS reste_a_payer,
                   (SELECT MAX(v.date_versement) FROM versements v WHERE v.id_inscription = i.id_inscription AND v.date_versement <= NOW()) as dernier_paiement
               FROM inscriptions i 
               JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude 
               WHERE i.id_etudiant = ?
               ORDER BY i.date_inscription DESC 
               LIMIT 1";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$num_etu]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return [
                    'reste_a_payer' => 0,
                    'montant_total' => 0,
                    'dernier_paiement' => null
                ];
            }

            return $result;
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération de la scolarité étudiant : " . $e->getMessage());
            return [
                'reste_a_payer' => 0,
                'montant_total' => 0,
                'dernier_paiement' => null
            ];
        }
    }

    // Récupérer les montants payés et le reste à payer pour une inscription à une date donnée
    public function getMontantsAsOf($id_inscription, $asOfDate)
    {
        try {
            $query = "SELECT
                   n.montant_scolarite as montant_total,
                   COALESCE((SELECT SUM(v2.montant) FROM versements v2 WHERE v2.id_inscription = i.id_inscription AND v2.date_versement <= ?), 0) AS montant_paye,
                   GREATEST(n.montant_scolarite - COALESCE((SELECT SUM(v3.montant) FROM versements v3 WHERE v3.id_inscription = i.id_inscription AND v3.date_versement <= ?), 0), 0) AS reste_a_payer
               FROM inscriptions i
               JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude
               WHERE i.id_inscription = ?
               LIMIT 1";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$asOfDate, $asOfDate, $id_inscription]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return [
                    'montant_total' => 0,
                    'montant_paye' => 0,
                    'reste_a_payer' => 0
                ];
            }

            return $result;
        } catch (\PDOException $e) {
            $this->logger->error("Erreur lors de la récupération des montants : " . $e->getMessage());
            return [
                'montant_total' => 0,
                'montant_paye' => 0,
                'reste_a_payer' => 0
            ];
        }
    }
}