## CONTRAINTTES ABSOLUES
- ❌ NE PAS modifier les contrôleurs dans `app/controllers/`
- ❌ NE PAS modifier les routes dans `config/routes.php`
- ❌ NE PAS modifier les modèles dans `app/models/`
- ✅ UNIQUEMENT créer de nouveaux fichiers dans views/pages`
# DESCRIPTION EXHAUSTIVE DES ÉCRANS — CHECKMASTER
## PARTIE 2 : COMMISSION & SOUTENANCE

---

## ═══════════════════════════════════════════
## MENU : COMMISSION
## ═══════════════════════════════════════════

---

### 📊 ÉCRAN COM-01 : Tableau de Bord Commission

**ID & TITRE :** `COM_DASHBOARD` — Tableau de bord commission

**OBJECTIF :** Fournir à la commission de validation une vue synthétique de l'état des rapports, des dossiers de soutenance et du processus de validation.

**VUE PRINCIPALE :** Dashboard (Widgets + Graphiques)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Donnée affichée | Source | Calcul |
|---|---|---|
| Rapports en attente | `rapport_etudiants` WHERE statut='en_attente' | COUNT |
| Rapports validés | `rapport_etudiants` WHERE statut='valider' | COUNT |
| Rapports rejetés | `rapport_etudiants` WHERE statut='rejeter' | COUNT |
| Rapports en cours | `rapport_etudiants` WHERE statut='en_cours' | COUNT |
| Taux de validation | rapports validés / total rapports | Pourcentage |
| Activités récentes | `pister` + `evaluations_rapports` | 10 dernières actions |
| Répartition par domaine | `programmer_soutenance` JOIN `domaine` | GROUP BY domaine |

**ACTIONS & BOUTONS :**
- Widgets cliquables renvoyant vers les écrans correspondants
- Aucune action de modification directe

**RÈGLES MÉTIER :**
- Données filtrées par l'année académique active
- Visible uniquement par les membres de la commission de validation et les enseignants administratifs

**ÉTAT DE SORTIE / FEEDBACK :** Aucun — lecture seule.

**LA ROUTE :** `?page=dashboard_commission`

**COMPORTEMENT DE LA PAGE :**
- Chargement des statistiques côté serveur via `DashboardCommissionController`
- Widgets avec animations de compteur
- Graphiques de répartition (camembert/barres)
- Liens vers : Réception rapports, Analyse, Suivi d'avancement

**Critères de filtrage :** Année académique (automatique)

**Traçabilité :** Oui — log d'accès dans `pister`

**Sécurité :** Accès `COM_DASHBOARD`. Membres commission + Admin uniquement.

---

### 📥 ÉCRAN COM-02 : Réception des Rapports de Stage

**ID & TITRE :** `COM_RECEPTION_RAPPORT` — Réception des rapports de stage

**OBJECTIF :** Réceptionner, vérifier la conformité et enregistrer l'arrivée des rapports déposés par les étudiants au niveau de la commission.

**VUE PRINCIPALE :** Segmentation polarisée (Zone d'actions + Tableau)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| N° Rapport | `rapport_etudiants.id_rapport` |
| Étudiant | JOIN `etudiants` (nom + prénom) |
| Thème | `rapport_etudiants.theme_rapport` |
| Date dépôt | JOIN `deposer.date_depot` |
| Statut | `rapport_etudiants.statut_rapport` |
| Version | `rapport_etudiants.version` |
| Fichier | Lien vers le fichier (chemin_fichier) |
| Encadrant | JOIN `affecter` WHERE role='encadrant' → `enseignants` |
| Directeur | JOIN `affecter` WHERE role='directeur' → `enseignants` |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Consulter | Bleu | Ouverture du contenu du rapport |
| Télécharger | Bleu | Téléchargement du fichier |
| Affecter évaluateur | Bleu | Attribution d'un enseignant pour évaluation |
| Approuver réception | Vert | Marque le rapport comme reçu/confirmé |

**RÈGLES MÉTIER :**
- Seuls les rapports ayant un dépôt confirmé (`deposer`) apparaissent
- L'affectation d'évaluateur nécessite la sélection d'un enseignant existant
- Le rapport doit avoir le fichier téléversé pour être réceptionné

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Rapport réceptionné et affecté à l'évaluateur [Nom] »

**LA ROUTE :** `?page=reception_rapport_com`

**COMPORTEMENT DE LA PAGE :**
- Tableau avec filtres de statut (badges colorés)
- Clic sur une ligne → panneau latéral avec détail complet
- Possibilité de consulter le contenu HTML du rapport directement
- Lien vers l'écran d'analyse et approbation

**Critères de filtrage :** Statut, Étudiant, Date de dépôt, Encadrant — logique ET

**Traçabilité :** Oui — log dans `pister`

**Sécurité :** Accès `COM_RECEPTION_RAPPORT`. Commission + Admin.

---

### ✅ ÉCRAN COM-03 : Analyse et Approbation des Rapports

**ID & TITRE :** `ANA_APP_RAPPORT` — Analyse et approbation des rapports

**OBJECTIF :** Permettre aux membres de la commission d'évaluer en profondeur les rapports, de rendre une décision (approuver/désapprouver) et de commenter.

**VUE PRINCIPALE :** Segmentation polarisée (Statistiques + Tableau avec détail)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Statistiques (en haut) :*
| Donnée | Source |
|---|---|
| Total dossiers | COUNT `rapport_etudiants` avec dépôt |
| En attente d'évaluation | Rapports sans évaluation dans `evaluations_rapports` |
| Approuvés | Rapports avec decision='valider' |
| Rejetés | Rapports avec decision='rejeter' |

*Tableau des dossiers :*
| Colonne | Source |
|---|---|
| Étudiant | JOIN `etudiants` |
| Thème | `rapport_etudiants.theme_rapport` |
| Encadrant | JOIN `affecter` → `enseignants` |
| Statut | `rapport_etudiants.statut_rapport` |
| Évaluations | COUNT `evaluations_rapports` par rapport |

*Détail d'un dossier (panneau latéral) :*
- Infos étudiant, thème, dates, contenu du rapport HTML, évaluations existantes

*Champs de saisie (décision) :*
| Champ | Type | Obligatoire |
|---|---|---|
| Décision | Boutons Approuver/Désapprouver | Oui |
| Commentaire | Textarea | Oui |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Approuver le rapport | Vert | INSERT dans `evaluations_rapports` avec decision='valider' + UPDATE `rapport_etudiants.statut_rapport` |
| Désapprouver le rapport | Rouge (alerte) | INSERT avec decision='rejeter' + UPDATE statut |
| Consulter rapport | Bleu | Affiche le contenu en HTML |
| Télécharger en PDF | Bleu | Génère le PDF via DOMPDF |
| Traiter décision (AJAX) | — | POST asynchrone pour enregistrer la décision |

**RÈGLES MÉTIER :**
- Un commentaire est obligatoire pour toute décision
- L'approbation se fait via la table `approuver` (approbation formelle) ET `evaluations_rapports`
- Workflow : en_attente → en_cours → valider/rejeter
- Un rapport déjà approuvé ne peut plus être désapprouvé
- Les évaluations multiples sont possibles (plusieurs membres de la commission)
- L'évaluateur est identifié par `id_evaluateur` dans `evaluations_rapports`

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Rapport approuvé avec succès » (bandeau vert)
- Erreur : « Paramètres manquants » / « Rapport non trouvé »

**LA ROUTE :** `?page=evaluation_dossiers`  
- Détail : `?page=evaluations_dossiers_soutenance&detail={id}`  
- Action AJAX : `?page=evaluations_dossiers_soutenance&action=traiter_decision`

**COMPORTEMENT DE LA PAGE :**
- Statistiques en haut avec compteurs
- Tableau des dossiers en bas avec pagination
- Clic sur un dossier → panneau latéral avec contenu complet du rapport
- Boutons d'approbation/désapprobation dans le panneau latéral
- Appels AJAX pour les décisions (pas de rechargement de page)
- PDF généré via DOMPDF avec en-tête institutionnel

**Critères de filtrage :** Statut, Étudiant, Date, Encadrant — logique ET

**Traçabilité :** Oui — log dans `pister` + `evaluations_rapports` (qui a évalué, quand, décision)

**Sécurité :** Accès `ANA_APP_RAPPORT`. Commission uniquement. L'étudiant ne voit pas les évaluations individuelles.

---

### 📈 ÉCRAN COM-04 : Suivi d'Avancement

**ID & TITRE :** `SUIVI_VALIDATION_COM` — Suivi d'avancement

**OBJECTIF :** Visualiser la progression globale du processus de validation des dossiers et identifier les goulots d'étranglement.

**VUE PRINCIPALE :** Dashboard / Timeline

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Donnée | Source |
|---|---|
| Pipeline de validation | `rapport_etudiants` GROUP BY statut |
| Étapes par dossier | JOIN `evaluations_rapports` + `approuver` + `valider` |
| Temps moyen de traitement | diff(date_depot, date_evaluation) |
| Dossiers bloqués | Rapports en_attente depuis > X jours |

**ACTIONS & BOUTONS :**
- Aucune action directe (consultation)
- Liens vers les dossiers individuels

**RÈGLES MÉTIER :**
- Affiche la progression de chaque dossier dans le workflow
- Alerte si un dossier est bloqué depuis plus de 7 jours

**ÉTAT DE SORTIE / FEEDBACK :** Aucun — lecture seule.

**LA ROUTE :** `?page=processus_validation`

**COMPORTEMENT DE LA PAGE :**
- Vue pipeline : colonnes par statut (style Kanban)
- Chaque carte = un dossier avec résumé
- Indicateurs visuels de durée (vert=rapide, orange=normal, rouge=en retard)
- Connecté à l'écran d'analyse pour les actions

**Critères de filtrage :** Statut, Période — logique ET

**Traçabilité :** Non (lecture seule)

**Sécurité :** Accès `SUIVI_VALIDATION_COM`.

---

### ✏️ ÉCRAN COM-05 : Rédaction du Compte Rendu

**ID & TITRE :** `COM_REDACTION_CR` — Rédaction du CR

**OBJECTIF :** Permettre aux membres de la commission de rédiger les comptes rendus d'évaluation des rapports, avec possibilité de brouillon et export PDF.

**VUE PRINCIPALE :** Éditeur de texte riche (pas de segmentation polarisée classique)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| Étudiant concerné | Liste déroulante | Oui | — |
| Nom du CR | Texte (varchar 70) | Oui | Vide |
| Contenu du CR | Éditeur riche (longtext) | Oui | Vide |
| Rapports associés | Multi-sélection | Non | — |

*Sources :*
- Étudiant → `etudiants` (auto-remplissage)
- Rapports → `rapport_etudiants` de l'étudiant sélectionné

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Enregistrer (brouillon) | Bleu | INSERT/UPDATE dans `compte_rendu` |
| Publier | Vert | Publication du CR (visible par l'étudiant) |
| Exporter en PDF | Bleu | Génération du PDF via DOMPDF |

**RÈGLES MÉTIER :**
- Le CR peut être sauvegardé en brouillon avant publication
- La publication rend le CR visible pour l'étudiant concerné
- Un CR peut être lié à plusieurs rapports via `compte_rendu_rapport`
- L'enseignant auteur est enregistré dans `rendre` (id_CR, id_enseignant, date_env)
- Le chemin du PDF généré est stocké dans `compte_rendu.chemin_fichier_pdf`

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Compte rendu enregistré avec succès » / « CR exporté en PDF »
- Document PDF généré et téléchargeable

**LA ROUTE :** `?page=redaction_compte_rendu`  
- Brouillons : `?page=redaction_compte_rendu&action=brouillons`  
- Archives : `?page=redaction_compte_rendu&action=archives`  
- Export PDF : POST `?page=redaction_compte_rendu&action=export_pdf`

**COMPORTEMENT DE LA PAGE :**
- Éditeur WYSIWYG pour le contenu
- Auto-remplissage étudiant lors de la sélection
- Onglets : Rédaction / Brouillons / Archives
- Connectée à l'écran "Consultation CR" côté étudiant

**Critères de filtrage :** Étudiant, Date, Statut (brouillon/publié) — dans la vue archives

**Traçabilité :** Oui — `rendre` enregistre qui a rédigé/envoyé le CR et quand

**Sécurité :** Accès `COM_REDACTION_CR`. Commission + Admin. L'étudiant ne peut que consulter (via `ETU_CONSULTATION_CR`).

---

## ═══════════════════════════════════════════
## MENU : ESPACE ENSEIGNANT
## ═══════════════════════════════════════════

---

### 📊 ÉCRAN ENS-01 : Tableau de Bord Enseignant

**ID & TITRE :** `DASH_ENSEIGNANT` — Tableau de bord enseignant

**OBJECTIF :** Offrir à l'enseignant une vue globale de ses activités : étudiants encadrés, rapports à évaluer, soutenances programmées.

**VUE PRINCIPALE :** Dashboard (Widgets)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Donnée | Source |
|---|---|
| Étudiants encadrés | `affecter` WHERE id_enseignant = session + role='encadrant' |
| Rapports à évaluer | `evaluations_rapports` non traités |
| Soutenances planifiées | `enseignant_jury` JOIN `programmer_soutenance` |
| Activités récentes | `pister` de l'enseignant |

**ACTIONS & BOUTONS :**
- Widgets cliquables vers les détails

**RÈGLES MÉTIER :**
- L'enseignant ne voit que SES données
- Les enseignants administratifs ont des widgets supplémentaires

**ÉTAT DE SORTIE / FEEDBACK :** Lecture seule.

**LA ROUTE :** `?page=dashboard_enseignant`

**COMPORTEMENT DE LA PAGE :**
- Chargement personnalisé selon le profil enseignant
- Widgets avec compteurs
- Lien vers "Mes Étudiants"

**Critères de filtrage :** Année académique

**Traçabilité :** Oui

**Sécurité :** Accès `DASH_ENSEIGNANT`. Vue limitée aux données personnelles.

---

### 👨‍🎓 ÉCRAN ENS-02 : Mes Étudiants

**ID & TITRE :** Mes Étudiants (Enseignant)

**OBJECTIF :** Consulter la liste des étudiants sous la responsabilité de l'enseignant (selon son rôle : encadrant, responsable filière, responsable niveau).

**VUE PRINCIPALE :** Tableau

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Colonne | Source |
|---|---|
| N° Carte | `etudiants.num_carte_etud` |
| Nom/Prénom | `etudiants` |
| Niveau | JOIN `niveau_etude` |
| Promotion | `etudiants.promotion_etu` |
| Statut rapport | JOIN `rapport_etudiants.statut_rapport` |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Consulter dossier | Bleu | Panneau latéral avec détail étudiant |
| Exporter | Bleu | Export de la liste |

**RÈGLES MÉTIER :**
- **Enseignant simple** : voit uniquement les étudiants qu'il encadre (via `affecter`)
- **Responsable filière** : voit tous les étudiants MIAGE
- **Responsable niveau** : voit tous les étudiants de son niveau (via `niveau_etude.id_enseignant`)

**ÉTAT DE SORTIE / FEEDBACK :** Lecture seule.

**LA ROUTE :**
- Enseignant simple : `?page=liste_etudiants_ens_simple`
- Responsable filière : `?page=liste_etudiants_resp_filiere`
- Responsable niveau : `?page=liste_etudiants_resp_niveau`

**COMPORTEMENT DE LA PAGE :**
- Même contrôleur (`GestionEtudiantController`) mais données filtrées selon le rôle
- Tableau avec pagination et recherche
- Panneau latéral pour le détail

**Critères de filtrage :** Nom, Niveau, Promotion, Statut rapport — logique ET

**Traçabilité :** Non

**Sécurité :** Filtrage automatique selon l'identité connectée. Données sensibles masquées.

---

## ═══════════════════════════════════════════
## MENU : SOUTENANCE
## ═══════════════════════════════════════════

---

### 👥 ÉCRAN SOU-01 : Composition de Jury

**ID & TITRE :** `SOUT_COMPOS_JURY` — Composition de jury

**OBJECTIF :** Programmer les soutenances et composer les jurys en affectant les enseignants aux rôles requis (Président, Directeur mémoire, Examinateur, Encadrant, Maître de stage).

**VUE PRINCIPALE :** Segmentation polarisée (Formulaire + Tableau)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| Étudiant | Liste déroulante (auto-remplissage) | Oui | — |
| Thème soutenance | Texte (varchar 200) | Oui | Pré-rempli depuis rapport |
| Domaine | Liste déroulante | Oui | — |
| Session | Liste déroulante | Oui | — |
| Salle | Liste déroulante | Non | — |
| Date soutenance | Date | Non | — |
| Heure soutenance | Heure | Non | — |
| Membre jury (enseignant) | Liste déroulante | Oui | — |
| Rôle jury | Liste déroulante | Oui | — |

*Sources :*
- Étudiant → `etudiants` (candidature validée uniquement)
- Domaine → `domaine` (6 domaines)
- Session → `session` (Mai, Octobre, Décembre)
- Salle → `salles` (6 salles)
- Enseignant → `enseignants`
- Rôle jury → `qualite_jury` (Président, Directeur mémoire, Examinateur, Encadrant, Maître de stage)

*Données affichées :*
- Tableau des soutenances programmées avec composition du jury

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Créer attribution | Vert | INSERT dans `programmer_soutenance` + `enseignant_jury` |
| Modifier | Bleu | UPDATE de l'attribution |
| Supprimer | Bleu | DELETE de l'attribution |

**RÈGLES MÉTIER :**
- Un jury doit avoir au minimum : 1 Président, 1 Directeur mémoire
- Le Président doit être un Professeur Titulaire (grade = 'PT')
- Un enseignant ne peut pas être dans deux jurys à la même date/heure
- L'étudiant doit avoir sa candidature validée
- Le thème est pré-rempli depuis le rapport validé
- Le `num_soutenance` est la clé primaire (format à définir)

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Jury composé avec succès pour l'étudiant [Nom] »
- Erreur : « L'enseignant est déjà affecté à un autre jury ce jour »

**LA ROUTE :** `?page=programmation_soutenance` (API AJAX)

**COMPORTEMENT DE LA PAGE :**
- Auto-remplissage du thème lors de la sélection de l'étudiant
- Les enseignants sont chargés dynamiquement (AJAX `getEnseignants`)
- Filtrage des Professeurs Titulaires pour le rôle Président (`getProfesseursTitulaires`)
- Composition du jury dans un formulaire dynamique (ajout/suppression de membres)

**Critères de filtrage :** Session, Date, Domaine, Salle — logique ET

**Traçabilité :** Oui

**Sécurité :** Accès `SOUT_COMPOS_JURY`. Admin + Secrétaire uniquement.

---

### 📝 ÉCRAN SOU-02 : Évaluation Soutenance

**ID & TITRE :** `SOUT_EVALUATION` — Évaluation Soutenance

**OBJECTIF :** Saisir les notes d'évaluation de la soutenance selon les critères définis et leur barème par année académique.

**VUE PRINCIPALE :** Formulaire d'évaluation + Tableau récapitulatif

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| Étudiant | Liste déroulante | Oui | — |
| Jury | Liste déroulante | Oui | — |
| Critère : Exposé (EX) | Numérique (note/barème) | Oui | 0 |
| Critère : Réponses questions (RQ) | Numérique | Oui | 0 |
| Critère : Présentation mémoire (PM) | Numérique | Oui | 0 |
| Critère : Contenu mémoire (CM) | Numérique | Oui | 0 |
| Critère : Résolution problème (RP) | Numérique | Oui | 0 |

*Sources :*
- Critères → `critere_evaluation` (5 critères définis)
- Barèmes → `bareme_critere` (barème par critère et par année académique)
- Jury → `enseignant_jury`

*Calculs :*
- Note totale = Σ (note × coefficient/barème) par critère
- Moyenne pondérée selon les barèmes de l'année

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Enregistrer évaluation | Vert | INSERT dans `evaluer` |
| Supprimer évaluation | Bleu | DELETE de `evaluer` |
| Imprimer PV | Bleu | Génération PDF du PV de soutenance |

**RÈGLES MÉTIER :**
- Chaque note doit être ≤ au barème du critère pour l'année
- Les critères et barèmes sont chargés dynamiquement selon l'année académique (`getCriteresParAnnee`)
- Si une évaluation existe déjà → mode modification (`getEvaluationExistante`)
- Le PV de soutenance est imprimable en PDF

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Évaluation enregistrée avec succès. Note totale : XX/20 »
- Document : PDF du PV de soutenance généré

**LA ROUTE :** `?page=evaluation_soutenance`

**COMPORTEMENT DE LA PAGE :**
- Sélection de l'étudiant → chargement dynamique du jury et des critères
- Vérification de l'existence d'une évaluation précédente (AJAX)
- Calcul en temps réel de la note totale
- Export PV en PDF via `?page=evaluation_soutenance&action=imprimer_pv`

**Critères de filtrage :** Année académique, Session — logique ET

**Traçabilité :** Oui

**Sécurité :** Accès `SOUT_EVALUATION`. Seuls les membres du jury de l'étudiant peuvent évaluer.

---

### 📄 ÉCRAN SOU-03 : Édition des Bulletins

**ID & TITRE :** `SOUT_EDITION_BULLETIN` — Édition des bulletins

**OBJECTIF :** Générer et éditer les bulletins de résultats de soutenance pour les étudiants ayant soutenu.

**VUE PRINCIPALE :** Tableau + Génération PDF

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Donnée | Source |
|---|---|
| Étudiant | `etudiants` |
| Notes soutenance | `evaluer` (par critère) |
| Note totale | Calcul pondéré des critères |
| Mention | Calculée selon la note totale |
| Décision du jury | `decisions_jury` si applicable |
| Date soutenance | `programmer_soutenance.date_soutenance` |
| Composition jury | `enseignant_jury` JOIN `enseignants` + `qualite_jury` |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Générer bulletin | Bleu | Création du PDF du bulletin |
| Imprimer | Bleu | Impression directe |
| Exporter lot | Bleu | Génération en masse |

**RÈGLES MÉTIER :**
- Le bulletin ne peut être généré que si l'évaluation est complète (tous les critères notés)
- La mention est calculée automatiquement selon les seuils
- Le bulletin intègre les informations institutionnelles (UFHB, UFRMI)

**ÉTAT DE SORTIE / FEEDBACK :**
- Document PDF généré : Bulletin de soutenance

**LA ROUTE :** `?page=edition_bulletin`

**COMPORTEMENT DE LA PAGE :**
- Liste des étudiants ayant soutenu
- Clic → aperçu du bulletin
- Bouton génération PDF
- Export en masse possible

**Critères de filtrage :** Session, Année, Domaine

**Traçabilité :** Oui

**Sécurité :** Accès `SOUT_EDITION_BULLETIN`. Admin + Secrétaire.
