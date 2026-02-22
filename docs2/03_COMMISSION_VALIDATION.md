# 3. COMMISSION VALIDATION — Descriptions des Écrans

> **Workflow Commission :**
> 1. **Réception** (`COM_RECEPTION_RAPPORT`) : Vue de TOUS les rapports arrivés (nouveaux + anciens). **Aucune action d'affectation ici.**
> 2. Quand un membre **clique sur un nouveau rapport**, il est **automatiquement redirigé** vers **Analyse et approbation** (`ANA_APP_RAPPORT`).
> 3. **Analyse et approbation** : Le membre soumet sa décision (valider/rejeter). Il voit aussi tous les rapports qu'il a déjà approuvés.
> 4. C'est dans la **Rédaction du Compte Rendu** (`COM_REDACTION_CR`) qu'on **affecte l'encadreur et le directeur de mémoire**.

---

## 3.1 Écran : COM_DASHBOARD — Tableau de bord commission

**ID & TITRE :** `COM_DASHBOARD` — Tableau de bord commission

**OBJECTIF :** Vue synthétique de l'activité de la commission de validation.

**VUE PRINCIPALE :** Dashboard (widgets + graphiques).

**ÉLÉMENTS DE DONNÉES :**

| Donnée affichée | Source | Calcul |
|---|---|---|
| Rapports en attente | `rapport_etudiants` WHERE `statut_rapport = 'en_attente'` | COUNT |
| Rapports validés | `rapport_etudiants` WHERE `statut_rapport = 'valider'` | COUNT |
| Rapports rejetés | `rapport_etudiants` WHERE `statut_rapport = 'rejeter'` | COUNT |
| Rapports en cours | `rapport_etudiants` WHERE `statut_rapport = 'en_cours'` | COUNT |
| Comptes rendus rédigés | `compte_rendu` | COUNT |
| Graphique avancement | Répartition par statut | Camembert |

**ACTIONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Voir rapports reçus | Bleu | → `COM_RECEPTION_RAPPORT` |
| Analyser rapports | Bleu | → `ANA_APP_RAPPORT` |
| Suivi validation | Bleu | → `SUIVI_VALIDATION_COM` |
| Rédiger CR | Bleu | → `COM_REDACTION_CR` |

**LA ROUTE :** `?page=dashboard_commission`

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│              TABLEAU DE BORD — COMMISSION DE VALIDATION  [Année: 2025-2026] │
├──────────────────────────────────────────────────────────────────────────────┤
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐        │
│ │📋 EN ATTENTE │ │✅ VALIDÉS    │ │❌ REJETÉS    │ │📝 CR RÉDIGÉS │        │
│ │     15       │ │     42       │ │      8       │ │     35       │        │
│ │  [Voir ▸]    │ │  [Voir ▸]    │ │  [Voir ▸]    │ │  [Rédiger ▸] │        │
│ └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘        │
│                                                                              │
│ ┌────────────────────────────────┐ ┌────────────────────────────────┐       │
│ │ 📊 Avancement des rapports     │ │ 📊 Activité récente            │       │
│ │                                │ │                                │       │
│ │    Validés    ████████ 65%     │ │ • 3 rapports reçus aujourd'hui │       │
│ │    En attente ███      23%     │ │ • 2 CR finalisés cette semaine │       │
│ │    Rejetés    █        12%     │ │ • 1 rapport rejeté hier        │       │
│ └────────────────────────────────┘ └────────────────────────────────┘       │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 3.2 Écran : COM_RECEPTION_RAPPORT — Réception des rapports

**ID & TITRE :** `COM_RECEPTION_RAPPORT` — Réception des rapports

**OBJECTIF :** Afficher **TOUS les rapports déposés** (nouveaux et anciens), permettant à la commission de voir l'ensemble du flux. **Aucune action d'affectation ici.** Quand un membre clique sur un **nouveau rapport** (non encore évalué), il est **automatiquement redirigé** vers l'écran d'Analyse et Approbation.

