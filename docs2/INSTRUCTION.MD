# CONTRAINTTES ABSOLUES
- ❌ NE PAS modifier les contrôleurs dans `app/controllers/` (Mais il faut les lire pour comprendre)
- ❌ NE PAS modifier les routes dans `config/routes.php` (Mais il faut les lire pour comprendre)
- ❌ NE PAS modifier les modèles dans `app/models/` (Mais il faut les lire pour comprendre)
- ❌ Pas de slide panel, il y a la mention dans les instructions mais il ne faut pas le faire, pas les utiliser, ignore les.
- ❌ UE ET ECUE à retirer, il faut les supprimer complètement du projet, pas juste les cacher, il faut les supprimer de la base de données, des modèles, des contrôleurs, des vues, des routes, de tout le projet
- ❌ Pas de modal, il faut éviter les modals à tout prix, il faut faire en sorte que tout se fasse dans la même page, pas de pop-up, pas de fenêtre qui s'ouvre, pas de fenêtre qui bloque l'utilisateur, tout doit se faire dans la même page, avec le système de segmentation polarisée.
- ✅ La segmentation polarisée est obligatoire, il faut toujours avoir un pôle supérieur statique (saisie/validation) + pôle inférieur défilant, la segmentation en elle même ne doit absolument pas bougé, seulement le contenu du pôle inférieur doit bougé avec les différents boutons d'actions et le defilement du tableau.
- Palette UI obligatoire:
   * `primary: #1a5276` pour la sidebar.
   * `primary-lighter: #3498db` pour le fond global de l'application.
- Boutons/actions: uniquement nuances de bleu ou vert; ne pas utiliser orange/rouge pour des actions.
- Couleurs d'alerte (orange, rouge, vert alerte): réservées aux notifications, statuts et messages.
- Responsive prioritaire: chaque comportement doit être décrit pour desktop/tablette/mobile (Navigation tiroir sur mobile).
[INSTRUCTION A RESPECTER]
1. Segmentation Polarisée
   L'interface est scindée en deux horizons aux fonctions opposées :
   Le Pôle Supérieur : Une zone statique et souveraine. C’est l’établi de l’artisan. Elle ne bouge jamais car elle contient les outils de création. C’est là que l’utilisateur projette ses intentions (saisir, enregistrer).
   Le Pôle Inférieur : Un puits de données expansif. C’est la mémoire vive du système. Son défilement est autonome, permettant de parcourir l'histoire et l'inventaire sans jamais perdre de vue le pôle de commande.
2. Triangulation de la Donnée
   L'ajout du panneau latéral crée une troisième dimension fonctionnelle :
   La Perspective Profonde : Dissimulé par défaut pour préserver la concentration, ce panneau surgit pour offrir une plongée verticale dans le détail d'un seul objet. Il transforme une simple ligne de tableau en une entité riche, permettant de consulter, de supprimer ou de renvoyer l'objet vers le pôle supérieur pour chirurgie (modification).
3. Densité Sémantique Calibrée
   L'interface rejette l'uniformité. Elle adopte un dimensionnement morphologique :
   Chaque champ de saisie occupe l'espace exact de sa nature (le code est compressé, le libellé est étendu).
   Cette densité permet d'afficher une complexité maximale dans un périmètre visuel minimal, réduisant ainsi la fatigue liée au balayage oculaire.
4. Philosophie du « Zéro Rupture »
   C’est une architecture « sans couture » :
   Absence de Modales : L'interface ne s'interrompt jamais. Aucune fenêtre surgissante ne vient masquer les données de référence.
   Réinjection Circulaire : La modification n'est pas une nouvelle page, c'est un retour au sommet. La donnée sélectionnée en bas remonte mécaniquement au pôle supérieur, créant une boucle logique de traitement.
