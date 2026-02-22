# 1. GESTION DE LA SCOLARITÉ — Descriptions des Écrans

---

## 1.1 Écran : DASH_SCOLARITE — Tableau de bord scolarité

**ID & TITRE :** `DASH_SCOLARITE` — Tableau de bord scolarité

**OBJECTIF :** Offrir une vue synthétique et en temps réel de l'activité de la scolarité (nombre d'étudiants, inscriptions en cours, moyennes saisies, candidatures en attente) pour piloter efficacement le secrétariat pédagogique.

**VUE PRINCIPALE :** Dashboard (widgets statistiques + graphiques).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Donnée affichée | Source | Calcul |
|---|---|---|
| Nombre total d'étudiants (année active) | `etudiants` filtré par `id_annee_acad` active | COUNT |
| Nombre d'inscriptions en cours | `inscriptions` WHERE `statut_inscription = 'En cours'` | COUNT |
| Montant total perçu | `inscriptions` SUM(`montant_paye`) | SUM |
| Reste à payer global | `inscriptions` SUM(`reste_a_payer`) | SUM |
| Candidatures en attente | `candidature_soutenance` WHERE `statut_candidature = 'En attente'` | COUNT |
| Réclamations non traitées | `reclamations` WHERE statut = non résolu | COUNT |
| Répartition étudiants par niveau | `etudiants` JOIN `niveau_etude` | GROUP BY niveau |
| Répartition par genre | `etudiants` JOIN `genre` | GROUP BY genre |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Voir les étudiants | Bleu `#3498db` | Redirige vers `?page=gestion_etudiants&action=ajouter_des_etudiants` |
| Voir les inscriptions | Bleu `#3498db` | Redirige vers `?page=gestion_scolarite` |
| Voir les candidatures | Bleu `#3498db` | Redirige vers `?page=gestion_dossiers_candidatures` |

**RÈGLES MÉTIER :**
- Les statistiques sont filtrées par l'année académique active (affichée en haut à droite, lecture seule).
- Seuls les utilisateurs ayant la permission `peut_voir` sur `DASH_SCOLARITE` y accèdent.
- Les widgets se rafraîchissent à chaque chargement de page.

**ÉTAT DE SORTIE / FEEDBACK :** Aucune action de modification. Page en lecture seule.

**LA ROUTE :** `?page=dashboard_scolarite`

**COMPORTEMENT DE LA PAGE :**
- Au chargement, le système récupère les statistiques liées à l'année académique active.
- Le dashboard ne suit PAS la segmentation polarisée (pas de formulaire CRUD). Structure libre : grille de widgets + graphiques.
- Liens de navigation rapide vers les sous-écrans de la scolarité.
- Sur mobile : widgets empilés verticalement, graphiques redimensionnés.

**Critères de filtrage :** Année académique (implicite, année active).

**Traçabilité :** Connexion logguée dans `pister` (action = "Connexion").

**Sécurité :** Accès conditionné par permissions du groupe utilisateur. Données agrégées, pas de données nominatives sensibles exposées.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────┐
│                     TABLEAU DE BORD SCOLARITÉ        [Année: 2025-2026]│
├──────────────────────────────────────────────────────────────────────────┤
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐    │
│ │ 📊 ÉTUDIANTS │ │ 📋 INSCRITS  │ │ 💰 PERÇU     │ │ 📂 CANDID.   │    │
│ │     342      │ │     289      │ │  28.5M FCFA  │ │    12 att.   │    │
│ │  [Voir ▸]    │ │  [Voir ▸]    │ │  [Détails ▸] │ │  [Voir ▸]    │    │
│ └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘    │
│                                                                        │
│ ┌────────────────────────────────┐ ┌────────────────────────────────┐  │
│ │  📈 Répartition par Niveau     │ │  📈 Répartition par Genre      │  │
│ │                                │ │                                │  │
│ │  Master 1 ████████████ 180     │ │  Masculin  ████████████ 220    │  │
│ │  Master 2 ████████     162     │ │  Féminin   ██████       112    │  │
│ │                                │ │  Neutre    █              10    │  │
│ └────────────────────────────────┘ └────────────────────────────────┘  │
│                                                                        │
│ ┌────────────────────────────────────────────────────────────────────┐ │
│ │  ⚠️ ALERTES                                                       │ │
│ │  • 3 réclamations non traitées                                    │ │
│ │  • 5 étudiants avec reste à payer > 500 000 FCFA                  │ │
│ └────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 1.2 Écran : MAJ_ETUDIANT — Mise à jour étudiant