**VUE PRINCIPALE :** Tableau de réception (lecture + navigation).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| N° Rapport | `rapport_etudiants.id_rapport` |
| N° Étudiant | `etudiants.num_carte_etud` |
| Nom & Prénom | `etudiants.nom_etu` + `prenom_etu` |
| Nom rapport | `rapport_etudiants.nom_rapport` |
| Thème | `rapport_etudiants.theme_rapport` |
| Date dépôt | `deposer.date_depot` |
| Statut | `rapport_etudiants.statut_rapport` (badge : 🔵en_cours / 🟠en_attente / 🟢valider / 🔴rejeter) |
| Nouveau ? | Indicateur visuel si rapport non encore évalué (pastille 🔴) |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Clic sur un rapport **nouveau** | — | **Redirection automatique** vers `ANA_APP_RAPPORT` avec le rapport sélectionné |
| Clic sur un rapport **ancien** | — | Affiche les détails dans un panneau latéral |
| Télécharger rapport | Bleu | Télécharge le fichier PDF |
| Rechercher / Exporter / Imprimer | Bleu | Actions standard |

**RÈGLES MÉTIER :**
- **Vue en lecture seule** : aucune modification directe depuis cet écran.
- **Pas d'affectation ici** : l'affectation encadrant/directeur se fait dans `COM_REDACTION_CR`.
- Les rapports "nouveaux" (non encore évalués par la commission) sont marqués visuellement.
- Le clic sur un rapport nouveau déclenche une **redirection vers `?page=evaluation_dossiers&id_rapport=ID`**.

**LA ROUTE :** `?page=reception_rapport_com`

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ RÉCEPTION DES RAPPORTS                                  [Année A.: 2025-26] │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Total rapports: 23 │ Nouveaux: 🔴 5 │ Déjà traités: 18              │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rechercher]  [▼ Statut]           Afficher: [▼10]                │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ │                              [📤 Export]             [🖨 Impr.]       │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│  │N°Rap│N° Etud│Nom & Prénom│Nom Rapport│Thème   │Dt Dépôt│Statut │   │
│ │──┼──┼─────┼───────┼────────────┼───────────┼────────┼────────┼───────│   │
│ │ ☐│🔴│  12 │CI01.. │Konan Y.F.  │Mon rapport│Sys.inf │10/01/26│🟠Att. │   │
│ │ ☐│🔴│  11 │CI01.. │Traoré F.   │Audit SI   │Audit   │15/01/26│🔵Cours│   │
│ │ ☐│  │  10 │CI01.. │Ebe A.      │Gestion RH │Gestion │05/01/26│🟢Valid│   │
│ │ ☐│  │   9 │CI01.. │Yao K.      │Réseau     │Réseau  │01/01/26│🔴Rejet│   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur 23 entrées              [◀ Préc.][1][2][3][▶ Suiv.] │
│                                                                              │
│ 💡 Cliquez sur un rapport 🔴 nouveau pour le traiter (redirection auto)     │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 3.3 Écran : ANA_APP_RAPPORT — Analyse et approbation

**ID & TITRE :** `ANA_APP_RAPPORT` — Analyse et approbation des rapports

**OBJECTIF :** Permettre au membre de la commission d'analyser un rapport et de soumettre sa décision (valider/rejeter). Le membre voit également **tous les rapports qu'il a déjà approuvés**.

**VUE PRINCIPALE :** Segmentation Polarisée (Zone de décision + Tableau des rapports évalués par ce membre).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Zone de décision (Pôle Supérieur — rapport à évaluer) :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| Rapport | Auto-rempli (ID + nom + thème) | — | Pré-sélectionné si `id_rapport` en URL |
| Étudiant | Texte (lecture seule) | — | Auto |
| Thème | Texte (lecture seule) | — | Auto |
| Décision | Select ("Valider", "Rejeter") | **Oui** ★ | "Sélectionner" |
| Commentaire | Textarea | **Oui** ★ (obligatoire si rejet) | Vide |

*Tableau (Pôle Inférieur — "Mes évaluations") :*

