# RAPPORT DE MIGRATION PHPRULES
Généré le : 28/01/2026 00:01:53
Nombre de fichiers : 70

--- 

## FICHIER : access_denied_content.php

### 1. DATA MAPPING (VARIABLES)
- `$firstAccessiblePage`
- `$menuHierarchique`
- `$categorie`
- `$firstFonc`
- `$query`
- `$params`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [BOUCLE] Profondeur 0
```php
foreach($menuHierarchique as $categorie)
```
#### [CALCUL] Profondeur 1
```php
$query = parse_url($firstFonc->url_fonctionnalite, PHP_URL_QUERY);
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : admin_historique.php

### 1. DATA MAPPING (VARIABLES)
- `$currentTab`
- `$students`
- `$juries`
- `$academicYears`
- `$filters`
- `$totalPages`
- `$currentPage`
- `$globalStats`
- `$yearlyEvolution`
- `$mentionsDistribution`
- `$topEntreprises`
- `$messageSuccess`
- `$messageErreur`
- `$year`
- `$index`
- `$student`
- `$statusClass`
- `$statusText`
- `$jury`
- `$rawDate`
- `$hasValidDate`
- `$displayDate`
- `$president`
- `$encadreur`
- `$examinateur`
- `$directeur`
- `$row`
- `$totalMentions`
- `$barColors`
- `$label`
- `$count`
- `$percent`
- `$color`
- `$idx`
- `$ent`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CALCUL] Profondeur 0
```php
$currentTab = $GLOBALS['currentTab'] ?? 'students';
```
#### [CALCUL] Profondeur 0
```php
$students = $GLOBALS['students'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$juries = $GLOBALS['juries'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$academicYears = $GLOBALS['academicYears'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$filters = $GLOBALS['filters'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$totalPages = $GLOBALS['totalPages'] ?? 1;
```
#### [CALCUL] Profondeur 0
```php
$currentPage = $GLOBALS['currentPage'] ?? 1;
```
#### [CALCUL] Profondeur 0
```php
$globalStats = $GLOBALS['globalStats'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$yearlyEvolution = $GLOBALS['yearlyEvolution'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$mentionsDistribution = $GLOBALS['mentionsDistribution'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$topEntreprises = $GLOBALS['topEntreprises'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$messageErreur = $GLOBALS['messageErreur'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$currentTab === 'students' ? 'active' : '';
```
#### [CALCUL] Profondeur 0
```php
$currentTab === 'jury' ? 'active' : '';
```
#### [CALCUL] Profondeur 0
```php
$currentTab === 'stats' ? 'active' : '';
```
#### [CALCUL] Profondeur 0
```php
$currentTab === 'students' ? 'block' : 'hidden';
```
#### [CALCUL] Profondeur 0
```php
$index => $student): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo ($currentPage - 1) * 20 + $index + 1;
```
#### [CALCUL] Profondeur 0
```php
$statusClass = 'bg-gray-100 text-gray-800';
```
#### [CALCUL] Profondeur 0
```php
$statusClass = 'bg-green-100 text-green-800';
```
#### [CALCUL] Profondeur 0
```php
$statusClass = 'bg-red-100 text-red-800';
```
#### [CALCUL] Profondeur 0
```php
$currentTab === 'jury' ? 'block' : 'hidden';
```
#### [CALCUL] Profondeur 0
```php
$rawDate = $jury['date_soutenance'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$hasValidDate = $rawDate && !str_starts_with($rawDate, '0000-00-00');
```
#### [CALCUL] Profondeur 0
```php
$displayDate = $hasValidDate ? date('d/m/Y', strtotime($rawDate)) : 'N/A';
```
#### [CALCUL] Profondeur 0
```php
$president = trim($jury['president'] ?? '') ?: 'N/A';
```
#### [CALCUL] Profondeur 0
```php
$encadreur = trim($jury['encadreur'] ?? '') ?: 'N/A';
```
#### [CALCUL] Profondeur 0
```php
$examinateur = trim($jury['examinateur'] ?? '') ?: 'N/A';
```
#### [CALCUL] Profondeur 0
```php
$directeur = trim($jury['directeur'] ?? '') ?: 'N/A';
```
#### [CALCUL] Profondeur 0
```php
$currentTab === 'stats' ? 'block' : 'hidden';
```
#### [CALCUL] Profondeur 0
```php
$index => $row): ?>
                                        <tr class="<?php echo $index % 2 === 1 ? 'bg-gray-50' : '';
```
#### [CALCUL] Profondeur 0
```php
$barColors = [
                                    'Tres bien' => 'bg-purple-600',
                                    'Bien' => 'bg-blue-600',
                                    'Assez bien' => 'bg-green-600',
                                    'Passable' => 'bg-yellow-500',
                                ];
```
#### [CALCUL] Profondeur 0
```php
$totalMentions === 0): ?>
                                <p class="text-sm text-gray-500">Aucune évaluation disponible.</p>
                            <?php else: ?>
                                <?php foreach ($mentionsDistribution as $label => $count):
                                    $percent = $totalMentions > 0 ? round($count * 100 / $totalMentions, 1) : 0;
```
#### [CALCUL] Profondeur 0
```php
$color = $barColors[$label] ?? 'bg-gray-400';
```
#### [CALCUL] Profondeur 0
```php
$idx => $ent): ?>
                                        <li class="text-sm text-gray-600">
                                            <?php echo ($idx + 1) . '. ' . htmlspecialchars($ent['lib_entreprise']);
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=admin_historique&action=import`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**
- **ModalOverlay**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : archives_dossiers_soutenance_content.php

### 1. DATA MAPPING (VARIABLES)
- `$archives`
- `$archivesData`
- `$rapportsArchives`
- `$statistiques`
- `$filtres`
- `$status`
- `$date`
- `$time`
- `$valides`
- `$stat`
- `$rejetes`
- `$annee`
- `$selected`
- `$rapport`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
switch($status)
```
#### [CONDITION] Profondeur 0
```php
switch($status)
```
#### [CONDITION] Profondeur 0
```php
if($stat['statut'] === 'valider')
```
#### [CONDITION] Profondeur 0
```php
if($stat['statut'] === 'rejeter')
```
#### [CONDITION] Profondeur 0
```php
if(mode === 'cards')
```
#### [BOUCLE] Profondeur 0
```php
foreach($statistiques['repartition_statuts'] as $stat)
```
#### [BOUCLE] Profondeur 0
```php
foreach($statistiques['repartition_statuts'] as $stat)
```
#### [BOUCLE] Profondeur 0
```php
foreach($statistiques['repartition_annees'] as $annee)
```
#### [CALCUL] Profondeur 1
```php
$selected = ($filtres['annee'] ?? '') == $annee['annee'] ? 'selected' : '';
```
#### [CALCUL] Profondeur 0
```php
$archivesData = $archives ?? [];
```
#### [CALCUL] Profondeur 0
```php
$rapportsArchives = $archivesData['rapports_archives'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$statistiques = $archivesData['statistiques'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$filtres = $archivesData['filtres'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$time = time() - strtotime($date);
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **DateTimeManager**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : candidature_soutenance_content.php

### 1. DATA MAPPING (VARIABLES)
- `$stage_info`
- `$compte_rendu`
- `$has_candidature`
- `$candidature`
- `$candidatures_etudiant`
- `$disableCandidature`
- `$cand`
- `$onclick`
- `$btnClass`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [BOUCLE] Profondeur 0
```php
foreach($candidatures_etudiant as $cand)
```
#### [CALCUL] Profondeur 0
```php
$stage_info = isset($GLOBALS['stage_info']) ? $GLOBALS['stage_info'] : [];
```
#### [CALCUL] Profondeur 0
```php
$compte_rendu = isset($GLOBALS['compte_rendu']) ? $GLOBALS['compte_rendu'] : [];
```
#### [CALCUL] Profondeur 0
```php
$has_candidature = isset($GLOBALS['has_candidature']) ? $GLOBALS['has_candidature'] : false;
```
#### [CALCUL] Profondeur 0
```php
$candidature = isset($GLOBALS['candidature']) ? $GLOBALS['candidature'] : null;
```
#### [CALCUL] Profondeur 0
```php
$candidatures_etudiant = isset($GLOBALS['candidatures_etudiant']) ? $GLOBALS['candidatures_etudiant'] : [];
```
#### [CALCUL] Profondeur 0
```php
$onclick = empty($stage_info)
                        ? 'showWarningMessage();
```
#### [CALCUL] Profondeur 0
```php
$btnClass = $disableCandidature
                        ? 'bg-gray-400 cursor-not-allowed'
                        : 'bg-green-500 hover:bg-green-600';
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=candidature_soutenance&action=demande_candidature`
- `?page=candidature_soutenance&action=info_stage`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **FormLayout**
- **InputField**
- **DateTimeManager**
- **ActionTrigger**
- **RichTextEditor**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : dashboard_commission_content.php

### 1. DATA MAPPING (VARIABLES)
- `$stats`
- `$dashboardData`
- `$totalRapports`
- `$tauxValidation`
- `$tempsMoyen`
- `$enAttente`
- `$evolutionData`
- `$repartitionData`
- `$performanceData`
- `$activitesData`
- `$rapportsDetails`
- `$date`
- `$time`
- `$status`
- `$evolutionLabels`
- `$evolutionFinalises`
- `$evolutionRejetes`
- `$data`
- `$statusLabels`
- `$statusData`
- `$statusColors`
- `$rapport`
- `$activite`
- `$eval`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
switch($status)
```
#### [CONDITION] Profondeur 0
```php
if(current >= target)
```
#### [CONDITION] Profondeur 0
```php
if(e.ctrlKey && e.key === 'r')
```
#### [BOUCLE] Profondeur 0
```php
foreach($evolutionData as $data)
```
#### [BOUCLE] Profondeur 0
```php
foreach($repartitionData as $data)
```
#### [CALCUL] Profondeur 0
```php
$dashboardData = $stats ?? [];
```
#### [CALCUL] Profondeur 0
```php
$totalRapports = $dashboardData['total_rapports'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$tauxValidation = $dashboardData['taux_validation'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$tempsMoyen = $dashboardData['temps_moyen'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$enAttente = $dashboardData['en_attente'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$evolutionData = $dashboardData['evolution_mensuelle'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$repartitionData = $dashboardData['repartition_statuts'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$performanceData = $dashboardData['performance_categories'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$activitesData = $dashboardData['activites_recentes'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$rapportsDetails = $dashboardData['rapports_details'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$time = time() - strtotime($date);
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : dashboard_content.php

### 1. DATA MAPPING (VARIABLES)
- `$stat_etudiants`
- `$stat_enseignants`
- `$stat_personnel`
- `$stat_utilisateurs`
- `$date`
- `$jours`
- `$mois`
- `$date_fr`
- `$heure`
- `$activite`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CALCUL] Profondeur 0
```php
$stat_etudiants = $GLOBALS['stats_etudiants'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
```
#### [CALCUL] Profondeur 0
```php
$stat_enseignants = $GLOBALS['stats_enseignants'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
```
#### [CALCUL] Profondeur 0
```php
$stat_personnel = $GLOBALS['stats_personnel'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
```
#### [CALCUL] Profondeur 0
```php
$stat_utilisateurs = $GLOBALS['stats_utilisateurs'] ?? ['total' => 0, 'actifs' => 0, 'inactifs' => 0, 'taux_activite' => 0];
```
#### [CALCUL] Profondeur 0
```php
$date_fr = $jours[$date->format('w')] . ' ' . $date->format('d') . ' ' . $mois[$date->format('n') - 1] . ' ' . $date->format('Y');
```
#### [CALCUL] Profondeur 0
```php
$heure = $date->format('H:i');
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : dashboard_enseignant_content.php

### 1. DATA MAPPING (VARIABLES)
- `$pdo`
- `$enseignantModel`
- `$niveauEtudeModel`
- `$etudiantModel`
- `$ueModel`
- `$ecueModel`
- `$enseignant`
- `$enseignantId`
- `$total_etudiants`
- `$total_ues`
- `$total_ecues`
- `$mes_cours`
- `$cours`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(!$enseignantId)
```
#### [CALCUL] Profondeur 0
```php
$pdo = Database::getConnection();
```
#### [CALCUL] Profondeur 0
```php
$enseignant = $enseignantModel->getEnseignantByLogin($_SESSION['login_utilisateur']);
```
#### [CALCUL] Profondeur 0
```php
$enseignantId = $enseignant->id_enseignant;
```
#### [CALCUL] Profondeur 0
```php
$total_etudiants = $GLOBALS['total_etudiants'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$total_ues = $GLOBALS['total_ues'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$total_ecues = $GLOBALS['total_ecues'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$mes_cours = $GLOBALS['mes_cours'] ?? [];
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=dashboard_enseignant&action=get_stats`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
_Aucun pattern UI_

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : dashboard_scolarite_content.php

### 1. DATA MAPPING (VARIABLES)
- `$dashboardController`
- `$dashboardData`
- `$stats`
- `$inscriptionsParNiveau`
- `$niveau`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CALCUL] Profondeur 0
```php
$dashboardData = $dashboardController->getDashboardData();
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
_Aucun pattern UI_

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : dashboard_secretaire_content.php

### 1. DATA MAPPING (VARIABLES)
- `$dashboardController`
- `$dashboardData`
- `$stats`
- `$inscriptionsParNiveau`
- `$statsParNiveau`
- `$niveau`
- `$totalInscriptions`
- `$paiementsComplets`
- `$pourcentageReussite`
- `$queryActivites`
- `$stmtActivites`
- `$activitesRecentes`
- `$queryReclamations`
- `$stmtReclamations`
- `$reclamationsRecentes`
- `$montantTotalPerçu`
- `$montantEnAttente`
- `$montantTotal`
- `$pourcentagePerçu`
- `$nouvellesInscriptionsMois`
- `$niveauxAffichage`
- `$activite`
- `$reclamation`
- `$statutClass`
- `$statutText`
- `$reclamationsEnAttente`
- `$reclamationsResolues`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
switch($reclamation['statut_reclamation'])
```
#### [CALCUL] Profondeur 1
```php
$statutClass = 'bg-yellow-100 text-yellow-800';
```
#### [CALCUL] Profondeur 1
```php
$statutClass = 'bg-green-100 text-green-800';
```
#### [CALCUL] Profondeur 1
```php
$statutClass = 'bg-gray-100 text-gray-800';
```
#### [BOUCLE] Profondeur 0
```php
foreach($inscriptionsParNiveau as $niveau)
```
#### [BOUCLE] Profondeur 0
```php
foreach($inscriptionsParNiveau as $niveau)
```
#### [CALCUL] Profondeur 0
```php
$dashboardData = $dashboardController->getDashboardData();
```
#### [CALCUL] Profondeur 0
```php
$pourcentageReussite = $totalInscriptions > 0 ? round(($paiementsComplets / $totalInscriptions) * 100) : 0;
```
#### [CALCUL] Profondeur 0
```php
$db = Database::getConnection();
```
#### [CALCUL] Profondeur 0
```php
$stmtActivites = $db->prepare($queryActivites);
```
#### [CALCUL] Profondeur 0
```php
$activitesRecentes = $stmtActivites->fetchAll(PDO::FETCH_ASSOC);
```
#### [CALCUL] Profondeur 0
```php
$stmtReclamations = $db->prepare($queryReclamations);
```
#### [CALCUL] Profondeur 0
```php
$reclamationsRecentes = $stmtReclamations->fetchAll(PDO::FETCH_ASSOC);
```
#### [CALCUL] Profondeur 0
```php
$montantTotal = $montantTotalPerçu + $montantEnAttente;
```
#### [CALCUL] Profondeur 0
```php
$nouvellesInscriptionsMois = $stats['nouvelles_inscriptions'] ?? 0;
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : dossiers_academiques_content.php

### 1. DATA MAPPING (VARIABLES)
- `$pdo`
- `$etudiantModel`
- `$niveauModel`
- `$niveaux`
- `$itemsPerPage`
- `$currentPage`
- `$offset`
- `$niveauFiltre`
- `$searchFiltre`
- `$etudiants`
- `$search`
- `$totalItems`
- `$totalPages`
- `$niv`
- `$allEtudiants`
- `$etu`
- `$startPage`
- `$endPage`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CALCUL] Profondeur 0
```php
$pdo = Database::getConnection();
```
#### [CALCUL] Profondeur 0
```php
$niveaux = $niveauModel->getAllNiveauxEtudes();
```
#### [CALCUL] Profondeur 0
```php
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($currentPage - 1) * $itemsPerPage;
```
#### [CALCUL] Profondeur 0
```php
$niveauFiltre = isset($_GET['niveau']) ? $_GET['niveau'] : '';
```
#### [CALCUL] Profondeur 0
```php
$searchFiltre = isset($_GET['search']) ? $_GET['search'] : '';
```
#### [CALCUL] Profondeur 0
```php
$etudiants = $etudiantModel->getAllListeEtudiants();
```
#### [CALCUL] Profondeur 0
```php
$etudiants = array_filter($etudiants, function ($e) use ($niveauFiltre) {
        return isset($e->id_niv_etude) && $e->id_niv_etude == $niveauFiltre;
```
#### [CALCUL] Profondeur 0
```php
$totalPages = ceil($totalItems / $itemsPerPage);
```
#### [CALCUL] Profondeur 0
```php
$niveauFiltre == $niv->id_niv_etude ? 'selected' : '' ?>>
                            <?= htmlspecialchars($niv->lib_niv_etude) ?>
                        </option>
                    <?php endforeach;
```
#### [CALCUL] Profondeur 0
```php
$allEtudiants = $etudiantModel->getAllListeEtudiants();
```
#### [CALCUL] Profondeur 0
```php
$allEtudiants = array_filter($allEtudiants, function ($e) use ($niveauFiltre) {
                            return isset($e->id_niv_etude) && $e->id_niv_etude == $niveauFiltre;
```
#### [CALCUL] Profondeur 0
```php
$startPage = max(1, $currentPage - 2);
```
#### [CALCUL] Profondeur 0
```php
$endPage = min($totalPages, $currentPage + 2);
```
#### [CALCUL] Profondeur 0
```php
$i == $currentPage ? 'text-white bg-green-600 border-green-600' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50' ?> border rounded-md">
                            <?= $i ?>
                        </a>
                    <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=dossiers_academiques&action=enregistrer_dossier`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**
- **ModalOverlay**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : evaluations_dossiers_soutenance_content.php

### 1. DATA MAPPING (VARIABLES)
- `$stats`
- `$detail`
- `$decision`
- `$dossiers`
- `$dossier`
- `$statusClass`
- `$statusText`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(this.value === 'rejeter')
```
#### [CONDITION] Profondeur 0
```php
if(this.value === 'valider')
```
#### [CONDITION] Profondeur 0
```php
if(!decision)
```
#### [CONDITION] Profondeur 0
```php
switch($dossier['etape_validation'])
```
#### [CALCUL] Profondeur 1
```php
$statusClass = 'bg-blue-100 text-blue-800';
```
#### [CALCUL] Profondeur 1
```php
$statusClass = 'bg-green-100 text-green-800';
```
#### [CALCUL] Profondeur 1
```php
$statusClass = 'bg-orange-100 text-orange-800';
```
#### [CALCUL] Profondeur 1
```php
$statusClass = 'bg-gray-100 text-gray-800';
```
#### [CONDITION] Profondeur 0
```php
if(current >= target)
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=evaluations_dossiers_soutenance`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **FormLayout**
- **SmartSelect**
- **SelectionGroup**
- **ActionTrigger**
- **RichTextEditor**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : evaluation_soutenance_content.php

### 1. DATA MAPPING (VARIABLES)
- `$controller`
- `$message`
- `$messageType`
- `$result`
- `$soutenances`
- `$criteres`
- `$anneesAcademiques`
- `$anneeAcademiqueCourante`
- `$annee`
- `$soutenance`
- `$critere`
- `$noteTotale`
- `$mention`
- `$mentionClass`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($_SERVER['REQUEST_METHOD'] === 'POST')
```
#### [CONDITION] Profondeur 1
```php
switch($_POST['action'])
```
#### [CALCUL] Profondeur 2
```php
$result = $controller->enregistrerEvaluation();
```
#### [CALCUL] Profondeur 2
```php
$messageType = $result['success'] ? 'success' : 'error';
```
#### [CALCUL] Profondeur 2
```php
$result = $controller->supprimerEvaluation();
```
#### [CONDITION] Profondeur 0
```php
if($soutenance['est_evalue'] > 0 && $soutenance['note_finale'])
```
#### [CONDITION] Profondeur 1
```php
if($noteTotale >= 16)
```
#### [CALCUL] Profondeur 2
```php
$mentionClass = 'bg-green-100 text-green-800';
```
#### [CONDITION] Profondeur 1
```php
elseif($noteTotale >= 14)
```
#### [CALCUL] Profondeur 2
```php
$mentionClass = 'bg-blue-100 text-blue-800';
```
#### [CONDITION] Profondeur 1
```php
elseif($noteTotale >= 12)
```
#### [CALCUL] Profondeur 2
```php
$mentionClass = 'bg-yellow-100 text-yellow-800';
```
#### [CONDITION] Profondeur 1
```php
elseif($noteTotale >= 10)
```
#### [CALCUL] Profondeur 2
```php
$mentionClass = 'bg-orange-100 text-orange-800';
```
#### [CALCUL] Profondeur 1
```php
$mentionClass = 'bg-red-100 text-red-800';
```
#### [CONDITION] Profondeur 0
```php
if(selectedOption && selectedOption.value)
```
#### [CONDITION] Profondeur 0
```php
if(input.value && input.value !== '')
```
#### [CONDITION] Profondeur 1
```php
if(note > baremeMax)
```
#### [CONDITION] Profondeur 0
```php
if(noteTotaleInput && isValid)
```
#### [CONDITION] Profondeur 0
```php
if(!isValid)
```
#### [CONDITION] Profondeur 0
```php
if(note >= 16)
```
#### [CONDITION] Profondeur 0
```php
if(note >= 14)
```
#### [CONDITION] Profondeur 0
```php
if(note >= 12)
```
#### [CONDITION] Profondeur 0
```php
if(note >= 10)
```
#### [CONDITION] Profondeur 0
```php
if(input.value && input.value !== '')
```
#### [CONDITION] Profondeur 1
```php
if(note < 0 || note > baremeMax)
```
#### [CONDITION] Profondeur 0
```php
if(!hasValue)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === this)
```
#### [CALCUL] Profondeur 0
```php
$soutenances = $controller->getSoutenancesProgrammeesForView();
```
#### [CALCUL] Profondeur 0
```php
$criteres = $controller->getCriteresEvaluation();
```
#### [CALCUL] Profondeur 0
```php
$anneesAcademiques = $controller->getAnneesAcademiques();
```
#### [CALCUL] Profondeur 0
```php
$anneeAcademiqueCourante = $controller->getAnneeAcademiqueCourante();
```
#### [CALCUL] Profondeur 0
```php
$messageType === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' ?>">
        <div class="flex items-center">
            <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
            <span><?= htmlspecialchars($message) ?></span>
            <button onclick="closeNotification()" class="ml-4 text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
<?php endif;
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`
- `canEdit()`
- `canDelete()`
- `isArray()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=evaluation_soutenance&action=getEvaluationExistante&num_etu=${numEtu}`
- `?page=evaluation_soutenance&action=getCriteresParAnnee&id_annee_acad=${anneeId}`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**
- **RichTextEditor**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : fiche_etudiant_archive.php

### 1. DATA MAPPING (VARIABLES)
- `$studentFile`
- `$messageSuccess`
- `$messageErreur`
- `$note`
- `$soutenanceNote`
- `$mention`
- `$m1_avg`
- `$m2_s1_avg`
- `$memo_avg`
- `$coeff_m1`
- `$coeff_m2_s1`
- `$coeff_memo`
- `$total_coeffs`
- `$m1_calc`
- `$m2_s1_calc`
- `$memo_calc`
- `$general_avg`
- `$general_mention`
- `$raw_timeline`
- `$timeline_steps`
- `$last_valid_date_found`
- `$reversed_timeline`
- `$title`
- `$step`
- `$current_step`
- `$timelineDate`
- `$hasTimelineDate`
- `$rawDateSout`
- `$rawHeureSout`
- `$hasDateSout`
- `$hasHeureSout`
- `$juryMembers`
- `$member`
- `$parts`
- `$name`
- `$role`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(!$studentFile)
```
#### [CONDITION] Profondeur 0
```php
if($m1_avg === null && $m2_s1_avg === null && $memo_avg === null)
```
#### [BOUCLE] Profondeur 0
```php
foreach($reversed_timeline as $title => $step)
```
#### [CALCUL] Profondeur 0
```php
$studentFile = $GLOBALS['studentFile'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$messageErreur = $GLOBALS['messageErreur'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$soutenanceNote = $studentFile['soutenance']['note_soutenance'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$m1_avg = $studentFile['moyenne_m1'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$m2_s1_avg = $studentFile['moyenne_m2_s1'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$total_coeffs = $coeff_m1 + $coeff_m2_s1 + $coeff_memo;
```
#### [CALCUL] Profondeur 0
```php
$m1_calc = $m1_avg ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$m2_s1_calc = $m2_s1_avg ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$memo_calc = $memo_avg ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$general_avg = ($m1_calc * $coeff_m1 + $m2_s1_calc * $coeff_m2_s1 + $memo_calc * $coeff_memo) / $total_coeffs;
```
#### [CALCUL] Profondeur 0
```php
$raw_timeline = [
    'Inscription' => ['date' => $studentFile['date_inscription'] ?? '2022-09-15', 'icon' => 'fa-user-check'],
    'Début de Stage' => ['date' => $studentFile['stage']['date_debut_stage'] ?? null, 'icon' => 'fa-briefcase'],
    'Validation Thème' => ['date' => $studentFile['rapport']['date_validation'] ?? null, 'icon' => 'fa-check-double'],
    'Soutenance' => ['date' => $studentFile['soutenance']['date_soutenance'] ?? null, 'icon' => 'fa-graduation-cap'],
    'Diplômé' => ['date' => ($soutenanceNote && $soutenanceNote >= 10) ? ($studentFile['soutenance']['date_soutenance'] ?? date('Y-m-d')) : null, 'icon' => 'fa-award']
];
```
#### [CALCUL] Profondeur 0
```php
$title => $step): ?>
                <li class="mb-10 ml-6">
                    <?php if($step['is_completed']): ?>
                    <span class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -left-4 ring-8 ring-white">
                        <i class="fas <?php echo $step['icon'];
```
#### [CALCUL] Profondeur 0
```php
$timelineDate = $step['date'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$hasTimelineDate = $timelineDate && !str_starts_with($timelineDate, '0000-00-00');
```
#### [CALCUL] Profondeur 0
```php
$rawDateSout = $studentFile['soutenance']['date_soutenance'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$rawHeureSout = $studentFile['soutenance']['heure_soutenance'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$hasDateSout = $rawDateSout && !str_starts_with($rawDateSout, '0000-00-00');
```
#### [CALCUL] Profondeur 0
```php
$hasHeureSout = $rawHeureSout && stripos($rawHeureSout, '00:00:00') === false;
```
#### [CALCUL] Profondeur 0
```php
$parts = explode(':', $member);
```
#### [CALCUL] Profondeur 0
```php
$name = trim($parts[0] ?? 'N/A');
```
#### [CALCUL] Profondeur 0
```php
$role = trim($parts[1] ?? 'N/A');
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=admin_historique&action=update_student`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_candidatures_soutenance_content.php

### 1. DATA MAPPING (VARIABLES)
- `$candidatures`
- `$examiner`
- `$etape`
- `$etudiantData`
- `$etapeData`
- `$statutFiltre`
- `$resumes_candidatures`
- `$resume`
- `$candidature`
- `$decision`
- `$rejets`
- `$key`
- `$data`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($statutFiltre !== 'all')
```
#### [CONDITION] Profondeur 0
```php
if($c['statut_candidature'] !== 'En attente')
```
#### [CALCUL] Profondeur 1
```php
$resume = (new Etudiant(Database::getConnection()))->getResumeCandidature($c['id_candidature']);
```
#### [CONDITION] Profondeur 0
```php
if($data['validation'] === 'rejeté')
```
#### [CONDITION] Profondeur 0
```php
switch($etape)
```
#### [CONDITION] Profondeur 0
```php
if(etape == 1)
```
#### [CONDITION] Profondeur 0
```php
if(etape == 2)
```
#### [CONDITION] Profondeur 0
```php
if(etape == 3)
```
#### [CONDITION] Profondeur 0
```php
if(etape == 4)
```
#### [BOUCLE] Profondeur 0
```php
foreach($candidatures as $c)
```
#### [BOUCLE] Profondeur 0
```php
foreach($etapeData as $key => $data)
```
#### [CALCUL] Profondeur 0
```php
$candidatures = $GLOBALS['candidatures_soutenance'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$examiner = $GLOBALS['examiner'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$etape = $GLOBALS['etape'] ?? 1;
```
#### [CALCUL] Profondeur 0
```php
$etudiantData = $GLOBALS['etudiantData'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$etapeData = $GLOBALS['etapeData'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$statutFiltre = $_GET['statut'] ?? 'all';
```
#### [CALCUL] Profondeur 0
```php
$etape == 4 ? ' resume-step' : '');
```
#### [CALCUL] Profondeur 0
```php
$etape == 1 ? 'active' : '');
```
#### [CALCUL] Profondeur 0
```php
$etape == 2 ? 'active' : '');
```
#### [CALCUL] Profondeur 0
```php
$etape == 3 ? 'active' : '');
```
#### [CALCUL] Profondeur 0
```php
$etape == 4 ? 'active' : '';
```
#### [CALCUL] Profondeur 0
```php
$etape == 1)
                        echo 'active-scolarite';
```
#### [CALCUL] Profondeur 0
```php
$etape == 2)
                        echo 'active-stage';
```
#### [CALCUL] Profondeur 0
```php
$etape == 3)
                        echo 'active-semestre';
```
#### [CALCUL] Profondeur 0
```php
$etape == 4)
                        echo 'active-resume';
```
#### [CALCUL] Profondeur 0
```php
$etape == 4): ?>
                                <!-- Résumé final -->
                                <h3>Résumé de l'évaluation</h3>
                                <div class="resume-final">
                                    <?php
                                    $decision = 'Validée';
```
#### [CALCUL] Profondeur 0
```php
$key => $data) {
                                        if ($data['validation'] === 'rejeté') {
                                            $rejets++;
```
#### [CALCUL] Profondeur 0
```php
$decision === 'Validée' ? 'validee' : 'rejetee';
```
#### [CALCUL] Profondeur 0
```php
$decision === 'Validée'): ?>
                                            <p>🎉 Félicitations ! Votre candidature a été validée. Vous pouvez procéder à votre
                                                soutenance.</p>
                                        <?php else: ?>
                                            <p>❌ Votre candidature a été rejetée. Veuillez corriger les problèmes identifiés
                                                ci-dessous.</p>
                                        <?php endif;
```
#### [CALCUL] Profondeur 0
```php
$etape == 1): ?>
                                    <div class="info-item">
                                        <strong>Statut des paiements:</strong>
                                        <span><?php echo htmlspecialchars($etapeData['status']);
```
#### [CALCUL] Profondeur 0
```php
$etape == 2): ?>
                                    <div class="info-item">
                                        <strong>Entreprise :</strong>
                                        <span><?php echo htmlspecialchars($etapeData['entreprise']);
```
#### [CALCUL] Profondeur 0
```php
$etape == 3): ?>
                                    <div class="info-item">
                                        <strong>Semestre actuel:</strong>
                                        <span><?php echo htmlspecialchars($etapeData['semestre']);
```
#### [CALCUL] Profondeur 0
```php
$etape == 3 ? 'Terminer l\'évaluation' : 'Valider';
```
#### [CALCUL] Profondeur 0
```php
$etape == 4): ?>
                                <form method="post"
                                    action="?page=gestion_candidatures_soutenance&action=envoyer_resultats&examiner=<?php echo $examiner;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_candidatures_soutenance&action=rejeter_etape&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape; ?>`
- `?page=gestion_candidatures_soutenance&action=valider_etape&examiner=<?php echo $examiner; ?>&etape=<?php echo $etape; ?>`
- `?page=gestion_candidatures_soutenance&action=envoyer_resultats&examiner=<?php echo $examiner; ?>`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**
- **ModalOverlay**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_dossiers_candidatures_content.php

### 1. DATA MAPPING (VARIABLES)
- `$rapportsVerifies`
- `$statistiques`
- `$statutFilter`
- `$searchTerm`
- `$rapport`
- `$perPage`
- `$totalRapports`
- `$totalPages`
- `$startIndex`
- `$rapportsPage`
- `$params`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($statutFilter !== 'all')
```
#### [CONDITION] Profondeur 0
```php
if(e.target === this)
```
#### [CALCUL] Profondeur 0
```php
$rapportsVerifies = $GLOBALS['rapports_verifies'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$statistiques = $GLOBALS['statistiques'] ?? ['total' => 0, 'approuves' => 0, 'desapprouves' => 0];
```
#### [CALCUL] Profondeur 0
```php
$statutFilter = $_GET['statut'] ?? 'all';
```
#### [CALCUL] Profondeur 0
```php
$searchTerm = $_GET['search'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$totalPages = ($totalRapports > 0) ? ceil($totalRapports / $perPage) : 1;
```
#### [CALCUL] Profondeur 0
```php
$p = isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p'] > 0 ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$startIndex = ($p - 1) * $perPage;
```
#### [CALCUL] Profondeur 0
```php
$statutFilter === 'all' ? 'selected' : '';
```
#### [CALCUL] Profondeur 0
```php
$statutFilter === 'approuve' ? 'selected' : '';
```
#### [CALCUL] Profondeur 0
```php
$statutFilter === 'desapprouve' ? 'selected' : '';
```
#### [CALCUL] Profondeur 0
```php
$p == 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50' ?> rounded-l-md">&laquo;
```
#### [CALCUL] Profondeur 0
```php
$i == $p ? 'bg-green-100 text-green-700 font-bold' : 'text-gray-700 hover:bg-gray-50' ?>"><?= $i ?></a>
                        <?php endfor;
```
#### [CALCUL] Profondeur 0
```php
$p == $totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50' ?> rounded-r-md">&raquo;
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_dossiers_candidatures&action=get_details_rapport&id_rapport=${idRapport}`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_etudiants_content.php

### 1. DATA MAPPING (VARIABLES)
_Aucune variable détectée_

### 2. LOGIQUE MÉTIER EXTRACTÉE
_Aucune logique complexe_

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
_Aucun pattern UI_

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_notes_evaluations_content.php

### 1. DATA MAPPING (VARIABLES)
- `$students`
- `$niveauxEtude`
- `$selectedNiveau`
- `$selectedStudent`
- `$studentGrades`
- `$niveau`
- `$etudiant`
- `$currentSemestre`
- `$totalCreditsSemestre`
- `$ueSemestre`
- `$ecues`
- `$ecue`
- `$note_ecue`
- `$grade`
- `$commentaire_ecue`
- `$moyenne_ue`
- `$nb_ecue`
- `$somme`
- `$note`
- `$commentaire`
- `$totalNotes`
- `$totalCredits`
- `$sumMaj`
- `$credMaj`
- `$moyMaj`
- `$sumMin`
- `$credMin`
- `$moyMin`
- `$semestreValide`
- `$creditsValides`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($currentSemestre !== $ue->lib_semestre)
```
#### [CONDITION] Profondeur 1
```php
if($currentSemestre !== null)
```
#### [CONDITION] Profondeur 1
```php
if($ueSemestre->lib_semestre === $currentSemestre)
```
#### [BOUCLE] Profondeur 1
```php
foreach($GLOBALS['studentUes'] as $ueSemestre)
```
#### [CALCUL] Profondeur 1
```php
$currentSemestre = $ue->lib_semestre;
```
#### [CONDITION] Profondeur 0
```php
if($ecue->id_ue == $ue->id_ue)
```
#### [CONDITION] Profondeur 0
```php
if($grade->id_ecue == $ecue->id_ecue)
```
#### [CALCUL] Profondeur 1
```php
$note_ecue = $grade->moyenne;
```
#### [CONDITION] Profondeur 0
```php
if($grade->id_ecue == $ecue->id_ecue)
```
#### [CALCUL] Profondeur 1
```php
$commentaire_ecue = $grade->commentaire;
```
#### [CONDITION] Profondeur 0
```php
if($grade->id_ecue == $ecue->id_ecue && $grade->moyenne !== null)
```
#### [CALCUL] Profondeur 1
```php
$somme += $grade->moyenne;
```
#### [CONDITION] Profondeur 0
```php
if($nb_ecue > 0)
```
#### [CONDITION] Profondeur 0
```php
if($grade->id_ue == $ue->id_ue)
```
#### [CALCUL] Profondeur 1
```php
$note = $grade->moyenne;
```
#### [CONDITION] Profondeur 0
```php
if($grade->id_ue == $ue->id_ue)
```
#### [CALCUL] Profondeur 1
```php
$commentaire = $grade->commentaire;
```
#### [CONDITION] Profondeur 0
```php
if($grade->credit > 3)
```
#### [CALCUL] Profondeur 1
```php
$sumMaj += $grade->moyenne * $grade->credit;
```
#### [CALCUL] Profondeur 1
```php
$credMaj += $grade->credit;
```
#### [CONDITION] Profondeur 0
```php
if($grade->credit <= 3)
```
#### [CALCUL] Profondeur 1
```php
$sumMin += $grade->moyenne * $grade->credit;
```
#### [CALCUL] Profondeur 1
```php
$credMin += $grade->credit;
```
#### [CONDITION] Profondeur 0
```php
if($grade->moyenne >= 10)
```
#### [CONDITION] Profondeur 0
```php
if(studentId && niveauId)
```
#### [BOUCLE] Profondeur 0
```php
foreach($GLOBALS['studentUes'] as $ue)
```
#### [BOUCLE] Profondeur 1
```php
foreach($GLOBALS['studentEcues'] as $ecue)
```
#### [BOUCLE] Profondeur 1
```php
foreach($ecues as $ecue)
```
#### [BOUCLE] Profondeur 2
```php
foreach($GLOBALS['studentGrades'] as $grade)
```
#### [BOUCLE] Profondeur 2
```php
foreach($GLOBALS['studentGrades'] as $grade)
```
#### [BOUCLE] Profondeur 1
```php
foreach($ecues as $ecue)
```
#### [BOUCLE] Profondeur 2
```php
foreach($GLOBALS['studentGrades'] as $grade)
```
#### [BOUCLE] Profondeur 1
```php
foreach($GLOBALS['studentGrades'] as $grade)
```
#### [BOUCLE] Profondeur 1
```php
foreach($GLOBALS['studentGrades'] as $grade)
```
#### [BOUCLE] Profondeur 0
```php
foreach($GLOBALS['studentGrades'] as $grade)
```
#### [CALCUL] Profondeur 1
```php
$totalNotes += $grade->moyenne * $grade->credit;
```
#### [CALCUL] Profondeur 1
```php
$totalCredits += $grade->credit;
```
#### [BOUCLE] Profondeur 0
```php
foreach($GLOBALS['studentGrades'] as $grade)
```
#### [BOUCLE] Profondeur 0
```php
foreach($GLOBALS['studentGrades'] as $grade)
```
#### [BOUCLE] Profondeur 0
```php
foreach($GLOBALS['studentGrades'] as $grade)
```
#### [CALCUL] Profondeur 0
```php
$students = $GLOBALS['listeEtudiants'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$niveauxEtude = $GLOBALS['niveauxEtude'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$selectedNiveau = $GLOBALS['selectedNiveau'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$selectedStudent = $GLOBALS['selectedStudent'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$studentGrades = $GLOBALS['studentGrades'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$moyMaj = $credMaj ? round($sumMaj / $credMaj, 2) : '-';
```
#### [CALCUL] Profondeur 0
```php
$moyMin = $credMin ? round($sumMin / $credMin, 2) : '-';
```
#### [CALCUL] Profondeur 0
```php
$semestreValide = ($moyMaj !== '-' && $moyMin !== '-' && $moyMaj >= 10 && $moyMin >= 10);
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_notes_evaluations<?php 
                                echo !empty($GLOBALS[`
- `?page=gestion_notes_evaluations&niveau=${niveauId}`
- `?page=gestion_notes_evaluations&niveau=${niveauId}&student=${studentId}`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_rapports_content.php

### 1. DATA MAPPING (VARIABLES)
- `$infosDepot`
- `$candidature_validee`
- `$message_candidature`
- `$candidatures_etudiant`
- `$candidature`
- `$statistiquesRapports`
- `$rapportsRecents`
- `$rapport`
- `$infoDepot`
- `$peutDeposer`
- `$messageDepot`
- `$dejaDepose`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($candidature['statut_candidature'] === 'Validée')
```
#### [CONDITION] Profondeur 0
```php
if(!$candidature_validee)
```
#### [CONDITION] Profondeur 0
```php
switch(type)
```
#### [CONDITION] Profondeur 0
```php
if(progress <= 0)
```
#### [BOUCLE] Profondeur 0
```php
foreach($candidatures_etudiant as $candidature)
```
#### [CALCUL] Profondeur 0
```php
$infosDepot = isset($GLOBALS['infosDepot']) ? $GLOBALS['infosDepot'] : [];
```
#### [CALCUL] Profondeur 0
```php
$candidatures_etudiant = isset($GLOBALS['candidatures_etudiant']) ? $GLOBALS['candidatures_etudiant'] : [];
```
#### [CALCUL] Profondeur 0
```php
$infoDepot = $infosDepot[$rapport->id_rapport] ?? ['peutDeposer' => true, 'messageDepot' => '', 'dejaDepose' => false];
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_rapports`
- `?page=gestion_rapports&action=creer_rapport&edit=${rapportId}`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **FormLayout**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_reclamations_content.php

### 1. DATA MAPPING (VARIABLES)
- `$cardReclamation`
- `$card`

### 2. LOGIQUE MÉTIER EXTRACTÉE
_Aucune logique complexe_

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
_Aucun pattern UI_

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_reclamations_scolarite_content.php

### 1. DATA MAPPING (VARIABLES)
- `$reclamationsEnCours`
- `$reclamationsTraitees`
- `$rec`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(status === 'résolue' || status === 'traitée')
```
#### [CONDITION] Profondeur 0
```php
if(status === 'rejeté' || status === 'rejetée')
```
#### [CONDITION] Profondeur 0
```php
if(!table)
```
#### [CONDITION] Profondeur 0
```php
if(totalRows > 0)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === this)
```
#### [BOUCLE] Profondeur 0
```php
for(let i = 1; i <= pageCount; i++)
```
#### [CALCUL] Profondeur 0
```php
$reclamationsEnCours = $GLOBALS['reclamationsEnCours'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$reclamationsTraitees = $GLOBALS['reclamationsTraitees'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$i => $rec): ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= $i+1 ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($rec->nom_etu . ' ' . $rec->prenom_etu) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate"
                                            title="<?= htmlspecialchars($rec->titre_reclamation ?? '') ?>">
                                            <?= htmlspecialchars($rec->titre_reclamation ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-600 max-w-xs truncate"
                                            title="<?= htmlspecialchars($rec->description_reclamation ?? '') ?>">
                                            <?= htmlspecialchars($rec->description_reclamation ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($rec->date_creation ?? '') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php if($rec->statut_reclamation === 'en attente') echo 'bg-yellow-100 text-yellow-800';
```
#### [CALCUL] Profondeur 0
```php
$i => $rec): ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= $i+1 ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($rec->nom_etu . ' ' . $rec->prenom_etu) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 max-w-xs truncate"
                                            title="<?= htmlspecialchars($rec->titre_reclamation ?? '') ?>">
                                            <?= htmlspecialchars($rec->titre_reclamation ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-600 max-w-xs truncate"
                                            title="<?= htmlspecialchars($rec->description_reclamation ?? '') ?>">
                                            <?= htmlspecialchars($rec->description_reclamation ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($rec->date_creation ?? '') ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php if(strtolower($rec->statut_reclamation) === 'résolue' || strtolower($rec->statut_reclamation) === 'traitée') echo 'bg-green-100 text-green-800';
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_reclamations_scolarite&action=changer_statut&id=<?= $rec->id_reclamation ?>`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_rh_content.php

### 1. DATA MAPPING (VARIABLES)
- `$activeTab`
- `$messageErreur`
- `$messageSuccess`
- `$personnel_admin`
- `$enseignants`
- `$listeGrades`
- `$listeFonctions`
- `$listeSpecialites`
- `$pers_admin_a_modifier`
- `$enseignant_a_modifier`
- `$action`
- `$admin_edit`
- `$enseignant_edit`
- `$admin`
- `$enseignant`
- `$specialite`
- `$fonction`
- `$grade`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(selectAllCheckbox && deleteButton)
```
#### [CONDITION] Profondeur 1
```php
if(e.target.name === 'selected_ids[]')
```
#### [CONDITION] Profondeur 0
```php
if(deleteBtn && form)
```
#### [CONDITION] Profondeur 0
```php
if(checkboxes.length === 0)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === deleteModal)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === modifyModal)
```
#### [CONDITION] Profondeur 0
```php
if(selectAllCheckbox && deleteButton && form)
```
#### [CALCUL] Profondeur 0
```php
$activeTab = $_GET['tab'] ?? 'pers_admin';
```
#### [CALCUL] Profondeur 0
```php
$messageErreur = $GLOBALS['messageErreur'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$personnel_admin = $GLOBALS['listePersAdmin'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$enseignants = $GLOBALS['listeEnseignants'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeGrades = $GLOBALS['listeGrades'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeFonctions = $GLOBALS['listeFonctions'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeSpecialites = $GLOBALS['listeSpecialites'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$pers_admin_a_modifier = $GLOBALS['pers_admin_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$enseignant_a_modifier = $GLOBALS['enseignant_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$action = $_GET['action'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$id = $_GET['id'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$admin_edit = $pers_admin_a_modifier ?? null;
```
#### [CALCUL] Profondeur 0
```php
$enseignant_edit = $enseignant_a_modifier ?? null;
```
#### [CALCUL] Profondeur 0
```php
$activeTab === 'pers_admin') ? 'border-green-500 text-green-600 bg-green-50' : 'border-transparent hover:border-gray-300' ?>">
                            <i class="fas fa-users-cog mr-2"></i> Personnel administratif
                        </a>
                        <a href="?page=gestion_rh&tab=enseignant"
                            class="tab-button whitespace-nowrap py-3 px-4 border-b-2 font-medium text-sm hover:text-gray-700
                                  <?= ($activeTab === 'enseignant') ? 'border-green-500 text-green-600 bg-green-50' : 'border-transparent hover:border-gray-300' ?>">
                            <i class="fas fa-user-tag mr-2"></i> Enseignants
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Contenu des onglets -->
            <div>
                <!-- Onglet Personnel administratif -->
                <?php if ($activeTab === 'pers_admin'): ?>
                <div id="tab_pers_admin" class="flex flex-col">
                    <?php if (!empty($messageSuccess)): ?>
                    <div id="success-message"
                        class="bg-green-50 border-l-4 border-green-400 text-green-700 p-4 rounded-md shadow-sm mb-6"
                        role="alert">
                        <p><?= htmlspecialchars($messageSuccess) ?></p>
                    </div>
                    <?php endif;
```
#### [CALCUL] Profondeur 0
```php
$action === 'add' || $action === 'edit'): ?>
                    <div class="fixed inset-0 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50"
                        id="modal-admin">
                        <div class="relative mx-auto p-6 w-full max-w-2xl bg-white rounded-lg shadow-xl">
                            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
                                <h3 class="text-xl font-semibold text-green-600">
                                    <?= ($action === 'edit' && isset($pers_admin_a_modifier)) ? 'Modifier un membre du personnel' : 'Ajouter un membre du personnel' ?>
                                </h3>
                                <a href="?page=gestion_rh&tab=pers_admin"
                                    class="text-gray-400 hover:text-gray-500 transition-colors duration-200">
                                    <i class="fas fa-times text-xl"></i>
                                </a>
                            </div>

                            <form action="?page=gestion_rh&tab=pers_admin" method="POST" class="space-y-6">
                                <?php if ($action === 'edit' && isset($pers_admin_a_modifier)): ?>
                                <input type="hidden" name="id_pers_admin"
                                    value="<?= htmlspecialchars($pers_admin_a_modifier->id_pers_admin) ?>">
                                <?php endif;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->nom_pers_admin) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>

                                    <div>
                                        <label for="prenom"
                                            class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
                                        <input type="text" name="prenom" id="prenom" style="outline: none;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->prenom_pers_admin) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="email"
                                            class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                        <input type="email" name="email" id="email" style="outline: none;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->email_pers_admin) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                    <div>
                                        <label for="telephone"
                                            class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                        <input type="tel" name="telephone" id="telephone" style="outline: none;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->tel_pers_admin) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="poste"
                                            class="block text-sm font-medium text-gray-700 mb-2">Poste</label>
                                        <input type="text" name="poste" id="poste" style="outline: none;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->poste) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                    <div>
                                        <label for="date_embauche"
                                            class="block text-sm font-medium text-gray-700 mb-2">Date
                                            d'embauche</label>
                                        <input type="date" name="date_embauche" id="date_embauche"
                                            style="outline: none;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($pers_admin_a_modifier)) ? htmlspecialchars($pers_admin_a_modifier->date_embauche) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>


                                <div class="flex justify-between space-x-4 pt-6 border-t border-gray-200">
                                    <a href="?page=gestion_rh&tab=pers_admin"
                                        class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors duration-200">
                                        Annuler
                                    </a>
                                    <button type="submit"
                                        name="<?= ($action === 'edit') ? 'btn_modifier_pers_admin' : 'btn_add_pers_admin' ?>"
                                        class="px-6 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200">
                                        <?= ($action === 'edit' && isset($pers_admin_a_modifier)) ? 'Modifier' : 'Enregistrer' ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif;
```
#### [CALCUL] Profondeur 0
```php
$activeTab === 'enseignant'): ?>
                <div id="tab_enseignant" class="flex flex-col">
                    <?php if (!empty($messageSuccess)): ?>
                    <div id="success-message"
                        class="bg-green-50 border-l-4 border-green-400 text-green-700 p-4 rounded-md shadow-sm mb-6"
                        role="alert">
                        <p><?= htmlspecialchars($messageSuccess) ?></p>
                    </div>
                    <?php endif;
```
#### [CALCUL] Profondeur 0
```php
$action === 'add' || $action === 'edit'): ?>
                    <div class="fixed inset-0  bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50"
                        id="modal-enseignant">
                        <div class="relative mx-auto p-6 w-full max-w-2xl bg-white rounded-lg shadow-xl">
                            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-200">
                                <h3 class="text-xl font-semibold text-green-600">
                                    <?= ($action === 'edit' && isset($enseignant_a_modifier)) ? 'Modifier un enseignant' : 'Ajouter un enseignant' ?>
                                </h3>
                                <a href="?page=gestion_rh&tab=enseignant"
                                    class="text-gray-400 hover:text-gray-500 transition-colors duration-200">
                                    <i class="fas fa-times text-xl"></i>
                                </a>
                            </div>

                            <form action="?page=gestion_rh&tab=enseignant" method="POST" class="space-y-6">
                                <?php if ($action === 'edit' && isset($enseignant_a_modifier)): ?>
                                <input type="hidden" name="id_enseignant"
                                    value="<?= htmlspecialchars($enseignant_a_modifier->id_enseignant) ?>">
                                <?php endif;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($enseignant_a_modifier)) ? htmlspecialchars($enseignant_a_modifier->nom_enseignant) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>

                                    <div>
                                        <label for="prenom_enseignant"
                                            class="block text-sm font-medium text-gray-700 mb-2">Prénom</label>
                                        <input type="text" name="prenom" id="prenom_enseignant" style="outline: none;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($enseignant_a_modifier)) ? htmlspecialchars($enseignant_a_modifier->prenom_enseignant) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="email_enseignant"
                                            class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                        <input type="email" name="email" id="email_enseignant" style="outline: none;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($enseignant_a_modifier)) ? htmlspecialchars($enseignant_a_modifier->mail_enseignant) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>

                                    <div>
                                        <label for="specialite"
                                            class="block text-sm font-medium text-gray-700 mb-2">Spécialité</label>
                                        <select name="id_specialite" id="specialite" style="outline: none;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($enseignant_a_modifier) && $enseignant_a_modifier->id_specialite == $specialite->id_specialite) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($specialite->lib_specialite) ?>
                                            </option>
                                            <?php endforeach;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($enseignant_a_modifier) && $enseignant_a_modifier->id_fonction == $fonction->id_fonction) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($fonction->lib_fonction) ?>
                                            </option>
                                            <?php endforeach;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($enseignant_a_modifier)) ? htmlspecialchars($enseignant_a_modifier->date_occupation) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="grade"
                                            class="block text-sm font-medium text-gray-700 mb-2">Grade</label>
                                        <select name="id_grade" id="grade" style="outline: none;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($enseignant_a_modifier) && $enseignant_a_modifier->id_grade == $grade->id_grade) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($grade->lib_grade) ?>
                                            </option>
                                            <?php endforeach;
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit' && isset($enseignant_a_modifier)) ? htmlspecialchars($enseignant_a_modifier->date_grade) : '' ?>"
                                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200"
                                            required>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label for="type_enseignant" class="block text-sm font-medium text-gray-700">
                                            Type d'enseignant
                                        </label>
                                        <select name="type_enseignant" id="type_enseignant" required
                                            class="focus:outline-none w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                                            <option value="Simple"
                                                <?php echo ($enseignant_a_modifier && $enseignant_a_modifier->type_enseignant) ? 'selected' : '';
```
#### [CALCUL] Profondeur 0
```php
$action === 'edit') ? 'btn_modifier_enseignant' : 'btn_add_enseignant' ?>"
                                        class="px-6 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200">
                                        <?= ($action === 'edit' && isset($enseignant_a_modifier)) ? 'Modifier' : 'Ajouter' ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif;
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`
- `canDelete()`
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_rh&tab=pers_admin`
- `?page=gestion_rh&tab=enseignant`
- `?page=gestion_rh&tab=${type}&action=edit&id_${type}=${id}`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **DateTimeManager**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_scolarite_content.php

### 1. DATA MAPPING (VARIABLES)
- `$etudiantsInscrits`
- `$listeAllEtudiant`
- `$allVersement`
- `$items_par_page`
- `$page_actuelle`
- `$total_items`
- `$total_pages`
- `$debut`
- `$versements_pages`
- `$totalEtudiants`
- `$complete`
- `$partial`
- `$etudiant`
- `$reste_a_payer`
- `$pourcentageComplete`
- `$pourcentagePartial`
- `$pourcentagePending`
- `$versement`
- `$debut_pagination`
- `$fin_pagination`
- `$classes`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($reste_a_payer <= 0)
```
#### [CONDITION] Profondeur 0
```php
if($debut_pagination > 1)
```
#### [CONDITION] Profondeur 1
```php
if($debut_pagination > 2)
```
#### [CONDITION] Profondeur 0
```php
if($fin_pagination < $total_pages)
```
#### [CONDITION] Profondeur 0
```php
if(resteAPayer == 0)
```
#### [CONDITION] Profondeur 0
```php
if(amount > resteAPayer)
```
#### [CONDITION] Profondeur 0
```php
if(typeof isVersement === 'undefined')
```
#### [CONDITION] Profondeur 0
```php
if(!id)
```
#### [CONDITION] Profondeur 0
```php
if(versementsAExporter.length === 0)
```
#### [CONDITION] Profondeur 0
```php
if(versementsAImprimer.length === 0)
```
#### [BOUCLE] Profondeur 0
```php
foreach($etudiantsInscrits as $etudiant)
```
#### [CALCUL] Profondeur 1
```php
$reste_a_payer = isset($etudiant['reste_a_payer']) ? floatval($etudiant['reste_a_payer']) : 0;
```
#### [BOUCLE] Profondeur 0
```php
for($i = $debut_pagination; $i <= $fin_pagination; $i++)
```
#### [CALCUL] Profondeur 1
```php
$classes = $i === $page_actuelle 
                                            ? 'relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600'
                                            : 'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50';
```
#### [CALCUL] Profondeur 0
```php
$etudiantsInscrits = isset($GLOBALS['etudiantsInscrits']) ? $GLOBALS['etudiantsInscrits'] : [];
```
#### [CALCUL] Profondeur 0
```php
$page_actuelle = isset($_GET['page_versements']) ? (int)$_GET['page_versements'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $items_par_page);
```
#### [CALCUL] Profondeur 0
```php
$debut = ($page_actuelle - 1) * $items_par_page;
```
#### [CALCUL] Profondeur 0
```php
$pourcentageComplete = $totalEtudiants > 0 ? round(($complete / $totalEtudiants) * 100) : 0;
```
#### [CALCUL] Profondeur 0
```php
$pourcentagePartial = $totalEtudiants > 0 ? round(($partial / $totalEtudiants) * 100) : 0;
```
#### [CALCUL] Profondeur 0
```php
$pourcentagePending = count($listeAllEtudiant) > 0 ? round(($totalEtudiants / count($listeAllEtudiant)) * 100) : 0;
```
#### [CALCUL] Profondeur 0
```php
$debut_pagination = max(1, $page_actuelle - 2);
```
#### [CALCUL] Profondeur 0
```php
$fin_pagination = min($total_pages, $page_actuelle + 2);
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_scolarite<?php echo isset($GLOBALS[`
- `?page=gestion_scolarite`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_utilisateurs_content.php

### 1. DATA MAPPING (VARIABLES)
- `$utilisateur_a_modifier`
- `$showModal`
- `$utilisateurs`
- `$niveau_acces`
- `$types_utilisateur`
- `$groupes_utilisateur`
- `$allUtilisateurs`
- `$totalUtilisateurs`
- `$utilisateursActifs`
- `$utilisateursInactifs`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$utilisateur`
- `$total_items`
- `$total_pages`
- `$enseignantsNonUtilisateurs`
- `$enseignant`
- `$personnelNonUtilisateurs`
- `$personnel`
- `$etudiantsNonUtilisateurs`
- `$etudiant`
- `$type`
- `$groupe`
- `$niveau`
- `$grouped`
- `$user`
- `$groupName`
- `$usersGroup`
- `$start`
- `$end`
- `$searchParam`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($page < 1)
```
#### [CONDITION] Profondeur 0
```php
elseif($page > $total_pages && $total_pages > 0)
```
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 1
```php
if($start > 2)
```
#### [CONDITION] Profondeur 0
```php
if($end < $total_pages)
```
#### [CALCUL] Profondeur 1
```php
$searchParam = !empty($search) ? '&search=' . urlencode($search) : '';
```
#### [CONDITION] Profondeur 0
```php
if(e.target === userModal)
```
#### [CONDITION] Profondeur 0
```php
if(searchTerm === '')
```
#### [CONDITION] Profondeur 0
```php
if(filteredUsers.length > 0)
```
#### [CONDITION] Profondeur 0
```php
if(!hasVisibleResults)
```
#### [CONDITION] Profondeur 0
```php
if(visibleRows.length === 0)
```
#### [CONDITION] Profondeur 0
```php
if(checkedBoxes.length > 0)
```
#### [CONDITION] Profondeur 0
```php
if(checkedBoxes.length > 0)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === disableModal)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === modifyModal)
```
#### [CONDITION] Profondeur 0
```php
if(selectedOption && selectedOption.dataset.login)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === masseModal)
```
#### [CONDITION] Profondeur 0
```php
if(action === 'addMasse')
```
#### [CONDITION] Profondeur 0
```php
if(e.key === 'Shift' && e.shiftKey)
```
#### [BOUCLE] Profondeur 1
```php
for(let i = start; i <= end; i++)
```
#### [CONDITION] Profondeur 0
```php
if(e.ctrlKey || e.metaKey)
```
#### [CONDITION] Profondeur 1
```php
if(option.tagName === 'OPTION')
```
#### [CONDITION] Profondeur 0
```php
if(successMessage || errorMessage)
```
#### [BOUCLE] Profondeur 0
```php
foreach($utilisateurs as $user)
```
#### [CALCUL] Profondeur 1
```php
$groupName = $user->lib_GU ?? 'Sans groupe';
```
#### [CALCUL] Profondeur 0
```php
$utilisateurs = $GLOBALS['utilisateurs'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$allUtilisateurs = $GLOBALS['utilisateurs'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$utilisateursActifs = count(array_filter($allUtilisateurs, function($u) { return $u->statut_utilisateur === 'Actif';
```
#### [CALCUL] Profondeur 0
```php
$utilisateursInactifs = $totalUtilisateurs - $utilisateursActifs;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$allUtilisateurs = array_filter($allUtilisateurs, function($utilisateur) use ($search) {
        return stripos($utilisateur->nom_utilisateur, $search) !== false ||
               stripos($utilisateur->prenom_utilisateur, $search) !== false ||
               stripos($utilisateur->email_utilisateur, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$groupName => $usersGroup):
                        ?>
                        <tr class="bg-gray-50 group-header" data-group="<?php echo htmlspecialchars(md5($groupName));
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                            <?= $i ?>
                        </a>
                        <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_utilisateurs`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**
- **ModalOverlay**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : import_result.php

### 1. DATA MAPPING (VARIABLES)
- `$importSummary`
- `$messageSuccess`
- `$messageErreur`
- `$hasErrors`
- `$index`
- `$error`
- `$success`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CALCUL] Profondeur 0
```php
$importSummary = $GLOBALS['importSummary'] ?? ['total_success' => 0, 'total_errors' => 0, 'successes' => [], 'errors' => []];
```
#### [CALCUL] Profondeur 0
```php
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$messageErreur = $GLOBALS['messageErreur'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$hasErrors = ($importSummary['total_errors'] ?? 0) > 0;
```
#### [CALCUL] Profondeur 0
```php
$index => $error): ?>
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 font-mono"><?php echo htmlspecialchars($error['line'] ?? 'N/A');
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : liste_etudiants_content.php

### 1. DATA MAPPING (VARIABLES)
- `$pdo`
- `$anneeAcademiqueModel`
- `$niveauEtudeModel`
- `$etudiantModel`
- `$enseignantModel`
- `$specialiteModel`
- `$ecueModel`
- `$ueModel`
- `$estAdministrateur`
- `$enseignant`
- `$enseignantId`
- `$niveauxResponsable`
- `$specialitesResponsable`
- `$uesEnseignant`
- `$ecuesEnseignant`
- `$typeAffichage`
- `$titre`
- `$sousTitre`
- `$niv`
- `$spec`
- `$estResponsableNiveau`
- `$estResponsableFiliere`
- `$estEnseignantSimple`
- `$listeAnnees`
- `$listeNiveaux`
- `$etudiants`
- `$search`
- `$promotion`
- `$niveau`
- `$ecue`
- `$filteredEtudiants`
- `$etudiant`
- `$matchesSearch`
- `$matchesPromotion`
- `$matchesNiveau`
- `$niveauIds`
- `$ueItem`
- `$ecueItem`
- `$matchesUe`
- `$ecueEnseignant`
- `$ueEnseignant`
- `$matchesEcue`
- `$perPage`
- `$totalEtudiants`
- `$totalPages`
- `$startIndex`
- `$etudiantsPage`
- `$uesEnseignantIndexed`
- `$ecuesEnseignantIndexed`
- `$annee`
- `$lib`
- `$data`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(!$estAdministrateur)
```
#### [CONDITION] Profondeur 1
```php
if(!$enseignant)
```
#### [CALCUL] Profondeur 1
```php
$enseignant = $enseignantModel->getEnseignantByLogin($_SESSION['login_utilisateur']);
```
#### [CALCUL] Profondeur 1
```php
$enseignantId = $enseignant->id_enseignant;
```
#### [CONDITION] Profondeur 0
```php
switch($typeAffichage)
```
#### [CONDITION] Profondeur 1
```php
if($ue !== '')
```
#### [CONDITION] Profondeur 2
```php
if($ecueEnseignant->id_ue == $ue && $etudiant->id_niv_etude == $ecueEnseignant->id_niveau_etude)
```
#### [BOUCLE] Profondeur 2
```php
foreach($ecuesEnseignant as $ecueEnseignant)
```
#### [BOUCLE] Profondeur 2
```php
foreach($uesEnseignant as $ueEnseignant)
```
#### [CONDITION] Profondeur 1
```php
if($ecue !== '')
```
#### [CONDITION] Profondeur 2
```php
if($ecueEnseignant->id_ecue == $ecue && $etudiant->id_niv_etude == $ecueEnseignant->id_niveau_etude)
```
#### [BOUCLE] Profondeur 2
```php
foreach($ecuesEnseignant as $ecueEnseignant)
```
#### [BOUCLE] Profondeur 1
```php
foreach($uesEnseignant as $ueItem)
```
#### [BOUCLE] Profondeur 1
```php
foreach($ecuesEnseignant as $ecueItem)
```
#### [CALCUL] Profondeur 1
```php
$filteredEtudiants = array_filter($etudiants, function ($etudiant) use ($search, $promotion, $niveau) {
            $matchesSearch = $search === '' ||
                strpos(strtolower($etudiant->nom_etu), $search) !== false ||
                strpos(strtolower($etudiant->prenom_etu), $search) !== false ||
                strpos(strtolower($etudiant->email_etu), $search) !== false;
```
#### [CALCUL] Profondeur 1
```php
$matchesPromotion = $promotion === '' || (isset($etudiant->id_annee_acad) && $etudiant->id_annee_acad == $promotion);
```
#### [CALCUL] Profondeur 1
```php
$matchesNiveau = $niveau === '' || (isset($etudiant->id_niv_etude) && $etudiant->id_niv_etude == $niveau);
```
#### [CALCUL] Profondeur 1
```php
$niveauIds = array_map(function ($niv) { return $niv->id_niv_etude;
```
#### [CALCUL] Profondeur 1
```php
$filteredEtudiants = array_filter($etudiants, function ($etudiant) use ($search, $promotion, $niveau, $niveauIds) {
            $matchesSearch = $search === '' ||
                strpos(strtolower($etudiant->nom_etu), $search) !== false ||
                strpos(strtolower($etudiant->prenom_etu), $search) !== false ||
                strpos(strtolower($etudiant->email_etu), $search) !== false;
```
#### [CALCUL] Profondeur 1
```php
$matchesNiveau = $niveau === '' ? in_array($etudiant->id_niv_etude, $niveauIds) : ($etudiant->id_niv_etude == $niveau);
```
#### [CALCUL] Profondeur 1
```php
$filteredEtudiants = array_filter($etudiants, function ($etudiant) use ($niveauIds, $search, $promotion, $ue, $ecue, $ecuesEnseignant, $uesEnseignant) {
            if (!in_array($etudiant->id_niv_etude, $niveauIds)) {
                return false;
```
#### [CALCUL] Profondeur 1
```php
$matchesSearch = $search === '' ||
                strpos(strtolower($etudiant->nom_etu), $search) !== false ||
                strpos(strtolower($etudiant->prenom_etu), $search) !== false ||
                strpos(strtolower($etudiant->email_etu), $search) !== false;
```
#### [CONDITION] Profondeur 0
```php
if($typeAffichage === 'enseignant')
```
#### [BOUCLE] Profondeur 1
```php
foreach($uesEnseignant as $ueItem)
```
#### [BOUCLE] Profondeur 1
```php
foreach($ecuesEnseignant as $ecueItem)
```
#### [CALCUL] Profondeur 0
```php
$pdo = Database::getConnection();
```
#### [CALCUL] Profondeur 0
```php
$niveauxResponsable = array_filter($niveauEtudeModel->getAllNiveauxEtudes(), function ($niv) use ($enseignantId) {
        return isset($niv->id_enseignant) && $niv->id_enseignant == $enseignantId;
```
#### [CALCUL] Profondeur 0
```php
$specialitesResponsable = array_filter($specialiteModel->getAllSpecialites(), function ($spec) use ($enseignantId) {
        return isset($spec->id_enseignant) && $spec->id_enseignant == $enseignantId;
```
#### [CALCUL] Profondeur 0
```php
$uesEnseignant = $ueModel->getUesByEnseignant($enseignantId);
```
#### [CALCUL] Profondeur 0
```php
$ecuesEnseignant = $ecueModel->getEcuesByEnseignant($enseignantId);
```
#### [CALCUL] Profondeur 0
```php
$sousTitre = 'Mes UE/ECUE';
```
#### [CALCUL] Profondeur 0
```php
$listeAnnees = $anneeAcademiqueModel->getAllAnneeAcademiques();
```
#### [CALCUL] Profondeur 0
```php
$listeNiveaux = $niveauEtudeModel->getAllNiveauxEtudes();
```
#### [CALCUL] Profondeur 0
```php
$etudiants = $etudiantModel->getAllListeEtudiants();
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
```
#### [CALCUL] Profondeur 0
```php
$promotion = isset($_GET['promotion']) ? $_GET['promotion'] : '';
```
#### [CALCUL] Profondeur 0
```php
$niveau = isset($_GET['niveau']) ? $_GET['niveau'] : '';
```
#### [CALCUL] Profondeur 0
```php
$ue = isset($_GET['ue']) ? $_GET['ue'] : '';
```
#### [CALCUL] Profondeur 0
```php
$ecue = isset($_GET['ecue']) ? $_GET['ecue'] : '';
```
#### [CALCUL] Profondeur 0
```php
$totalPages = ($totalEtudiants > 0) ? ceil($totalEtudiants / $perPage) : 1;
```
#### [CALCUL] Profondeur 0
```php
$p = isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p'] > 0 ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$startIndex = ($p - 1) * $perPage;
```
#### [CALCUL] Profondeur 0
```php
$typeAffichage === 'enseignant') {
    foreach ($uesEnseignant as $ueItem) {
        $uesEnseignantIndexed[$ueItem->id_ue] = $ueItem->lib_ue;
```
#### [CALCUL] Profondeur 0
```php
$promotion == $annee->id_annee_acad ? 'selected' : '' ?>>
                            <?= htmlspecialchars(date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin))) ?>
                        </option>
                    <?php endforeach;
```
#### [CALCUL] Profondeur 0
```php
$typeAffichage === 'responsable_niveau'): ?>
                    <select name="niveau"
                        class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                        <option value="">Tous mes niveaux</option>
                        <?php foreach ($niveauxResponsable as $niv): ?>
                            <option value="<?= htmlspecialchars($niv->id_niv_etude) ?>" <?= $niveau == $niv->id_niv_etude ? 'selected' : '' ?>>
                                <?= htmlspecialchars($niv->lib_niv_etude) ?>
                            </option>
                        <?php endforeach;
```
#### [CALCUL] Profondeur 0
```php
$typeAffichage === 'responsable_filiere' || $typeAffichage === 'administrateur'): ?>
                    <select name="niveau"
                        class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                        <option value="">Tous les Niveaux</option>
                        <?php foreach ($listeNiveaux as $niv): ?>
                            <option value="<?= htmlspecialchars($niv->id_niv_etude) ?>" <?= $niveau == $niv->id_niv_etude ? 'selected' : '' ?>>
                                <?= htmlspecialchars($niv->lib_niv_etude) ?>
                            </option>
                        <?php endforeach;
```
#### [CALCUL] Profondeur 0
```php
$typeAffichage === 'enseignant'): ?>
                    <select name="ue"
                        class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                        <option value="">Toutes les UE</option>
                        <?php foreach ($uesEnseignantIndexed as $id => $lib): ?>
                            <option value="<?= htmlspecialchars($id) ?>" <?= $ue == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lib) ?>
                            </option>
                        <?php endforeach;
```
#### [CALCUL] Profondeur 0
```php
$id => $data): ?>
                            <option value="<?= htmlspecialchars($id) ?>" <?= $ecue == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($data['lib_ecue'] . ' (' . $data['lib_ue'] . ')') ?>
                            </option>
                        <?php endforeach;
```
#### [CALCUL] Profondeur 0
```php
$typeAffichage === 'administrateur'): ?>
                                                <?php if (canEdit()): ?>
                                                <a href="?page=modifier_etudiant&id=<?= htmlspecialchars($etudiant->num_etu ?? '') ?>" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-green-500 text-white text-xs font-medium rounded hover:bg-green-600 transition"
                                                    title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif;
```
#### [CALCUL] Profondeur 0
```php
$typeAffichage === 'enseignant' ? '&ue=' . htmlspecialchars($ue) . '&ecue=' . htmlspecialchars($ecue) : '' ?>"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                <i class="fas fa-chevron-left mr-2"></i>Précédent
                            </a>
                        <?php endif;
```

### 3. PERMISSIONS & HABILITATIONS
- `canView()`
- `canEdit()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=supprimer_etudiant&id=${idEtudiant}`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : notes_resultats_content.php

### 1. DATA MAPPING (VARIABLES)
- `$etudiant`
- `$moyenneGenerale`
- `$nbUeValide`
- `$classement`
- `$totalEtudiants`
- `$notes`
- `$semestres`
- `$semestre`
- `$note`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(selectedSemester === 'all' || semesterCell.textContent ===
                        selectedSemester)
```
#### [CONDITION] Profondeur 0
```php
if(row.style.display !== 'none')
```
#### [CALCUL] Profondeur 0
```php
$etudiant = $GLOBALS['etudiant'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$moyenneGenerale = $GLOBALS['moyenneGenerale'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$nbUeValide = $GLOBALS['nbUeValide'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$classement = $GLOBALS['classement'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$totalEtudiants = $GLOBALS['totalEtudiants'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$notes = $GLOBALS['notes'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$semestres = $GLOBALS['semestres'] ?? [];
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **SmartSelect**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : parametres_generaux_content.php

### 1. DATA MAPPING (VARIABLES)
- `$cardPGeneraux`
- `$card`

### 2. LOGIQUE MÉTIER EXTRACTÉE
_Aucune logique complexe_

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
_Aucun pattern UI_

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : parametres_specifiques_content.php

### 1. DATA MAPPING (VARIABLES)
- `$cardPSpecifiques`
- `$card`

### 2. LOGIQUE MÉTIER EXTRACTÉE
_Aucune logique complexe_

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
_Aucun pattern UI_

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : piste_audit_content.php

### 1. DATA MAPPING (VARIABLES)
- `$action`
- `$auditLog`
- `$key`
- `$log`
- `$totalPages`
- `$page`
- `$perPage`
- `$totalLogs`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
switch($action)
```
#### [CONDITION] Profondeur 0
```php
switch($_GET['error'])
```
#### [CALCUL] Profondeur 0
```php
$i = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$i == $page): ?>
                                <span class="pagination-item"
                                    style="background:var(--ufhb-green);
```

### 3. PERMISSIONS & HABILITATIONS
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=piste_audit`
- `?page=piste_audit&action=cleanup`
- `?page=piste_audit&action=delete_log`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**
- **ModalOverlay**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : plannificaiton_soutenance_content.php

### 1. DATA MAPPING (VARIABLES)
- `$controller`
- `$message`
- `$messageType`
- `$result`
- `$etudiantsAvecJury`
- `$etudiantsDisponibles`
- `$salles`
- `$planifications`
- `$etudiant`
- `$salle`
- `$index`
- `$planification`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($_SERVER['REQUEST_METHOD'] === 'POST')
```
#### [CONDITION] Profondeur 1
```php
switch($_POST['action'])
```
#### [CALCUL] Profondeur 2
```php
$result = $controller->planifierSoutenance();
```
#### [CALCUL] Profondeur 2
```php
$messageType = $result['success'] ? 'success' : 'error';
```
#### [CALCUL] Profondeur 2
```php
$result = $controller->supprimerPlanification();
```
#### [CONDITION] Profondeur 0
```php
if(selectedOption && selectedOption.value)
```
#### [CONDITION] Profondeur 0
```php
if(!etudiantSelect)
```
#### [CONDITION] Profondeur 0
```php
if(!planification)
```
#### [CALCUL] Profondeur 0
```php
$etudiantsAvecJury = $controller->getEtudiantsAvecJuryForView();
```
#### [CALCUL] Profondeur 0
```php
$etudiantsDisponibles = $controller->getEtudiantsDisponiblesForView();
```
#### [CALCUL] Profondeur 0
```php
$salles = $controller->getSallesForView();
```
#### [CALCUL] Profondeur 0
```php
$planifications = $controller->getPlanificationsForView();
```
#### [CALCUL] Profondeur 0
```php
$messageType === 'success' ? 'bg-green-600' : 'bg-red-600' ?> text-white px-6 py-4 rounded-lg shadow-lg">
            <div class="flex items-center">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        </div>
    </div>
    <script>
        
        setTimeout(() => {
            const notification = document.querySelector('.fixed.top-4.right-4');
```
#### [CALCUL] Profondeur 0
```php
$index => $planification): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= $index + 1 ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($planification['nom_etudiant']) ?>
                                </div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($planification['matricule_etudiant']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate"
                                    title="<?= htmlspecialchars($planification['theme_soutenance']) ?>">
                                    <?= htmlspecialchars($planification['theme_soutenance']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars($planification['nom_salle'] ?? 'Non définie') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= $planification['date_soutenance'] ? date('d/m/Y', strtotime($planification['date_soutenance'])) : '-' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= $planification['heure_soutenance'] ? date('H:i', strtotime($planification['heure_soutenance'])) : '-' ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Planifiée
                                </span>
                            </td>
                            <?php if (canEdit() || canDelete()): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <?php if (canEdit()): ?>
                                    <button onclick="editPlanification(<?= $planification['id_programmation'] ?>)"
                                        class="bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-medium py-1 px-3 rounded transition-colors duration-200">
                                        Modifier
                                    </button>
                                    <?php endif;
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`
- `canEdit()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **DateTimeManager**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : processus_validation_content.php

### 1. DATA MAPPING (VARIABLES)
- `$controller`
- `$message`
- `$id_rapport`
- `$id_enseignant`
- `$donnees`
- `$commentaire`
- `$result`
- `$statistiques`
- `$rapports`
- `$membresCommission`
- `$membre`
- `$rapport`
- `$evaluation`
- `$evaluateursIds`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(!$id_enseignant)
```
#### [CALCUL] Profondeur 1
```php
$donnees = $controller->getDonneesPage();
```
#### [CONDITION] Profondeur 0
```php
if($id_enseignant && $id_rapport)
```
#### [CALCUL] Profondeur 1
```php
$result = $controller->finaliserRapport($id_rapport, $id_enseignant, $commentaire);
```
#### [CONDITION] Profondeur 0
```php
if(statusFilter && report.dataset.status !== statusFilter)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === this)
```
#### [CALCUL] Profondeur 0
```php
$commentaire = $_POST['commentaire_validation'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$message = ['success' => false, 'message' => 'Erreur : ID enseignant manquant'];
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=rapport_a_valider&action=consulter&id=`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**
- **RichTextEditor**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : profil_content.php

### 1. DATA MAPPING (VARIABLES)
- `$nom_user`
- `$login_user`
- `$statut_user`
- `$niveau_acces`
- `$lib_GU`
- `$libelle_type_utilisateur`
- `$libelle_niveau_acces`
- `$libelle_GU`
- `$specialite`
- `$grade`
- `$fonction`
- `$date_grade`
- `$date_fonction`
- `$telephone`
- `$poste`
- `$date_embauche`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CALCUL] Profondeur 0
```php
$nom_user = $_SESSION['nom_utilisateur'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$login_user = $_SESSION['login_utilisateur'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$statut_user = $_SESSION['statut_utilisateur'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$niveau_acces = $_SESSION['niveau_acces'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$lib_GU = $_SESSION['lib_GU'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$libelle_type_utilisateur = $_SESSION['type_utilisateur'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$libelle_niveau_acces = $_SESSION['niveau_acces'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$libelle_GU = $_SESSION['lib_GU'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$specialite = $_SESSION['specialite'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$grade = $_SESSION['grade'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$fonction = $_SESSION['fonction'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$date_grade = $_SESSION['date_grade'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$date_fonction = $_SESSION['date_fonction'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$telephone = $_SESSION['telephone'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$poste = $_SESSION['poste'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$date_embauche = $_SESSION['date_embauche'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$libelle_type_utilisateur === 'Enseignant simple' || $libelle_type_utilisateur === 'Enseignant administratif'): ?>
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-6">Informations professionnelles</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <div class="mb-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Spécialité</label>
                                        <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                            <div class="flex items-center">
                                                <i class="fas fa-graduation-cap text-gray-400 mr-2"></i>
                                                <span><?= htmlspecialchars($specialite) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Grade</label>
                                        <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                            <div class="flex items-center">
                                                <i class="fas fa-award text-gray-400 mr-2"></i>
                                                <span><?= htmlspecialchars($grade) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="mb-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Fonction</label>
                                        <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                            <div class="flex items-center">
                                                <i class="fas fa-briefcase text-gray-400 mr-2"></i>
                                                <span><?= htmlspecialchars($fonction) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Date d'obtention du
                                            grade</label>
                                        <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                            <div class="flex items-center">
                                                <i class="fas fa-calendar-check text-gray-400 mr-2"></i>
                                                <span><?= htmlspecialchars($date_grade) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Date d'occupation de la
                                            fonction</label>
                                        <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                            <div class="flex items-center">
                                                <i class="fas fa-calendar-alt text-gray-400 mr-2"></i>
                                                <span><?= htmlspecialchars($date_fonction) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($libelle_type_utilisateur === 'Personnel administratif'): ?>
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-800 mb-6">Informations professionnelles</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <div class="mb-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                        <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                            <div class="flex items-center">
                                                <i class="fas fa-phone text-gray-400 mr-2"></i>
                                                <span><?= htmlspecialchars($telephone) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Poste</label>
                                        <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                            <div class="flex items-center">
                                                <i class="fas fa-briefcase text-gray-400 mr-2"></i>
                                                <span><?= htmlspecialchars($poste) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="mb-5">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Date d'embauche</label>
                                        <div class="relative rounded-lg pl-3 pr-4 py-2 border border-gray-200">
                                            <div class="flex items-center">
                                                <i class="fas fa-calendar-alt text-gray-400 mr-2"></i>
                                                <span><?= htmlspecialchars($date_embauche) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif;
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=profil&tab=password`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **FormLayout**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : Programation_soutenance_content.php

### 1. DATA MAPPING (VARIABLES)
- `$controller`
- `$etudiants`
- `$etudiantsDisponibles`
- `$enseignants`
- `$professeursTitulaires`
- `$attributions`
- `$etudiant`
- `$professeur`
- `$enseignant`
- `$attribution`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(option.value !== '')
```
#### [CONDITION] Profondeur 0
```php
switch(selectId)
```
#### [CONDITION] Profondeur 0
```php
if(!etudiantId || !theme)
```
#### [CALCUL] Profondeur 0
```php
$etudiants = $controller->getEtudiantsForView();
```
#### [CALCUL] Profondeur 0
```php
$etudiantsDisponibles = $controller->getEtudiantsDisponiblesForView();
```
#### [CALCUL] Profondeur 0
```php
$enseignants = $controller->getEnseignantsForView();
```
#### [CALCUL] Profondeur 0
```php
$professeursTitulaires = $controller->getProfesseursTitulairesForView();
```
#### [CALCUL] Profondeur 0
```php
$attributions = $controller->getAttributionsForView();
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`
- `canEdit()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=programation_soutenance&action=getAttributions`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : rapport_a_valider_content.php

### 1. DATA MAPPING (VARIABLES)
- `$database`
- `$pdo`
- `$rapportModel`
- `$evaluationModel`
- `$etudiantModel`
- `$auditLog`
- `$id_utilisateur`
- `$tousLesRapports`
- `$stats`
- `$rapport`
- `$rapportsAvecEtudiants`
- `$etudiant`
- `$evaluations`
- `$error`
- `$id_rapport`
- `$decision`
- `$commentaire`
- `$evaluationExistante`
- `$result`
- `$success_message`
- `$error_message`
- `$statutColors`
- `$statutLabels`
- `$statutClass`
- `$statutLabel`
- `$votes_valider`
- `$votes_rejeter`
- `$eval`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($_SERVER['REQUEST_METHOD'] === 'POST')
```
#### [CONDITION] Profondeur 1
```php
switch($_POST['action'])
```
#### [CONDITION] Profondeur 2
```php
if($id_rapport && $decision)
```
#### [CALCUL] Profondeur 3
```php
$evaluationExistante = $evaluationModel->evaluationExiste($id_rapport, $id_utilisateur);
```
#### [CALCUL] Profondeur 3
```php
$result = $evaluationModel->mettreAJourEvaluation($evaluationExistante['id_evaluation'], $decision, $commentaire);
```
#### [CALCUL] Profondeur 3
```php
$result = $evaluationModel->ajouterEvaluation($id_rapport, $id_utilisateur, $decision, $commentaire);
```
#### [CALCUL] Profondeur 2
```php
$id_rapport = $_POST['id_rapport'] ?? null;
```
#### [CALCUL] Profondeur 2
```php
$decision = $_POST['decision'] ?? null;
```
#### [CALCUL] Profondeur 2
```php
$commentaire = $_POST['commentaire'] ?? '';
```
#### [CONDITION] Profondeur 0
```php
if(rapport.evaluations && rapport.evaluations.length > 0)
```
#### [CONDITION] Profondeur 0
```php
if(mobileMenuButton && sidebar)
```
#### [CONDITION] Profondeur 0
```php
if(e.key === 'Escape')
```
#### [BOUCLE] Profondeur 0
```php
foreach($tousLesRapports as $rapport)
```
#### [BOUCLE] Profondeur 0
```php
foreach($tousLesRapports as $rapport)
```
#### [CALCUL] Profondeur 1
```php
$etudiant = $etudiantModel->getEtudiantById($rapport->num_etu);
```
#### [CALCUL] Profondeur 1
```php
$evaluations = $evaluationModel->getEvaluationsRapport($rapport->id_rapport);
```
#### [BOUCLE] Profondeur 0
```php
foreach($rapport->evaluations as $eval)
```
#### [CALCUL] Profondeur 0
```php
$pdo = $database->getConnection();
```
#### [CALCUL] Profondeur 0
```php
$tousLesRapports = $rapportModel->getAllRapports();
```
#### [CALCUL] Profondeur 0
```php
$error = "Erreur lors du chargement des rapports : " . $e->getMessage();
```
#### [CALCUL] Profondeur 0
```php
$success_message = $_SESSION['success_message'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$error_message = $_SESSION['error_message'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$statutColors = [
                                        'en_attente' => 'bg-yellow-100 text-yellow-800',
                                        'en_cours' => 'bg-blue-100 text-blue-800',
                                        'valider' => 'bg-green-100 text-green-800',
                                        'rejeter' => 'bg-red-100 text-red-800'
                                    ];
```
#### [CALCUL] Profondeur 0
```php
$statutClass = $statutColors[$rapport->statut_rapport] ?? 'bg-gray-100 text-gray-800';
```
#### [CALCUL] Profondeur 0
```php
$statutLabel = $statutLabels[$rapport->statut_rapport] ?? $rapport->statut_rapport;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **FormLayout**
- **SmartSelect**
- **SelectionGroup**
- **ActionTrigger**
- **RichTextEditor**
- **ModalOverlay**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : recu_versement.php

### 1. DATA MAPPING (VARIABLES)
- `$scolarite`
- `$versement`
- `$id_versement`
- `$maybeId`
- `$lastVersement`
- `$maybeIns`
- `$inscription`
- `$dateVersement`
- `$montantsAsOf`
- `$numeroRecu`
- `$nomEtudiant`
- `$prenomEtudiant`
- `$montant`
- `$methodePaiement`
- `$anneeAcademique`
- `$nomNiveau`
- `$montantScolarite`
- `$montantPaye`
- `$resteAPayer`
- `$scheme`
- `$baseUrl`
- `$logo1Path`
- `$logo2Path`
- `$logo1Data`
- `$logo2Data`
- `$type`
- `$data`
- `$qrPath`
- `$qrData`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(images.length === 0)
```
#### [CONDITION] Profondeur 0
```php
if(loadedCount === images.length)
```
#### [CONDITION] Profondeur 0
```php
if(document.readyState === 'complete')
```
#### [CALCUL] Profondeur 0
```php
$scolarite = new Scolarite(Database::getConnection());
```
#### [CALCUL] Profondeur 0
```php
$versement = $scolarite->getVersementById($id_versement);
```
#### [CALCUL] Profondeur 0
```php
$versement === false) {
    $maybeId = $id_versement ?? ($_GET['id'] ?? null);
```
#### [CALCUL] Profondeur 0
```php
$lastVersement = $scolarite->getLastVersementByInscription($maybeId);
```
#### [CALCUL] Profondeur 0
```php
$maybeIns = $scolarite->getInscriptionById($maybeId);
```
#### [CALCUL] Profondeur 0
```php
$versement = [
                    'id_versement' => $maybeIns['id_inscription'],
                    'id_inscription' => $maybeIns['id_inscription'],
                    'montant' => $maybeIns['montant_premier_versement'] ?? ($maybeIns['montant_paye'] ?? 0),
                    'date_versement' => $maybeIns['date_inscription'] ?? date('Y-m-d'),
                    'methode_paiement' => $maybeIns['methode_paiement'] ?? '',
                    'nom_etudiant' => $maybeIns['nom_etudiant'] ?? '',
                    'prenom_etudiant' => $maybeIns['prenom_etudiant'] ?? ''
                ];
```
#### [CALCUL] Profondeur 0
```php
$versement === false) {
    echo '<div style="padding:20px;
```
#### [CALCUL] Profondeur 0
```php
$inscription = $scolarite->getInscriptionById($versement['id_inscription']);
```
#### [CALCUL] Profondeur 0
```php
$dateVersement = $versement['date_versement'] ?? date('Y-m-d');
```
#### [CALCUL] Profondeur 0
```php
$montantsAsOf = $scolarite->getMontantsAsOf($versement['id_inscription'], $dateVersement);
```
#### [CALCUL] Profondeur 0
```php
$numeroRecu = ReceiptUtils::genererNumeroRecu($versement['id_versement'] ?? 0);
```
#### [CALCUL] Profondeur 0
```php
$numeroRecu = 'REC-' . ($versement['id_versement'] ?? '0');
```
#### [CALCUL] Profondeur 0
```php
$nomEtudiant = $versement['nom_etudiant'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$prenomEtudiant = $versement['prenom_etudiant'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$montant = floatval($versement['montant'] ?? 0);
```
#### [CALCUL] Profondeur 0
```php
$methodePaiement = $versement['methode_paiement'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$anneeAcademique = $inscription['annee_academique'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$nomNiveau = $inscription['nom_niveau'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$montantScolarite = floatval($montantsAsOf['montant_total'] ?? ($inscription['montant_total'] ?? 0));
```
#### [CALCUL] Profondeur 0
```php
$montantPaye = floatval($montantsAsOf['montant_paye'] ?? ($inscription['montant_paye'] ?? 0));
```
#### [CALCUL] Profondeur 0
```php
$resteAPayer = floatval($montantsAsOf['reste_a_payer'] ?? ($inscription['reste_a_payer'] ?? 0));
```
#### [CALCUL] Profondeur 0
```php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
```
#### [CALCUL] Profondeur 0
```php
$baseUrl = $scheme . ':
?>
<?php

$logo1Path = __DIR__ . '/../../public/image/logo_ufhb.png';
```
#### [CALCUL] Profondeur 0
```php
$logo2Path = __DIR__ . '/../../public/image/logo_mi_sbg.png';
```
#### [CALCUL] Profondeur 0
```php
$type = mime_content_type($logo1Path) ?: 'image/png';
```
#### [CALCUL] Profondeur 0
```php
$logo1Data = 'data:' . $type . ';
```
#### [CALCUL] Profondeur 0
```php
$type = mime_content_type($logo2Path) ?: 'image/png';
```
#### [CALCUL] Profondeur 0
```php
$logo2Data = 'data:' . $type . ';
```
#### [CALCUL] Profondeur 0
```php
$qrPath = __DIR__ . '/../../public/image/Lien_vers_l_acceuil_de_CM-1024.png';
```
#### [CALCUL] Profondeur 0
```php
$type = mime_content_type($qrPath) ?: 'image/png';
```
#### [CALCUL] Profondeur 0
```php
$qrData = 'data:' . $type . ';
```

### 3. PERMISSIONS & HABILITATIONS
- `checkAll()`

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : redaction_compte_rendu_content.php

### 1. DATA MAPPING (VARIABLES)
- `$rapports_valides`
- `$enseignants`
- `$notifType`
- `$notifMsg`
- `$rapport`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(selectedReports.length === 0)
```
#### [CONDITION] Profondeur 0
```php
if(selectedReports.length === 0)
```
#### [CONDITION] Profondeur 0
```php
switch(sectionType)
```
#### [CONDITION] Profondeur 0
```php
if(index + 1 < currentStep)
```
#### [CONDITION] Profondeur 0
```php
if(index + 1 === currentStep)
```
#### [CONDITION] Profondeur 0
```php
if(e.ctrlKey || e.metaKey)
```
#### [CONDITION] Profondeur 1
```php
switch(e.key)
```
#### [CONDITION] Profondeur 0
```php
if(e.key === 'Escape')
```
#### [CONDITION] Profondeur 0
```php
if(selected.length === 0)
```
#### [CONDITION] Profondeur 0
```php
if(selectedReports.length === 0)
```
#### [CONDITION] Profondeur 0
```php
if(notifType && notifMsg)
```
#### [CONDITION] Profondeur 1
```php
if(notifType === 'success')
```
#### [CALCUL] Profondeur 0
```php
$rapports_valides = $GLOBALS['rapports_valides'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$enseignants = $GLOBALS['enseignants'] ?? [];
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=redaction_compte_rendu`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **FormLayout**
- **SmartSelect**
- **ActionTrigger**
- **ModalOverlay**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : releve_notes.php

### 1. DATA MAPPING (VARIABLES)
- `$logoLeft`
- `$logoLeftUrl`
- `$type`
- `$data`
- `$baseUrl`
- `$logoRight`
- `$logoRightUrl`
- `$semestres`
- `$grade`
- `$lib_semestre`
- `$credit`
- `$sem`
- `$semIndex`
- `$types`
- `$totalCredits`
- `$totalMoy`
- `$totalCoef`
- `$sumMaj`
- `$credMaj`
- `$moyMaj`
- `$moyenne`
- `$code_ecue`
- `$code_ue`
- `$lib_ue`
- `$lib_ecue`
- `$code`
- `$sumMin`
- `$credMin`
- `$moyMin`
- `$totalNotes`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [BOUCLE] Profondeur 0
```php
foreach($GLOBALS['studentGrades'] as $grade)
```
#### [CALCUL] Profondeur 1
```php
$lib_semestre = is_object($grade) ? ($grade->lib_semestre ?? 'Semestre ?') : ($grade['lib_semestre'] ?? 'Semestre ?');
```
#### [CALCUL] Profondeur 1
```php
$credit = is_object($grade) ? ($grade->credit ?? 0) : ($grade['credit'] ?? 0);
```
#### [CALCUL] Profondeur 1
```php
$type = ($credit > 3) ? 'majeures' : 'mineures';
```
#### [BOUCLE] Profondeur 0
```php
foreach($types['majeures'] as $grade)
```
#### [CALCUL] Profondeur 1
```php
$moyenne = is_object($grade) ? ($grade->moyenne ?? 0) : ($grade['moyenne'] ?? 0);
```
#### [CALCUL] Profondeur 1
```php
$code_ecue = is_object($grade) ? ($grade->code_ecue ?? '') : ($grade['code_ecue'] ?? '');
```
#### [CALCUL] Profondeur 1
```php
$code_ue = is_object($grade) ? ($grade->code_ue ?? '') : ($grade['code_ue'] ?? '');
```
#### [CALCUL] Profondeur 1
```php
$lib_ue = is_object($grade) ? ($grade->lib_ue ?? '') : ($grade['lib_ue'] ?? '');
```
#### [CALCUL] Profondeur 1
```php
$lib_ecue = is_object($grade) ? ($grade->lib_ecue ?? '') : ($grade['lib_ecue'] ?? '');
```
#### [CALCUL] Profondeur 1
```php
$sumMaj += $moyenne * $credit;
```
#### [CALCUL] Profondeur 1
```php
$code = !empty($code_ecue) ? $code_ecue : (!empty($code_ue) ? $code_ue : '-');
```
#### [CALCUL] Profondeur 1
```php
$sumMin += $moyenne * $credit;
```
#### [BOUCLE] Profondeur 0
```php
foreach($GLOBALS['studentGrades'] as $grade)
```
#### [CALCUL] Profondeur 1
```php
$totalNotes += $moyenne * $credit;
```
#### [CALCUL] Profondeur 0
```php
$logoLeft = __DIR__ . '/../../public/image/logo_ufhb.png';
```
#### [CALCUL] Profondeur 0
```php
$logoLeftUrl = '/image/logo_ufhb.png';
```
#### [CALCUL] Profondeur 0
```php
$type = mime_content_type($logoLeft) ?: 'image/png';
```
#### [CALCUL] Profondeur 0
```php
$logoRight = __DIR__ . '/../../public/image/logo_mi_sbg.png';
```
#### [CALCUL] Profondeur 0
```php
$logoRightUrl = '/image/logo_mi_sbg.png';
```
#### [CALCUL] Profondeur 0
```php
$type = mime_content_type($logoRight) ?: 'image/png';
```
#### [CALCUL] Profondeur 0
```php
$sem => $types):
                $totalCredits = 0;
```
#### [CALCUL] Profondeur 0
```php
$moyMaj = $credMaj ? round($sumMaj / $credMaj, 2) : '-';
```
#### [CALCUL] Profondeur 0
```php
$moyMin = $credMin ? round($sumMin / $credMin, 2) : '-';
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : sauvegarde_restauration_content.php

### 1. DATA MAPPING (VARIABLES)
- `$backups`
- `$backup`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
switch($_GET['error'])
```
#### [CONDITION] Profondeur 0
```php
if(e.target === this)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === this)
```

### 3. PERMISSIONS & HABILITATIONS
- `isAdmin()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=sauvegarde_restauration&action=create`
- `?page=sauvegarde_restauration&action=delete`
- `?page=sauvegarde_restauration&action=restore`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : verification_candidatures_soutenance_content.php

### 1. DATA MAPPING (VARIABLES)
- `$rapports`
- `$nbRapports`
- `$statsRapports`
- `$statut`
- `$message`
- `$messageType`
- `$rapport`
- `$approb`
- `$apprList`
- `$last`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
switch($statut)
```
#### [CONDITION] Profondeur 0
```php
switch($statut)
```
#### [CONDITION] Profondeur 0
```php
if(action === 'valider')
```
#### [CONDITION] Profondeur 0
```php
if(!response.ok)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === modal)
```
#### [CONDITION] Profondeur 0
```php
if(e.key === 'Escape')
```
#### [CALCUL] Profondeur 0
```php
$rapports = $GLOBALS['rapports'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$nbRapports = $GLOBALS['nbRapports'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$statsRapports = $GLOBALS['statsRapports'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$messageType = $_SESSION['message_type'] ?? 'info';
```
#### [CALCUL] Profondeur 0
```php
$messageType === 'success') ? 'bg-green-500 text-white' :
                (($messageType === 'error') ? 'bg-red-500 text-white' :
                    'bg-blue-500 text-white')) . '">';
```
#### [CALCUL] Profondeur 0
```php
$messageType === 'success') ? 'fa-check-circle' :
            (($messageType === 'error') ? 'fa-exclamation-circle' :
                'fa-info-circle')) . ' mr-2"></i>';
```
#### [CALCUL] Profondeur 0
```php
$i => $rapport): ?>
                                <tr
                                    class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-purple-50 transition-all duration-300">
                                    <td class="font-semibold text-gray-800">
                                        <div class="flex items-center gap-3">
                                            <div>
                                                <div class="font-semibold">
                                                    <?= htmlspecialchars($rapport->nom_etu . ' ' . $rapport->prenom_etu) ?>
                                                </div>
                                                <div class="text-sm text-gray-500">Étudiant</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($rapport->nom_rapport) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">Rapport de master</div>
                                    </td>
                                    <td>
                                        <div class="italic text-blue-700 max-w-xs truncate">
                                            <?= htmlspecialchars($rapport->theme_rapport) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="font-semibold text-gray-700"><?= date('d/m/Y', strtotime($rapport->date_depot)) ?></span>
                                        </div>
                                    </td>
                                    <?php
                                    
                                    $approb = null;
```
#### [CALCUL] Profondeur 0
```php
$apprList = Approuver::getByRapport($rapport->id_rapport);
```
#### [CALCUL] Profondeur 0
```php
$approb = isset($last['decision']) ? $last['decision'] : ($last->decision ?? null);
```
#### [CALCUL] Profondeur 0
```php
$approb === 'approuve'): ?>
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-800 text-sm font-semibold">
                                                <i class="fas fa-check mr-2"></i> Approuvé
                                            </span>
                                        <?php elseif ($approb === 'desapprouve' || $approb === 'rejete'): ?>
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full bg-red-100 text-red-800 text-sm font-semibold">
                                                <i class="fas fa-times mr-2"></i> Désapprouvé
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-800 text-sm font-semibold">
                                                <i class="fas fa-clock mr-2"></i> En attente
                                            </span>
                                        <?php endif;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=verification_candidatures_soutenance`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **ActionTrigger**
- **RichTextEditor**
- **ModalOverlay**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : archives_compte_rendu_content.php

### 1. DATA MAPPING (VARIABLES)
- `$search`
- `$year`
- `$stats`
- `$annee`
- `$archives`
- `$archive`
- `$totalPages`
- `$currentPage`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(filter === 'all')
```
#### [CONDITION] Profondeur 0
```php
if(mobileMenuButton && sidebar)
```
#### [CALCUL] Profondeur 0
```php
$year == $annee['annee']) ? 'active' : '';
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
- `ressources/routes/archivesCompteRenduRoutes.php?page=archives_compte_rendu&action=view&id=${id}`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **InputField**
- **ActionTrigger**
- **ModalOverlay**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : brouillons_compte_rendu_content.php

### 1. DATA MAPPING (VARIABLES)
_Aucune variable détectée_

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(d.id !== `dropdown${id}`)
```
#### [CONDITION] Profondeur 0
```php
switch(sortBy)
```
#### [CONDITION] Profondeur 0
```php
if(draftsToRender.length === 0)
```
#### [CONDITION] Profondeur 0
```php
if(e.key === 'Escape')
```
#### [CONDITION] Profondeur 0
```php
if(e.ctrlKey && e.key === 'f')
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `#editor`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : actions.php

### 1. DATA MAPPING (VARIABLES)
- `$action_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeActions`
- `$action`
- `$total_items`
- `$total_pages`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(!this.disabled)
```
#### [CALCUL] Profondeur 0
```php
$action_a_modifier = $GLOBALS['action_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeActions = $GLOBALS['listeActions'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeActions = array_filter($listeActions, function ($action) use ($search) {
        return stripos($action->lib_action, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'bg-green-50 text-green-600 border-green-500' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=actions`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : annees_academiques.php

### 1. DATA MAPPING (VARIABLES)
- `$annee_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeAnnees`
- `$annee`
- `$anneeStr`
- `$total_items`
- `$total_pages`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(!this.disabled)
```
#### [CALCUL] Profondeur 0
```php
$annee_a_modifier = $GLOBALS['annee_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeAnnees = $GLOBALS['listeAnnees'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeAnnees = array_filter($listeAnnees, function ($annee) use ($search) {
        $anneeStr = date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin));
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'btn-gradient-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=annees_academiques`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **DateTimeManager**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : criteres_evaluation.php

### 1. DATA MAPPING (VARIABLES)
_Aucune variable détectée_

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(filteredData.length === 0)
```
#### [CONDITION] Profondeur 0
```php
if(!anneeFilter)
```
#### [CONDITION] Profondeur 0
```php
if(critere.baremes.length === 0)
```
#### [CONDITION] Profondeur 0
```php
if(anneeSelect.value && pointsInput.value)
```
#### [CONDITION] Profondeur 1
```php
if(!anneesBaremes[anneeId])
```
#### [CONDITION] Profondeur 0
```php
if(!anneesBaremes[anneeId])
```
#### [CONDITION] Profondeur 0
```php
if(!anneesBaremes[anneeId])
```
#### [CONDITION] Profondeur 0
```php
if(!isValid)
```
#### [CONDITION] Profondeur 0
```php
if(!libCritere)
```
#### [CONDITION] Profondeur 0
```php
if(anneeSelect.value && pointsInput.value)
```
#### [CONDITION] Profondeur 0
```php
if(baremes.length === 0)
```
#### [CONDITION] Profondeur 0
```php
if(!anneesBaremes[bareme.annee_id])
```
#### [CONDITION] Profondeur 0
```php
if(totalBareme > 20)
```
#### [CONDITION] Profondeur 1
```php
if(isEditMode && critereId)
```
#### [CONDITION] Profondeur 0
```php
if(event.target === modal)
```
#### [BOUCLE] Profondeur 0
```php
for(const anneeId in anneesBaremes)
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`
- `canEdit()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=criteres_evaluation&action=getAnnees`
- `?page=criteres_evaluation&action=getCriteres`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : ecue.php

### 1. DATA MAPPING (VARIABLES)
- `$ecue_a_modifier`
- `$listeEcues`
- `$listeUes`
- `$ue_selected`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$ecue`
- `$total_items`
- `$total_pages`
- `$pageSlug`
- `$enseignant`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($ue->id_ue == $ecue_a_modifier->id_ue)
```
#### [CONDITION] Profondeur 0
```php
if($ue->id_ue == $_POST['id_ue'])
```
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(!this.disabled)
```
#### [CONDITION] Profondeur 0
```php
if(!ueId || ueId === '')
```
#### [BOUCLE] Profondeur 0
```php
foreach($listeUes as $ue)
```
#### [BOUCLE] Profondeur 0
```php
foreach($listeUes as $ue)
```
#### [CALCUL] Profondeur 0
```php
$ecue_a_modifier = $GLOBALS['ecue_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$listeEcues = $GLOBALS['listeEcues'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeUes = $GLOBALS['listeUes'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeEcues = array_filter($listeEcues, function ($ecue) use ($search) {
        return stripos($ecue->lib_ecue, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$pageSlug = $_GET['page'] ?? 'parametres_generaux';
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'bg-green-50 text-green-600 border-green-500' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=<?= $pageSlug ?>&action=ecue`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : entreprises.php

### 1. DATA MAPPING (VARIABLES)
- `$entreprise_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$pageSlug`
- `$listeEntreprises`
- `$entreprise`
- `$total_items`
- `$total_pages`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === deleteModal)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === modifyModal)
```
#### [CALCUL] Profondeur 0
```php
$entreprise_a_modifier = $GLOBALS['entreprise_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$pageSlug = $_GET['page'] ?? 'parametres_generaux';
```
#### [CALCUL] Profondeur 0
```php
$listeEntreprises = $GLOBALS['listeEntreprises'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeEntreprises = array_filter($listeEntreprises, function ($entreprise) use ($search) {
        return stripos($entreprise->lib_entreprise, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'btn-gradient-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=<?= $pageSlug ?>&action=entreprises`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : fonctions.php

### 1. DATA MAPPING (VARIABLES)
- `$fonction_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeFonctions`
- `$fonction`
- `$total_items`
- `$total_pages`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === deleteModal)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === modifyModal)
```
#### [CALCUL] Profondeur 0
```php
$fonction_a_modifier = $GLOBALS['fonction_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeFonctions = $GLOBALS['listeFonctions'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeFonctions = array_filter($listeFonctions, function ($fonction) use ($search) {
        return stripos($fonction->lib_fonction, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'btn-gradient-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=fonctions`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : fonction_utilisateur.php

### 1. DATA MAPPING (VARIABLES)
- `$groupe_a_modifier`
- `$type_a_modifier`
- `$listeGroupe`
- `$listeType`
- `$listeTypesAll`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$groupe`
- `$type`
- `$total_items_groupe`
- `$total_pages_groupe`
- `$total_items_type`
- `$total_pages_type`
- `$activeTab`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(selectAllCheckbox && deleteSelectedBtn)
```
#### [CONDITION] Profondeur 1
```php
if(e.target.name === 'selected_ids[]')
```
#### [CONDITION] Profondeur 0
```php
if(deleteBtn && form)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === deleteModal)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === modifyModal)
```
#### [CALCUL] Profondeur 0
```php
$groupe_a_modifier = $GLOBALS['groupe_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$type_a_modifier = $GLOBALS['type_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$listeGroupe = $GLOBALS['listeGroupes'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeType = $GLOBALS['listeTypes'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeTypesAll = $GLOBALS['listeTypesAll'] ?? $listeType;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeGroupe = array_filter($listeGroupe, function ($groupe) use ($search) {
        return stripos($groupe->lib_GU, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$listeType = array_filter($listeType, function ($type) use ($search) {
        return stripos($type->lib_type_utilisateur, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages_groupe = ceil($total_items_groupe / $limit);
```
#### [CALCUL] Profondeur 0
```php
$total_pages_type = ceil($total_items_type / $limit);
```
#### [CALCUL] Profondeur 0
```php
$activeTab = $_GET['tab'] ?? 'groupes';
```
#### [CALCUL] Profondeur 0
```php
$activeTab === 'groupes') ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-users-cog mr-2"></i>Groupes d'Utilisateurs
                    </a>
                    <a href="?page=parametres_generaux&action=fonction_utilisateur&tab=types"
                        class="<?= ($activeTab === 'types') ? 'border-green-500 text-green-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?> whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-user-tag mr-2"></i>Types d'Utilisateurs
                    </a>
                </nav>
            </div>

            <!-- Contenu de l'onglet Groupes -->
            <?php if ($activeTab === 'groupes'): ?>
                <div class="space-y-6">
                    <!-- Formulaire d'ajout/modification -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-600 mb-4 flex items-center">
                            <i
                                class="fas <?= isset($_GET['id_groupe']) ? 'fa-edit text-green-500' : 'fa-plus-circle text-green-500' ?> mr-2"></i>
                            <?= isset($_GET['id_groupe']) ? 'Modifier le groupe' : 'Ajouter un nouveau groupe' ?>
                        </h3>
                        <form method="POST" action="?page=parametres_generaux&action=fonction_utilisateur&tab=groupes"
                            id="groupeForm">
                            <?php if ($groupe_a_modifier): ?>
                                <input type="hidden" name="id_groupe"
                                    value="<?= htmlspecialchars($groupe_a_modifier->id_GU) ?>">
                            <?php endif;
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-md text-sm font-medium transition-all duration-200">
                                                <?= $i ?>
                                            </a>
                                        <?php endfor;
```
#### [CALCUL] Profondeur 0
```php
$activeTab === 'types'): ?>
                <div class="space-y-6">
                    <!-- Formulaire d'ajout/modification -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-600 mb-4 flex items-center">
                            <i
                                class="fas <?= isset($_GET['id_type']) ? 'fa-edit text-green-500' : 'fa-plus-circle text-green-500' ?> mr-2"></i>
                            <?= isset($_GET['id_type']) ? 'Modifier le type' : 'Ajouter un nouveau type' ?>
                        </h3>
                        <form method="POST" action="?page=parametres_generaux&action=fonction_utilisateur&tab=types"
                            id="typeForm">
                            <?php if ($type_a_modifier): ?>
                                <input type="hidden" name="id_type_utilisateur"
                                    value="<?= htmlspecialchars($type_a_modifier->id_type_utilisateur) ?>">
                            <?php endif;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=fonction_utilisateur&tab=groupes`
- `?page=parametres_generaux&action=fonction_utilisateur&tab=types`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_attribution.php

### 1. DATA MAPPING (VARIABLES)
- `$listeGroupes`
- `$listeTypesAll`
- `$selectedTypeId`
- `$pageSlug`
- `$listeFonctionnalites`
- `$selectedGroupe`
- `$permissionsGroupe`
- `$messageSuccess`
- `$messageErreur`
- `$csrfToken`
- `$permByFoncId`
- `$url`
- `$query`
- `$params`
- `$page`
- `$action`
- `$tree`
- `$cat`
- `$catCode`
- `$catData`
- `$items`
- `$parentsByCode`
- `$childrenByParent`
- `$orphans`
- `$isSousPage`
- `$code`
- `$parentCode`
- `$children`
- `$pcode`
- `$hub`
- `$parents`
- `$isEditable`
- `$rowNum`
- `$parent`
- `$parentId`
- `$parentCodeF`
- `$parentLabel`
- `$hasChildren`
- `$groupId`
- `$parentPerm`
- `$disabled`
- `$nameBase`
- `$child`
- `$cid`
- `$ccode`
- `$clabel`
- `$cperm`
- `$cdisabled`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(!$query)
```
#### [CONDITION] Profondeur 0
```php
if($parentCode !== '')
```
#### [CONDITION] Profondeur 0
```php
if($code !== '')
```
#### [CONDITION] Profondeur 0
```php
if(v === undefined || v === null || v === '')
```
#### [CONDITION] Profondeur 0
```php
if(boxes.length === 0)
```
#### [BOUCLE] Profondeur 0
```php
foreach($permissionsGroupe as $p)
```
#### [BOUCLE] Profondeur 0
```php
foreach($listeFonctionnalites as $f)
```
#### [CALCUL] Profondeur 1
```php
$cat = (string)($f->lib_categorie ?? 'Autres');
```
#### [CALCUL] Profondeur 1
```php
$catCode = (string)($f->code_categorie ?? 'autres');
```
#### [BOUCLE] Profondeur 0
```php
foreach($tree as $catCode => &$catData)
```
#### [BOUCLE] Profondeur 1
```php
foreach($items as $f)
```
#### [CALCUL] Profondeur 2
```php
$isSousPage = !empty($f->est_sous_page);
```
#### [CALCUL] Profondeur 2
```php
$code = isset($f->code_fonctionnalite) ? (string)$f->code_fonctionnalite : '';
```
#### [CALCUL] Profondeur 2
```php
$parentCode = isset($f->page_parente) ? (string)$f->page_parente : '';
```
#### [BOUCLE] Profondeur 1
```php
foreach($parentsByCode as $code => $p)
```
#### [CALCUL] Profondeur 2
```php
$children = $childrenByParent[$code] ?? [];
```
#### [BOUCLE] Profondeur 1
```php
foreach($childrenByParent as $pcode => $children)
```
#### [CALCUL] Profondeur 1
```php
$code => $p) {
        $children = $childrenByParent[$code] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeGroupes = $GLOBALS['listeGroupes'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeTypesAll = $GLOBALS['listeTypesAll'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$selectedTypeId = $GLOBALS['selectedTypeId'] ?? ($_GET['type'] ?? 'all');
```
#### [CALCUL] Profondeur 0
```php
$pageSlug = $_GET['page'] ?? 'parametres_generaux';
```
#### [CALCUL] Profondeur 0
```php
$listeFonctionnalites = $GLOBALS['listeFonctionnalites'] ?? ($GLOBALS['listeTraitements'] ?? []);
```
#### [CALCUL] Profondeur 0
```php
$selectedGroupe = $GLOBALS['selectedGroupe'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$permissionsGroupe = $GLOBALS['permissionsGroupe'] ?? ($GLOBALS['attributionsGroupe'] ?? []);
```
#### [CALCUL] Profondeur 0
```php
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$messageErreur = $GLOBALS['messageErreur'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$csrfToken = \CheckMaster\Core\Csrf::token();
```
#### [CALCUL] Profondeur 0
```php
$page = isset($params['page']) ? (string) $params['page'] : '';
```
#### [CALCUL] Profondeur 0
```php
$action = isset($params['action']) ? (string) $params['action'] : '';
```
#### [CALCUL] Profondeur 0
```php
$isEditable = function_exists('canEdit') ? (bool)canEdit() : true;
```
#### [CALCUL] Profondeur 0
```php
$selectedTypeId === 'all') ? 'selected' : '' ?>>Tous les types</option>
                            <?php foreach ($listeTypesAll as $t): ?>
                                <option value="<?= (int)$t->id_type_utilisateur ?>" <?= ((string)$selectedTypeId === (string)$t->id_type_utilisateur) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t->lib_type_utilisateur) ?>
                                </option>
                            <?php endforeach;
```
#### [CALCUL] Profondeur 0
```php
$catCode => $catData): ?>
                                    <tr class="bg-gray-100">
                                        <td colspan="8" class="px-4 py-2 text-sm font-semibold text-gray-700">
                                            <?= htmlspecialchars($catData['label']) ?>
                                        </td>
                                    </tr>

                                    <?php foreach (($catData['parents'] ?? []) as $parent): ?>
                                        <?php
                                        $parentId = (int)($parent->id_fonctionnalite ?? 0);
```
#### [CALCUL] Profondeur 0
```php
$parentCodeF = (string)($parent->code_fonctionnalite ?? '');
```
#### [CALCUL] Profondeur 0
```php
$parentLabel = (string)($parent->label_fonctionnalite ?? $parent->lib_fonctionnalite ?? $parentCodeF);
```
#### [CALCUL] Profondeur 0
```php
$children = (isset($parent->children) && is_array($parent->children)) ? $parent->children : [];
```
#### [CALCUL] Profondeur 0
```php
$groupId = 'grp-' . md5($catCode . '|' . $parentCodeF . '|' . (string)$parentId);
```
#### [CALCUL] Profondeur 0
```php
$parentPerm = $parentId > 0 && isset($permByFoncId[$parentId]) ? $permByFoncId[$parentId] : null;
```
#### [CALCUL] Profondeur 0
```php
$nameBase = $parentId > 0 ? "permissions[$parentId]" : '';
```
#### [CALCUL] Profondeur 0
```php
$cid = (int)($child->id_fonctionnalite ?? 0);
```
#### [CALCUL] Profondeur 0
```php
$ccode = (string)($child->code_fonctionnalite ?? (string)$cid);
```
#### [CALCUL] Profondeur 0
```php
$clabel = (string)($child->label_fonctionnalite ?? $child->lib_fonctionnalite ?? $ccode);
```
#### [CALCUL] Profondeur 0
```php
$cperm = $cid > 0 && isset($permByFoncId[$cid]) ? $permByFoncId[$cid] : null;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : gestion_menus.php

### 1. DATA MAPPING (VARIABLES)
- `$categories`
- `$tree`
- `$isEditable`
- `$messageSuccess`
- `$messageErreur`
- `$csrfToken`
- `$cid`
- `$node`
- `$parents`
- `$pid`
- `$pcode`
- `$plabel`
- `$purl`
- `$pordre`
- `$children`
- `$hasChildren`
- `$groupId`
- `$chid`
- `$chcode`
- `$chlabel`
- `$churl`
- `$chordre`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CALCUL] Profondeur 0
```php
$categories = $GLOBALS['menuMgmtCategories'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$tree = $GLOBALS['menuMgmtTree'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$isEditable = $GLOBALS['menuMgmtIsEditable'] ?? (function_exists('canEdit') ? (bool)canEdit() : true);
```
#### [CALCUL] Profondeur 0
```php
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$messageErreur = $GLOBALS['messageErreur'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$csrfToken = \CheckMaster\Core\Csrf::token();
```
#### [CALCUL] Profondeur 0
```php
$cid => $node): ?>
                            <?php $c = $node['categorie'];
```
#### [CALCUL] Profondeur 0
```php
$parents = $node['parents'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$pid = (int)($p->id_fonctionnalite ?? 0);
```
#### [CALCUL] Profondeur 0
```php
$pcode = (string)($p->code_fonctionnalite ?? '');
```
#### [CALCUL] Profondeur 0
```php
$plabel = (string)($p->label_fonctionnalite ?? $p->lib_fonctionnalite ?? $pcode);
```
#### [CALCUL] Profondeur 0
```php
$purl = (string)($p->url_fonctionnalite ?? '#');
```
#### [CALCUL] Profondeur 0
```php
$pordre = (int)($p->ordre_fonctionnalite ?? 0);
```
#### [CALCUL] Profondeur 0
```php
$children = (isset($p->children) && is_array($p->children)) ? $p->children : [];
```
#### [CALCUL] Profondeur 0
```php
$groupId = 'grp-' . md5((string)$cid . '|' . $pcode . '|' . (string)$pid);
```
#### [CALCUL] Profondeur 0
```php
$chid = (int)($ch->id_fonctionnalite ?? 0);
```
#### [CALCUL] Profondeur 0
```php
$chcode = (string)($ch->code_fonctionnalite ?? '');
```
#### [CALCUL] Profondeur 0
```php
$chlabel = (string)($ch->label_fonctionnalite ?? $ch->lib_fonctionnalite ?? $chcode);
```
#### [CALCUL] Profondeur 0
```php
$churl = (string)($ch->url_fonctionnalite ?? '#');
```
#### [CALCUL] Profondeur 0
```php
$chordre = (int)($ch->ordre_fonctionnalite ?? 0);
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `isChecked()`

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : grades.php

### 1. DATA MAPPING (VARIABLES)
- `$grade_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeGrades`
- `$grade`
- `$total_items`
- `$total_pages`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(!this.disabled)
```
#### [CALCUL] Profondeur 0
```php
$grade_a_modifier = $GLOBALS['grade_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeGrades = $GLOBALS['listeGrade'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeGrades = array_filter($listeGrades, function ($grade) use ($search) {
        return stripos($grade->lib_grade, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'btn-gradient-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=grades`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : messages.php

### 1. DATA MAPPING (VARIABLES)
- `$message_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeMessages`
- `$message`
- `$total_items`
- `$total_pages`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
switch($message->type_message)
```
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(!this.disabled)
```
#### [CALCUL] Profondeur 0
```php
$message_a_modifier = $GLOBALS['message_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeMessages = $GLOBALS['listeMessages'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeMessages = array_filter($listeMessages, function ($message) use ($search) {
        return stripos($message->lib_message, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'bg-green-50 text-green-600 border-green-500' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=messages`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**
- **RichTextEditor**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : niveaux_acces.php

### 1. DATA MAPPING (VARIABLES)
- `$niveau_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeNiveaux`
- `$niveau_acces`
- `$total_items`
- `$total_pages`
- `$niveau`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === deleteModal)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === modifyModal)
```
#### [CALCUL] Profondeur 0
```php
$niveau_a_modifier = $GLOBALS['niveau_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeNiveaux = $GLOBALS['listeNiveaux'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeNiveaux = array_filter($listeNiveaux, function ($niveau_acces) use ($search) {
        return stripos($niveau_acces->lib_niv_acces, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'btn-gradient-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=niveaux_acces`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : niveaux_approbation.php

### 1. DATA MAPPING (VARIABLES)
- `$niveau_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeNiveaux`
- `$niveau`
- `$total_items`
- `$total_pages`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === deleteModal)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === modifyModal)
```
#### [CALCUL] Profondeur 0
```php
$niveau_a_modifier = $GLOBALS['niveau_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeNiveaux = $GLOBALS['listeNiveaux'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeNiveaux = array_filter($listeNiveaux, function ($niveau) use ($search) {
        return stripos($niveau->lib_approb, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'btn-gradient-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=niveaux_approbation`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : niveaux_etude.php

### 1. DATA MAPPING (VARIABLES)
- `$niveau_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeNiveaux`
- `$niveau`
- `$total_items`
- `$total_pages`
- `$enseignant`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(!this.disabled)
```
#### [CALCUL] Profondeur 0
```php
$niveau_a_modifier = $GLOBALS['niveau_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeNiveaux = $GLOBALS['listeNiveaux'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeNiveaux = array_filter($listeNiveaux, function ($niveau) use ($search) {
        return stripos($niveau->lib_niv_etude, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'bg-green-50 text-green-600 border-green-500' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=niveaux_etude`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : salles.php

### 1. DATA MAPPING (VARIABLES)
- `$salle_a_modifier`
- `$messageErreur`
- `$messageSuccess`
- `$pageSlug`
- `$pdo`
- `$lib_salle`
- `$stmt`
- `$checkStmt`
- `$selected_ids`
- `$success`
- `$usageStmt`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeSalles`
- `$total_items`
- `$total_pages`
- `$salle`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(!this.disabled)
```
#### [BOUCLE] Profondeur 0
```php
foreach($selected_ids as $id)
```
#### [CALCUL] Profondeur 1
```php
$usageStmt = $pdo->prepare("SELECT COUNT(*) FROM programmer WHERE id_salle = ?");
```
#### [CALCUL] Profondeur 1
```php
$stmt = $pdo->prepare("DELETE FROM salles WHERE id_salle = ?");
```
#### [CALCUL] Profondeur 0
```php
$pageSlug = $_GET['page'] ?? 'parametres_generaux';
```
#### [CALCUL] Profondeur 0
```php
$pdo = Database::getConnection();
```
#### [CALCUL] Profondeur 0
```php
$stmt = $pdo->prepare("UPDATE salles SET lib_salle = ? WHERE id_salle = ?");
```
#### [CALCUL] Profondeur 0
```php
$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM salles WHERE lib_salle = ?");
```
#### [CALCUL] Profondeur 0
```php
$stmt = $pdo->prepare("INSERT INTO salles (lib_salle) VALUES (?)");
```
#### [CALCUL] Profondeur 0
```php
$selected_ids = $_POST['selected_ids'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$stmt = $pdo->prepare("SELECT * FROM salles WHERE id_salle = ?");
```
#### [CALCUL] Profondeur 0
```php
$salle_a_modifier = $stmt->fetch(PDO::FETCH_OBJ);
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$stmt = $pdo->prepare("SELECT * FROM salles WHERE lib_salle LIKE ? ORDER BY lib_salle ASC");
```
#### [CALCUL] Profondeur 0
```php
$listeSalles = $stmt->fetchAll(PDO::FETCH_OBJ);
```
#### [CALCUL] Profondeur 0
```php
$stmt = $pdo->query("SELECT * FROM salles ORDER BY lib_salle ASC");
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'btn-gradient-primary text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=<?= $pageSlug ?>&action=salles`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : semestres.php

### 1. DATA MAPPING (VARIABLES)
- `$semestre_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeSemestres`
- `$semestre`
- `$total_items`
- `$total_pages`
- `$niveau`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(!this.disabled)
```
#### [CALCUL] Profondeur 0
```php
$semestre_a_modifier = $GLOBALS['semestre_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeSemestres = $GLOBALS['listeSemestres'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeSemestres = array_filter($listeSemestres, function ($semestre) use ($search) {
        return stripos($semestre->lib_semestre, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'bg-green-50 text-green-600 border-green-500' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=semestres`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : specialites.php

### 1. DATA MAPPING (VARIABLES)
- `$specialite_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeSpecialites`
- `$specialite`
- `$total_items`
- `$total_pages`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(!this.disabled)
```
#### [CALCUL] Profondeur 0
```php
$specialite_a_modifier = $GLOBALS['specialite_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeSpecialites = $GLOBALS['listeSpecialites'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeSpecialites = array_filter($listeSpecialites, function($specialite) use ($search) {
        return stripos($specialite->lib_specialite, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'bg-green-500 text-white' : 'bg-white text-gray-700 border border-gray-300' ?> rounded-lg text-sm font-medium hover:bg-gray-50">
                            <?= $i ?>
                        </a>
                        <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=specialites`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : statut_jury.php

### 1. DATA MAPPING (VARIABLES)
- `$statut_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeStatuts`
- `$statut`
- `$total_items`
- `$total_pages`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === deleteModal)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === modifyModal)
```
#### [CALCUL] Profondeur 0
```php
$statut_a_modifier = $GLOBALS['statut_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeStatuts = $GLOBALS['listeStatuts'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeStatuts = array_filter($listeStatuts, function ($statut) use ($search) {
        return stripos($statut->lib_jury, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'bg-green-50 text-green-600 border-green-500' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?>">
                                                    <?= $i ?>
                                                </a>
                                            <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=parametres_generaux&action=statut_jury`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : traitements.php

### 1. DATA MAPPING (VARIABLES)
- `$traitement_a_modifier`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$pageSlug`
- `$listeTraitements`
- `$traitement`
- `$total_items`
- `$total_pages`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(!this.disabled)
```
#### [CALCUL] Profondeur 0
```php
$traitement_a_modifier = $GLOBALS['traitement_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$pageSlug = $_GET['page'] ?? 'parametres_generaux';
```
#### [CALCUL] Profondeur 0
```php
$listeTraitements = $GLOBALS['listeTraitements'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeTraitements = array_filter($listeTraitements, function ($traitement) use ($search) {
        return stripos($traitement->lib_traitement, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'bg-green-50 text-green-600 border-green-500' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=<?= $pageSlug ?>&action=traitements`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : ue.php

### 1. DATA MAPPING (VARIABLES)
- `$ue_a_modifier`
- `$listeAnnees`
- `$listeNiveauxEtude`
- `$listeSemestres`
- `$page`
- `$limit`
- `$offset`
- `$search`
- `$listeUes`
- `$total_items`
- `$total_pages`
- `$pageSlug`
- `$semestresData`
- `$semestre`
- `$annee`
- `$niveau`
- `$enseignant`
- `$start`
- `$end`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 0
```php
if(!this.disabled)
```
#### [CALCUL] Profondeur 0
```php
$ue_a_modifier = $GLOBALS['ue_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$listeAnnees = $GLOBALS['listeAnnees'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeNiveauxEtude = $GLOBALS['listeNiveauxEtude'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeSemestres = $GLOBALS['listeSemestres'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
```
#### [CALCUL] Profondeur 0
```php
$offset = ($page - 1) * $limit;
```
#### [CALCUL] Profondeur 0
```php
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
```
#### [CALCUL] Profondeur 0
```php
$listeUes = $GLOBALS['listeUes'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$listeUes = array_filter($listeUes, function ($ue) use ($search) {
        return stripos($ue->lib_ue, $search) !== false;
```
#### [CALCUL] Profondeur 0
```php
$total_pages = ceil($total_items / $limit);
```
#### [CALCUL] Profondeur 0
```php
$pageSlug = $_GET['page'] ?? 'parametres_generaux';
```
#### [CALCUL] Profondeur 0
```php
$semestresData = array_map(function ($semestre) {
    return [
        'id_semestre' => $semestre->id_semestre,
        'lib_semestre' => $semestre->lib_semestre,
        'id_niv_etude' => $semestre->id_niv_etude
    ];
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $page - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($total_pages, $page + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $page ? 'bg-green-50 text-green-600 border-green-500' : 'bg-white text-gray-500 hover:bg-gray-50 border-gray-300' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=<?= $pageSlug ?>&action=ue`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **SelectionGroup**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : soumettre_reclamation.php

### 1. DATA MAPPING (VARIABLES)
- `$erreurs`
- `$erreur`
- `$typesReclamation`
- `$key`
- `$label`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(!counter)
```
#### [CALCUL] Profondeur 0
```php
$key => $label): ?>
                        <option value="<?php echo htmlspecialchars($key);
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : suivi_historique_reclamation.php

### 1. DATA MAPPING (VARIABLES)
- `$statistiques`
- `$reclamations`
- `$rec`
- `$totalPages`
- `$page`
- `$totalReclamations`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
switch($rec['type_reclamation'])
```
#### [CONDITION] Profondeur 0
```php
switch($rec['statut_reclamation'])
```
#### [CONDITION] Profondeur 0
```php
switch($rec['statut_reclamation'])
```
#### [CONDITION] Profondeur 0
```php
if(!response.ok)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === modal)
```

### 3. PERMISSIONS & HABILITATIONS
- `canCreate()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_reclamations&action=get_reclamation_details&id=${reclamationId}`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : commentaire_rapport.php

### 1. DATA MAPPING (VARIABLES)
- `$statistiquesCompteRendu`
- `$rapports`
- `$rapport`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($rapport['statut_rapport'] == 'en_attente')
```
#### [CONDITION] Profondeur 0
```php
if($rapport['statut_rapport'] == 'en_cours')
```
#### [CONDITION] Profondeur 0
```php
if($rapport['statut_rapport'] == 'valider')
```
#### [CONDITION] Profondeur 0
```php
if($rapport['statut_rapport'] == 'rejeter')
```
#### [CONDITION] Profondeur 0
```php
if(e.target === modal)
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_rapports&action=get_commentaires&id=${rapportId}`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : creer_rapport.php

### 1. DATA MAPPING (VARIABLES)
- `$isEditingExisting`
- `$isEditMode`
- `$rapport`
- `$erreurs`
- `$erreur`
- `$contenuRapport`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(!editor)
```
#### [CONDITION] Profondeur 0
```php
if(!nomRapport)
```
#### [CONDITION] Profondeur 0
```php
if(!themeRapport)
```
#### [CONDITION] Profondeur 0
```php
if(!response.ok)
```
#### [CONDITION] Profondeur 0
```php
if(!response.ok)
```
#### [CONDITION] Profondeur 0
```php
if(blob.size === 0)
```
#### [CONDITION] Profondeur 0
```php
switch(type)
```
#### [CONDITION] Profondeur 0
```php
if(progress <= 0)
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_rapports`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**
- **RichTextEditor**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : suivi_rapport.php

### 1. DATA MAPPING (VARIABLES)
- `$pdo`
- `$statistiques`
- `$rapports`
- `$rapport`
- `$status_class`
- `$status_label`
- `$stmt`
- `$estDepose`
- `$enregistrementColor`
- `$enregistrementIcon`
- `$soumissionColor`
- `$soumissionIcon`
- `$soumissionDate`
- `$dateDepot`
- `$eval`
- `$decision`
- `$evalColor`
- `$evalIcon`
- `$finalColor`
- `$finalLabel`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($rapport['statut_rapport'] === 'valider')
```
#### [CONDITION] Profondeur 0
```php
elseif($rapport['statut_rapport'] === 'rejeter')
```
#### [CONDITION] Profondeur 0
```php
elseif($rapport['statut_rapport'] === 'en_cours')
```
#### [CALCUL] Profondeur 1
```php
$status_class = 'en-cours';
```
#### [CONDITION] Profondeur 0
```php
if($decision['lib_approb'] === 'Niveau 2')
```
#### [CONDITION] Profondeur 0
```php
if($eval['decision'] === 'approuve')
```
#### [CALCUL] Profondeur 1
```php
$evalIcon = 'fa-check';
```
#### [CONDITION] Profondeur 0
```php
elseif($eval['decision'] === 'desapprouve')
```
#### [CALCUL] Profondeur 1
```php
$evalIcon = 'fa-times';
```
#### [CONDITION] Profondeur 0
```php
if(status && status !== '')
```
#### [CONDITION] Profondeur 1
```php
if(itemStatus !== status)
```
#### [CONDITION] Profondeur 0
```php
if(showItem && search)
```
#### [CONDITION] Profondeur 0
```php
if(item.style.display !== 'none')
```
#### [CONDITION] Profondeur 0
```php
if(visibleCount === 0)
```
#### [CONDITION] Profondeur 1
```php
if(!noResultsMessage)
```
#### [BOUCLE] Profondeur 0
```php
foreach($rapport['decisions'] as $decision)
```
#### [CALCUL] Profondeur 0
```php
$pdo = Database::getConnection();
```
#### [CALCUL] Profondeur 0
```php
$status_class = 'en-attente';
```
#### [CALCUL] Profondeur 0
```php
$stmt = $pdo->prepare("SELECT COUNT(*) FROM deposer WHERE num_etu = ? AND id_rapport = ?");
```
#### [CALCUL] Profondeur 0
```php
$estDepose = $stmt->fetchColumn() > 0;
```
#### [CALCUL] Profondeur 0
```php
$enregistrementIcon = 'fa-check';
```
#### [CALCUL] Profondeur 0
```php
$soumissionColor = $estDepose ? '#1976d2' : '#9e9e9e';
```
#### [CALCUL] Profondeur 0
```php
$soumissionIcon = $estDepose ? 'fa-check' : 'fa-hourglass';
```
#### [CALCUL] Profondeur 0
```php
$stmt = $pdo->prepare("SELECT date_depot FROM deposer WHERE num_etu = ? AND id_rapport = ?");
```
#### [CALCUL] Profondeur 0
```php
$dateDepot = $stmt->fetchColumn();
```
#### [CALCUL] Profondeur 0
```php
$soumissionDate = $dateDepot ? date('d M Y - H:i', strtotime($dateDepot)) : 'Date inconnue';
```
#### [CALCUL] Profondeur 0
```php
$evalIcon = 'fa-hourglass';
```
#### [CALCUL] Profondeur 0
```php
$finalColor = ($eval && $eval['decision'] === 'approuve' && $rapport['statut_rapport'] === 'valider') ? '#388e3c' : '#9e9e9e';
```
#### [CALCUL] Profondeur 0
```php
$finalLabel = ($eval && $eval['decision'] === 'approuve' && $rapport['statut_rapport'] === 'valider') ? 'Validé' : 'En attente';
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **SmartSelect**
- **InputField**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : ajouter_des_etudiants.php

### 1. DATA MAPPING (VARIABLES)
- `$listeEtudiants`
- `$etudiant_a_modifier`
- `$modalAction`
- `$showModal`
- `$currentPage`
- `$itemsPerPage`
- `$totalItems`
- `$totalPages`
- `$startIndex`
- `$endIndex`
- `$currentPageItems`
- `$allEtudiants`
- `$value`
- `$selected`
- `$etudiant`
- `$start`
- `$end`
- `$searchParam`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if($start > 1)
```
#### [CONDITION] Profondeur 1
```php
if($start > 2)
```
#### [CONDITION] Profondeur 0
```php
if($end < $totalPages)
```
#### [CALCUL] Profondeur 1
```php
$searchParam = !empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '';
```
#### [CONDITION] Profondeur 0
```php
if(e.target === selectAllCheckbox)
```
#### [CONDITION] Profondeur 0
```php
if(currentPage > 1)
```
#### [CONDITION] Profondeur 0
```php
if(start > 1)
```
#### [CONDITION] Profondeur 1
```php
if(start > 2)
```
#### [CONDITION] Profondeur 0
```php
if(end < totalFilteredPages)
```
#### [CONDITION] Profondeur 0
```php
if(currentPage < totalFilteredPages)
```
#### [CONDITION] Profondeur 0
```php
if(selectedCheckboxes.length === 0)
```
#### [BOUCLE] Profondeur 0
```php
for($i = 2000; $i <= 2030; $i++)
```
#### [CALCUL] Profondeur 1
```php
$value = $i . '-' . ($i + 1);
```
#### [CALCUL] Profondeur 1
```php
$selected = ($etudiant_a_modifier && $etudiant_a_modifier->promotion_etu === $value) ? 'selected' : '';
```
#### [BOUCLE] Profondeur 0
```php
for(let i = start; i <= end; i++)
```
#### [CALCUL] Profondeur 0
```php
$listeEtudiants = $GLOBALS['listeEtudiants'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$etudiant_a_modifier = $GLOBALS['etudiant_a_modifier'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$modalAction = $GLOBALS['modalAction'] ?? '';
```
#### [CALCUL] Profondeur 0
```php
$currentPage = $GLOBALS['currentPage'] ?? 1;
```
#### [CALCUL] Profondeur 0
```php
$itemsPerPage = $GLOBALS['itemsPerPage'] ?? 10;
```
#### [CALCUL] Profondeur 0
```php
$totalItems = $GLOBALS['totalItems'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$totalPages = $GLOBALS['totalPages'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$startIndex = $GLOBALS['startIndex'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$endIndex = $GLOBALS['endIndex'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$currentPageItems = $GLOBALS['listeEtudiants'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$allEtudiants = $GLOBALS['allEtudiants'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$start = max(1, $currentPage - 2);
```
#### [CALCUL] Profondeur 0
```php
$end = min($totalPages, $currentPage + 2);
```
#### [CALCUL] Profondeur 0
```php
$i === $currentPage ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor;
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_etudiants&action=ajouter_des_etudiants`
- `?page=gestion_etudiants&action=ajouter_des_etudiants&modalAction=edit&num_etu=${numEtu}`
- `?page=gestion_etudiants&action=ajouter_des_etudiants&modalAction=add`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **DateTimeManager**
- **SelectionGroup**
- **ActionTrigger**
- **ModalOverlay**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : inscrire_des_etudiants.php

### 1. DATA MAPPING (VARIABLES)
- `$etudiantsNonInscrits`
- `$niveaux`
- `$etudiantsInscrits`
- `$listeAnnees`
- `$annee`
- `$etudiant`
- `$niveau`
- `$inscrit`
- `$printId`
- `$isVersement`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CONDITION] Profondeur 0
```php
if(hiddenMontantPaye && hiddenMontantPaye.value)
```
#### [CONDITION] Profondeur 0
```php
if(selectedEtudiant && selectedEtudiant.options[selectedEtudiant.selectedIndex])
```
#### [CONDITION] Profondeur 0
```php
if(montantPayeDataset > 0)
```
#### [CONDITION] Profondeur 0
```php
if(premierVersement > montantTotal)
```
#### [CONDITION] Profondeur 0
```php
if(currentPage > 1)
```
#### [CONDITION] Profondeur 0
```php
if(currentPage < totalPages)
```
#### [CONDITION] Profondeur 0
```php
if(e.target === deleteModal)
```
#### [BOUCLE] Profondeur 0
```php
for(let i = 1; i <= totalPages; i++)
```
#### [CALCUL] Profondeur 0
```php
$etudiantsNonInscrits = isset($GLOBALS['etudiantsNonInscrits']) ? $GLOBALS['etudiantsNonInscrits'] : [];
```
#### [CALCUL] Profondeur 0
```php
$niveaux = isset($GLOBALS['niveaux']) ? $GLOBALS['niveaux'] : [];
```
#### [CALCUL] Profondeur 0
```php
$etudiantsInscrits = isset($GLOBALS['etudiantsInscrits']) ? $GLOBALS['etudiantsInscrits'] : [];
```
#### [CALCUL] Profondeur 0
```php
$listeAnnees = isset($GLOBALS['listeAnnees']) ? $GLOBALS['listeAnnees'] : [];
```

### 3. PERMISSIONS & HABILITATIONS
- `canEdit()`
- `canCreate()`
- `canDelete()`

### 4. ROUTES & ACTIONS DETECTÉES
- `?page=gestion_etudiants&action=inscrire_des_etudiants`
- `?page=gestion_etudiants&action=inscrire_des_etudiants&modalAction=modifier&id=${idInscription}`
- `?page=gestion_etudiants&action=inscrire_des_etudiants&modalAction=supprimer&id=${inscriptionToDelete}`

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**
- **FormLayout**
- **SmartSelect**
- **InputField**
- **ActionTrigger**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : recu_inscription.php

### 1. DATA MAPPING (VARIABLES)
- `$inscription`
- `$etudiant`
- `$niveau`
- `$anneeAcademique`
- `$montantTotal`
- `$montantPaye`
- `$nombreTranches`
- `$prochainVersement`
- `$dateProchainVersement`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CALCUL] Profondeur 0
```php
$inscription = $GLOBALS['inscriptionAModifier'] ?? [];
```
#### [CALCUL] Profondeur 0
```php
$etudiant = [
    'nom_etu' => $inscription['nom_etudiant'] ?? '',
    'prenom_etu' => $inscription['prenom_etudiant'] ?? ''
];
```
#### [CALCUL] Profondeur 0
```php
$niveau = [
    'lib_niv_etude' => $inscription['nom_niveau'] ?? 'N/A'
];
```
#### [CALCUL] Profondeur 0
```php
$anneeAcademique = [
    'date_deb' => $inscription['date_deb'] ?? date('Y-m-d'),
    'date_fin' => $inscription['date_fin'] ?? date('Y-m-d', strtotime('+1 year'))
];
```
#### [CALCUL] Profondeur 0
```php
$montantTotal = $inscription['montant_total'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$montantPaye = $inscription['montant_premier_versement'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$nombreTranches = $inscription['nombre_tranche'] ?? 1;
```
#### [CALCUL] Profondeur 0
```php
$prochainVersement = ReceiptUtils::calculerProchainVersement($montantTotal, $montantPaye, $nombreTranches);
```
#### [CALCUL] Profondeur 0
```php
$dateProchainVersement = ReceiptUtils::calculerDateProchainVersement($inscription['date_inscription'] ?? date('Y-m-d'), $nombreTranches);
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
- **DataTable / Grid**

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

## FICHIER : compte_rendu_etudiant.php

### 1. DATA MAPPING (VARIABLES)
- `$compte_rendu`
- `$statut`
- `$date_soutenance`
- `$note_technique`
- `$note_presentation`
- `$commentaires`

### 2. LOGIQUE MÉTIER EXTRACTÉE
#### [CALCUL] Profondeur 0
```php
$compte_rendu = $GLOBALS['compte_rendu'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$statut = $compte_rendu['statut'] ?? 'en_attente';
```
#### [CALCUL] Profondeur 0
```php
$statut === 'accepté' ? 'bg-green-100 text-green-800' :
                                ($statut === 'refusé' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800');
```
#### [CALCUL] Profondeur 0
```php
$date_soutenance = $compte_rendu['date_soutenance'] ?? null;
```
#### [CALCUL] Profondeur 0
```php
$note_technique = $compte_rendu['note_technique'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$note_presentation = $compte_rendu['note_presentation'] ?? 0;
```
#### [CALCUL] Profondeur 0
```php
$commentaires = $compte_rendu['commentaires'] ?? 'Aucun commentaire disponible.';
```

### 3. PERMISSIONS & HABILITATIONS
_Aucun check détecté_

### 4. ROUTES & ACTIONS DETECTÉES
_Aucune route détectée_

### 5. SUGGESTIONS DE COMPOSANTS (SLICING)
_Aucun pattern UI_

### BILAN MIGRATION
**À GARDER :** Variables métier ($), Règles de calcul et conditions, Système d'habilitations, Endpoints d'API/Actions
**À JETER :** Structures HTML rigides, Scripts de manipulation DOM directe, Styles inline et classes CSS locales, Espaces et formatage legacy

---