**ID & TITRE :** `MAJ_ETUDIANT` — Mise à jour étudiant

**OBJECTIF :** Permettre la création, la modification et la suppression des fiches étudiants dans le référentiel, avec gestion de la pagination et de l'export.

**VUE PRINCIPALE :** Segmentation Polarisée (Formulaire en haut + Tableau en bas).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie (Pôle Supérieur) :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Année Académique | Liste déroulante (`select`) | Non | — | Année en cours (auto-détectée) |
| Identifiant MESRS | Texte (`input text`) | Non | 25 | Vide |
| N° Étudiant | Texte (`input text`) | **Oui** ★ | 25 | Vide |
| Nom | Texte (`input text`) | **Oui** ★ | 50 | Vide |
| Prénom | Texte (`input text`) | **Oui** ★ | 100 | Vide |
| Date de Naissance | Date (`input date`) | **Oui** ★ | — | Vide |
| Genre | Liste déroulante (`select`) | **Oui** ★ | — | "Sélectionner" |
| Niveau | Liste déroulante (`select`) | Non | — | "Sélectionner un niveau" |
| Promotion | Liste déroulante (`select`) | **Oui** ★ | — | Année en cours |
| Email | Email (`input email`) | **Oui** ★ | 60 | Vide |

*Données affichées (Pôle Inférieur — Tableau) :*

| Colonne | Source |
|---|---|
| ☑ (checkbox) | — |
| Identifiant MESRS | `etudiants.num_ident_etud` |
| N° Carte Étudiant | `etudiants.num_carte_etud` |
| Nom | `etudiants.nom_etu` |
| Prénom | `etudiants.prenom_etu` |
| Date Nais. | `etudiants.date_naiss_etu` |
| Genre | `genre.libelle_genre` (JOIN) |
| Email | `etudiants.email_etu` |
| Promotion | `etudiants.promotion_etu` |
| Actions | Boutons Modifier / Supprimer |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Enregistrer | Vert dégradé `#22c55e → #16a34a` | POST → crée un étudiant en BDD. Affiche notification succès. |
| Modifier (en mode édition) | Vert dégradé | POST → met à jour l'étudiant. Le bouton "Enregistrer" devient "Modifier". |
| Réinitialiser | Gris/Blanc bordure | Reset du formulaire (vidage des champs). |
| Annuler (mode édition) | Gris/Blanc bordure | Redirige vers la page sans étudiant en édition. |
| ✏️ Modifier (action tableau) | Bleu `#2563eb` (icône) | Réinjection circulaire : les données de la ligne remontent dans le pôle supérieur. |
| 🗑️ Supprimer (action tableau) | Rouge `#dc2626` (icône) | Confirmation `confirm()` JS puis POST suppression. |
| Sélectionner tout | Indigo `#6366f1` | Coche toutes les checkboxes visibles. |
| Désélectionner tout | Gris `#6b7280` | Décoche toutes les checkboxes. |
| Supprimer (N sélectionnés) | Rouge `#ef4444` | Suppression de masse avec confirmation. |
| Imprimer | Bleu `#3b82f6` | Ouvre une fenêtre d'impression avec le tableau formaté. |
| Exporter | Orange `#f97316` | Télécharge un fichier CSV de la liste filtrée. |