| Colonne | Source |
|---|---|
| N° Rapport | `rapport_etudiants.id_rapport` |
| Étudiant | Join `etudiants` |
| Thème | `theme_rapport` |
| Ma décision | `evaluations_rapports.decision_evaluation` par ce membre |
| Mon commentaire | `evaluations_rapports.commentaire` |
| Date | `evaluations_rapports.date_evaluation` |
| Statut rapport | `rapport_etudiants.statut_rapport` |
| Actions | Voir détails |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Valider le rapport | Vert | POST → `decision_evaluation = 'valider'`, met à jour `statut_rapport`, crée `approuver` |
| Rejeter le rapport | Rouge | POST → `decision_evaluation = 'rejeter'`, met à jour `statut_rapport`, commentaire obligatoire |
| Voir le rapport (contenu) | Bleu | Ouvre le contenu/PDF dans un panneau latéral |
| Rechercher / Exporter / Imprimer | Bleu | Actions standard |

**RÈGLES MÉTIER :**
- Quand un rapport est reçu via redirection depuis `COM_RECEPTION_RAPPORT`, il est pré-sélectionné.
- Un commentaire est **obligatoire pour un rejet**.
- La validation crée une entrée dans `evaluations_rapports` ET dans `approuver` (avec le niveau d'approbation correspondant).
- La décision propage le changement de statut dans `rapport_etudiants.statut_rapport`.
- Le membre de la commission voit **ses propres évaluations passées** dans le tableau inférieur. Il voit **tous les rapports qu'il a approuvés**.
- L'évaluateur est identifié automatiquement via sa session (id_utilisateur → id_enseignant via `getEnseignantIdFromAdmin()`).

**ÉTAT DE SORTIE / FEEDBACK :**
- Validation : "Rapport validé avec succès" (vert).
- Rejet : "Rapport rejeté. L'étudiant sera notifié." (orange).

**LA ROUTE :** `?page=evaluation_dossiers` / `?page=evaluation_dossiers&id_rapport=ID`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée.
- Si `id_rapport` est dans l'URL (redirection depuis réception), le rapport est pré-chargé.
- Panneau latéral pour visualiser le contenu du rapport.
- Le tableau montre les rapports que CE membre a évalués.
- Statistiques en haut : nombre total de dossiers à évaluer, validés par ce membre, rejetés par ce membre.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ ANALYSE ET APPROBATION DES RAPPORTS                     [Année A.: 2025-26] │
│                                                                              │
│ ┌──────────┐ ┌──────────┐ ┌──────────┐                                     │
│ │📋 À TRAITER│ │✅ VALIDÉS │ │❌ REJETÉS │                                   │
│ │      5     │ │     18    │ │      3    │                                   │
│ └──────────┘ └──────────┘ └──────────┘                                     │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ ── RAPPORT À ÉVALUER ───────────────────────────────────────────────  │   │
│ │ Rapport N° 12 — "Mon rapport de stage"                               │   │
│ │ Étudiant: Konan Yao Franklin   Thème: Système d'information          │   │
│ │                                                                       │   │
│ │ Décision *: [▼ Valider / Rejeter]                                    │   │
│ │                                                                       │   │
│ │ Commentaire * (obligatoire si rejet):                                │   │
│ │ ┌──────────────────────────────────────────────────────────────────┐   │   │
│ │ │                                                                  │   │   │
│ │ └──────────────────────────────────────────────────────────────────┘   │   │
│ │                   [👁 Voir rapport]  [✔ Soumettre décision]           │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │    MES ÉVALUATIONS PASSÉES                   Afficher: [▼10]          │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ │                              [📤 Export]             [🖨 Impr.]       │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│N°Rap│Étudiant    │Thème    │Ma décision│Mon commentaire │Date  │Act│   │
│ │──┼─────┼────────────┼─────────┼───────────┼────────────────┼──────┼───│   │
│ │ ☐│  10 │Ebe A.      │Gestion  │🟢Validé   │RAS             │15/02 │👁 │   │
│ │ ☐│   8 │Bamba M.    │Réseau   │🟢Validé   │Bon rapport     │10/02 │👁 │   │
│ │ ☐│   7 │Diallo S.   │Sécurité │🔴Rejeté   │Plagiat détecté │05/02 │👁 │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 3.4 Écran : SUIVI_VALIDATION_COM — Suivi d'avancement

**ID & TITRE :** `SUIVI_VALIDATION_COM` — Suivi d'avancement

**OBJECTIF :** Vue consolidée pour suivre l'avancement global du processus de validation.

**VUE PRINCIPALE :** Tableau avec indicateurs visuels.

**ÉLÉMENTS DE DONNÉES :** Consolidation de `rapport_etudiants`, `deposer`, `evaluations_rapports`, `approuver`.

**ACTIONS :** Filtrer, Exporter, Imprimer (lecture seule).

**LA ROUTE :** `?page=processus_validation`

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ SUIVI D'AVANCEMENT — PROCESSUS DE VALIDATION            [Année A.: 2025-26] │
│                                                                              │
│ Filtres: [▼ Statut] [▼ Encadrant] [▼ Directeur] [🔍 Rechercher]            │
│                                                Afficher: [▼10]              │
│ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                          │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ ☐│N°Rap│Étudiant │Thème    │Dépôt  │Encadr.│Dir.  │Statut │Eval.│Prog.│  │
│ │──┼─────┼─────────┼─────────┼───────┼───────┼──────┼───────┼─────┼─────│  │
│ │ ☐│  1  │Konan Y. │Sys.inf. │10/01  │Brou P.│Soro E│🟢Valid│✅   │██100%│  │
│ │ ☐│  2  │Traoré F.│Audit SI │15/01  │  —    │ —    │🟠Att. │⏳   │██ 40%│  │
│ │ ☐│  3  │Ebe A.   │Gestion  │20/01  │Kone B.│ —    │🔵Cours│ —   │█  20%│  │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ [📤 Exporter]  [🖨 Imprimer]                                                │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 3.5 Écran : COM_REDACTION_CR — Rédaction du Compte Rendu

> **C'est ici qu'on affecte l'encadreur pédagogique et le directeur de mémoire** (et non dans la réception).

### 3.5.1 Sous-Écran : CR_HUB — Hub Comptes Rendus

**LA ROUTE :** `?page=redaction_compte_rendu`

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HUB COMPTES RENDUS                                      [Année A.: 2025-26] │
│                                                                              │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐                         │
│ │ 📝 RÉDIGER   │ │ 📋 BROUILLONS│ │ 📦 ARCHIVES  │                         │
│ │  Nouveau CR  │ │     3 CR     │ │    12 CR     │                         │
│ │  [Créer ▸]   │ │  [Voir ▸]    │ │  [Voir ▸]    │                         │
│ └──────────────┘ └──────────────┘ └──────────────┘                         │
│                                                                              │
│ Derniers CR:                                                                 │
│ • CR-2026-001 — Éval. Rapport Konan — 15/02/2026 (Brouillon)              │
│ • CR-2026-002 — Éval. Rapport Traoré — 14/02/2026 (Finalisé)              │
└──────────────────────────────────────────────────────────────────────────────┘
```

### 3.5.2 Sous-Écran : CR_REDACTION — Rédaction (+ Affectation encadrant/directeur)

**ID & TITRE :** `CR_REDACTION` — Rédaction de CR

**OBJECTIF :** Éditeur de texte riche pour rédiger le CR + **affecter l'encadrant pédagogique et le directeur de mémoire** à chaque rapport concerné.

**VUE PRINCIPALE :** Éditeur de texte + formulaire d'affectation.

**ÉLÉMENTS DE DONNÉES :**

*Champs de métadonnées :*

| Champ | Type | Obligatoire | Source |
|---|---|---|---|
| Étudiant lié | Select (recherche) | **Oui** ★ | `etudiants` |
| Nom du CR | Texte | **Oui** ★ | 70 car. max |
| Rapports liés | Multi-select (checkboxes) | **Oui** ★ | `rapport_etudiants` (rapports validés) |
| Contenu du CR | Éditeur WYSIWYG | **Oui** ★ | Longtext |

*Affectation encadreur/directeur (par rapport lié) :*

| Champ | Type | Obligatoire | Source |
|---|---|---|---|
| Encadrant pédagogique | Select (`enseignants`) par rapport | Non | `affecter.role = 'encadrant'` |
| Directeur de mémoire | Select (`enseignants`) par rapport | Non | `affecter.role = 'directeur'` |

**ACTIONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Enregistrer | Vert | POST → crée `compte_rendu`, crée `compte_rendu_rapport` (liens), crée `affecter` (encadrant + directeur par rapport), génère PDF via Dompdf, envoie email aux étudiants |
| Exporter PDF | Bleu | POST → génère et télécharge le PDF du CR |

**RÈGLES MÉTIER :**
- Lors de l'enregistrement, le système :
  1. Crée le CR dans `compte_rendu` (nom, contenu, date, chemin PDF).
  2. Génère le PDF via **Dompdf** et le stocke dans `ressources/uploads/comptes_rendus/`.
  3. Lie les rapports au CR dans `compte_rendu_rapport`.
  4. **Affecte les encadrants/directeurs** dans la table `affecter` pour chaque rapport : `INSERT INTO affecter (id_enseignant, id_rapport, role)`.
  5. Envoie un **email** à chaque étudiant concerné avec le PDF en pièce jointe.
- La liste des rapports sélectionnables provient de `Valider::getRapportsValides()` (rapports validés).
- La liste des enseignants provient de `enseignants.getAllEnseignants()`.

**LA ROUTE :** `?page=redaction_compte_rendu` (POST)

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ RÉDACTION DU COMPTE RENDU                               [Année A.: 2025-26] │
│                                                                              │
│ Étudiant lié *: [▼ Sélect. étudiant]   Nom du CR *: [_________________]    │
│                                                                              │
│ Rapports liés (validés) :                                                   │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ ☑ Rapport #10 — "Gestion RH" (Ebe A.)                                │   │
│ │    Encadrant péd.*: [▼ Sélect. enseignant]                            │   │
│ │    Directeur mém.*: [▼ Sélect. enseignant]                            │   │
│ │                                                                       │   │
│ │ ☑ Rapport #8 — "Réseau" (Bamba M.)                                   │   │
│ │    Encadrant péd.*: [▼ Sélect. enseignant]                            │   │
│ │    Directeur mém.*: [▼ Sélect. enseignant]                            │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [B] [I] [U] [H1] [H2] [Liste] [Lien] [Image]                        │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │                                                                       │   │
│ │  Le jury de la commission de validation, réuni le ...                 │   │
│ │                                                                       │   │
│ │  a examiné le rapport de stage de M./Mme ...                          │   │
│ │                                                                       │   │
│ │  Observations: ...                                                    │   │
│ │                                                                       │   │
│ │  Décision du jury: ...                                                │   │
│ │                                                                       │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│                          [📄 Aperçu PDF]  [✔ Enregistrer et générer PDF]     │
└──────────────────────────────────────────────────────────────────────────────┘
```

### 3.5.3 / 3.5.4 — Brouillons / Archives

**Routes :** `?page=redaction_compte_rendu&action=brouillons` / `&action=archives`

**Tableaux avec barre standard :**
```
┌────────────────────────────────────────────────────────────────────────┐   
│ BROUILLONS / ARCHIVES CR                   Afficher: [▼10]            │   
│ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   
├────────────────────────────────────────────────────────────────────────┤   
│ ☐│N° CR│Nom CR        │Étudiant   │Date     │Statut    │Actions      │   
│──┼─────┼──────────────┼───────────┼─────────┼──────────┼─────────────│   
│ ☐│  1  │Éval. Konan   │Konan Y.F. │15/02/26 │📝Brouill.│✏ 📥 🗑     │   
│ ☐│  2  │Éval. Traoré  │Traoré F.  │14/02/26 │✅Finalisé│📥 👁        │   
└────────────────────────────────────────────────────────────────────────┘   
Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]     
```

(End of file - total 396 lines)
