# 📄 Système d'Impression des Procès-Verbaux - 3 Annexes en 1 PDF

## ✅ Fonctionnalité Implémentée

Le système génère maintenant **un seul document PDF contenant les 3 annexes** sur 3 pages différentes :

### Page 1 - Annexe 1 : Soutenance de Mémoire

- Liste détaillée des critères d'évaluation avec notes et barèmes
- Note finale = somme des notes attribuées
- Signatures du jury (Président, Examinateur, Directeur, Encadreur, Maître de stage)

### Page 2 - Annexe 2 : PV Jury Standard

- 3 éléments évalués :
  - Moyenne Générale (coefficient 1)
  - Note de Soutenance (coefficient 1)
  - Note de Mémoire (coefficient 2)
- Note finale calculée avec pondération : `(MG × 1 + NS × 1 + NM × 2) / 4`
- Mention automatique (Très Bien, Bien, Assez Bien, Passable, Insuffisant)

### Page 3 - Annexe 3 : PV Jury Formation Continue

- 2 critères :
  - Moyenne Générale Master 1 (coefficient 1) - **Saisie manuelle requise**
  - Note de Mémoire (coefficient 2)
- Note finale : `(M1 × 1 + NM × 2) / 3`
- Mention automatique

---

## 🎯 Comment Utiliser

### Étape 1 : Évaluer un étudiant

1. Aller sur la page **Évaluation Soutenance**
2. Sélectionner un étudiant dans la liste déroulante
3. Remplir les notes pour chaque critère
4. Cliquer sur **Enregistrer l'évaluation**

### Étape 2 : Générer le PDF avec les 3 annexes

1. Dans le tableau des évaluations, localiser l'étudiant évalué
2. Cliquer sur le bouton **"🖨️ Imprimer PV"**
3. Une fenêtre modale s'ouvre
4. Saisir la **Moyenne Générale Master 1** (requise pour l'Annexe 3)
5. Cliquer sur **"Générer les 3 PV"**
6. Le PDF s'ouvre dans un nouvel onglet avec les 3 pages

---

## 📋 Structure du Système

### Fichiers Modifiés/Créés

#### Contrôleur

- `app/controllers/EvaluationSoutenanceController.php`
  - Méthode `imprimerPV()` : Génère les 3 annexes dans un seul PDF
  - Utilise `<div style="page-break-after: always;"></div>` pour séparer les pages

#### Templates PDF

- `ressources/views/pv_soutenance/annexe1.php` - Template Annexe 1
- `ressources/views/pv_soutenance/annexe2.php` - Template Annexe 2
- `ressources/views/pv_soutenance/annexe3.php` - Template Annexe 3

#### Routes

- `ressources/routes/evaluationSoutenanceRoutes.php`
  - Route : `?page=evaluation_soutenance&action=imprimer_pv&num_etu=XXX&moyenne_master1=YY`

#### Interface Utilisateur

- `ressources/views/evaluation_soutenance_content.php`
  - Bouton "Imprimer PV" dans chaque ligne du tableau
  - Modal pour saisir la moyenne Master 1
  - JavaScript pour gérer le modal et la soumission

---

## ⚙️ Paramètres Techniques

### URL de Génération

```
?page=evaluation_soutenance&action=imprimer_pv&num_etu=[NUM]&moyenne_master1=[NOTE]
```

### Paramètres

- `num_etu` (requis) : Numéro de l'étudiant
- `moyenne_master1` (optionnel) : Moyenne Master 1 pour Annexe 3 (par défaut : 12.0)

### Bibliothèque PDF

- **Dompdf** : Génération de PDF à partir du HTML
- Configuration : A4 Portrait, Police DejaVu Sans

---

## 🔧 Points Techniques Importants

### 1. Séparation des Pages

Le contrôleur combine les 3 templates HTML avec des sauts de page :

```php
$htmlComplet = $htmlAnnexe1 . '<div style="page-break-after: always;"></div>' .
               $htmlAnnexe2 . '<div style="page-break-after: always;"></div>' .
               $htmlAnnexe3;
```

### 2. Données Spécifiques par Annexe

Chaque annexe reçoit son propre tableau `$data` avec les variables nécessaires :

- Annexe 1 : `criteres[]`, `note_finale`, `total_bareme`
- Annexe 2 : `moyenne_generale`, `note_soutenance`, `note_memoire`, coefficients
- Annexe 3 : `moyenne_master1`, `note_memoire`, coefficients

### 3. Mention Automatique

La fonction `calculerMention($noteTotale)` retourne :

- ≥ 16 : Très Bien
- ≥ 14 : Bien
- ≥ 12 : Assez Bien
- ≥ 10 : Passable
- < 10 : Insuffisant

### 4. Logo

Le logo `FHB.png` est inclus dans les en-têtes des 3 annexes.
Chemin : `public/images/FHB.png`

---

## 🐛 Dépannage

### Problème : PDF vide ou erreur

**Solution** : Vérifier que l'étudiant a bien été évalué (notes enregistrées dans la table `evaluer`)

### Problème : Données manquantes dans le PDF

**Solution** : Vérifier que :

- Le jury est bien assigné à la soutenance
- Les rôles du jury sont correctement définis (Président, Examinateur, Directeur, Encadreur)
- Le maître de stage est renseigné dans `informations_stage`

### Problème : Moyenne Master 1 toujours à 12.0

**Solution** : Saisir la valeur dans le modal lors de la génération du PDF

---

## 📊 Base de Données

### Tables Utilisées

- `evaluer` : Notes attribuées par critère
- `critere_evaluation` : Libellés des critères
- `correspondre` : Barèmes par année académique
- `programmer` : Soutenances programmées
- `etudiants` : Informations des étudiants
- `composer_jury` : Membres du jury
- `enseignants` : Noms des enseignants du jury
- `roles_jury` : Rôles (Président, Examinateur, etc.)
- `informations_stage` : Maître de stage

---

## 🎨 Design

- En-têtes avec logo et titre de l'annexe
- Tableaux avec bordures noires
- Zones de signature
- Formules de calcul visibles
- Mentions en gras
- Style professionnel adapté aux documents officiels

---

## ✅ Tests à Effectuer

1. ✅ Évaluer un étudiant avec toutes les notes
2. ✅ Cliquer sur "Imprimer PV"
3. ✅ Saisir une moyenne Master 1 (ex: 14.50)
4. ✅ Vérifier que le PDF contient bien 3 pages
5. ✅ Vérifier que les données sont correctes sur chaque page
6. ✅ Vérifier que les mentions sont correctement calculées
7. ✅ Vérifier que les signatures sont présentes

---

## 📝 Notes

- La moyenne générale pour l'Annexe 2 est actuellement fixée à 12.5 (à adapter selon vos besoins)
- Les coefficients sont paramétrables dans le contrôleur
- Le format de date est automatiquement converti en DD/MM/YYYY
- Le PDF s'ouvre dans un nouvel onglet (target="\_blank")

---

**Date de dernière mise à jour** : 15 octobre 2025
**Version** : 2.0 - Génération unique des 3 annexes