**RÈGLES MÉTIER :**
- Le `num_carte_etud` (N° Étudiant) est la clé primaire. Il est unique et ne peut être dupliqué.
- En mode modification, le `old_num_etu` est conservé en `input hidden` pour permettre le changement du numéro.
- Le genre provient de la table `genre` (1=Masculin, 2=Féminin, 3=Neutre).
- La promotion est liée aux années académiques existantes.
- Le niveau provient de la table `niveau_etude` (Master 1, Master 2).
- Année académique active : l'année courante est pré-sélectionnée automatiquement (comparaison `date_deb` / `date_fin`).
- **Auto-remplissage** : Non applicable ici (création manuelle).
- La recherche est côté client (JS) : filtre sur nom, prénom, N° étudiant.
- Pagination côté serveur : paramètres `p` (page), `limit` (items par page).
- Protection CSRF via token en champ hidden.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès ajout/modification : notification verte glissante en haut à droite, auto-disparition après 5s.
- Erreur : notification rouge glissante en haut à droite, auto-disparition après 5s.
- Après suppression : rechargement de la page avec notification de succès.

**LA ROUTE :** `?page=gestion_etudiants&action=ajouter_des_etudiants`

**COMPORTEMENT DE LA PAGE :**
- **Chargement initial** : formulaire vide (mode ajout), tableau paginé des étudiants de l'année active.
- **Clic sur Modifier (tableau)** : redirection vers `?page=gestion_etudiants&action=ajouter_des_etudiants&num_etu=XXXX`. Le formulaire se pré-remplit avec les données de l'étudiant sélectionné. Le bouton passe de "Enregistrer" à "Modifier" et un bouton "Annuler" apparaît.
- **Clic sur Supprimer** : `confirm()` puis soumission POST avec `selected_ids[]`.
- **Recherche** : filtrage JS en temps réel dans le tableau.
- **Pagination** : liens `Précédent`, numéros de page, `Suivant`. Le select "Afficher" permet de changer le nombre d'items par page (2, 5, 10, 25, 50, 100).
- **Connexion avec autres pages** : Aucune navigation directe vers d'autres écrans depuis cet écran. Les étudiants créés ici sont référencés dans Inscription, Candidature, Rapport, etc.
- **Responsive** : En mobile, le formulaire occupe toute la largeur, les champs se mettent en colonne. Le tableau défile horizontalement.

**Critères de filtrage :**
- Recherche textuelle (nom, prénom, numéro) — logique OU.
- Pagination (items par page).

**Traçabilité :** Chaque création/modification/suppression d'étudiant génère une entrée dans `pister` (action = "Création"/"Modification"/"Suppression", nom_table = "etudiants").

**Sécurité :**
- Accessible si `canEdit()` retourne `true` (vérification des permissions via `permissions_helper.php`).
- Les colonnes Actions et les checkboxes ne sont visibles que si `canEdit() === true`.
- Protection CSRF obligatoire sur le formulaire.
- Données d'email non masquées (pas de données RGPD sensibles dans ce contexte universitaire ivoirien).

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│                                              [Année Académique: ▼ 2025-2026]│
│ PÔLE SUPÉRIEUR (STATIQUE)                                                   │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Identifiant MESRS   N° Étudiant *    Nom *            Prénom *        │   │
│ │ [_______________]   [___________]    [____________]   [____________]  │   │
│ │                                                                       │   │
│ │ Date Naissance *    Genre *          Niveau           Promotion *     │   │
│ │ [____/____/____]    [▼ Sélect.]      [▼ Sélect.]     [▼ 2025-2026]   │   │
│ │                                                                       │   │
│ │ Email *                                                               │   │
│ │ [________________________@_______]                                    │   │
│ │                                                                       │   │
│ │                                      [Réinitialiser]  [✔ Enregistrer] │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ PÔLE INFÉRIEUR (DÉFILANT)                                                    │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │          LISTE DES ÉTUDIANTS                                          │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ Afficher: [▼10]  [🔍 Rechercher un étudiant...]                       │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)] [🖨 Impr.] [📤 Exp.]│   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐ │ID MESRS │N° Etud.│ Nom    │Prénom  │Dt Nais.│Genre│Email  │Promo│Act│
│ │───┼─────────┼────────┼────────┼────────┼────────┼─────┼───────┼─────┼───│
│ │ ☐ │CI01..   │CI01..  │Karamoko│Ibrahim │01/01/96│Masc.│k@g.com│16-17│✏🗑│
│ │ ☐ │CI01..   │CI01..  │Doumun  │Solange │15/03/97│Fém. │d@g.com│04-05│✏🗑│
│ │ ☐ │CI01..   │CI01..  │Ebe     │Alex    │22/08/95│Masc.│e@g.com│14-15│✏🗑│
│ │ ...                                                                   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Affichage de 1 à 10 sur 342 entrées            [◀ Préc.][1][2]...[▶] │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 1.3 Écran : INSCRIPTION_ETUDIANT — Inscription étudiant

