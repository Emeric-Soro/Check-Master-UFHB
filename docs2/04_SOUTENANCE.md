# 4. SOUTENANCE — Descriptions des Écrans

---

## 4.1 Écran : SOUT_COMPOS_JURY — Composition de jury

**ID & TITRE :** `SOUT_COMPOS_JURY` — Composition de jury / Programmation soutenance

**OBJECTIF :** Programmer les soutenances (date, heure, salle) et composer le jury (président, directeur de mémoire, examinateur, encadrant, maître de stage) pour chaque étudiant.

**VUE PRINCIPALE :** Segmentation Polarisée (Formulaire de programmation + Tableau des programmes).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie (Pôle Supérieur) :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Année Académique | Select (lecture seule) | — | — | Année active |
| Date soutenance | Date | **Oui** ★ | 10 | Vide |
| Heure | Time | **Oui** ★ | 5 | Vide |
| Salle | Select (`salles`) | **Oui** ★ | — | "Sélectionner" |
| Étudiant | Select déroulant (`etudiants`) | **Oui** ★ | — | "Sélectionner" |
| Thème | Texte (auto-rempli ou saisie) | **Oui** ★ | 300 | Auto-rempli si rapport existant |
| **Composition Jury :** | | | | |
| Président | Select (`enseignants`) | **Oui** ★ | — | "Sélectionner" |
| Examinateur | Select (`enseignants`) | **Oui** ★ | — | "Sélectionner" |
| Directeur M.S. | Select (`enseignants`) | **Oui** ★ | — | "Sélectionner" |
| Encadrant S | Select (`enseignants`) | **Oui** ★ | — | "Sélectionner" |
| Directeur S | Select (`enseignants`) | **Oui** ★ | — | "Sélectionner" |

*Données affichées (Pôle Inférieur — Tableau) :*

| Colonne | Source |
|---|---|
| Date S. | `programmer_soutenance.date_soutenance` |
| Heure | `programmer_soutenance.heure_soutenance` |
| Salle | `salles.lib_salle` (JOIN) |
| Étudiant | `etudiants.nom_etu` + `prenom_etu` |
| Thème | `programmer_soutenance.theme_soutenance` |
| Jury | Concaténation des membres du jury depuis `enseignant_jury` |
| Actions | Modifier / Supprimer |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Valider | Vert | POST → crée `programmer_soutenance` + entrées dans `enseignant_jury` |
| Modifier (mode édition) | Vert | Met à jour la programmation |
| Rechercher | Bleu | Filtre le tableau |
| Exporter | Bleu | Export CSV |
| Imprimer | Bleu | Impression du planning |

**RÈGLES MÉTIER :**
- **Auto-remplissage** : Quand on sélectionne un étudiant, le thème se remplit automatiquement depuis `rapport_etudiants.theme_rapport` ou `programmer_soutenance.theme_soutenance` si existant.
- Un étudiant ne peut avoir qu'une seule soutenance programmée par session.
- Les membres du jury sont des enseignants (table `enseignants`). Un même enseignant ne peut pas cumuler plusieurs rôles dans le même jury.
- Les rôles du jury proviennent de `qualite_jury` : Président (PJ), Directeur mémoire (DM), Examinateur (EX), Encadrant (EN), Maître de stage (MS).
- La salle est sélectionnée parmi les salles disponibles (`salles`).
- Vérifier qu'il n'y a pas de conflit horaire (même salle, même créneau).
- Le `num_soutenance` est généré automatiquement.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : "Soutenance programmée avec succès" (notification verte).
- Erreur conflit : "Conflit horaire : la salle est déjà occupée à ce créneau" (notification rouge).