5. Morphologie Adaptative (Le Design en Tiroirs)
   Sa structure est liquide :
   Sur grand écran, c'est un tableau de bord panoramique où tout est accessible.
   Sur petit écran, elle se transforme en une superposition de couches (tiroirs). La navigation devient un jeu de va-et-vient ergonomique où le bouton "retour" assure la transition entre la liste (l'inventaire) et le volet (le détail).
   Ceci sera défini pour toutes les opérations CRUD, de Haut en Bas de chaque élément du projet
   Petite précision : Pour certaines opérations, on récupère les informations, on les retape pas de nouveaux, on a un menu déroulant et par exemple quand on clique sur le menu déroulant pour sélectionner un Etudiant, toutes les informations nécessaires se remplissent automatique
   Autre exemple, pour créer un utilisateur, on sélectionne le type et si par exemple le type c'est étudiant, pour le nom qui sera associé à cet utilisateur ça sera uniquement les étudiants, je ne sais pas si je me fais comprendre
   Nous allons décrire un peu rapidement certains éléments :
   Pour toutes les pages, en haut à droite nous avons l'année académique active en sorti
   Pour la majorité des pages CRUD
   Ensuite la partie statique avec les différentes cases et le bouton valider qui change pour devenir également appliquer les modifications
   Juste en bas le tableau, avant de trouver le tableau il y a un onglet pour régler la pagination puis le bouton recherche, exporter, importer, imprimer, ensuite le tableau avec les boutons d'actions modifier supprimer/archiver.
6. Couleurs et Design System (obligatoire)
   Palette figée du système :
   primary: '#1a5276' (couleur de la sidebar)
   primary-lighter: '#3498db' (background global de l'application)
   Boutons/actions : nuances de bleu ou vert uniquement.
   Couleurs d'alerte (orange, rouge, vert alerte) : réservées aux notifications, badges et messages d'état, pas aux boutons d'action.
7. Responsive prioritaire
   Le responsive est primordial: toutes les pages doivent être pleinement utilisables sur desktop, tablette et mobile.
   En mobile, privilégier les tiroirs (liste -> détail -> retour), avec actions toujours accessibles sans casser le flux.
8. Gestion des utilisateurs (règle métier de référence)
   La création des utilisateurs se fait dans l'écran Gestion Utilisateurs.
   Aucun compte utilisateur ne doit être créé automatiquement ailleurs (inscription, import ou autre flux métier) sans passer par la logique de cet écran.
[INSTRUCTION A RESPECTER]

================================================================================
RÈGLES TRANSVERSES OBLIGATOIRES (INSTRUCTION.MD)
================================================================================
# CONTRAINTTES ABSOLUES
- ❌ NE PAS modifier les contrôleurs dans `app/controllers/` (Mais il faut les lire pour comprendre)
- ❌ NE PAS modifier les routes dans `config/routes.php` (Mais il faut les lire pour comprendre)
- ❌ NE PAS modifier les modèles dans `app/models/` (Mais il faut les lire pour comprendre)
- ❌ Pas de slide panel, il y a la mention dans les instructions mais il ne faut pas le faire, pas les utiliser, ignore les.
- ❌ UE ET ECUE à retirer, il faut les supprimer complètement du projet, pas juste les cacher, il faut les supprimer de la base de données, des modèles, des contrôleurs, des vues, des routes, de tout le projet
- ❌ Pas de modal, il faut éviter les modals à tout prix, il faut faire en sorte que tout se fasse dans la même page, pas de pop-up, pas de fenêtre qui s'ouvre, pas de fenêtre qui bloque l'utilisateur, tout doit se faire dans la même page, avec le système de segmentation polarisée.
- ✅ La segmentation polarisée est obligatoire, il faut toujours avoir un pôle supérieur statique (saisie/validation) + pôle inférieur défilant, la segmentation en elle même ne doit absolument pas bougé, seulement le contenu du pôle inférieur doit bougé avec les différents boutons d'actions et le defilement du tableau.
- Palette UI obligatoire:
   * `primary: #1a5276` pour la sidebar.
   * `primary-lighter: #3498db` pour le fond global de l'application.
- Boutons/actions: uniquement nuances de bleu ou vert; ne pas utiliser orange/rouge pour des actions.
- Couleurs d'alerte (orange, rouge, vert alerte): réservées aux notifications, statuts et messages.
- Responsive prioritaire: chaque comportement doit être décrit pour desktop/tablette/mobile (Navigation tiroir sur mobile).
- 
# RÈGLES TRANSVERSES OBLIGATOIRES
- Philosophie Backend Only: Toutes les interfaces sont décrites ici pour la logique de comportement backend, mais aucun fichier .php dans /views n'est maintenu.
- Segmentation polarisée: pôle supérieur statique (saisie/validation) + pôle inférieur défilant (liste/historique).
- Triangulation de la donnée: panneau latéral caché par défaut pour le détail, suppression, modification via réinjection.
- Densité sémantique calibrée: champs dimensionnés selon la nature des données, sans surcharge visuelle.
- Philosophie zéro rupture: pas de modal bloquante sauf action destructrice; la modification se fait par retour au pôle supérieur.
- Morphologie adaptative (Backend logic): mobile = navigation en tiroirs (liste/détail); desktop = vue panoramique.
- Année académique active visible en haut à droite sur toutes les pages. (en lecture seule en dehors de l'admin)
- Pour chaque CRUD: formulaire statique en haut, puis barre pagination/recherche/export/import/imprimer, puis tableau avec actions modifier/supprimer/archiver.
- Auto-remplissage obligatoire quand une référence est sélectionnée (étudiant, enseignant, entreprise, inscription, type utilisateur, groupe utilisateur).
- Palette UI obligatoire:
    * `primary: #1a5276` pour la sidebar.
    * `primary-lighter: #3498db` pour le fond global de l'application.
- Boutons/actions: uniquement nuances de bleu ou vert; ne pas utiliser orange/rouge pour des actions.
- Couleurs d'alerte (orange, rouge, vert alerte): réservées aux notifications, statuts et messages.
- Responsive prioritaire: chaque comportement doit être décrit pour desktop/tablette/mobile (Navigation tiroir sur mobile).
- Création utilisateur centralisée: les comptes sont créés dans l'écran Gestion Utilisateurs.
- Pratiquement toutes les pages vont adopter le système Segmentation polarisée (Zone de saisie/validation statique en haut + zone de liste/historique défilante en bas) pour
  une expérience utilisateur cohérente et optimisée) sauf les écrans de type "Tableau de bord" qui peuvent adopter une structure plus libre (widgets + graphiques), editeur de texte, ou formulaire de configuration avancée.

# CONTRAINTTES ABSOLUES
- ❌ NE PAS modifier les contrôleurs dans `app/controllers/` (Mais il faut les lire pour comprendre)
- ❌ NE PAS modifier les routes dans `config/routes.php` (Mais il faut les lire pour comprendre)
- ❌ NE PAS modifier les modèles dans `app/models/` (Mais il faut les lire pour comprendre)
- ❌ Pas de slide panel, il y a la mention dans les instructions mais il ne faut pas le faire, pas les utiliser, ignore les.
- ❌ UE ET ECUE à retirer, il faut les supprimer complètement du projet, pas juste les cacher, il faut les supprimer de la base de données, des modèles, des contrôleurs, des vues, des routes, de tout le projet
- ❌ Pas de modal, il faut éviter les modals à tout prix, il faut faire en sorte que tout se fasse dans la même page, pas de pop-up, pas de fenêtre qui s'ouvre, pas de fenêtre qui bloque l'utilisateur, tout doit se faire dans la même page, avec le système de segmentation polarisée.
- ✅ La segmentation polarisée est obligatoire, il faut toujours avoir un pôle supérieur statique (saisie/validation) + pôle inférieur défilant, la segmentation en elle même ne doit absolument pas bougé, seulement le contenu du pôle inférieur doit bougé avec les différents boutons d'actions et le defilement du tableau.
- Palette UI obligatoire:
   * `primary: #1a5276` pour la sidebar.
   * `primary-lighter: #3498db` pour le fond global de l'application.
- Boutons/actions: uniquement nuances de bleu ou vert; ne pas utiliser orange/rouge pour des actions.
- Couleurs d'alerte (orange, rouge, vert alerte): réservées aux notifications, statuts et messages.
- Responsive prioritaire: chaque comportement doit être décrit pour desktop/tablette/mobile (Navigation tiroir sur mobile).

(End of file - total 108 lines)
