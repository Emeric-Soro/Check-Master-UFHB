# STRUCTURE COMPLÈTE DE LA BASE DE DONNÉES

**Date d'analyse:** 10 mars 2026
**Base de données:** ufrmi1802974_2q2mpf
**Nombre total de tables:** 62

---

## SOMMAIRE DES TABLES

1. [action](#action)
2. [affecter](#affecter)
3. [annee_academique](#annee_academique)
4. [app_settings](#app_settings)
5. [auth_rate_limits](#auth_rate_limits)
6. [avoir](#avoir)
7. [bareme_critere](#bareme_critere)
8. [candidature_soutenance](#candidature_soutenance)
9. [categories_fonctionnalites](#categories_fonctionnalites)
10. [compte_rendu](#compte_rendu)
11. [compte_rendu_rapport](#compte_rendu_rapport)
12. [critere_evaluation](#critere_evaluation)
13. [decisions_jury](#decisions_jury)
14. [deposer](#deposer)
15. [document](#document)
16. [domaine](#domaine)
17. [enseignant_jury](#enseignant_jury)
18. [enseignants](#enseignants)
19. [entreprises](#entreprises)
20. [etablissement_origine](#etablissement_origine)
21. [etudiants](#etudiants)
22. [evaluations_rapports](#evaluations_rapports)
23. [evaluer](#evaluer)
24. [filiere](#filiere)
25. [fonction](#fonction)
26. [fonctionnalites](#fonctionnalites)
27. [frais_inscription](#frais_inscription)
28. [genre](#genre)
29. [grade](#grade)
30. [groupe_utilisateur](#groupe_utilisateur)
31. [informations_stage](#informations_stage)
32. [inscriptions](#inscriptions)
33. [maitre_de_stage](#maitre_de_stage)
34. [mentions](#mentions)
35. [messages](#messages)
36. [mode_paiement](#mode_paiement)
37. [niveau_acces_donnees](#niveau_acces_donnees)
38. [niveau_approbation](#niveau_approbation)
39. [niveau_etude](#niveau_etude)
40. [notes](#notes)
41. [occuper](#occuper)
42. [password_resets](#password_resets)
43. [permissions](#permissions)
44. [personnel_admin](#personnel_admin)
45. [pister](#pister)
46. [programmer_soutenance](#programmer_soutenance)
47. [qualite_jury](#qualite_jury)
48. [rapport_etudiants](#rapport_etudiants)
49. [reclamations](#reclamations)
50. [rendre](#rendre)
51. [resume_candidature](#resume_candidature)
52. [route_actions](#route_actions)
53. [salles](#salles)
54. [semestre](#semestre)
55. [session](#session)
56. [specialite](#specialite)
57. [statut_jury](#statut_jury)
58. [statut_reclamation](#statut_reclamation)
59. [type_enseignant](#type_enseignant)
60. [type_utilisateur](#type_utilisateur)
61. [utilisateur](#utilisateur)
62. [valider](#valider)

---

## STRUCTURE DÉTAILLÉE DES TABLES

### action

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_action` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lib_action` | varchar(120) | varchar(120) NOT NULL |

---

### affecter

**Nombre de champs:** 4

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_enseignant` | varchar(20) | varchar(20) NOT NULL |
| 2 | `role` | enum | enum('encadrant' |
| 3 | `id_rapport` | int | int NOT NULL |
| 4 | `id_jury` | int | int DEFAULT NULL |

---

### annee_academique

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_annee_acad` | int | int NOT NULL |
| 2 | `date_deb` | date | date NOT NULL |
| 3 | `date_fin` | date | date NOT NULL |

---

### app_settings

**Nombre de champs:** 4

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `setting_key` | varchar(100) | varchar(100) NOT NULL |
| 2 | `setting_value` | text | text NOT NULL |
| 3 | `is_sensitive` | tinyint(1) | tinyint(1) NOT NULL DEFAULT '0' |
| 4 | `updated_at` | timestamp | timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

---

### auth_rate_limits

**Nombre de champs:** 10

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `action` | varchar(16) | varchar(16) NOT NULL |
| 3 | `ip` | varchar(45) | varchar(45) NOT NULL |
| 4 | `identifier` | varchar(128) | varchar(128) NOT NULL |
| 5 | `attempts` | int | int NOT NULL DEFAULT '0' |
| 6 | `window_start` | datetime | datetime NOT NULL |
| 7 | `last_attempt` | datetime | datetime NOT NULL |
| 8 | `blocked_until` | datetime | datetime DEFAULT NULL |
| 9 | `created_at` | timestamp | timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP |
| 10 | `updated_at` | timestamp | timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP |

---

### avoir

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_grade` | varchar(2) | varchar(2) NOT NULL |
| 2 | `id_enseignant` | varchar(20) | varchar(20) NOT NULL |
| 3 | `date_grade` | date | date NOT NULL |

---

### bareme_critere

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_annee_acad` | int | int NOT NULL |
| 2 | `id_critere` | varchar(2) | varchar(2) NOT NULL |
| 3 | `bareme` | int | int NOT NULL |

---

### candidature_soutenance

**Nombre de champs:** 7

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_candidature` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `num_etu` | varchar(25) | varchar(25) NOT NULL |
| 3 | `date_candidature` | datetime | datetime NOT NULL |
| 4 | `statut_candidature` | enum | enum( |
| 5 | `date_traitement` | datetime | datetime DEFAULT NULL |
| 6 | `id_pers_admin` | int | int DEFAULT NULL |
| 7 | `commentaire_admin` | text | text |

---

### categories_fonctionnalites

**Nombre de champs:** 8

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_categorie` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `code_categorie` | varchar(50) | varchar(50) NOT NULL |
| 3 | `lib_categorie` | varchar(100) | varchar(100) NOT NULL |
| 4 | `description_categorie` | text | text |
| 5 | `icone_categorie` | varchar(100) | varchar(100) DEFAULT NULL |
| 6 | `ordre_categorie` | int | int DEFAULT '0' |
| 7 | `actif` | tinyint(1) | tinyint(1) DEFAULT '1' |
| 8 | `date_creation` | timestamp | timestamp NULL DEFAULT CURRENT_TIMESTAMP |

---

### compte_rendu

**Nombre de champs:** 6

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_CR` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `num_etu` | varchar(25) | varchar(25) NOT NULL |
| 3 | `nom_CR` | varchar(70) | varchar(70) NOT NULL |
| 4 | `contenu_CR` | longtext | longtext |
| 5 | `chemin_fichier_pdf` | varchar(255) | varchar(255) DEFAULT NULL |
| 6 | `date_CR` | datetime | datetime NOT NULL |

---

### compte_rendu_rapport

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_CR` | int | int NOT NULL |
| 2 | `id_rapport` | int | int NOT NULL |

---

### critere_evaluation

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_critere` | varchar(2) | varchar(2) NOT NULL |
| 2 | `lib_critere` | varchar(100) | varchar(100) NOT NULL |

---

### decisions_jury

**Nombre de champs:** 4

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_decision` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lib_decision` | varchar(120) | varchar(120) NOT NULL |
| 3 | `description` | text | text |
| 4 | `actif` | tinyint(1) | tinyint(1) NOT NULL DEFAULT '1' |

---

### deposer

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `num_etu` | varchar(25) | varchar(25) NOT NULL |
| 2 | `id_rapport` | int | int NOT NULL |
| 3 | `date_depot` | datetime | datetime NOT NULL |

---

### document

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_document` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lien_document` | varchar(255) | varchar(255) NOT NULL |

---

### domaine

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_domaine` | int | int NOT NULL |
| 2 | `lib_domaine` | varchar(150) | varchar(150) NOT NULL |

---

### enseignant_jury

**Nombre de champs:** 4

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `num_soutenance` | varchar(20) | varchar(20) NOT NULL |
| 2 | `id_enseignant` | varchar(20) | varchar(20) NOT NULL |
| 3 | `id_qualite_jury` | varchar(2) | varchar(2) NOT NULL |
| 4 | `date_composer_jury` | datetime | datetime DEFAULT NULL |

---

### enseignants

**Nombre de champs:** 8

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_enseignant` | varchar(20) | varchar(20) NOT NULL |
| 2 | `nom_enseignant` | varchar(50) | varchar(50) NOT NULL |
| 3 | `prenom_enseignant` | varchar(100) | varchar(100) NOT NULL |
| 4 | `tel_enseignant` | varchar(20) | varchar(20) DEFAULT NULL |
| 5 | `mail_enseignant` | varchar(100) | varchar(100) DEFAULT NULL |
| 6 | `id_specialite` | int | int DEFAULT NULL |
| 7 | `type_enseignant` | int | int DEFAULT NULL |
| 8 | `id_etablissement_origin` | int | int DEFAULT NULL |

---

### entreprises

**Nombre de champs:** 6

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_entreprise` | int | int NOT NULL |
| 2 | `lib_long_entreprise` | varchar(100) | varchar(100) NOT NULL |
| 3 | `lib_court_en` | varchar(50) | varchar(50) NOT NULL |
| 4 | `logo` | varchar(256) | varchar(256) DEFAULT NULL |
| 5 | `email` | varchar(100) | varchar(100) DEFAULT NULL |
| 6 | `telephone` | varchar(20) | varchar(20) DEFAULT NULL |

---

### etablissement_origine

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_etablissement` | int | int NOT NULL |
| 2 | `libelle_long` | varchar(120) | varchar(120) NOT NULL |
| 3 | `libelle_court` | varchar(30) | varchar(30) NOT NULL |

---

### etudiants

**Nombre de champs:** 8

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `num_ident_etud` | varchar(25) | varchar(25) DEFAULT NULL |
| 2 | `num_carte_etud` | varchar(25) | varchar(25) NOT NULL |
| 3 | `nom_etu` | varchar(50) | varchar(50) NOT NULL |
| 4 | `prenom_etu` | varchar(100) | varchar(100) NOT NULL |
| 5 | `date_naiss_etu` | date | date DEFAULT NULL |
| 6 | `id_genre` | char(1) | char(1) DEFAULT NULL |
| 7 | `email_etu` | varchar(100) | varchar(100) DEFAULT NULL |
| 8 | `promotion_etu` | varchar(30) | varchar(30) DEFAULT NULL |

---

### evaluations_rapports

**Nombre de champs:** 7

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_evaluation` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `id_rapport` | int | int NOT NULL |
| 3 | `id_evaluateur` | int | int NOT NULL |
| 4 | `decision_evaluation` | enum | enum('valider' |
| 5 | `commentaire` | text | text |
| 6 | `date_evaluation` | datetime | datetime NOT NULL DEFAULT CURRENT_TIMESTAMP |
| 7 | `date_modification` | datetime | datetime DEFAULT NULL |

---

### evaluer

**Nombre de champs:** 5

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `num_etudiant` | varchar(25) | varchar(25) NOT NULL |
| 2 | `num_jury` | int | int NOT NULL |
| 3 | `id_critere` | varchar(2) | varchar(2) NOT NULL |
| 4 | `date_eval` | date | date NOT NULL |
| 5 | `note` | double | double NOT NULL |

---

### filiere

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_filiere` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lib_filiere` | varchar(100) | varchar(100) NOT NULL |

---

### fonction

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_fonction` | varchar(2) | varchar(2) NOT NULL |
| 2 | `lib_fonction` | varchar(100) | varchar(100) NOT NULL |
| 3 | `origine_entreprise` | tinyint(1) | tinyint(1) DEFAULT NULL |

---

### fonctionnalites

**Nombre de champs:** 13

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_fonctionnalite` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `id_categorie` | int | int NOT NULL |
| 3 | `code_fonctionnalite` | varchar(50) | varchar(50) NOT NULL |
| 4 | `lib_fonctionnalite` | varchar(100) | varchar(100) NOT NULL |
| 5 | `label_fonctionnalite` | varchar(150) | varchar(150) DEFAULT NULL |
| 6 | `description_fonctionnalite` | text | text |
| 7 | `url_fonctionnalite` | varchar(255) | varchar(255) NOT NULL |
| 8 | `icone_fonctionnalite` | varchar(100) | varchar(100) DEFAULT NULL |
| 9 | `ordre_fonctionnalite` | int | int DEFAULT '0' |
| 10 | `est_sous_page` | tinyint(1) | tinyint(1) DEFAULT '0' |
| 11 | `page_parente` | varchar(50) | varchar(50) DEFAULT NULL |
| 12 | `actif` | tinyint(1) | tinyint(1) DEFAULT '1' |
| 13 | `date_creation` | timestamp | timestamp NULL DEFAULT CURRENT_TIMESTAMP |

---

### frais_inscription

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_niv_etude` | varchar(2) | varchar(2) NOT NULL |
| 2 | `id_annee_acad` | int | int NOT NULL |
| 3 | `montant` | decimal | decimal(10 |

---

### genre

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_genre` | char(1) | char(1) NOT NULL |
| 2 | `libelle_genre` | varchar(20) | varchar(20) NOT NULL |

---

### grade

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_grade` | varchar(2) | varchar(2) NOT NULL |
| 2 | `lib_grade` | varchar(50) | varchar(50) NOT NULL |

---

### groupe_utilisateur

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_GU` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lib_GU` | varchar(100) | varchar(100) NOT NULL |
| 3 | `id_type_utilisateur` | int | int DEFAULT NULL |

---

### informations_stage

**Nombre de champs:** 7

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_info_stage` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `num_etu` | varchar(25) | varchar(25) NOT NULL |
| 3 | `id_entreprise` | int | int NOT NULL |
| 4 | `date_debut_stage` | date | date NOT NULL |
| 5 | `date_fin_stage` | date | date NOT NULL |
| 6 | `sujet_stage` | text | text NOT NULL |
| 7 | `id_maitre_stage` | varchar(15) | varchar(15) NOT NULL |

---

### inscriptions

**Nombre de champs:** 11

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `num_carte_etud` | varchar(25) | varchar(25) NOT NULL |
| 2 | `id_annee_acad` | int | int NOT NULL |
| 3 | `num_versement` | int | int NOT NULL DEFAULT '1' |
| 4 | `date_inscription` | datetime | datetime DEFAULT NULL |
| 5 | `date_versement` | datetime | datetime DEFAULT CURRENT_TIMESTAMP |
| 6 | `id_niv_etude` | varchar(2) | varchar(2) DEFAULT NULL |
| 7 | `montant_verser` | decimal | decimal(10 |
| 8 | `methode_paiement` | varchar(2) | varchar(2) DEFAULT NULL |
| 9 | `num_piece_mp` | varchar(100) | varchar(100) DEFAULT NULL |
| 10 | `solde` | decimal | decimal(10 |
| 11 | `fiche_inscription` | varchar(255) | varchar(255) DEFAULT NULL COMMENT 'Chemin vers le fichier de la fiche d''inscription (PDF ou image)' |

---

### maitre_de_stage

**Nombre de champs:** 7

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_maitre_stage` | varchar(15) | varchar(15) NOT NULL |
| 2 | `Nom` | varchar(50) | varchar(50) DEFAULT NULL |
| 3 | `prenom` | varchar(100) | varchar(100) DEFAULT NULL |
| 4 | `email` | varchar(100) | varchar(100) DEFAULT NULL |
| 5 | `telephone` | varchar(20) | varchar(20) DEFAULT NULL |
| 6 | `id_entreprise` | int | int NOT NULL |
| 7 | `id_fonction` | varchar(2) | varchar(2) DEFAULT NULL |

---

### mentions

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_mention` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lib_mention` | varchar(100) | varchar(100) NOT NULL |
| 3 | `actif` | tinyint(1) | tinyint(1) NOT NULL DEFAULT '1' |

---

### messages

**Nombre de champs:** 4

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_message` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `contenu_message` | text | text NOT NULL |
| 3 | `lib_message` | varchar(60) | varchar(60) NOT NULL |
| 4 | `type_message` | varchar(60) | varchar(60) NOT NULL |

---

### mode_paiement

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_mode_paiement` | varchar(2) | varchar(2) NOT NULL |
| 2 | `libelle_mode_paement` | varchar(25) | varchar(25) NOT NULL |

---

### niveau_acces_donnees

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_niveau_acces_donnees` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lib_niveau_acces_donnees` | varchar(70) | varchar(70) NOT NULL |

---

### niveau_approbation

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_approb` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lib_approb` | varchar(50) | varchar(50) NOT NULL |

---

### niveau_etude

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_niv_etude` | varchar(2) | varchar(2) NOT NULL |
| 2 | `lib_niv_etude` | varchar(50) | varchar(50) NOT NULL |

---

### notes

**Nombre de champs:** 6

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `num_etu` | varchar(25) | varchar(25) NOT NULL |
| 2 | `id_annee_acad` | int | int DEFAULT NULL |
| 3 | `moyenne_M1` | decimal | decimal(4 |
| 4 | `moyenne_M2` | decimal | decimal(4 |
| 5 | `date_creation` | datetime | datetime DEFAULT CURRENT_TIMESTAMP |
| 6 | `date_modification` | datetime | datetime DEFAULT CURRENT_TIMESTAMP |

---

### occuper

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_fonction` | varchar(2) | varchar(2) NOT NULL |
| 2 | `id_enseignant` | varchar(20) | varchar(20) NOT NULL |
| 3 | `date_occupation` | date | date NOT NULL |

---

### password_resets

**Nombre de champs:** 6

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `email` | varchar(255) | varchar(255) NOT NULL |
| 3 | `token` | varchar(255) | varchar(255) NOT NULL |
| 4 | `expires_at` | datetime | datetime NOT NULL |
| 5 | `used` | tinyint(1) | tinyint(1) NOT NULL DEFAULT '0' |
| 6 | `created_at` | datetime | datetime DEFAULT CURRENT_TIMESTAMP |

---

### permissions

**Nombre de champs:** 8

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_permission` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `id_GU` | int | int NOT NULL |
| 3 | `id_fonctionnalite` | int | int NOT NULL |
| 4 | `peut_voir` | tinyint(1) | tinyint(1) NOT NULL DEFAULT '0' |
| 5 | `peut_creer` | tinyint(1) | tinyint(1) NOT NULL DEFAULT '0' |
| 6 | `peut_modifier` | tinyint(1) | tinyint(1) NOT NULL DEFAULT '0' |
| 7 | `peut_supprimer` | tinyint(1) | tinyint(1) NOT NULL DEFAULT '0' |
| 8 | `date_attribution` | datetime | datetime NOT NULL DEFAULT CURRENT_TIMESTAMP |

---

### personnel_admin

**Nombre de champs:** 7

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_pers_admin` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `nom_pers_admin` | varchar(50) | varchar(50) NOT NULL |
| 3 | `prenom_pers_admin` | varchar(100) | varchar(100) NOT NULL |
| 4 | `email_pers_admin` | varchar(100) | varchar(100) NOT NULL |
| 5 | `tel_pers_admin` | varchar(20) | varchar(20) NOT NULL |
| 6 | `poste` | varchar(60) | varchar(60) NOT NULL |
| 7 | `date_embauche` | date | date NOT NULL |

---

### pister

**Nombre de champs:** 6

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_piste` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `id_utilisateur` | int | int NOT NULL |
| 3 | `action` | varchar(60) | varchar(60) NOT NULL COMMENT 'Type d''action (CREATE |
| 4 | `statut_action` | enum | enum('Erreur' |
| 5 | `nom_table` | varchar(50) | varchar(50) DEFAULT NULL COMMENT 'Nom de la table concernee' |
| 6 | `date_creation` | timestamp | timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP |

---

### programmer_soutenance

**Nombre de champs:** 9

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `num_soutenance` | varchar(20) | varchar(20) NOT NULL |
| 2 | `num_etud` | varchar(25) | varchar(25) NOT NULL |
| 3 | `theme_soutenance` | varchar(255) | varchar(255) NOT NULL |
| 4 | `id_domaine` | int | int DEFAULT NULL |
| 5 | `id_session` | int | int NOT NULL |
| 6 | `id_salle` | int | int DEFAULT NULL |
| 7 | `date_soutenance` | date | date DEFAULT NULL |
| 8 | `heure_soutenance` | time | time DEFAULT NULL |
| 9 | `id_annee_acad` | int | int DEFAULT NULL |

---

### qualite_jury

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_role_jury` | varchar(2) | varchar(2) NOT NULL |
| 2 | `lib_role` | varchar(50) | varchar(50) NOT NULL |

---

### rapport_etudiants

**Nombre de champs:** 10

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_rapport` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `num_etu` | varchar(25) | varchar(25) NOT NULL |
| 3 | `date_redaction_rapport` | datetime | datetime NOT NULL |
| 4 | `theme_rapport` | varchar(255) | varchar(255) NOT NULL |
| 5 | `nom_rapport` | varchar(255) | varchar(255) DEFAULT NULL |
| 6 | `chemin_fichier` | varchar(255) | varchar(255) DEFAULT NULL |
| 7 | `statut_rapport` | enum | enum( |
| 8 | `date_modification` | datetime | datetime DEFAULT NULL |
| 9 | `taille_fichier` | int | int DEFAULT NULL |
| 10 | `version` | int | int NOT NULL DEFAULT '1' |

---

### reclamations

**Nombre de champs:** 7

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_reclamation` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `num_carte_etud` | varchar(25) | varchar(25) DEFAULT NULL |
| 3 | `objet_reclamation` | varchar(150) | varchar(150) NOT NULL |
| 4 | `description_reclamation` | text | text NOT NULL |
| 5 | `statut_reclamation` | int | int NOT NULL |
| 6 | `date_creation` | datetime | datetime NOT NULL DEFAULT CURRENT_TIMESTAMP |
| 7 | `date_mise_a_jour` | datetime | datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

---

### rendre

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_CR` | int | int NOT NULL |
| 2 | `id_enseignant` | varchar(20) | varchar(20) NOT NULL |
| 3 | `date_env` | datetime | datetime NOT NULL |

---

### resume_candidature

**Nombre de champs:** 6

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `num_etu` | varchar(25) | varchar(25) NOT NULL |
| 3 | `id_candidature` | int | int NOT NULL |
| 4 | `resume_json` | longtext | longtext NOT NULL |
| 5 | `decision` | varchar(20) | varchar(20) NOT NULL |
| 6 | `date_enregistrement` | datetime | datetime DEFAULT CURRENT_TIMESTAMP |

---

### route_actions

**Nombre de champs:** 8

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_route_action` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `route_pattern` | varchar(255) | varchar(255) NOT NULL |
| 3 | `http_method` | enum | enum( |
| 4 | `action_crud` | enum | enum( |
| 5 | `description` | text | text |
| 6 | `actif` | tinyint(1) | tinyint(1) NOT NULL DEFAULT '1' |
| 7 | `created_at` | timestamp | timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP |
| 8 | `updated_at` | timestamp | timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP |

---

### salles

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_salle` | int | int NOT NULL |
| 2 | `lib_salle` | varchar(100) | varchar(100) NOT NULL |

---

### semestre

**Nombre de champs:** 3

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_semestre` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lib_semestre` | varchar(15) | varchar(15) NOT NULL |
| 3 | `id_niv_etude` | varchar(2) | varchar(2) NOT NULL |

---

### session

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_session` | int | int NOT NULL |
| 2 | `lib_session` | varchar(30) | varchar(30) NOT NULL |

---

### specialite

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_specialite` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lib_specialite` | varchar(100) | varchar(100) NOT NULL |

---

### statut_jury

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_jury` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lib_jury` | varchar(50) | varchar(50) NOT NULL |

---

### statut_reclamation

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_statut_reclamation` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `libelle_statut_reclamation` | varchar(50) | varchar(50) NOT NULL |

---

### type_enseignant

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_type_enseignant` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `libelle` | varchar(100) | varchar(100) NOT NULL |

---

### type_utilisateur

**Nombre de champs:** 2

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_type_utilisateur` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `lib_type_utilisateur` | varchar(100) | varchar(100) NOT NULL |

---

### utilisateur

**Nombre de champs:** 8

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_utilisateur` | int | int NOT NULL AUTO_INCREMENT |
| 2 | `nom_utilisateur` | varchar(200) | varchar(200) NOT NULL |
| 3 | `id_type_utilisateur` | int | int NOT NULL |
| 4 | `id_GU` | int | int NOT NULL |
| 5 | `id_niv_acces_donnee` | int | int NOT NULL |
| 6 | `statut_utilisateur` | enum | enum('Actif' |
| 7 | `login_utilisateur` | varchar(60) | varchar(60) NOT NULL |
| 8 | `mdp_utilisateur` | varchar(255) | varchar(255) NOT NULL |

---

### valider

**Nombre de champs:** 5

| # | Nom du champ | Type | Définition complète |
|---|--------------|------|---------------------|
| 1 | `id_enseignant` | varchar(20) | varchar(20) NOT NULL |
| 2 | `id_rapport` | int | int NOT NULL |
| 3 | `date_validation` | datetime | datetime NOT NULL |
| 4 | `commentaire_validation` | varchar(1000) | varchar(1000) NOT NULL |
| 5 | `decision_validation` | enum | enum('valider' |

---

