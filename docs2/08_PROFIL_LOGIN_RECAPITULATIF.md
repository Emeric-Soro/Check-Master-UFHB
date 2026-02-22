# 8. PROFIL UTILISATEUR — Description de l'Écran

---

## 8.1 Écran : PROFIL — Mon Profil

**ID & TITRE :** `PROFIL` — Mon Profil

**OBJECTIF :** Permettre à tout utilisateur connecté de consulter ses informations de profil, de modifier son mot de passe et de mettre à jour certaines informations personnelles (selon son rôle).

**VUE PRINCIPALE :** Page de profil (formulaire de consultation/modification, pas de tableau).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Section Information personnelle (lecture seule pour la plupart des champs) :*

| Champ | Type | Modifiable | Source |
|---|---|---|---|
| Nom Utilisateur | Texte (lecture seule) | Non | `utilisateur.nom_utilisateur` |
| Login | Texte (lecture seule) | Non | `utilisateur.login_utilisateur` |
| Type utilisateur | Texte (lecture seule) | Non | `type_utilisateur.lib_type_utilisateur` |
| Groupe | Texte (lecture seule) | Non | `groupe_utilisateur.lib_GU` |
| Niveau d'accès | Texte (lecture seule) | Non | `niveau_acces_donnees.lib_niveau_acces_donnees` |
| Statut | Badge (lecture seule) | Non | `utilisateur.statut_utilisateur` |

*Section Changement de mot de passe :*

| Champ | Type | Obligatoire | Taille max |
|---|---|---|---|
| Ancien mot de passe | Password | **Oui** ★ | 255 |
| Nouveau mot de passe | Password | **Oui** ★ | 255 |
| Confirmation nouveau MDP | Password | **Oui** ★ | 255 |

*Informations complémentaires (selon le rôle) :*

| Si l'utilisateur est... | Données supplémentaires affichées | Source |
|---|---|---|
| Étudiant | N° carte, nom, prénom, email, date naissance, genre, promotion, niveau | `etudiants` |
| Enseignant | Nom, prénom, email, téléphone, grade, spécialité, type, établissement | `enseignants` |
| Personnel admin | Nom, prénom, email, téléphone, poste, date embauche | `personnel_admin` |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Changer le mot de passe | Vert | POST → vérifie ancien MDP, hash nouveau MDP, met à jour `utilisateur.mdp_utilisateur` |
| Déconnexion | Rouge | Détruit la session, redirige vers la page de connexion |

**RÈGLES MÉTIER :**
- L'ancien mot de passe doit être correct (vérification `password_verify` avec le hash bcrypt).
- Le nouveau mot de passe doit correspondre à la confirmation.
- Le nouveau mot de passe doit respecter une politique minimale (ex : 8 caractères minimum).
- Les champs de profil (nom, login, type, groupe) ne sont PAS modifiables par l'utilisateur, car la création de comptes est centralisée dans `SYS_UTILISATEURS`.
- Les informations complémentaires sont en lecture seule (la modification se fait via les écrans de référentiel correspondants).
- Un mot de passe réinitialisé par l'admin (via `SYS_UTILISATEURS`) peut être changé ici par l'utilisateur.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès changement MDP : "Mot de passe modifié avec succès" (notification verte).
- Erreur ancien MDP : "L'ancien mot de passe est incorrect" (notification rouge).
- Erreur confirmation : "Les mots de passe ne correspondent pas" (notification rouge).

**LA ROUTE :** `?page=profil`

**COMPORTEMENT DE LA PAGE :**
- Pas de segmentation polarisée classique (pas de tableau).
- Structure en sections :
  1. Carte de profil (informations principales).
  2. Informations détaillées (selon le rôle).
  3. Formulaire de changement de mot de passe.
- Pas de panneau latéral.
- Accessible à TOUS les utilisateurs connectés (tous les groupes).
- Responsive : sections empilées en mobile.

**Critères de filtrage :** Aucun.

**Traçabilité :** Changement de MDP logué dans `pister`.

**Sécurité :**
- Chaque utilisateur ne voit que SON profil (filtrage par session).
- L'ancien MDP est vérifié côté serveur avant tout changement.
- Le nouveau MDP est hashé en bcrypt.
- Protection CSRF sur le formulaire.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ MON PROFIL                                                                   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ 👤 INFORMATIONS DU COMPTE                                             │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ Nom :          Koua Brou                                              │   │
│ │ Login :        kouabrou@gmail.com                                     │   │
│ │ Type :         Enseignant administratif                               │   │
│ │ Groupe :       Administrateur                                         │   │
│ │ Niveau accès : Écriture                                               │   │
│ │ Statut :       🟢 Actif                                               │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ 📋 INFORMATIONS COMPLÉMENTAIRES                                       │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ Email :       brou.patrice@ufhb.edu.ci                               │   │
│ │ Téléphone :   07 00 00 00 00                                         │   │
│ │ Grade :       Maître de conférence                                    │   │
│ │ Spécialité :  Informatique                                            │   │
│ │ Établissement: UFHB                                                   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ 🔒 CHANGER MON MOT DE PASSE                                          │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ Ancien mot de passe *      [●●●●●●●●●●●]                             │   │
│ │ Nouveau mot de passe *     [●●●●●●●●●●●]                             │   │
│ │ Confirmer nouveau MDP *    [●●●●●●●●●●●]                             │   │
│ │                                                                       │   │
│ │                                         [✔ Changer le mot de passe]   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │                           [🚪 Se déconnecter]                         │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

