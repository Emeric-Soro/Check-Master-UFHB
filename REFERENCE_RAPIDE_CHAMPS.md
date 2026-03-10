# RÉFÉRENCE RAPIDE - TOUS LES CHAMPS DE LA BASE DE DONNÉES

## action

- id_action
- lib_action

## affecter

- id_enseignant
- role
- id_rapport
- id_jury

## annee_academique

- id_annee_acad
- date_deb
- date_fin

## app_settings

- setting_key
- setting_value
- is_sensitive
- updated_at

## auth_rate_limits

- id
- action
- ip
- identifier
- attempts
- window_start
- last_attempt
- blocked_until
- created_at
- updated_at

## avoir

- id_grade
- id_enseignant
- date_grade

## bareme_critere

- id_annee_acad
- id_critere
- bareme

## candidature_soutenance

- id_candidature
- num_etu
- id_annee_acad
- date_candidature
- statut_candidature
- id_pers_admin
- observations

## categories_fonctionnalites

- id_categorie
- code_categorie
- lib_categorie
- label_categorie
- description
- icone
- ordre
- actif

## compte_rendu

- id_CR
- num_etu
- titre
- contenu
- date_creation
- date_modification

## compte_rendu_rapport

- id_CR
- id_rapport

## critere_evaluation

- id_critere
- lib_critere

## decisions_jury

- id_decision
- num_soutenance
- decision
- id_mention

## deposer

- num_etu
- id_rapport
- date_depot

## document

- id_document
- lib_document

## domaine

- id_domaine
- lib_domaine

## enseignant_jury

- num_soutenance
- id_enseignant
- id_qualite_jury
- note_jury

## enseignants

- id_enseignant
- nom_enseignant
- prenom_enseignant
- tel_enseignant
- mail_enseignant
- id_specialite
- type_enseignant
- id_etablissement_origin

## entreprises

- id_entreprise
- nom_entreprise
- adresse_entreprise
- tel_entreprise
- email_entreprise
- secteur_activite

## etablissement_origine

- id_etablissement
- lib_etablissement_court
- lib_etablissement_long

## etudiants

- num_ident_etud
- num_carte_etud
- nom_etu
- prenom_etu
- date_naiss_etu
- id_genre
- email_etu
- promotion_etu

## evaluations_rapports

- id_evaluation
- id_rapport
- id_enseignant
- note_contenu
- note_presentation
- note_redaction
- observations

## evaluer

- num_etudiant
- num_jury
- id_critere
- note
- id_annee_acad

## filiere

- id_filiere
- lib_filiere

## fonction

- id_fonction
- lib_fonction
- abreviation

## fonctionnalites

- id_fonctionnalite
- id_categorie
- code_fonctionnalite
- lib_fonctionnalite
- label_fonctionnalite
- description_fonctionnalite
- url_fonctionnalite
- icone_fonctionnalite
- ordre_fonctionnalite
- est_sous_page
- page_parente
- actif
- date_creation

## frais_inscription

- id_niv_etude
- id_annee_acad
- montant

## genre

- id_genre
- libelle_genre

## grade

- id_grade
- lib_grade

## groupe_utilisateur

- id_GU
- lib_GU
- id_type_utilisateur

## informations_stage

- id_info_stage
- num_etu
- id_entreprise
- id_maitre_stage
- date_debut
- date_fin
- sujet_stage

## inscriptions

- num_carte_etud
- id_annee_acad
- num_versement
- date_inscription
- date_versement
- id_niv_etude
- montant_verser
- methode_paiement
- num_piece_mp
- solde
- fiche_inscription

## maitre_de_stage

- id_maitre_stage
- nom_maitre_stage
- prenom_maitre_stage
- tel_maitre_stage
- email_maitre_stage
- id_entreprise
- id_fonction

## mentions

- id_mention
- lib_mention
- active

## messages

- id_message
- expediteur
- destinataire
- contenu

## mode_paiement

- id_mode_paiement
- lib_mode_paiement

