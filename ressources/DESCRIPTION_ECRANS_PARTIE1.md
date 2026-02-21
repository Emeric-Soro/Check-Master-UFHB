## CONTRAINTTES ABSOLUES
- ❌ NE PAS modifier les contrôleurs dans `app/controllers/`
- ❌ NE PAS modifier les routes dans `config/routes.php`
- ❌ NE PAS modifier les modèles dans `app/models/`
- ✅ UNIQUEMENT créer de nouveaux fichiers dans views/pages`
# DESCRIPTION EXHAUSTIVE DES ÉCRANS — CHECKMASTER
## PARTIE 1 : SCOLARITÉ & ESPACE ÉTUDIANT

---

## ═══════════════════════════════════════════
## MENU : SCOLARITÉ
## ═══════════════════════════════════════════

---

### 📊 ÉCRAN SCR-01 : Tableau de Bord Scolarité

**ID & TITRE :** `DASH_SCOLARITE` — Tableau de bord scolarité

**OBJECTIF :** Offrir une vue synthétique et temps réel de l'activité de la scolarité (effectifs, inscriptions, candidatures, réclamations) pour permettre un pilotage rapide.

**VUE PRINCIPALE :** Dashboard (Widgets + Graphiques)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Donnée affichée | Source | Calcul |
|---|---|---|
| Nombre total d'étudiants | `etudiants` (COUNT) | Filtrés par année académique active |
| Nombre d'inscriptions | `inscriptions` (COUNT) | Par année académique active |
| Candidatures en attente | `candidature_soutenance` WHERE statut='En attente' | COUNT |
| Réclamations en cours | `reclamations` WHERE statut != résolu | COUNT |
| Répartition par niveau | `etudiants` JOIN `niveau_etude` | GROUP BY niveau |
| Répartition par genre | `etudiants` JOIN `genre` | GROUP BY genre |
| Évolution des effectifs | `etudiants` GROUP BY `promotion_etu` | Historique |
| Taux de paiement | `inscriptions.solde` | (montant_verser / montant_scolarite) × 100 |

**ACTIONS & BOUTONS :**
- Aucun bouton d'action directe (consultation uniquement)
- Widgets cliquables → redirection vers l'écran détaillé correspondant

**RÈGLES MÉTIER :**
- Les données sont filtrées par l'année académique active affichée en haut à droite
- Seuls les profils Administrateur, Secrétaire, Responsable scolarité y ont accès
- Rafraîchissement des données à chaque chargement de page

**ÉTAT DE SORTIE / FEEDBACK :** Aucun — page en lecture seule.

**LA ROUTE :** `?page=dashboard_scolarite`

**COMPORTEMENT DE LA PAGE :**
- Se charge avec les statistiques calculées côté serveur
- Les widgets sont des liens vers les écrans correspondants (ex: clic sur "Candidatures" → `?page=gestion_dossiers_candidatures`)
- Aucune saisie possible
- Responsive : widgets empilés verticalement sur mobile

**Critères de filtrage :** Année académique (automatique = active)

**Traçabilité :** Oui — log d'accès dans `pister` (action: "Accès", nom_table: "tableau_de_bord")

**Sécurité :** Accès restreint via `permissions` (peut_voir) sur `DASH_SCOLARITE`. Données non sensibles.

---

### 📝 ÉCRAN SCR-02 : Mise à jour Étudiant

**ID & TITRE :** `MAJ_ETUDIANT` — Mise à jour étudiant

**OBJECTIF :** Créer, modifier et gérer la fiche complète de chaque étudiant (identité, niveau, promotion, genre).

**VUE PRINCIPALE :** Segmentation polarisée (Formulaire statique en haut + Tableau en bas)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie (Pôle Supérieur) :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| N° Identification | Texte (varchar 25) | Oui | Vide |
| N° Carte étudiant | Texte (varchar 25) | Oui | Vide |
| Nom | Texte (varchar 50) | Oui | Vide |
| Prénom | Texte (varchar 100) | Oui | Vide |
| Email | Email (varchar 60) | Oui | Vide |
| Date de naissance | Date | Oui | Vide |
| Genre | Liste déroulante | Oui | — |
| Promotion | Texte (varchar 15) | Oui | Vide |
| Niveau | Liste déroulante | Non | NULL |
| Année académique | Liste déroulante | Non | Année active |

*Sources des listes déroulantes :*
- Genre → table `genre` (Masculin, Féminin, Neutre)
- Niveau → table `niveau_etude` (Master 1, Master 2)
- Année académique → table `annee_academique`

*Données affichées (Pôle Inférieur — Tableau) :*

| Colonne | Source |
|---|---|
| N° Carte | `etudiants.num_carte_etud` |
| Nom | `etudiants.nom_etu` |
| Prénom | `etudiants.prenom_etu` |
| Email | `etudiants.email_etu` |
| Niveau | JOIN `niveau_etude.lib_niv_etude` |
| Promotion | `etudiants.promotion_etu` |
| Actions | Boutons Modifier / Supprimer |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Enregistrer | Vert (#27ae60) | INSERT dans `etudiants` — crée un nouvel étudiant |
| Appliquer les modifications | Bleu (#3498db) | UPDATE dans `etudiants` — modifie l'étudiant sélectionné |
| Modifier (ligne tableau) | Bleu (#3498db) | Réinjection des données dans le pôle supérieur |
| Supprimer (ligne tableau) | Bleu (#2c3e50) | DELETE de l'étudiant (confirmation requise) |
| Rechercher | Bleu (#3498db) | Filtrage du tableau |
| Exporter | Bleu (#3498db) | Export CSV/Excel du tableau |
| Importer | Bleu (#3498db) | Import de masse depuis fichier |
| Imprimer | Bleu (#3498db) | Impression du tableau |

**RÈGLES MÉTIER :**
- Le `num_carte_etud` est la clé primaire, il doit être unique
- Le genre doit exister dans la table `genre`
- Une modification réinjecte les données dans le formulaire (philosophie zéro rupture)
- Import de masse : format CSV attendu avec colonnes correspondantes
- **Aucun compte utilisateur n'est créé ici** — cela passe par Gestion Utilisateurs

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : Message « Étudiant enregistré avec succès » (bandeau vert)
- Erreur : Message « Erreur : N° carte déjà existant » (bandeau rouge)
- Modification : Message « Modifications appliquées avec succès »

**LA ROUTE :** `?page=gestion_etudiants&action=ajouter_des_etudiants`

**COMPORTEMENT DE LA PAGE :**
- Le formulaire en haut est statique (ne défile pas)
- Le tableau en bas défile indépendamment
- Clic "Modifier" sur une ligne → les données remontent dans le formulaire (réinjection circulaire)
- Le bouton "Enregistrer" se transforme en "Appliquer les modifications" en mode édition
- Pagination avant le tableau (choix 10/25/50/100 lignes)
- Panneau latéral : détail complet de l'étudiant en clic

**Critères de filtrage :** Nom, Prénom, N° carte, Niveau, Promotion, Genre (recherche multi-critères, logique ET)

**Traçabilité :** Oui — chaque CREATE/UPDATE/DELETE génère un log dans `pister` (action: "Création"/"Modification", nom_table: "etudiants")

**Sécurité :** Accès via permissions `MAJ_ETUDIANT` (peut_voir, peut_creer, peut_modifier, peut_supprimer). Données personnelles (email, date naissance) masquées si utilisateur sans droit `peut_voir`.

---

### 💰 ÉCRAN SCR-03 : Inscription Étudiant (Paiements)

**ID & TITRE :** `INSCRIPTION_ETUDIANT` — Inscription étudiant (Paiements)

**OBJECTIF :** Gérer les inscriptions financières des étudiants : enregistrer les versements, suivre les échéances et la situation financière.

**VUE PRINCIPALE :** Segmentation polarisée (Formulaire + Tableau)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| Étudiant | Liste déroulante (auto-remplissage) | Oui | — |
| Année académique | Liste déroulante | Oui | Année active |
| N° Versement | Numérique | Oui | Auto-incrémenté |
| Date versement | Date/Heure | Oui | Date du jour |
| Montant versé | Numérique (int) | Oui | 0 |
| Mode de paiement | Liste déroulante | Oui | — |
| N° Pièce mode de paiement | Numérique | Oui | — |

*Auto-remplissage :* Quand on sélectionne un étudiant → Nom, Prénom, Niveau, Montant scolarité se remplissent automatiquement.

*Sources listes :*
- Étudiant → `etudiants` (num_carte_etud, nom_etu, prenom_etu)
- Mode de paiement → `mode_paiement` (Espèce, Virement, Chèque, Orange money, Wave, Mtn money, Moov money)
- Année académique → `annee_academique`

*Données affichées (Tableau) :*
- Historique des versements de l'étudiant sélectionné
- Colonnes : N° versement, Date, Montant, Mode paiement, Solde restant

*Calculs :*
- `solde = montant_scolarite - SUM(montant_verser)` pour l'inscription
- Statut échéance : "Payée" si montant couvert, "En retard" si date dépassée

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Enregistrer versement | Vert | INSERT dans `inscriptions` + calcul du solde |
| Modifier | Bleu | Réinjection dans le formulaire |
| Supprimer | Bleu | DELETE du versement |
| Imprimer reçu | Bleu | Génération PDF du reçu de paiement |

**RÈGLES MÉTIER :**
- Le montant_scolarite et montant_inscription viennent de `niveau_etude`
- Master 1 : scolarité = 975 000 FCFA, inscription = 450 000 FCFA
- Master 2 : scolarité = 1 025 000 FCFA, inscription = 450 000 FCFA
- Le solde ne peut pas être négatif (pas de trop-perçu accepté)
- Un étudiant ne peut s'inscrire qu'une fois par année académique

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Versement enregistré avec succès. Solde restant : XX FCFA »
- Erreur : « Montant supérieur au solde restant » / « Inscription déjà existante pour cette année »

**LA ROUTE :** `?page=gestion_scolarite`

**COMPORTEMENT DE LA PAGE :**
- Sélection de l'étudiant dans le menu déroulant → remplissage automatique de ses infos
- Le tableau affiche l'historique des versements + échéances
- Panneau latéral pour le détail complet de la situation financière
- Redirection possible depuis le dashboard scolarité

**Critères de filtrage :** Étudiant (recherche par nom/numéro), Année académique, Mode de paiement, Statut (payé/en retard)

**Traçabilité :** Oui — log dans `pister` pour chaque versement (action: "Création", nom_table: "inscriptions")

**Sécurité :** Données financières sensibles. Accès `INSCRIPTION_ETUDIANT`. Montants masqués pour les profils sans droit de lecture.

---

### 📊 ÉCRAN SCR-04 : Saisie des Moyennes

**ID & TITRE :** `MOYENNE_ETUDIANT` — Saisie des moyennes

**OBJECTIF :** Permettre la saisie et la gestion des moyennes M1 et M2 des étudiants par année académique.

**VUE PRINCIPALE :** Segmentation polarisée (Formulaire + Tableau)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| Étudiant | Liste déroulante (auto-remplissage) | Oui | — |
| Année académique | Liste déroulante | Oui | Année active |
| Moyenne M1 | Numérique décimal (4,2) | Oui | 0.00 |
| Moyenne M2 | Numérique décimal (4,2) | Oui | 0.00 |

*Données affichées :*
- Tableau des notes existantes : Étudiant, Année, Moyenne M1, Moyenne M2, Date création, Date modification

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Enregistrer | Vert | INSERT dans `notes` |
| Modifier | Bleu | UPDATE dans `notes` |
| Supprimer | Bleu | DELETE de la note |
| Exporter | Bleu | Export des notes en CSV/Excel |

**RÈGLES MÉTIER :**
- Les moyennes sont comprises entre 0.00 et 20.00
- Un seul enregistrement par étudiant et par année académique
- `date_modification` mise à jour automatiquement via ON UPDATE CURRENT_TIMESTAMP
- La moyenne M1 est requise pour pouvoir saisir la moyenne M2

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Notes enregistrées avec succès »
- Erreur : « Moyenne hors limites (0-20) » / « Notes déjà existantes pour cet étudiant cette année »

**LA ROUTE :** `?page=gestion_notes_evaluations`

**COMPORTEMENT DE LA PAGE :**
- Auto-remplissage lors de la sélection de l'étudiant
- Réinjection circulaire pour la modification
- Pagination et recherche dans le tableau

**Critères de filtrage :** Étudiant, Année académique, Plage de notes (logique ET)

**Traçabilité :** Oui — log dans `pister`

**Sécurité :** Accès `MOYENNE_ETUDIANT`. Notes accessibles en lecture seule pour les étudiants.

---

### 📁 ÉCRAN SCR-05 : Dossiers de Candidatures

**ID & TITRE :** `DOSSIER_CANDIDATURE` — Dossiers de candidatures

**OBJECTIF :** Gérer les dossiers de candidature à la soutenance soumis par les étudiants : consulter, valider ou rejeter administrativement.

**VUE PRINCIPALE :** Segmentation polarisée (Zone d'actions + Tableau des dossiers)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| N° Candidature | `candidature_soutenance.id_candidature` |
| Étudiant | JOIN `etudiants` (nom + prénom) |
| Date candidature | `candidature_soutenance.date_candidature` |
| Statut | `candidature_soutenance.statut_candidature` (En attente/Validée/Rejetée) |
| Commentaire admin | `candidature_soutenance.commentaire_admin` |
| Date traitement | `candidature_soutenance.date_traitement` |
| Rapport associé | Lien vers le rapport (téléchargement PDF) |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Valider | Vert | UPDATE statut → 'Validée', date_traitement = NOW() |
| Rejeter | Bleu (#2c3e50) | UPDATE statut → 'Rejetée' + commentaire obligatoire |
| Consulter rapport | Bleu | Ouverture du rapport en PDF |
| Télécharger PDF | Bleu | Téléchargement du fichier rapport |
| Détail | Bleu | Panneau latéral avec le dossier complet |

**RÈGLES MÉTIER :**
- Seuls les profils scolarité/admin peuvent valider/rejeter
- Le rejet nécessite obligatoirement un commentaire explicatif
- La validation vérifie que le rapport est déposé et complet
- Un dossier validé ne peut plus être rejeté et vice-versa (irréversible)
- Le résumé de candidature est stocké en JSON dans `resume_candidature`

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès validation : « Candidature validée avec succès » + notification à l'étudiant
- Succès rejet : « Candidature rejetée — motif enregistré »

**LA ROUTE :** `?page=gestion_dossiers_candidatures`

**COMPORTEMENT DE LA PAGE :**
- Le tableau liste tous les dossiers avec badges de statut colorés (En attente=orange, Validée=vert, Rejetée=rouge)
- Clic sur un dossier → panneau latéral avec détail complet
- Actions valider/rejeter disponibles depuis le panneau latéral
- Lien vers le rapport PDF de l'étudiant

**Critères de filtrage :** Statut (multi-sélection), Date de candidature (plage), Étudiant (recherche texte) — logique ET

**Traçabilité :** Oui — log dans `pister` pour chaque validation/rejet

**Sécurité :** Accès via `DOSSIER_CANDIDATURE`. Commentaires admin masqués pour les étudiants.

---

### ⚠️ ÉCRAN SCR-06 : Réclamations (Scolarité)

**ID & TITRE :** `RECLAMATION_ETUDIANT` — Réclamations (côté scolarité)

**OBJECTIF :** Permettre à la scolarité de consulter, prendre en charge, traiter et clôturer les réclamations soumises par les étudiants.

**VUE PRINCIPALE :** Segmentation polarisée (Zone d'actions + Tableau)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Données affichées :*

| Colonne | Source |
|---|---|
| N° Réclamation | `reclamations.id_reclamation` |
| Étudiant | JOIN `etudiants` |
| Objet | `reclamations.objet_reclamation` |
| Description | `reclamations.description_reclamation` |
| Statut | JOIN `statut_reclamation` |
| Date création | `reclamations.date_creation` |
| Dernière mise à jour | `reclamations.date_mise_a_jour` |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Prendre en charge | Bleu | UPDATE statut → "En cours de traitement" |
| Terminer | Vert | UPDATE statut → "Traité" |
| Rejeter | Bleu (#2c3e50) | UPDATE statut → "Rejeté" |
| Détail | Bleu | Panneau latéral avec l'historique complet |
| Changer statut | Bleu | Modification rapide du statut |

**RÈGLES MÉTIER :**
- Le workflow du statut suit : Soumise → En cours → Traité/Rejeté
- Impossible de revenir en arrière dans le workflow
- Le traitement doit être accompagné d'une réponse/commentaire

**ÉTAT DE SORTIE / FEEDBACK :**
- Notification à l'étudiant lors du changement de statut

**LA ROUTE :** `?page=gestion_reclamations_scolarite`

**COMPORTEMENT DE LA PAGE :**
- Badges colorés pour chaque statut
- Panneau latéral pour le détail + historique des changements
- Connexion vers l'écran Réclamations étudiant (même données, vue différente)

**Critères de filtrage :** Statut (multi-sélection), Date (plage), Étudiant — logique ET

**Traçabilité :** Oui — `pister`

**Sécurité :** Accès `RECLAMATION_ETUDIANT`. Description masquée en liste (visible uniquement dans le détail).

---

## ═══════════════════════════════════════════
## MENU : ESPACE ÉTUDIANT (ETUDIANT_ENV)
## ═══════════════════════════════════════════

---

### 📋 ÉCRAN ETU-01 : Candidature

**ID & TITRE :** `ETU_CANDIDATURE` — Candidature à la soutenance

**OBJECTIF :** Permettre à l'étudiant de soumettre sa candidature à la soutenance après avoir complété toutes les conditions requises.

**VUE PRINCIPALE :** Formulaire multi-étapes (Wizard)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| (Auto-rempli) Nom | Texte (lecture seule) | — | Session étudiant |
| (Auto-rempli) Prénom | Texte (lecture seule) | — | Session étudiant |
| (Auto-rempli) N° carte | Texte (lecture seule) | — | Session étudiant |
| Confirmation soumission | Checkbox | Oui | Non coché |

*Données affichées :*
- Résumé de la situation de l'étudiant (notes, inscriptions, rapport)
- Statut actuel de la candidature si déjà soumise
- Historique des candidatures précédentes

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Soumettre ma candidature | Vert | INSERT dans `candidature_soutenance` avec statut 'En attente' |

**RÈGLES MÉTIER :**
- L'étudiant doit être inscrit pour l'année académique active
- Une seule candidature par étudiant (pas de doublon)
- Si candidature déjà validée ou en attente → bouton grisé
- Si candidature rejetée → possibilité de re-soumettre

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Votre candidature a été soumise avec succès. Elle est en attente de validation. »
- Erreur : « Vous avez déjà une candidature en cours. »

**LA ROUTE :** `?page=candidature_soutenance`

**COMPORTEMENT DE LA PAGE :**
- Affiche le statut actuel de la candidature (badge coloré)
- Si "Validée" → message de confirmation + lien vers "Mes rapports"
- Si "Rejetée" → affiche le commentaire admin + possibilité de re-candidater
- Connectée à l'écran "Mes rapports" (accès bloqué si candidature non validée)

**Critères de filtrage :** Aucun (vue personnelle)

**Traçabilité :** Oui

**Sécurité :** Accès `ETU_CANDIDATURE`. L'étudiant ne voit que SA candidature.

---

### 📄 ÉCRAN ETU-02 : Mes Rapports

**ID & TITRE :** `ETUD_RAPPORT` — Mes rapports

**OBJECTIF :** Permettre à l'étudiant de créer, déposer, suivre et gérer ses rapports de stage/mémoire.

**VUE PRINCIPALE :** Segmentation polarisée (Formulaire de dépôt + Liste des rapports)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie (Création rapport) :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| Thème du rapport | Texte (varchar 150) | Oui | Vide |
| Fichier (contenu) | Upload fichier / Éditeur | Oui | Vide |

*Données affichées :*

| Colonne | Source |
|---|---|
| Thème | `rapport_etudiants.theme_rapport` |
| Date rédaction | `rapport_etudiants.date_redaction_rapport` |
| Statut | `rapport_etudiants.statut_rapport` (en_cours/valider/rejeter/en_attente) |
| Version | `rapport_etudiants.version` |
| Taille fichier | `rapport_etudiants.taille_fichier` |
| Commentaires | Depuis `evaluations_rapports` |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Déposer rapport | Vert | INSERT dans `rapport_etudiants` + `deposer` |
| Supprimer | Bleu | DELETE (si statut = en_cours uniquement) |
| Consulter commentaires | Bleu | Affiche les retours des évaluateurs |
| Exporter | Bleu | Export de la liste des rapports |

**RÈGLES MÉTIER :**
- **PRÉREQUIS** : la candidature à la soutenance doit être validée (statut = 'Validée')
- Si candidature non validée → page bloquée avec message « Accès bloqué tant que votre candidature n'est pas validée »
- Un rapport ne peut être supprimé que s'il est en statut "en_cours"
- Un rapport validé ou rejeté ne peut pas être modifié
- La version s'incrémente automatiquement à chaque nouvelle soumission

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Rapport déposé avec succès (version N) »
- Erreur : « Accès bloqué — candidature non validée »

**LA ROUTE :** `?page=gestion_rapports`

**COMPORTEMENT DE LA PAGE :**
- Vérification du statut candidature AVANT le chargement de la page
- Si bloqué → message d'erreur avec icône cadenas
- Formulaire de création avec éditeur de texte riche
- Suivi de l'avancement : `?page=gestion_rapports&action=suivi_rapport`
- Commentaires consultables : `?page=gestion_rapports&action=commentaire_rapport`

**Critères de filtrage :** Statut du rapport, Date — logique ET

**Traçabilité :** Oui

**Sécurité :** Accès `ETUD_RAPPORT`. L'étudiant ne voit que SES rapports.

---

### ⚠️ ÉCRAN ETU-03 : Réclamations (Étudiant)

**ID & TITRE :** `ETU_RECLAMATION` — Réclamations

**OBJECTIF :** Permettre à l'étudiant de soumettre des réclamations, suivre leur état et consulter l'historique.

**VUE PRINCIPALE :** Segmentation polarisée (Formulaire de soumission + Historique)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| Objet | Texte (varchar 150) | Oui | Vide |
| Description | Textarea (text) | Oui | Vide |

*Données affichées :*
- Liste des réclamations de l'étudiant avec statuts
- Historique des mouvements par réclamation

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Soumettre réclamation | Vert | INSERT dans `reclamations` |
| Suivi/Historique | Bleu | Vue détaillée du suivi |
| Exporter | Bleu | Export des réclamations |

**RÈGLES MÉTIER :**
- L'étudiant ne peut voir que ses propres réclamations
- Le statut initial est automatiquement "Soumise"
- L'étudiant ne peut pas modifier le statut

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Réclamation soumise avec succès. Référence : REC-XXX »

**LA ROUTE :** `?page=gestion_reclamations`

**COMPORTEMENT DE LA PAGE :**
- Formulaire en haut pour soumettre une nouvelle réclamation
- Tableau en bas avec l'historique de ses réclamations
- Panneau latéral pour le détail d'une réclamation
- Badges de statut colorés (Soumise=bleu, En cours=orange, Traité=vert, Rejeté=rouge)

**Critères de filtrage :** Statut, Date — logique ET

**Traçabilité :** Oui

**Sécurité :** Accès `ETU_RECLAMATION`. Vue limitée aux données personnelles.

---

### 📰 ÉCRAN ETU-04 : Consultation du Compte Rendu

**ID & TITRE :** `ETU_CONSULTATION_CR` — Consultation du compte rendu

**OBJECTIF :** Permettre à l'étudiant de consulter le compte rendu de sa soutenance/évaluation rédigé par la commission.

**VUE PRINCIPALE :** Vue de consultation (lecture seule)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Données affichées :*

| Donnée | Source |
|---|---|
| Nom du CR | `compte_rendu.nom_CR` |
| Contenu | `compte_rendu.contenu_CR` |
| Date | `compte_rendu.date_CR` |
| Fichier PDF | `compte_rendu.chemin_fichier_pdf` |
| Rapports associés | JOIN `compte_rendu_rapport` → `rapport_etudiants` |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Télécharger PDF | Bleu | Téléchargement du fichier PDF du compte rendu |

**RÈGLES MÉTIER :**
- L'étudiant ne voit que le CR qui le concerne (filtré par `num_etu`)
- Aucune modification possible (lecture seule)
- Le CR n'apparaît que s'il a été publié par la commission

**ÉTAT DE SORTIE / FEEDBACK :** Aucun — lecture seule.

**LA ROUTE :** `?page=consultation_cr_etud`

**COMPORTEMENT DE LA PAGE :**
- Affichage du contenu du CR en HTML
- Bouton télécharger si un PDF existe
- Si aucun CR disponible → message « Aucun compte rendu disponible pour le moment »

**Critères de filtrage :** Aucun (vue personnelle)

**Traçabilité :** Non

**Sécurité :** Accès `ETU_CONSULTATION_CR` (peut_voir uniquement). L'étudiant ne voit que son CR.