# 9. ÉCRAN DE CONNEXION (Login)

**ID & TITRE :** `LOGIN` — Connexion

**OBJECTIF :** Authentifier les utilisateurs du système pour accéder à leur espace personnel.

**VUE PRINCIPALE :** Page de connexion centrée (formulaire unique).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Champ | Type | Obligatoire | Taille max |
|---|---|---|---|
| Login (email/identifiant) | Texte | **Oui** ★ | 60 |
| Mot de passe | Password | **Oui** ★ | 255 |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Se connecter | Bleu primaire `#1a5276` | POST → vérifie login + MDP hashé, crée session |
| Mot de passe oublié | Lien bleu | Redirige vers un formulaire de réinitialisation |

**RÈGLES MÉTIER :**
- Le login est vérifié contre `utilisateur.login_utilisateur`.
- Le mot de passe est vérifié avec `password_verify()` contre `utilisateur.mdp_utilisateur`.
- Le statut de l'utilisateur doit être 'Actif'. Un utilisateur 'Inactif' ne peut pas se connecter.
- La connexion réussie crée une session PHP et redirige vers le dashboard correspondant au type/groupe de l'utilisateur.
- La connexion échouée est logguée dans `pister` (action = "Connexion", statut = "Erreur").
- La connexion réussie est aussi logguée (statut = "Succès").
- Un rate limiter peut être implémenté via la table `authentication_rate_limits`.
- La réinitialisation de MDP utilise la table `password_resets` (token unique, expiration, flag `used`).

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : redirection vers le dashboard.
- Erreur : "Login ou mot de passe incorrect" (message générique pour la sécurité).
- Compte inactif : "Votre compte est désactivé. Contactez l'administrateur."

**LA ROUTE :** `/` ou `?page=login` (page par défaut non authentifiée).

**COMPORTEMENT DE LA PAGE :**
- Page centrée, pas de sidebar ni header.
- Logo de l'application en haut.
- Formulaire de connexion au centre.
- Lien "Mot de passe oublié ?" sous le formulaire.
- Aucun menu visible.
- Redirection automatique vers la page de connexion si la session expire.

**Critères de filtrage :** Aucun.

**Traçabilité :** Toute tentative de connexion est logguée.

**Sécurité :**
- Mots de passe hashés bcrypt.
- Protection CSRF.
- Rate limiting possible.
- Sessions sécurisées (HTTPOnly, Secure flags).

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│                                                                              │
│                                                                              │
│                          ┌────────────────────┐                              │
│                          │  🎓 CheckMaster    │                              │
│                          │  UFHB - UFR MI     │                              │
│                          └────────────────────┘                              │
│                                                                              │
│                   ┌──────────────────────────────┐                           │
│                   │                              │                           │
│                   │  Login *                     │                           │
│                   │  [________________________]  │                           │
│                   │                              │                           │
│                   │  Mot de passe *              │                           │
│                   │  [●●●●●●●●●●●●●●●●●●●●●●●]  │                           │
│                   │                              │                           │
│                   │  [=======Se connecter======]│                           │
│                   │                              │                           │
│                   │  Mot de passe oublié ?       │                           │
│                   │                              │                           │
│                   └──────────────────────────────┘                           │
│                                                                              │
│                   © 2026 UFR MI - UFHB                                      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

# 10. TABLEAU RÉCAPITULATIF — TOUS LES ÉCRANS