**LA ROUTE :** `?page=programmation_soutenance`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée complète.
- Le formulaire contient une sous-section "Composition Jury" avec 5 selects d'enseignants.
- Clic Modifier dans le tableau → réinjection circulaire vers le pôle supérieur.
- Connexion avec `SOUT_EVALUATION` (l'évaluation se fait ensuite).
- Connexion avec `MAJ_ETUDIANT` (les étudiants) et le référentiel enseignants.

**Critères de filtrage :** Date, salle, étudiant.

**Traçabilité :** Oui, programmations logguées dans `pister`.

**Sécurité :** Réservé aux utilisateurs autorisés (scolarité, admin).

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ PROGRAMMATION SOUTENANCE                                [Année Académique ▼]│
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Date soutenance *   Heure *     Salle *                              │   │
│ │ [____/____/____]    [__:__]     [▼ Sélect. salle]                    │   │
│ │                                                                       │   │
│ │ Étudiant *                       Thème *                              │   │
│ │ [▼ Sélect. étudiant]             [________________________] (auto)   │   │
│ │                                                                       │   │
│ │ ─── Composition Jury ───────────────────────────────────────────────  │   │
│ │ Président *              Examinateur *                                │   │
│ │ [▼ Sélect. enseignant]   [▼ Sélect. enseignant]                      │   │
│ │                                                                       │   │
│ │ Directeur M.S. *        Encadrant S *          Directeur S *         │   │
│ │ [▼ Sélect.]             [▼ Sélect.]            [▼ Sélect.]           │   │
│ │                                                                       │   │
│ │                                                         [✔ VALIDER]   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │         LISTE DES PROGRAMMES                                          │   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│Date S.│Heure│Salle  │Étudiant     │Thème       │Jury           │Actions│ │
│ │──┼────────┼─────┼───────┼─────────────┼────────────┼───────────────┼──────│ │
│ │ ☐│15/03/26│09:00│Salle A│Konan Y.F.   │Sys. d'info │Brou(PJ),Soro..│✏️ 🗑│ │
│ │ ☐│15/03/26│11:00│Salle B│Traoré F.    │Audit SI    │Kone(PJ),Yao..│✏️ 🗑│ │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 4.2 Écran : SOUT_EVALUATION — Évaluation Soutenance

**ID & TITRE :** `SOUT_EVALUATION` — Évaluation Soutenance

**OBJECTIF :** Permettre aux membres du jury de saisir les notes de soutenance selon les critères d'évaluation définis, et d'enregistrer la décision finale.

**VUE PRINCIPALE :** Segmentation Polarisée (Formulaire de notation + Tableau des évaluations).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie (Pôle Supérieur) :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Soutenance | Select (programmation) | **Oui** ★ | — | "Sélectionner" |
| Étudiant (auto-rempli) | Texte (lecture seule) | — | — | Auto |
| Thème (auto-rempli) | Texte (lecture seule) | — | — | Auto |
| **Notes par critère :** | | | | |
| Exposé (EX) | Nombre (0-20) | **Oui** ★ | — | Vide |
| Réponses aux questions (RQ) | Nombre (0-20) | **Oui** ★ | — | Vide |
| Présentation du mémoire (PM) | Nombre (0-20) | **Oui** ★ | — | Vide |
| Contenu du mémoire (CM) | Nombre (0-20) | **Oui** ★ | — | Vide |
| Résolution du problème (RP) | Nombre (0-20) | **Oui** ★ | — | Vide |
| **Moyenne calculée** | Texte (lecture seule) | — | — | Calculée auto |
| Décision du jury | Select (`decisions_jury`) | Non | — | — |

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| N° Soutenance | `programmer_soutenance.num_soutenance` |
| Étudiant | Join `etudiants` |
| EX | `evaluer.note` WHERE critère=EX |
| RQ | `evaluer.note` WHERE critère=RQ |
| PM | `evaluer.note` WHERE critère=PM |
| CM | `evaluer.note` WHERE critère=CM |
| RP | `evaluer.note` WHERE critère=RP |
| Moyenne | Calculée (pondérée par barèmes) |
| Décision | `decisions_jury` |
| Actions | Modifier |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Enregistrer notes | Vert | POST → crée/MAJ entrées dans `evaluer` |
| Rechercher / Exporter / Imprimer | Bleu | Actions standard |

**RÈGLES MÉTIER :**
- Les critères d'évaluation proviennent de `critere_evaluation` (5 critères prédéfinis).
- Les barèmes (pondération) proviennent de `bareme_critere` pour l'année académique courante.
- La moyenne est calculée : Σ(note × barème) / Σ(barèmes).
- Chaque note est entre 0 et 20.
- Le jury identifié (via `num_jury` dans `evaluer`) est l'enseignant connecté ou le membre du jury sélectionné.
- La date d'évaluation est automatique.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : "Notes enregistrées avec succès" (vert).

**LA ROUTE :** `?page=evaluation_soutenance`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée.
- Sélection de la soutenance → auto-remplissage étudiant/thème.
- Les champs de notes sont dynamiquement générés selon les critères présents dans `critere_evaluation`.
- La moyenne se recalcule en temps réel à chaque saisie de note.
- Connexion avec `SOUT_COMPOS_JURY` (la programmation est en amont) et `SOUT_EDITION_BULLETIN` (édition du bulletin ensuite).

**Critères de filtrage :** Session de soutenance, étudiant.

**Traçabilité :** Chaque saisie de note logguée.

**Sécurité :** Réservé aux membres du jury. L'enseignant ne peut saisir que les notes des soutenances auxquelles il participe.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ ÉVALUATION SOUTENANCE                                   [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Soutenance *: [▼ Sélect. programmation]                              │   │
│ │ Étudiant: [auto-rempli]        Thème: [auto-rempli]                  │   │
│ │                                                                       │   │
│ │ ─── Notes par critère (sur 20) ─────────────────────────────────────  │   │
│ │ Exposé (EX)         Rép. questions (RQ)   Prés. mémoire (PM)        │   │
│ │ [____/20]           [____/20]              [____/20]                  │   │
│ │                                                                       │   │
│ │ Contenu mémoire (CM)    Résolution problème (RP)                     │   │
│ │ [____/20]               [____/20]                                     │   │
│ │                                                                       │   │
│ │ Moyenne calculée: [  14.75/20  ] (auto)                              │   │
│ │ Décision du jury: [▼ ...]                                            │   │
│ │                                                      [✔ Enregistrer]  │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│N°Sout│Étudiant  │ EX │ RQ │ PM │ CM │ RP │Moyenne│Décision │Actions│   │
│ │──┼───────┼──────────┼────┼────┼────┼────┼────┼───────┼─────────┼───────│   │
│ │ ☐│S001  │Konan Y.F.│14.5│13.0│15.0│16.0│14.0│ 14.50 │Admis    │  ✏   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 4.3 Écran : SOUT_EDITION_BULLETIN — Édition des bulletins

**ID & TITRE :** `SOUT_EDITION_BULLETIN` — Édition des bulletins

**OBJECTIF :** Générer et imprimer les bulletins de soutenance (PV de soutenance) contenant les notes, la composition du jury et la décision finale.

**VUE PRINCIPALE :** Segmentation Polarisée (Filtre/sélection + Tableau + Génération PDF).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Filtres :*

| Champ | Type |
|---|---|
| Session | Select |
| Étudiant | Select/Recherche |

*Données affichées :*

| Donnée | Source |
|---|---|
| Étudiant | Join `etudiants` |
| Thème | `programmer_soutenance.theme_soutenance` |
| Date soutenance | `programmer_soutenance.date_soutenance` |
| Jury | `enseignant_jury` JOIN `enseignants` + `qualite_jury` |
| Notes par critère | `evaluer` |
| Moyenne | Calculée |
| Décision | `decisions_jury` |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Générer bulletin PDF | Vert | Génère un document PDF formaté |
| Générer tous les bulletins | Vert | Génère tous les PDF en lot |
| Imprimer | Bleu | Impression |

**RÈGLES MÉTIER :**
- Le bulletin ne peut être généré que si toutes les notes sont saisies ET la décision du jury est enregistrée.
- Le PDF contient : informations étudiant, thème, composition du jury, grille de notes, moyenne, décision, signatures.
- Le PDF est stocké dans `pdf_cr_pv_rapetd`.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : "Bulletin généré avec succès" + lien de téléchargement.
- Document PDF généré.

**LA ROUTE :** `?page=edition_bulletin`

**COMPORTEMENT DE LA PAGE :**
- Filtrage pour sélectionner les soutenances.
- Tableau récapitulatif avec bouton de génération individuel ou en masse.
- Aperçu du bulletin avant génération.
- Connexion avec `SOUT_EVALUATION` (les notes doivent être saisies avant).

**Critères de filtrage :** Session, date, étudiant.

**Traçabilité :** Génération de bulletin logguée.

**Sécurité :** Réservé aux utilisateurs autorisés.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ ÉDITION DES BULLETINS DE SOUTENANCE                     [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Session: [▼ Toutes]     Étudiant: [🔍 Rechercher...]                 │   │
│ │                                                                       │   │
│ │                                         [📄 Générer tous les bulletins]│   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Étudiant    │Thème      │Date Sout.│ Jury          │Moyenne│Décis.│Gen.│  │
│ │─────────────┼───────────┼──────────┼───────────────┼───────┼──────┼────│  │
│ │ Konan Y.F.  │Sys.info   │15/03/2026│Brou(PJ)+3     │ 14.50 │Admis │📄  │  │
│ │ Traoré F.   │Audit SI   │15/03/2026│Kone(PJ)+3     │ 16.25 │Admis │📄  │  │
│ │ Ebe A.      │Gestion    │16/03/2026│       —       │   —   │  —   │⏳  │  │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

(End of file - total 306 lines)