**ID & TITRE :** `INSCRIPTION_ETUDIANT` — Inscription étudiant

**OBJECTIF :** Gérer les inscriptions et les paiements de scolarité des étudiants (enregistrement des versements, suivi du solde, gestion des tranches).

**VUE PRINCIPALE :** Segmentation Polarisée (Formulaire en haut + Tableau en bas).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie (Pôle Supérieur) :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Année Académique | Select (lecture seule) | — | — | Année active |
| Niveau | Select (`niveau_etude`) | **Oui** ★ | — | "Sélectionner" |
| Frais Scolarité FCFA | Texte (lecture seule, auto-rempli) | — | — | Selon niveau |
| Nom Prénom | Select déroulant (recherche étudiant) | **Oui** ★ | — | "Sélectionner" |
| Identifiant | Texte (lecture seule, auto-rempli) | — | 15 | Auto-rempli |
| N° Carte Étudiant | Texte (lecture seule, auto-rempli) | — | 15 | Auto-rempli |
| N° Versement | Texte (lecture seule) | — | 2 | Auto-incrémenté |
| Date Versement | Date | **Oui** ★ | 10 | Date du jour |
| Montant Versé | Nombre | **Oui** ★ | 7 | Vide |
| Reste à Payer | Texte (lecture seule, calculé) | — | 7 | Calculé |
| Mode Paiement | Select (`mode_paiement`) | **Oui** ★ | — | "Sélectionner" |
| N° Moyen de Paiement | Texte | Non | 15 | Vide |

*Données affichées (Pôle Inférieur — Tableau) :*

| Colonne | Source |
|---|---|
| N° Étud. | `etudiants.num_carte_etud` |
| Nom & Prénom | `etudiants.nom_etu` + `prenom_etu` |
| N° Vers. | `inscriptions.num_versement` |
| Date Verse. | `inscriptions.date_versement` |
| Mnt versé | `inscriptions.montant_verser` |
| Mode paie. | `inscriptions.methode_paiement` |
| N° M.P | `inscriptions.num_piece_mp` |
| Actions | Modifier / Supprimer |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Valider | Vert | POST → enregistre l'inscription/versement |
| Rechercher | Bleu | Filtre le tableau |
| Exporter | Bleu | Export CSV/Excel |
| Imprimer | Bleu | Impression du tableau |

**RÈGLES MÉTIER :**
- **Auto-remplissage obligatoire** : Quand l'utilisateur sélectionne un étudiant dans le menu déroulant "Nom Prénom", les champs Identifiant et N° Carte Étudiant se remplissent automatiquement.
- Quand un niveau est sélectionné, le montant de scolarité (`montant_scolarite` de `niveau_etude`) s'affiche automatiquement.
- Le "Reste à payer" = Frais scolarité − Somme des montants déjà versés.
- Le `num_versement` s'incrémente automatiquement à chaque nouveau versement pour le même étudiant/année.
- Un étudiant ne peut pas payer plus que le reste à payer.
- Les modes de paiement sont : Espèce, Virement, Chèque, Orange money, Wave, MTN money, Moov money.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : notification verte "Versement enregistré avec succès".
- Erreur (montant supérieur au reste) : notification rouge.