| N° | Code | Libellé | Menu | Route | Type |
|---|---|---|---|---|---|
| 1 | `DASH_SCOLARITE` | Tableau de bord scolarité | Scolarité | `?page=dashboard_scolarite` | Dashboard |
| 2 | `MAJ_ETUDIANT` | Mise à jour étudiant | Scolarité > Gest. Étudiants | `?page=gestion_etudiants&action=ajouter_des_etudiants` | CRUD |
| 3 | `INSCRIPTION_ETUDIANT` | Inscription étudiant | Scolarité > Gest. Étudiants | `?page=gestion_scolarite` | CRUD |
| 4 | `MOYENNE_ETUDIANT` | Saisie des moyennes | Scolarité > Gest. Étudiants | `?page=gestion_notes_evaluations` | CRUD |
| 5 | `DOSSIER_CANDIDATURE` | Dossiers de candidatures | Scolarité > Gest. Candidatures | `?page=gestion_dossiers_candidatures` | CRUD |
| 6 | `RECLAMATION_ETUDIANT` | Réclamations (scolarité) | Scolarité > Gest. Candidatures | `?page=gestion_reclamations_scolarite` | CRUD |
| 7 | `ETU_CANDIDATURE` | Candidature | Env. Étudiant | `?page=candidature_soutenance` | CRUD |
| 8 | `ETUD_RAPPORT` | Mes rapports | Env. Étudiant | `?page=gestion_rapports` | CRUD |
| 9 | `ETU_RECLAMATION` | Réclamations (étudiant) | Env. Étudiant | `?page=gestion_reclamations` | CRUD |
| 10 | `ETU_CONSULTATION_CR` | Consultation CR | Env. Étudiant | `?page=consultation_cr_etud` | Lecture |
| 11 | `COM_DASHBOARD` | Tableau de bord commission | Commission | `?page=dashboard_commission` | Dashboard |
| 12 | `COM_RECEPTION_RAPPORT` | Réception rapports | Commission > Gest. Rapports | `?page=reception_rapport_com` | CRUD |
| 13 | `ANA_APP_RAPPORT` | Analyse et approbation | Commission > Gest. Rapports | `?page=evaluation_dossiers` | CRUD |
| 14 | `SUIVI_VALIDATION_COM` | Suivi d'avancement | Commission > Gest. Rapports | `?page=processus_validation` | Lecture |
| 15 | `CR_HUB` | Hub Comptes Rendus | Commission > CR | `?page=redaction_compte_rendu` | Hub |
| 16 | `CR_REDACTION` | Rédaction CR | Commission > CR | `?page=redaction_compte_rendu` | Éditeur |
| 17 | `CR_BROUILLONS` | Brouillons CR | Commission > CR | `?page=redaction_compte_rendu&action=brouillons` | Liste |
| 18 | `CR_ARCHIVES` | Archives CR | Commission > CR | `?page=redaction_compte_rendu&action=archives` | Liste |
| 19 | `SOUT_COMPOS_JURY` | Composition jury | Soutenance | `?page=programmation_soutenance` | CRUD |
| 20 | `SOUT_EVALUATION` | Évaluation soutenance | Soutenance | `?page=evaluation_soutenance` | CRUD |
| 21 | `SOUT_EDITION_BULLETIN` | Édition bulletins | Soutenance | `?page=edition_bulletin` | Génération |
| 22 | `ENS_DASHBOARD` | Tableau de bord enseignant | Espace Enseignant | `?page=tableau_bord_enseignant` | Dashboard |
| 23 | `DASH_ENSEIGNANT` | Dashboard Enseignant (via Espaces) | Commission > Espaces | `?page=dashboard_enseignant` | Dashboard |
| 24 | `ADM_DASHBOARD` | Dashboard Admin | Admin | `?page=dashboard` | Dashboard |
| 25 | `ADMIN_ANNEE_ACADEMIQUE` | Ouverture/Fermeture AC | Admin | `?page=parametres_generaux&action=annees_academiques` | CRUD |
| 26 | `PARAM_HUB` | Paramètres Généraux | Admin > Paramétrage | `?page=parametres_generaux` | Hub |
| 27 | `PARAM_SPEC` | Paramètres Spécifiques | Admin > Paramétrage | `?page=parametres_specifiques` | Onglets |
| 28 | `SYS_UTILISATEURS` | Gestion Utilisateurs | Admin > Sécurité | `?page=gestion_utilisateurs` | CRUD |
| 29 | `SYS_AUDIT` | Piste Audit | Admin > Sécurité | `?page=piste_audit` | Lecture |
| 30 | `SYS_BACKUP` | Sauvegarde/Restauration | Admin > Sécurité | `?page=sauvegarde_restauration` | Action |
| 31 | `SYS_HISTORIQUE` | Historique et Archivage | Admin > Sécurité | `?page=admin_historique` | CRUD |
| 32 | `MAJ_ENSEIGNANT` | Mise à jour enseignant | Admin > Référentiel | `?page=maj_enseignant` | CRUD |
| 33 | `MAJ_PERSONNEL_ADMIN` | Mise à jour personnel admin | Admin > Référentiel | `?page=maj_personnel_admin` | CRUD |
| 34 | `PARAM_ATTRIB` | Gestion Attributions | Admin > Paramétrage > Gén. | `?page=parametres_generaux&action=gestion_attribution` | Matrice |
| 35-48 | `PARAM_*` | Sous-écrans de configuration | Admin > Paramétrage > Gén. | `?page=parametres_generaux&action=...` | CRUD |
| 49 | `PROFIL` | Mon Profil | Profil | `?page=profil` | Consultation |
| 50 | `LOGIN` | Connexion | — | `/` | Authentification |

(End of file - total 255 lines)
