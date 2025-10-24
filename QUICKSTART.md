# Guide de démarrage rapide - Permissions CRUD

Ce guide vous aide à configurer et utiliser le nouveau système de permissions en 10 minutes.

## Pour les administrateurs

### Étape 1 : Accéder à l'interface (1 min)

1. Connectez-vous avec votre compte administrateur
2. Allez dans le menu : **Paramètres Généraux**
3. Cliquez sur : **Gestion des Attributions**

Vous verrez l'interface de gestion des permissions.

### Étape 2 : Comprendre l'interface (2 min)

```
┌─────────────────────────────────────────────────────┐
│ Groupes (gauche)    │    Matrice CRUD (droite)      │
│                     │                                │
│ ▸ Administrateurs   │  Fonctionnalité │ C│A│M│S    │
│ ▸ Enseignants       │  ─────────────────────────    │
│ ▸ Étudiants         │  Utilisateurs  │☑│☑│☑│☑    │
│ ▸ Scolarité         │  Étudiants     │☑│☐│☑│☐    │
│ ▸ ...               │  Notes         │☑│☐│☐│☐    │
└─────────────────────────────────────────────────────┘
```

**Colonnes de la matrice :**
- **C** = Consulter (voir la page)
- **A** = Ajouter (créer de nouveaux éléments)
- **M** = Modifier (modifier des éléments existants)
- **S** = Supprimer (supprimer des éléments)

### Étape 3 : Configurer les permissions (5 min)

#### Exemple 1 : Rôle "Consultation seule"

Pour un groupe qui peut **uniquement voir** les informations :

1. Sélectionnez le groupe (ex: "Consultants")
2. Pour chaque fonctionnalité autorisée :
   - ☑ Cochez "Consulter" (C)
   - ☐ Laissez décoché "Ajouter", "Modifier", "Supprimer"

**Résultat :** L'utilisateur pourra voir les pages mais aucun bouton d'action n'apparaîtra.

#### Exemple 2 : Rôle "Contributeur"

Pour un groupe qui peut **voir et ajouter** mais pas modifier :

1. Sélectionnez le groupe (ex: "Assistants")
2. Pour chaque fonctionnalité :
   - ☑ Cochez "Consulter" (C)
   - ☑ Cochez "Ajouter" (A)
   - ☐ Laissez décoché "Modifier" et "Supprimer"

**Résultat :** L'utilisateur verra les boutons "Ajouter" mais pas "Modifier" ou "Supprimer".

#### Exemple 3 : Rôle "Gestionnaire"

Pour un groupe avec **tous les droits** :

1. Sélectionnez le groupe (ex: "Responsables")
2. Pour chaque fonctionnalité :
   - ☑ Cochez tout : C, A, M, S

**Résultat :** L'utilisateur verra et pourra utiliser tous les boutons.

### Étape 4 : Tester (2 min)

1. Créez un utilisateur de test dans le groupe configuré
2. Connectez-vous avec ce compte
3. Vérifiez que les boutons correspondent aux permissions

**Important :** Les modifications sont **automatiques**. Pas besoin de sauvegarder.

---

## Pour les utilisateurs

### Que vais-je voir ?

Selon vos permissions, vous verrez :

| Permission | Ce que vous voyez |
|------------|-------------------|
| **Aucune** | Fonctionnalité absente du menu |
| **Consulter seul** | Page visible, aucun bouton d'action |
| **Consulter + Ajouter** | Bouton "Ajouter" visible uniquement |
| **Consulter + Modifier** | Bouton "Modifier" visible |
| **Consulter + Supprimer** | Bouton "Supprimer" visible |
| **Toutes** | Tous les boutons visibles |

### Si vous essayez une action non autorisée

Vous verrez un message :
```
❌ Accès refusé : vous n'avez pas les permissions nécessaires pour cette action.
```

### En cas de problème

1. **Déconnectez-vous et reconnectez-vous** (cela recharge vos permissions)
2. Si le problème persiste, contactez votre administrateur

---

## Cas d'usage courants

### 🔹 Gestion des étudiants

**Groupe "Scolarité"** : Peut tout faire
- ☑ Consulter, ☑ Ajouter, ☑ Modifier, ☑ Supprimer

