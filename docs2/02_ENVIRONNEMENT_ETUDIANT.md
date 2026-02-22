# 2. ENVIRONNEMENT ÉTUDIANT — Descriptions des Écrans

> **Workflow Étudiant (unifié) :**
> 1. L'étudiant accède à son espace et **déclare les informations de son stage** (entreprise, maître de stage, dates).
> 2. Le bouton "Enregistrer les infos stage" le **redirige automatiquement** vers l'**éditeur de rédaction**.
> 3. Il rédige son rapport via l'**éditeur de texte riche** intégré. Le système applique le **Modèle Institutionnel**.
> 4. **Auto-save** permanent. L'étudiant ne peut **déposer** que s'il atteint le **quota minimum de mots** (ex : 5 000 mots).
> 5. Le bouton **"📤 Déposer"** dans l'éditeur **soumet le tout** : infos stage + rapport + candidature de soutenance.
> 6. À la soumission, le système **génère le PDF**, **fige le travail** dans une version immuable, et **crée automatiquement la candidature de soutenance**.

---

## 2.1 Écran : ETU_INFO_STAGE — Déclaration de stage

**ID & TITRE :** `ETU_INFO_STAGE` — Déclaration de stage

**OBJECTIF :** Permettre à l'étudiant de **déclarer les informations de son stage** (entreprise, encadrant, dates). Après enregistrement, l'étudiant est **redirigé automatiquement** vers l'éditeur de rédaction. Il n'y a **plus de formulaire de candidature séparé** : la candidature est créée automatiquement lors du dépôt du rapport.

**VUE PRINCIPALE :** Segmentation Polarisée.
- Pôle Supérieur : Formulaire Infos Stage uniquement.
- Pôle Inférieur : Historique des dépôts (stage + rapport + candidature).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Section 1 : Informations de stage (Pôle Supérieur) :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Entreprise | Texte avec autocomplétion (`entreprises`) | **Oui** ★ | 150 | Vide |
| Date début stage | Date | **Oui** ★ | 10 | Vide |
| Date fin stage | Date | **Oui** ★ | 10 | Vide |
| Sujet de stage | Texte | **Oui** ★ | 200 | Vide |
| Encadrant entreprise (Maître de stage) | Texte | **Oui** ★ | 150 | Vide |
| Email encadrant | Email | **Oui** ★ | 100 | Vide |
| Téléphone encadrant | Texte | **Oui** ★ | 20 | Vide |

*Données affichées (Tableau — Historique des dépôts) :*

| Colonne | Source |
|---|---|
| N° Rapport | `rapport_etudiants.id_rapport` |
| Nom rapport | `rapport_etudiants.nom_rapport` |
| Date dépôt | `deposer.date_depot` |
| Statut rapport | `rapport_etudiants.statut_rapport` (badge) |
| Statut candidature | `candidature_soutenance.statut_candidature` (badge) |
| Commentaire Admin | `candidature_soutenance.commentaire_admin` |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Enregistrer les infos stage | Vert | POST → crée/MAJ `informations_stage`. Si entreprise inconnue, elle est ajoutée dans `entreprises`. **Redirige vers l'éditeur** `?page=gestion_rapports&action=creer_rapport`. |
| Annuler | Gris | Vide le formulaire |

**RÈGLES MÉTIER :**
- **Validation des dates de stage** : la durée minimale est de **6 mois**. Les dates ne peuvent pas être dans le futur.
- **Auto-complétion entreprise** : l'étudiant tape le nom, le système propose les entreprises existantes. Si l'entreprise n'existe pas, elle est automatiquement ajoutée dans `entreprises`.
- Après enregistrement des infos stage, l'étudiant est **redirigé automatiquement** vers l'**éditeur de rédaction du rapport**.
- **Plus de formulaire de candidature séparé** : la candidature est créée automatiquement lors du dépôt du rapport dans l'éditeur.
- Les champs N° Étudiant et Nom/Prénom sont automatiquement remplis depuis la session.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès stage : "Les informations du stage ont été enregistrées avec succès. Vous allez être redirigé vers l'éditeur de rédaction." → redirection.
- Erreur durée : "La période de stage doit être d'au minimum 6 mois. Durée actuelle : X mois et Y semaines."
- Erreur date future : "La date de début/fin ne peut pas être dans le futur."

