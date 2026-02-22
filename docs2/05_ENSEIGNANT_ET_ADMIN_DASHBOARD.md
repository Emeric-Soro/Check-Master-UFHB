# 5. ESPACE ENSEIGNANT — Descriptions des Écrans

---

## 5.1 Écran : ENS_DASHBOARD — Tableau de bord enseignant

**ID & TITRE :** `ENS_DASHBOARD` — Tableau de bord enseignant

**OBJECTIF :** Offrir à l'enseignant une vue synthétique de ses activités : rapports à évaluer, soutenances planifiées, étudiants encadrés.

**VUE PRINCIPALE :** Dashboard (widgets + listes).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Donnée affichée | Source | Calcul |
|---|---|---|
| Rapports à évaluer | `affecter` WHERE `id_enseignant` = session → JOIN `rapport_etudiants` WHERE statut = 'en_attente' | COUNT |
| Rapports validés | Idem avec statut = 'valider' | COUNT |
| Soutenances planifiées | `enseignant_jury` WHERE `id_enseignant` = session → JOIN `programmer_soutenance` | COUNT |
| Prochaine soutenance | Idem, trié par date ASC, LIMIT 1 | MIN date |
| Étudiants encadrés | `affecter` WHERE role='encadrant' et `id_enseignant` = session | COUNT |
| Étudiants dirigés | `affecter` WHERE role='directeur' et `id_enseignant` = session | COUNT |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Voir rapports à évaluer | Bleu | Redirige (selon permissions) |
| Voir soutenances | Bleu | Redirige vers planning |

**RÈGLES MÉTIER :**
- L'enseignant ne voit que SES données (filtrage par `id_enseignant` de la session).
- Les statistiques sont liées à l'année académique active.
- L'enseignant peut être de type "Administratif" (type 1) ou "Simple" (type 2) — cela conditionne l'accès aux sous-menus.

**ÉTAT DE SORTIE / FEEDBACK :** Lecture seule.

**LA ROUTE :** `?page=tableau_bord_enseignant`

**COMPORTEMENT DE LA PAGE :**
- Dashboard libre (pas de segmentation polarisée).
- Widgets statistiques + liste des prochaines activités.
- Sur mobile : widgets empilés.
- Connexion avec les écrans Commission (si l'enseignant est membre de la commission) et Soutenance.

**Critères de filtrage :** Année académique (implicite), enseignant (implicite via session).

**Traçabilité :** Accès logué.

**Sécurité :** L'enseignant ne voit que SES données. Filtrage strict par session.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│           TABLEAU DE BORD ENSEIGNANT                    [Année A.: 2025-26] │
├──────────────────────────────────────────────────────────────────────────────┤
│ Bonjour, Pr. BROU Patrice Magloire                                          │
│                                                                              │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐        │
│ │ 📋 RAPPORTS  │ │ 🎓 SOUTENANC.│ │ 👨‍🎓 ENCADRÉS │ │ 📅 PROCHAINE │        │
│ │  À évaluer   │ │  Planifiées  │ │  Étudiants   │ │  Soutenance  │        │
│ │      5       │ │      3       │ │      8       │ │  15/03/2026  │        │
│ │  [Voir ▸]    │ │  [Voir ▸]    │ │  [Liste ▸]   │ │  09:00 S.A   │        │
│ └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘        │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ 📌 ACTIVITÉS RÉCENTES                                                 │   │
│ │ • Rapport de Konan Y.F. reçu le 10/02 — En attente d'évaluation     │   │
│ │ • Rapport de Traoré F. validé le 08/02                               │   │
│ │ • Soutenance de Ebe A. programmée le 16/03 à 14:00                   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 5.2 Sous-menu : COM_ESPACES — Espaces (Onglet Dashboard Enseignant)

**ID & TITRE :** `COM_ESPACES` / `DASH_ENSEIGNANT` — Espace enseignant (onglet)

**OBJECTIF :** Ce sous-menu "Espaces" contient l'onglet "Dashboard Enseignant" qui est un alias vers le tableau de bord enseignant. Il permet de regrouper les espaces auxquels l'enseignant a accès.

**VUE PRINCIPALE :** Même dashboard que `ENS_DASHBOARD`.

**LA ROUTE :** `?page=dashboard_enseignant`

**COMPORTEMENT DE LA PAGE :**
- Identique au `ENS_DASHBOARD`. C'est un point d'accès alternatif depuis le menu "Espaces" de la sidebar de l'espace commission.
- Permet aux enseignants qui ont accès à la commission de basculer vers leur vue personnelle.

**MAQUETTE ASCII :** Identique à `ENS_DASHBOARD` ci-dessus.

---

# 6. ADMINISTRATION PLATEFORME — Descriptions des Écrans

---

## 6.1 Écran : ADM_DASHBOARD — Dashboard Admin

**ID & TITRE :** `ADM_DASHBOARD` — Dashboard Admin

**OBJECTIF :** Offrir aux administrateurs une vue globale du système : utilisateurs actifs, activité récente, état du système, statistiques transversales.

**VUE PRINCIPALE :** Dashboard (widgets + graphiques + alertes).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Donnée affichée | Source | Calcul |
|---|---|---|
| Utilisateurs actifs | `utilisateur` WHERE `statut_utilisateur = 'Actif'` | COUNT |
| Utilisateurs inactifs | `utilisateur` WHERE `statut_utilisateur = 'Inactif'` | COUNT |
| Dernières connexions | `pister` WHERE action = 'Connexion' ORDER BY date DESC | TOP 10 |
| Erreurs récentes | `pister` WHERE `statut_action = 'Erreur'` | COUNT dernières 24h |
| Total étudiants | `etudiants` | COUNT |
| Total enseignants | `enseignants` | COUNT |
| Total personnel admin | `personnel_admin` | COUNT |
| Taille BDD | Information système | — |
| Année académique active | `annee_academique` filtrée | Date |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Gérer utilisateurs | Bleu | Redirige vers `SYS_UTILISATEURS` |
| Voir audit | Bleu | Redirige vers `SYS_AUDIT` |
| Paramétrage | Bleu | Redirige vers `ADM_PARAMETRAGE` |

**RÈGLES MÉTIER :**
- Accessible uniquement aux administrateurs (groupe `Administrateur`).
- Les statistiques couvrent toutes les données, pas de filtrage par année.

**ÉTAT DE SORTIE / FEEDBACK :** Lecture seule.

**LA ROUTE :** `?page=dashboard`

**COMPORTEMENT DE LA PAGE :**
- Dashboard libre.
- Widgets statistiques.
- Liste d'alertes système.
- Sur mobile : empilement vertical.

**Critères de filtrage :** Aucun spécifique (vue globale).

**Traçabilité :** Accès logué.

**Sécurité :** Admin uniquement.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│                    DASHBOARD ADMINISTRATEUR              [Année: 2025-2026] │
├──────────────────────────────────────────────────────────────────────────────┤
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐          │
│ │👤 USERS  │ │👨‍🎓 ÉTUDIA│ │👨‍🏫 ENSEIG│ │🏢 ADMIN  │ │⚠ ERREURS │          │
│ │ Actifs   │ │  Total   │ │  Total   │ │  Total   │ │ 24h      │          │
│ │    3     │ │   342    │ │    65    │ │     5    │ │    2     │          │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘          │
│                                                                              │
│ ┌────────────────────────────┐ ┌──────────────────────────────────────┐     │
│ │ 📊 Répartition utilisateurs│ │ 🕐 DERNIÈRES CONNEXIONS              │     │
│ │ Admin       ███  3         │ │ • Koua Brou — 22/02 01:00           │     │
│ │ Enseignants ████ 2         │ │ • Wah Medard — 21/02 23:30          │     │
│ │ Étudiants   █    1         │ │ • Irie Jemima — 20/02 14:15         │     │
│ └────────────────────────────┘ └──────────────────────────────────────┘     │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 6.2 Écran : ADMIN_ANNEE_ACADEMIQUE — Ouverture/Fermeture AC

**ID & TITRE :** `ADMIN_ANNEE_ACADEMIQUE` — Ouverture/Fermeture Année Académique

**OBJECTIF :** Permettre à l'administrateur de créer de nouvelles années académiques et de gérer leur ouverture/fermeture (activer/désactiver l'année en cours).