**Groupe "Enseignants"** : Peut consulter et modifier (notes, présence)
- ☑ Consulter, ☐ Ajouter, ☑ Modifier, ☐ Supprimer

**Groupe "Étudiants"** : Peut uniquement consulter leurs infos
- ☑ Consulter, ☐ Ajouter, ☐ Modifier, ☐ Supprimer

### 🔹 Gestion des notes

**Groupe "Enseignants"** : Peut saisir et modifier les notes
- ☑ Consulter, ☑ Ajouter, ☑ Modifier, ☐ Supprimer

**Groupe "Scolarité"** : Peut tout faire pour corrections
- ☑ Consulter, ☑ Ajouter, ☑ Modifier, ☑ Supprimer

**Groupe "Étudiants"** : Peut uniquement consulter
- ☑ Consulter, ☐ Ajouter, ☐ Modifier, ☐ Supprimer

### 🔹 Paramètres système

**Groupe "Administrateurs"** : Contrôle total
- ☑ Consulter, ☑ Ajouter, ☑ Modifier, ☑ Supprimer

**Tous les autres groupes** : Aucun accès
- ☐ Consulter, ☐ Ajouter, ☐ Modifier, ☐ Supprimer

---

## Questions fréquentes

### Q : Les modifications sont-elles immédiates ?
**R :** Oui pour les nouveaux accès. Pour les utilisateurs déjà connectés, ils doivent se déconnecter et se reconnecter.

### Q : Puis-je copier les permissions d'un groupe à un autre ?
**R :** Pas encore dans l'interface. Pour l'instant, il faut configurer manuellement chaque groupe.

### Q : Que se passe-t-il si je décoche tout ?
**R :** L'utilisateur n'aura plus accès à la fonctionnalité (elle disparaît du menu).

### Q : Puis-je donner des permissions à un utilisateur spécifique ?
**R :** Non, les permissions sont au niveau du groupe. Créez un nouveau groupe si nécessaire.

### Q : Comment savoir qui a modifié les permissions ?
**R :** Toutes les modifications sont enregistrées dans les logs d'audit (visible dans l'interface d'audit).

### Q : Puis-je annuler une modification ?
**R :** Oui, il suffit de recocher/décocher la case. Les modifications sont réversibles.

---

## Conseils de sécurité

✅ **Principe du moindre privilège** : Donnez uniquement les permissions nécessaires

✅ **Testez avant de déployer** : Créez un utilisateur test pour vérifier

✅ **Revoyez régulièrement** : Vérifiez les permissions tous les 3-6 mois

✅ **Documentez vos choix** : Notez pourquoi un groupe a certaines permissions

❌ **Ne donnez pas tout à tout le monde** : Même si c'est plus simple

❌ **N'oubliez pas le "Consulter"** : Sans READ, l'utilisateur ne voit rien

---

## Aide rapide

| Je veux... | Je fais... |
|------------|------------|
| Qu'un utilisateur voie une page | ☑ Consulter |
| Qu'il puisse ajouter | ☑ Consulter + ☑ Ajouter |
| Qu'il puisse modifier | ☑ Consulter + ☑ Modifier |
| Qu'il puisse supprimer | ☑ Consulter + ☑ Supprimer |
| Lui retirer l'accès | ☐ Tout décocher |
| Lui donner tous les droits | ☑ Tout cocher |

---

## Support

- 📖 Documentation complète : `README_PERMISSIONS.md`
- 🔧 Guide technique : `GUIDE_PERMISSIONS.md`
- 🚀 Guide migration : `MIGRATION_GUIDE.md`

**Problème ?** Créez une issue sur GitHub ou contactez l'équipe technique.

---

## Résumé en 30 secondes

1. **Menu** : Paramètres Généraux → Gestion des Attributions
2. **Sélectionner** un groupe d'utilisateurs
3. **Cocher/décocher** les cases C/A/M/S pour chaque fonctionnalité
4. **C'est fait !** Les modifications sont automatiques

🎯 **Règle d'or** : Au minimum, cochez "Consulter" (C) si vous voulez que l'utilisateur accède à la fonctionnalité.