**LA ROUTE :** `?page=gestion_scolarite`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée : formulaire statique en haut, tableau défilant des inscriptions en bas.
- Sélection de l'étudiant via menu déroulant → auto-remplissage des champs liés.
- Sélection du niveau → auto-remplissage du montant de scolarité.
- En mode modification : les données de la ligne sélectionnée remontent au pôle supérieur (réinjection circulaire).
- Connexion avec `MAJ_ETUDIANT` : les étudiants disponibles dans le select proviennent de cette table.

**Critères de filtrage :** Recherche par nom/numéro étudiant, filtrage par année académique.

**Traçabilité :** Oui, chaque inscription/versement est logué dans `pister`.

**Sécurité :** Accès conditionné par permissions. Montants financiers visibles uniquement pour les utilisateurs autorisés.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ INSCRIPTION ÉTUDIANT                                    [Année A.: ▼ S(9)]  │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Niveau                               Frais Scolarité FCFA            │   │
│ │ [▼ Master 2 S(7)]                    [    975 000     ] (auto)       │   │
│ │                                                                       │   │
│ │ Nom Prénom           Identifiant         N° Carte Etudiant           │   │
│ │ [▼ ______________]   [_________] (auto)  [_________] (auto)          │   │
│ │                                                                       │   │
│ │ N° Vers.  Date Vers.   Mnt Versé   Reste à Payer  Mode Paie.  N° M.P│   │
│ │ [ S(2) ]  [__/__/____] [ S(7)   ]  [  S(7)  ]     [▼ E(15)]  [E(15)]│   │
│ │                                                                       │   │
│ │                                                         [✔ VALIDER]   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │         LISTE DES ENREGISTREMENTS                                     │   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [� Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│N° Etud │Nom & Prénom│N° Vers│Date Verse.│Mnt versé│Mode p.│N° M.P│Act│  │
│ │──┼────────┼────────────┼───────┼───────────┼─────────┼───────┼──────┼───│  │
│ │ ☐│CI01... │Konan Y.F.  │  1    │19/02/2026 │300 000  │Espèce │T-001 │✏🗑│  │
│ │ ☐│CI01... │Konan Y.F.  │  2    │19/02/2026 │200 000  │Virem. │V-002 │✏🗑│  │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur 3 entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 1.4 Écran : MOYENNE_ETUDIANT — Saisie des moyennes

**ID & TITRE :** `MOYENNE_ETUDIANT` — Saisie des moyennes

**OBJECTIF :** Permettre la saisie des moyennes M1 et M2 des étudiants par année académique pour le suivi académique.

**VUE PRINCIPALE :** Segmentation Polarisée (Formulaire + Tableau).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie (Pôle Supérieur) :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Année Académique | Select (lecture seule) | — | — | Année active |
| Étudiant | Select déroulant (recherche) | **Oui** ★ | — | "Sélectionner" |
| N° Carte (auto-rempli) | Texte (lecture seule) | — | 25 | Auto-rempli |
| Nom (auto-rempli) | Texte (lecture seule) | — | 50 | Auto-rempli |
| Prénom (auto-rempli) | Texte (lecture seule) | — | 100 | Auto-rempli |
| Moyenne M1 | Nombre décimal (0-20) | **Oui** ★ | 4,2 | Vide |
| Moyenne M2 | Nombre décimal (0-20) | **Oui** ★ | 4,2 | Vide |

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| N° Étudiant | `etudiants.num_carte_etud` |
| Nom | `etudiants.nom_etu` |
| Prénom | `etudiants.prenom_etu` |
| Moyenne M1 | `notes.moyenne_M1` |
| Moyenne M2 | `notes.moyenne_M2` |
| Date saisie | `notes.date_creation` |
| Actions | Modifier / Supprimer |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Valider | Vert | POST → enregistre les moyennes en BDD (`notes`) |
| Rechercher | Bleu | Filtre le tableau |
| Exporter | Bleu | Export CSV |
| Imprimer | Bleu | Impression |

**RÈGLES MÉTIER :**
- **Auto-remplissage** : sélection de l'étudiant → remplissage auto de N°, Nom, Prénom.
- Les moyennes doivent être comprises entre 0 et 20 (décimal 4,2).
- Un étudiant ne peut avoir qu'une seule entrée de notes par année académique.
- La date de création et de modification sont gérées automatiquement.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : "Moyennes enregistrées avec succès" (notification verte).
- Erreur doublon : "Les moyennes existent déjà pour cet étudiant cette année" (notification rouge).

**LA ROUTE :** `?page=gestion_notes_evaluations`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée standard.
- Auto-remplissage à la sélection de l'étudiant.
- Réinjection circulaire pour la modification (clic sur ✏️ → données remontent).
- Table `notes` liée à `etudiants` via `num_etu` et à `annee_academique` via `id_annee_acad`.

**Critères de filtrage :** Recherche par nom/numéro étudiant, année académique.

**Traçabilité :** Oui, logué dans `pister`.

**Sécurité :** Accès conditionné par permissions. Seuls les utilisateurs avec `peut_creer` / `peut_modifier` sur cet écran.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ SAISIE DES MOYENNES                                     [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Étudiant                 N° Carte (auto)    Nom (auto)   Prénom(auto)│   │
│ │ [▼ Sélectionner..]       [____________]     [_________]  [_________] │   │
│ │                                                                       │   │
│ │ Moyenne M1               Moyenne M2                                   │   │
│ │ [______/20]              [______/20]                                   │   │
│ │                                                         [✔ VALIDER]   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [� Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│N° Etud.│  Nom     │ Prénom   │Moy. M1│Moy. M2│Date saisie │Actions│   │
│ │──┼────────┼──────────┼─────────┼───────┼───────┼────────────┼───────│   │
│ │ ☐│CI01... │ Konan    │ Y. Franck│ 14.50 │ 15.75 │ 20/02/2026 │ ✏️ 🗑 │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 1.5 Écran : DOSSIER_CANDIDATURE — Dossiers de candidatures

**ID & TITRE :** `DOSSIER_CANDIDATURE` — Dossiers de candidatures

**OBJECTIF :** Permettre à la scolarité de consulter, traiter (valider/rejeter) les candidatures de soutenance déposées par les étudiants, en affichant également les **informations de scolarité** (inscription, paiement) et les **moyennes** de chaque étudiant pour une décision éclairée.

**VUE PRINCIPALE :** Segmentation Polarisée (Zone de traitement en haut + Tableau des dossiers en bas).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie (Pôle Supérieur) :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Étudiant | Select (auto-rempli) | — | — | — |
| Date candidature | Date (lecture seule) | — | — | Auto |
| Statut | Select ("Validée", "Rejetée") | **Oui** ★ | — | "En attente" |
| Commentaire Admin | Textarea | Non | 500 | Vide |

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| N° Candidature | `candidature_soutenance.id_candidature` |
| N° Étudiant | `etudiants.num_carte_etud` |
| Nom & Prénom | `etudiants.nom_etu` + `prenom_etu` |
| Niveau | `niveau_etude.libelle_niveau` (JOIN via `etudiants`) |
| Moy. M1 | `notes.moyenne_M1` (via `num_etu` + année active) |
| Moy. M2 | `notes.moyenne_M2` (via `num_etu` + année active) |
| Montant scolarité | `inscriptions.montant_scolarite` (dernier versement) |
| Montant versé | SUM(`inscriptions.montant_verser`) |
| Reste à payer | Calculé (scolarité − total versé) |
| Statut paiement | Badge (🟢Soldé / 🟠Partiel / 🔴Impayé) |
| Date candidature | `candidature_soutenance.date_candidature` |
| Statut candidature | `candidature_soutenance.statut_candidature` (badge coloré) |
| Admin traitant | `personnel_admin.nom_pers_admin` |
| Date traitement | `candidature_soutenance.date_traitement` |
| Actions | Voir détails / Traiter |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Valider candidature | Vert | Change statut → "Validée", enregistre date_traitement et id_pers_admin |
| Rejeter candidature | Rouge (alerte, pas bouton) | Change statut → "Rejetée" avec commentaire obligatoire |
| Rechercher | Bleu | Filtre le tableau |
| Exporter | Bleu | Export CSV |
| Imprimer | Bleu | Impression |

**RÈGLES MÉTIER :**
- Seul le personnel administratif autorisé peut traiter les candidatures.
- Une candidature rejetée doit obligatoirement avoir un commentaire.
- La date de traitement est automatiquement renseignée.
- L'`id_pers_admin` du traitant est automatiquement enregistré.
- Statuts candidature possibles : "En attente" (orange badge), "Validée" (vert badge), "Rejetée" (rouge badge).
- **Affichage scolarité** : pour chaque candidature, le système affiche les informations de scolarité (montant versé, reste à payer) depuis `inscriptions`.
- **Affichage moyennes** : les moyennes M1 et M2 sont affichées depuis `notes` pour l'année en cours.
- **Statut paiement** : 🟢Soldé (reste=0), 🟠Partiel (reste>0 mais versements existants), 🔴Impayé (aucun versement).
- La scolarité peut utiliser ces informations pour prendre une décision éclairée sur la candidature.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès validation : "Candidature validée avec succès" (vert).
- Succès rejet : "Candidature rejetée" (orange).

**LA ROUTE :** `?page=gestion_dossiers_candidatures`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée avec zone de traitement en haut.
- Le panneau latéral (Triangulation de la donnée) peut surgir pour afficher le détail complet du dossier : résumé de candidature (`resume_candidature`), pièces jointes, historique.
- Clic sur une ligne → le panneau latéral s'ouvre avec les détails.
- Clic sur "Traiter" → la zone de traitement (pôle supérieur) se pré-remplit.
- Connexion avec `ETU_CANDIDATURE` : les candidatures viennent de l'espace étudiant.

**Critères de filtrage :** Statut (multi-select : En attente, Validée, Rejetée), recherche par nom/numéro, date.

**Traçabilité :** Oui, chaque validation/rejet est logué.

**Sécurité :** Accès restreint au personnel de scolarité autorisé.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ DOSSIERS DE CANDIDATURES                                [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Étudiant: [auto-rempli]        Date candidature: [auto]              │   │
│ │ Statut: [▼ En attente / Validée / Rejetée]                           │   │
│ │ Commentaire: [__________________________________________________]    │   │
│ │                                                         [✔ VALIDER]   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│N°C│N°Étud│Nom & Prénom│Niv│M1  │M2  │Versé   │Reste  │St.Paie│    │   │
│ │  │   │      │            │   │    │    │        │       │       │    │   │
│ │  │   │      │            │DtCand│St.Cand.│Admin │Dt Trait│Actions│   │   │
│ │──┼───┼──────┼────────────┼───┼────┼────┼────────┼───────┼───────┼────│   │
│ │ ☐│ 1 │CI01..│Konan Y.F.  │M2 │14.5│15.0│975 000 │     0 │🟢Soldé│    │   │
│ │  │   │      │            │10/01│🟠Att. │  —   │  —     │ 👁 ✏  │    │   │
│ │──┼───┼──────┼────────────┼───┼────┼────┼────────┼───────┼───────┼────│   │
│ │ ☐│ 2 │CI01..│Traoré F.   │M2 │12.0│13.5│675 000 │300 000│🟠Part.│    │   │
│ │  │   │      │            │15/01│🟢Valid │Brou K│20/01  │ 👁    │    │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
│                                                                              │
│ ┌──────────────────────────────┐ (Panneau latéral, caché par défaut)        │
│ │ DÉTAIL DU DOSSIER           │                                              │
│ │ N° Candidature: 1           │                                              │
│ │ Étudiant: Konan Yao Franck  │                                              │
│ │ Niveau: Master 2            │                                              │
│ │                              │                                              │
│ │ ── SCOLARITÉ ────────────── │                                              │
│ │ Scolarité: 975 000 FCFA     │                                              │
│ │ Total versé: 975 000 FCFA   │                                              │
│ │ Reste: 0 FCFA 🟢Soldé       │                                              │
│ │                              │                                              │
│ │ ── MOYENNES ─────────────── │                                              │
│ │ Moyenne M1: 14.50 / 20      │                                              │
│ │ Moyenne M2: 15.00 / 20      │                                              │
│ │                              │                                              │
│ │ ── CANDIDATURE ──────────── │                                              │
│ │ Statut: En attente          │                                              │
│ │ Résumé: [contenu...]        │                                              │
│ │                              │                                              │
│ │ [✔ Valider] [✖ Rejeter]     │                                              │
│ │ [← Fermer]                   │                                              │
│ └──────────────────────────────┘                                              │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 1.6 Écran : RECLAMATION_ETUDIANT — Réclamations (Scolarité)

**ID & TITRE :** `RECLAMATION_ETUDIANT` — Réclamations (côté scolarité)

**OBJECTIF :** Permettre au personnel de scolarité de consulter et traiter les réclamations soumises par les étudiants.

**VUE PRINCIPALE :** Segmentation Polarisée (Zone de traitement + Tableau).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| N° Réclamation | `reclamations.id_reclamation` |
| Étudiant | `etudiants.nom_etu` + `prenom_etu` |
| Objet | `reclamations.objet` |
| Date réclamation | `reclamations.date_reclamation` |
| Statut | `statut_reclamation.lib_statut` (badge) |
| Actions | Voir / Traiter |

*Champs de traitement :*

| Champ | Type | Obligatoire |
|---|---|---|
| Réponse | Textarea | **Oui** ★ |
| Nouveau statut | Select | **Oui** ★ |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Répondre | Vert | POST → enregistre la réponse, change le statut |
| Rechercher | Bleu | Filtre |
| Exporter | Bleu | Export |
| Imprimer | Bleu | Impression |

**RÈGLES MÉTIER :**
- Les réclamations sont créées par les étudiants depuis leur espace (`ETU_RECLAMATION`).
- La scolarité ne crée pas de réclamation, elle traite celles existantes.
- Le changement de statut est enregistré avec la date et l'utilisateur traitant.
- Les statuts proviennent de la table `statut_reclamation`.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : "Réclamation traitée avec succès".

**LA ROUTE :** `?page=gestion_reclamations_scolarite`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée.
- Panneau latéral pour les détails de la réclamation.
- Connexion avec `ETU_RECLAMATION` (espace étudiant) : les réclamations arrivent de là.

**Critères de filtrage :** Statut, date, étudiant.

**Traçabilité :** Oui, logué dans `pister`.

**Sécurité :** Accès restreint à la scolarité.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ RÉCLAMATIONS — SCOLARITÉ                                [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Réclamation de: [auto-rempli]     Objet: [auto-rempli]               │   │
│ │ Nouveau statut: [▼ Sélectionner]                                     │   │
│ │ Réponse: [___________________________________________________]       │   │
│ │                                                         [✔ Répondre]  │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [� Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│N° Récl.│Étudiant      │Objet         │Date Récl.│Statut     │Actions│  │
│ │──┼────────┼──────────────┼──────────────┼──────────┼───────────┼───────│  │
│ │ ☐│  1     │Konan Y.F.    │Note erronée  │12/01/2026│🟠En cours │ 👁 ✏  │  │
│ │ ☐│  2     │Traoré F.     │Absence injust│18/01/2026│🟢Résolu   │ 👁    │  │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

(End of file - total 637 lines)