**VUE PRINCIPALE :** Segmentation Polarisée (Formulaire + Tableau).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Date début | Date | **Oui** ★ | 10 | Vide |
| Date fin | Date | **Oui** ★ | 10 | Vide |

*Données affichées :*

| Colonne | Source |
|---|---|
| ID | `annee_academique.id_annee_acad` |
| Date début | `annee_academique.date_deb` |
| Date fin | `annee_academique.date_fin` |
| Libellé (calculé) | "YYYY-YYYY" |
| Statut (calculé) | Active/Inactive (basé sur date courante) |
| Actions | Modifier / Supprimer |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Créer | Vert | POST → crée `annee_academique` |
| Modifier | Vert | Met à jour les dates |
| Supprimer | Rouge (alerte) | Supprime l'année (avec vérification de dépendances) |

**RÈGLES MÉTIER :**
- La date de fin doit être postérieure à la date de début.
- L'année académique active est celle dont la période englobe la date actuelle.
- On ne peut pas supprimer une année qui a des données dépendantes (étudiants, inscriptions, notes).
- L'`id_annee_acad` est auto-incrémenté.
- Cette page redirige techniquement vers `?page=parametres_generaux&action=annees_academiques` mais est un écran de premier niveau dans le menu admin.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : "Année académique créée/modifiée avec succès".

**LA ROUTE :** `?page=parametres_generaux&action=annees_academiques`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée standard.
- Réinjection circulaire pour modification.
- Connexion avec tous les écrans qui dépendent de l'année académique.

**Critères de filtrage :** Aucun (toutes les années affichées).

**Traçabilité :** Oui, logué dans `pister` (action = "Création"/"Modification"/"Suppression", nom_table = "annee_academique").

**Sécurité :** Admin uniquement.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ OUVERTURE / FERMETURE ANNÉE ACADÉMIQUE                  [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Date début *              Date fin *                                  │   │
│ │ [____/____/____]          [____/____/____]                            │   │
│ │                                                                       │   │
│ │                                    [Réinitialiser]  [✔ Créer]         │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│   ID    │ Date début  │ Date fin    │ Libellé   │ Statut   │Actions│   │
│ │──┼─────────┼─────────────┼─────────────┼───────────┼──────────┼───────│   │
│ │ ☐│  22625  │ 01/09/2025  │ 31/07/2026  │ 2025-2026 │🟢Active  │ ✏️ 🗑│   │
│ │ ☐│  22524  │ 01/09/2024  │ 31/07/2025  │ 2024-2025 │⚪Inactive│ ✏️ 🗑│   │
│ │ ☐│  22423  │ 01/09/2023  │ 31/07/2024  │ 2023-2024 │⚪Inactive│ ✏️ 🗑│   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur 25 entrées              [◀ Préc.][1][2][3][▶ Suiv.] │
└──────────────────────────────────────────────────────────────────────────────┘
```

(End of file - total 253 lines)