## niveau_acces_donnees

- id_niveau_acces_donnees
- lib_niveau_acces_donnees

## niveau_approbation

- id_approb
- lib_niveau_approb

## niveau_etude

- id_niv_etude
- lib_niv_etude

## notes

- num_etu
- id_annee_acad
- moyenne_M1
- moyenne_M2
- date_creation
- date_modification

## occuper

- id_fonction
- id_enseignant
- date_debut

## password_resets

- id
- email
- token
- expires_at
- used
- created_at

## permissions

- id_permission
- id_GU
- id_fonctionnalite
- peut_voir
- peut_creer
- peut_modifier
- peut_supprimer
- date_attribution

## personnel_admin

- id_pers_admin
- nom_pers_admin
- prenom_pers_admin
- tel_pers_admin
- email_pers_admin
- id_fonction
- matricule_admin

## pister

- id_piste
- id_utilisateur
- action
- statut_action
- nom_table
- date_creation

## programmer_soutenance

- num_soutenance
- num_etud
- date_soutenance
- heure_debut
- heure_fin
- id_salle
- id_session
- id_annee_acad
- statut_soutenance

## qualite_jury

- id_role_jury
- lib_role

## rapport_etudiants

- id_rapport
- num_etu
- titre_rapport
- chemin_fichier
- date_depot
- statut_validation
- id_annee_acad
- type_rapport
- note_finale
- observations

## reclamations

- id_reclamation
- num_etu
- objet
- description
- date_reclamation
- statut_reclamation
- id_pers_admin

## rendre

- id_CR
- id_enseignant
- date_rendu

## resume_candidature

- id
- id_candidature
- titre
- contenu
- date_creation
- date_modification

## route_actions

- id_route_action
- route_pattern
- http_method
- action_name
- description
- required_permission
- actif
- created_at

## salles

- id_salle
- lib_salle

## semestre

- id_semestre
- lib_semestre
- id_niv_etude

## session

- id_session
- lib_session

## specialite

- id_specialite
- lib_specialite

## statut_jury

- id_jury
- lib_statut

## statut_reclamation

- id_statut_reclamation
- lib_statut

## type_enseignant

- id_type_enseignant
- lib_type_enseignant

## type_utilisateur

- id_type_utilisateur
- lib_type_utilisateur

## utilisateur

- id_utilisateur
- nom_utilisateur
- id_type_utilisateur
- id_GU
- id_niv_acces_donnee
- statut_utilisateur
- login_utilisateur
- mdp_utilisateur

## valider

- id_validation
- num_etu
- id_enseignant
- date_validation
- statut_validation

---

## CONVENTIONS DE NOMMAGE OBSERVÉES

### Préfixes:

- `id_` : Identifiants/clés primaires
- `num_` : Numéros (carte, matricule, soutenance)
- `lib_` : Libellés/labels
- `date_` : Dates
- `nom_` / `prenom_` : Noms et prénoms
- `tel_` / `mail_` / `email_` : Coordonnées
- `peut_` : Booléens de permission
- `statut_` : États/statuts
- `id_niv_` : Identifiants de niveau
- `id_type_` : Identifiants de type

### Suffixes:

- `_etu` / `_etud` : Relatif aux étudiants
- `_enseignant` : Relatif aux enseignants
- `_admin` : Relatif au personnel administratif
- `_acad` : Relatif aux années académiques
- `_utilisateur` : Relatif aux utilisateurs
- `_fonctionnalite` : Relatif aux fonctionnalités
- `_GU` : Relatif aux groupes utilisateurs
- `_paiement` : Relatif aux paiements

### Tables de liaison:

- Verbes à l'infinitif: affecter, avoir, deposer, evaluer, occuper, rendre, valider

### Clés étrangères:

- Généralement préfixées `id_` et suivent le nom de la table référencée
- Exemples: `id_genre`, `id_specialite`, `id_entreprise`

---

**Total: 62 tables, ~400 champs**