**LA ROUTE :** `?page=candidature_soutenance`
- Infos stage : `?page=candidature_soutenance&action=info_stage` (POST)
- CR étudiant : `?page=candidature_soutenance&action=compte_rendu_etudiant`

**COMPORTEMENT DE LA PAGE :**
- L'étudiant voit le formulaire d'infos stage. S'il a déjà rempli les infos, celles-ci sont pré-remplies pour modification.
- **Pas de formulaire de candidature** : le bouton "Enregistrer infos stage" redirige directement vers l'éditeur.
- L'historique en bas montre les dépôts (rapport + candidature créée automatiquement).
- L'étudiant peut accéder à ses comptes rendus depuis cette page.

**Critères de filtrage :** Aucun (l'étudiant ne voit que SES données).

**Traçabilité :** Oui, logué dans `pister`.

**Sécurité :** Filtrage strict par `num_etu` de la session.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ DÉCLARATION DE STAGE                                    [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ N° Étudiant: CI0111272399 (auto)    Nom: Agnaramon Boris C. (auto)   │   │
│ │                                                                       │   │
│ │ Entreprise *                             Sujet de stage *            │   │
│ │ [🔍 Saisir ou sélectionner...]           [________________________]   │   │
│ │                                                                       │   │
│ │ Date début * [____/____/____]  Date fin * [____/____/____]           │   │
│ │                                                                       │   │
│ │ Maître de stage (encadrant) *    Email *            Tél. *           │   │
│ │ [_________________________]      [_____________]    [___________]     │   │
│ │                                                                       │   │
│ │         [Annuler]  [✔ Enregistrer et passer à la rédaction ▸]         │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ 💡 Après enregistrement, vous serez redirigé vers l'éditeur de rédaction.   │
│    Le dépôt du rapport créera automatiquement votre candidature.            │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │          MES DÉPÔTS                          Afficher: [▼10]          │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│N° Rap│Nom Rapport  │Dt Dépôt   │St. Rapport│St. Candidat.│Comment.│   │
│ │──┼──────┼─────────────┼───────────┼───────────┼─────────────┼────────│   │
│ │ ☐│   1  │Mon rapport  │10/01/2026 │🔵En cours │🟠En attente │   —    │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 2.2 Écran : ETUD_RAPPORT — Mes rapports (Rédaction & Suivi)

**ID & TITRE :** `ETUD_RAPPORT` — Gestion des rapports

**OBJECTIF :** Permettre à l'étudiant de **rédiger son rapport de stage directement sur la plateforme** via un éditeur de texte riche (WYSIWYG), de suivre l'avancement de ses rapports, et de soumettre son travail une fois le quota de mots atteint. La supériorité de CheckMaster réside dans l'**auto-save**, la **page de garde institutionnelle automatique** et le **contrôle strict de soumission**.

**VUE PRINCIPALE :** Dashboard rapports (page d'accueil) + Éditeur de rédaction (sous-écran).

---

### 2.2.1 Sous-Écran : RAPPORT_DASHBOARD — Dashboard des rapports

**ID & TITRE :** `RAPPORT_DASHBOARD` — Dashboard des rapports

**OBJECTIF :** Vue synthétique des rapports de l'étudiant avec statistiques et liens rapides.

**ÉLÉMENTS DE DONNÉES :**

| Widget | Source | Calcul |
|---|---|---|
| Rapports créés | `rapport_etudiants` WHERE `num_etu` = session | COUNT |
| Rapports en attente | Idem WHERE `statut_rapport = 'en_attente'` | COUNT |
| Rapports validés | Idem WHERE `statut_rapport = 'valider'` | COUNT |
| Rapports rejetés | Idem WHERE `statut_rapport = 'rejeter'` | COUNT |

*Tableau des rapports récents (limité aux 5 derniers) :*

| Colonne | Source |
|---|---|
| N° Rapport | `rapport_etudiants.id_rapport` |
| Nom | `rapport_etudiants.nom_rapport` |
| Thème | `rapport_etudiants.theme_rapport` |
| Statut | badge |
| Date rédaction | `rapport_etudiants.date_redaction_rapport` |
| Dépôt | Oui/Non (via `deposer`) |
| Actions | Modifier / Supprimer / Déposer |

**ACTIONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| ✏️ Nouveau rapport | Vert | Redirige vers `?page=gestion_rapports&action=creer_rapport` |
| ✏️ Modifier (ligne) | Bleu | Redirige vers éditeur avec `?page=gestion_rapports&action=creer_rapport&edit=ID` |
| 🗑 Supprimer | Rouge | Suppression du rapport (si non déposé) |
| 📤 Déposer | Vert | SOUMETTRE le rapport → crée l'entrée dans `deposer`, fige le rapport |

**LA ROUTE :** `?page=gestion_rapports`

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ MES RAPPORTS                                            [Année A.: 2025-26] │
│                                                                              │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐        │
│ │📋 CRÉÉS      │ │🟠 EN ATTENTE │ │✅ VALIDÉS    │ │❌ REJETÉS    │        │
│ │      2       │ │      1       │ │      0       │ │      1       │        │
│ └──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘        │
│                                                                              │
│                                              [✏️ Nouveau rapport]           │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │          MES RAPPORTS RÉCENTS                Afficher: [▼10]          │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│N°│Nom          │Thème         │Statut    │Dt Réd.  │Dépôt│Actions │   │
│ │──┼──┼─────────────┼──────────────┼──────────┼─────────┼─────┼────────│   │
│ │ ☐│ 1│Mon rapport  │Sys. d'info   │🔵En cours│15/01/26 │ Non │✏📤🗑  │   │
│ │ ☐│ 2│Audit SI     │Audit sécurité│🔴Rejeté  │20/01/26 │ Oui │✏      │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

### 2.2.2 Sous-Écran : RAPPORT_EDITEUR — Éditeur de rédaction (Rédiger/Modifier)

**ID & TITRE :** `RAPPORT_EDITEUR` — Éditeur de rédaction

**OBJECTIF :** Éditeur de texte riche intégré pour que l'étudiant rédige son rapport **directement dans la plateforme** sans logiciel tiers. Le système applique automatiquement le **Modèle de Rapport Institutionnel**.

**VUE PRINCIPALE :** Éditeur WYSIWYG pleine page.

**ÉLÉMENTS DE DONNÉES :**

*Champs de métadonnées (en haut de l'éditeur) :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Nom du rapport | Texte | **Oui** ★ | 60 | Vide |
| Thème du rapport | Texte | **Oui** ★ | 150 | Auto-rempli depuis `informations_stage.sujet_stage` |

*Éditeur :*

| Élément | Description |
|---|---|
| Éditeur WYSIWYG | Éditeur de texte riche (TinyMCE / Quill / CKEditor) |
| Contenu HTML | Sauvegardé dans un fichier `rapport_{id}.html` sur le serveur |
| Barre d'outils | B, I, U, H1-H6, Listes, Liens, Images, Tableaux |
| Compteur de mots | ⬇️ Barre inférieure : `0 / 5 000 mots minimum` |
| Indicateur auto-save | ⬇️ "Sauvegardé automatiquement à HH:MM:SS" |

*Page de garde institutionnelle (auto-générée) :*

| Élément auto-rempli | Source |
|---|---|
| Logo de l'entreprise | `entreprises.lien_logo_entreprise` (via `informations_stage`) |
| Thème du rapport | `informations_stage.sujet_stage` |
| Nom du maître de stage | `informations_stage.encadrant_entreprise` |
| Nom de l'étudiant | Session → `etudiants.nom_etu` + `prenom_etu` |
| Année académique | Session → année active |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| 💾 Sauvegarder | Bleu | POST AJAX → sauvegarde `rapport_etudiants` + fichier HTML (sans figer) |
| 📤 Déposer le tout (stage + rapport + candidature) | Vert (désactivé si < quota mots) | POST → `sauvegarder` + `enregistrerDepotRapport()` → crée `deposer` + crée `candidature_soutenance` automatiquement, change statut à `en_cours`, génère PDF, VERSION FIGÉE |
| 📄 Exporter PDF (aperçu) | Bleu | POST → génère PDF via Dompdf pour aperçu |
| ⬅️ Retour au tableau de bord | Gris | Retour vers `?page=gestion_rapports` |

**RÈGLES MÉTIER :**
- **Auto-save** : le contenu est sauvegardé automatiquement à intervalles réguliers (AJAX POST vers `sauvegarderContenuRapport`). Le fichier HTML est stocké dans `ressources/uploads/rapports/rapport_{id}.html`.
- **Modèle Institutionnel** : à la création, le système insère automatiquement un template de page de garde HTML avec le logo entreprise, le thème, le maître de stage. L'étudiant rédige ensuite après la page de garde.
- **Quota de mots** : un compteur de mots en temps réel est affiché. Le bouton "Déposer" est **grisé/désactivé** tant que le nombre de mots est inférieur au quota (ex : 5 000 mots).
- **Soumission (dépôt)** : une fois déposé, l'étudiant **ne peut plus modifier le rapport**. Le statut passe à `en_cours`. Le rapport est **figé** dans une version immuable. Le dépôt crée une entrée dans `deposer` ET crée automatiquement une entrée dans `candidature_soutenance` (statut "En attente").
- **Candidature automatique** : la candidature de soutenance n'est plus un formulaire séparé. Elle est créée automatiquement lors du dépôt du rapport.
- **Vérification doublon** : un étudiant ne peut pas avoir deux rapports avec le même nom.
- **Un seul rapport en cours** : si l'étudiant a déjà un rapport déposé non rejeté, il ne peut pas en soumettre un autre (`aUnRapportEnCours()`).
- **Rapport rejeté** : si le dernier rapport déposé est "rejeté" (via `approuver` + `niveau_approbation`), l'étudiant peut créer un nouveau rapport.
- **Génération PDF** : via **Dompdf**, le contenu HTML est converti en PDF avec styles CSS optimisés (pagination, police Arial, marges 40px). Le PDF peut être exporté en aperçu ou figé lors du dépôt.

**ÉTAT DE SORTIE / FEEDBACK :**
- Auto-save : "Sauvegardé automatiquement à HH:MM" (discret, barre inférieure).
- Sauvegarde manuelle : notification JSON `{"success": true, "message": "Rapport enregistré avec succès!"}`.
- Dépôt : "Rapport déposé et candidature soumise avec succès. Votre dossier est maintenant en attente d'évaluation." + rapport figé.
- Erreur doublon nom : "Vous avez déjà un rapport avec ce nom."
- Erreur dépôt déjà fait : "Ce rapport ne peut plus être modifié car il a déjà été déposé."
- Erreur quota : "Vous devez atteindre le minimum de 5 000 mots avant de pouvoir déposer."
- Erreur rapport en cours : "Vous avez déjà un rapport en cours d'évaluation."

**LA ROUTE :** `?page=gestion_rapports&action=creer_rapport` (nouveau) / `?page=gestion_rapports&action=creer_rapport&edit=ID` (modification)

**COMPORTEMENT DE LA PAGE :**
- L'éditeur prend toute la largeur de la zone de contenu.
- Les infos de stage (`stage_info`) sont récupérées et injectées dans la page de garde.
- En mode édition (`edit=ID`), le contenu HTML est chargé depuis le fichier `rapport_{id}.html`.
- Si le rapport est déjà déposé (`rapportDejaDepose = true`), l'éditeur passe en **lecture seule** et le bouton Déposer disparaît.
- Le compteur de mots se met à jour en temps réel à chaque frappe.
- L'auto-save envoie le contenu via AJAX toutes les 30 secondes (sans rechargement de page).
- Le bouton "Déposer" n'est cliquable que si quota atteint ET rapport non encore déposé.
- Connexion avec `ETU_CANDIDATURE` (les infos stage sont pré-requises) et `COM_RECEPTION_RAPPORT` (le rapport apparaît côté commission après dépôt).

**Critères de filtrage :** Aucun.

**Traçabilité :** Création/modification logguée dans `pister` (table `rapport_etudiants`).

**Sécurité :** Filtrage strict par session. Vérification que le rapport appartient à l'étudiant. Rapport déposé → en lecture seule.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ RÉDACTION DU RAPPORT                                    [Année A.: 2025-26] │
│                                                                              │
│ [⬅ Retour]                                                                  │
│                                                                              │
│ Nom du rapport * [Mon rapport de stage              ]                       │
│ Thème *          [Système d'information de gestion   ] (auto si stage)      │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [B] [I] [U] [S] │ H1 │ H2 │ H3 │ [•] [1.] │ [📎] [🖼] [📊] [🔗]   │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ╔════════════════════════════════════════════════════════════════════╗ │   │
│ │ ║          [LOGO ENTREPRISE]                                       ║ │   │
│ │ ║                                                                   ║ │   │
│ │ ║           UNIVERSITÉ FÉLIX HOUPHOUËT-BOIGNY                      ║ │   │
│ │ ║              UFR Mathématiques & Informatique                     ║ │   │
│ │ ║                                                                   ║ │   │
│ │ ║          RAPPORT DE STAGE DE FIN D'ÉTUDES                        ║ │   │
│ │ ║                                                                   ║ │   │
│ │ ║  Thème : Système d'information de gestion                        ║ │   │
│ │ ║                                                                   ║ │   │
│ │ ║  Maître de stage : M. KOUADIO Jean-Marc                          ║ │   │
│ │ ║  Étudiant : AGNARAMON Boris Carnot                               ║ │   │
│ │ ║  Année académique : 2025 - 2026                                  ║ │   │
│ │ ╚════════════════════════════════════════════════════════════════════╝ │   │
│ │                                                                       │   │
│ │ ─── PAGE DE GARDE (Auto-générée) ─────────────── Fin page de garde ── │   │
│ │                                                                       │   │
│ │ INTRODUCTION                                                          │   │
│ │                                                                       │   │
│ │ Dans le cadre de notre formation en Master 2 à l'UFR MI...            │   │
│ │ Ce rapport présente les travaux réalisés au sein de l'entreprise...   │   │
│ │                                                                       │   │
│ │ █ Le curseur est ici — l'étudiant rédige 📝                           │   │
│ │                                                                       │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ 📝 2 340 / 5 000 mots minimum │ 🔄 Sauvegardé à 01:35:22            │   │
│ │ ████████░░░░░░░░░░ 47%        │                                      │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ [💾 Sauvegarder]  [📄 Aperçu PDF]  [📤 Déposer le tout (désactivé ⛔)]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

### 2.2.3 Sous-Écran : RAPPORT_SUIVI — Suivi de rapport

**ID & TITRE :** `RAPPORT_SUIVI` — Suivi de rapport

**OBJECTIF :** Voir le détail d'un rapport spécifique : statut, commentaires des évaluateurs, historique des approbations.

**VUE PRINCIPALE :** Page de détail (lecture seule).

**ÉLÉMENTS DE DONNÉES :**

| Donnée | Source |
|---|---|
| Nom rapport | `rapport_etudiants.nom_rapport` |
| Thème | `rapport_etudiants.theme_rapport` |
| Statut | `rapport_etudiants.statut_rapport` |
| Date rédaction | `rapport_etudiants.date_redaction_rapport` |
| Date dépôt | `deposer.date_depot` |
| Commentaires évaluateurs | `evaluations_rapports` → commentaires, décision |
| Historique approbation | `approuver` JOIN `niveau_approbation` |

**LA ROUTE :** `?page=gestion_rapports&action=detail&id=ID`

---

## 2.3 Écran : ETU_RECLAMATION — Réclamations

**ID & TITRE :** `ETU_RECLAMATION` — Réclamations (côté étudiant)

**OBJECTIF :** Permettre à l'étudiant de soumettre une réclamation (note, absence, problème administratif) et de suivre son traitement.

**VUE PRINCIPALE :** Segmentation Polarisée (Formulaire de soumission + Historique).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Objet de la réclamation | Texte | **Oui** ★ | 150 | Vide |
| Description détaillée | Textarea | **Oui** ★ | 2000 | Vide |
| Pièce justificative | File upload | Non | — | Vide |

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| N° Réclamation | `reclamations.id_reclamation` |
| Objet | `reclamations.objet` |
| Date | `reclamations.date_reclamation` |
| Statut | `statut_reclamation.lib_statut` (badge) |
| Réponse | (si traitée) |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Soumettre | Vert | POST → crée entrée dans `reclamations` |
| Voir détails | Bleu | Panneau latéral avec détails + réponse de la scolarité |

**RÈGLES MÉTIER :**
- L'étudiant peut soumettre plusieurs réclamations.
- Le `num_carte_etud` est automatiquement celui de la session connectée.
- L'étudiant ne peut pas modifier une réclamation déjà soumise.
- L'étudiant voit la réponse de la scolarité une fois la réclamation traitée.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : "Réclamation soumise avec succès. Elle sera traitée dans les plus brefs délais."

**LA ROUTE :** `?page=gestion_reclamations`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée.
- Panneau latéral (Triangulation) pour voir le détail d'une réclamation et sa réponse.

**Critères de filtrage :** Statut.

**Traçabilité :** Oui.

**Sécurité :** L'étudiant ne voit que SES réclamations.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ MES RÉCLAMATIONS                                        [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Objet de la réclamation *                                             │   │
│ │ [____________________________________________________________]        │   │
│ │                                                                       │   │
│ │ Description détaillée *                                               │   │
│ │ ┌──────────────────────────────────────────────────────────────────┐   │   │
│ │ │                                                                  │   │   │
│ │ └──────────────────────────────────────────────────────────────────┘   │   │
│ │                                                                       │   │
│ │ Pièce justificative: [Parcourir...]                                  │   │
│ │                                                    [✔ Soumettre]      │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │      HISTORIQUE MES RÉCLAMATIONS             Afficher: [▼10]          │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│N° Récl.│  Objet          │ Date       │ Statut      │ Actions     │   │
│ │──┼────────┼─────────────────┼────────────┼─────────────┼─────────────│   │
│ │ ☐│    1   │ Note erronée    │ 10/01/2026 │ 🟠En cours  │   👁 Détails│   │
│ │ ☐│    2   │ Absence injust. │ 05/01/2026 │ 🟢Résolu    │   👁 Détails│   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 2.4 Écran : ETU_CONSULTATION_CR — Consultation du compte rendu

**ID & TITRE :** `ETU_CONSULTATION_CR` — Consultation du compte rendu

**OBJECTIF :** Permettre à l'étudiant de consulter le(s) compte(s) rendu(s) de commission le concernant.

**VUE PRINCIPALE :** Page de consultation (lecture seule).

**ÉLÉMENTS DE DONNÉES :**

| Donnée | Source |
|---|---|
| Nom du CR | `compte_rendu.nom_CR` |
| Contenu du CR | `compte_rendu.contenu_CR` |
| Date du CR | `compte_rendu.date_CR` |
| Fichier PDF | `compte_rendu.chemin_fichier_pdf` |

**ACTIONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Télécharger PDF | Bleu | Télécharge le fichier PDF du CR |

**RÈGLES MÉTIER :**
- L'étudiant ne voit que les CR le concernant.
- Lecture seule.

**LA ROUTE :** `?page=candidature_soutenance&action=compte_rendu_etudiant`

**Sécurité :** Filtrage strict par session utilisateur.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ CONSULTATION DES COMPTES RENDUS                         [Année A.: 2025-26] │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ 📄 Compte Rendu — Évaluation Rapport de stage                        │   │
│ │ Date: 15/02/2026                                                      │   │
│ │ ┌──────────────────────────────────────────────────────────────────┐   │   │
│ │ │ Le jury de la commission de validation, réuni le 15/02/2026,    │   │   │
│ │ │ a examiné le rapport de stage de M. AGNARAMON Boris Carnot...   │   │   │
│ │ │                                                                  │   │   │
│ │ │ Décision : Rapport VALIDÉ avec observations.                     │   │   │
│ │ └──────────────────────────────────────────────────────────────────┘   │   │
│ │                                                                       │   │
│ │ [📥 Télécharger PDF]                                                  │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────┘
```

(End of file - total 488 lines)
