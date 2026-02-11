<?php
/**
 * Page unifiée de liste des étudiants
 * S'adapte automatiquement selon le type d'utilisateur :
 * - Enseignant simple : étudiants des UE/ECUE qu'il enseigne
 * - Responsable de niveau : étudiants des niveaux dont il est responsable
 * - Responsable de filière : étudiants des filières dont il est responsable
 */

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/AnneeAcademique.php';
require_once __DIR__ . '/../../app/models/NiveauEtude.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/models/Enseignant.php';
require_once __DIR__ . '/../../app/models/Specialite.php';
require_once __DIR__ . '/../../app/models/Ecue.php';
require_once __DIR__ . '/../../app/models/Ue.php';

$pdo = Database::getConnection();
$anneeAcademiqueModel = new AnneeAcademique($pdo);
$niveauEtudeModel = new NiveauEtude($pdo);
$etudiantModel = new Etudiant($pdo);
$enseignantModel = new Enseignant($pdo);
$specialiteModel = new Specialite($pdo);
$ecueModel = new Ecue($pdo);
$ueModel = new Ue($pdo);

// Vérification si l'utilisateur est administrateur
$estAdministrateur = isset($_SESSION['id_GU']) && $_SESSION['id_GU'] == 5;

// Récupération de l'enseignant connecté (sauf pour administrateur)
$enseignant = null;
$enseignantId = null;

if (!$estAdministrateur) {
    $enseignant = $enseignantModel->getEnseignantByLogin($_SESSION['login_utilisateur']);

    if (!$enseignant) {
        ?>
        <div class="min-h-screen flex items-center justify-center bg-gray-50">
            <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle text-yellow-500 text-6xl mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Accès non autorisé</h2>
                    <p class="text-gray-600 mb-6">
                        Cette page est réservée aux enseignants et administrateurs.
                    </p>
                    <a href="?page=dashboard" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fas fa-home mr-2"></i>Retour au tableau de bord
                    </a>
                </div>
            </div>
        </div>
        <?php
        exit;
    }

    $enseignantId = $enseignant->id_enseignant;
}

// Déterminer le type d'utilisateur et ses responsabilités
if ($estAdministrateur) {
    // L'administrateur a accès à tout
    $niveauxResponsable = [];
    $specialitesResponsable = [];
    $uesEnseignant = [];
    $ecuesEnseignant = [];
    $typeAffichage = 'administrateur';
    $titre = 'Liste des Étudiants';
    $sousTitre = 'Administrateur';
} else {
    $niveauxResponsable = array_filter($niveauEtudeModel->getAllNiveauxEtudes(), function ($niv) use ($enseignantId) {
        return isset($niv->id_enseignant) && $niv->id_enseignant == $enseignantId;
    });

    $specialitesResponsable = array_filter($specialiteModel->getAllSpecialites(), function ($spec) use ($enseignantId) {
        return isset($spec->id_enseignant) && $spec->id_enseignant == $enseignantId;
    });

    $uesEnseignant = $ueModel->getUesByEnseignant($enseignantId);
    $ecuesEnseignant = $ecueModel->getEcuesByEnseignant($enseignantId);

    $estResponsableNiveau = !empty($niveauxResponsable);
    $estResponsableFiliere = !empty($specialitesResponsable);
    $estEnseignantSimple = !$estResponsableNiveau && !$estResponsableFiliere && (!empty($uesEnseignant) || !empty($ecuesEnseignant));

    // Déterminer le type d'affichage
    $typeAffichage = 'aucun';
    $titre = 'Liste des Étudiants';
    $sousTitre = '';

    if ($estResponsableNiveau) {
        $typeAffichage = 'responsable_niveau';
        $sousTitre = 'Responsable Niveau';
    } elseif ($estResponsableFiliere) {
        $typeAffichage = 'responsable_filiere';
        $sousTitre = 'Responsable Filière';
    } elseif ($estEnseignantSimple) {
        $typeAffichage = 'enseignant';
        $sousTitre = 'Mes UE/ECUE';
    }
}

// Récupération des données communes
$listeAnnees = $anneeAcademiqueModel->getAllAnneeAcademiques();
$listeNiveaux = $niveauEtudeModel->getAllNiveauxEtudes();
$etudiants = $etudiantModel->getAllListeEtudiants();

// Récupération des filtres
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
$promotion = isset($_GET['promotion']) ? $_GET['promotion'] : '';
$niveau = isset($_GET['niveau']) ? $_GET['niveau'] : '';
$ue = isset($_GET['ue']) ? $_GET['ue'] : '';
$ecue = isset($_GET['ecue']) ? $_GET['ecue'] : '';

// Filtrage selon le type d'utilisateur
$filteredEtudiants = [];

switch ($typeAffichage) {
    case 'administrateur':
        // L'administrateur voit tous les étudiants
        $filteredEtudiants = array_filter($etudiants, function ($etudiant) use ($search, $promotion, $niveau) {
            $matchesSearch = $search === '' ||
                strpos(strtolower($etudiant->nom_etu), $search) !== false ||
                strpos(strtolower($etudiant->prenom_etu), $search) !== false ||
                strpos(strtolower($etudiant->email_etu), $search) !== false;
            $matchesPromotion = $promotion === '' || (isset($etudiant->id_annee_acad) && $etudiant->id_annee_acad == $promotion);
            $matchesNiveau = $niveau === '' || (isset($etudiant->id_niv_etude) && $etudiant->id_niv_etude == $niveau);
            return $matchesSearch && $matchesPromotion && $matchesNiveau;
        });
        break;

    case 'responsable_niveau':
        // Filtrer par niveaux dont il est responsable
        $niveauIds = array_map(function ($niv) { return $niv->id_niv_etude; }, $niveauxResponsable);
        $filteredEtudiants = array_filter($etudiants, function ($etudiant) use ($search, $promotion, $niveau, $niveauIds) {
            $matchesSearch = $search === '' ||
                strpos(strtolower($etudiant->nom_etu), $search) !== false ||
                strpos(strtolower($etudiant->prenom_etu), $search) !== false ||
                strpos(strtolower($etudiant->email_etu), $search) !== false;
            $matchesPromotion = $promotion === '' || (isset($etudiant->id_annee_acad) && $etudiant->id_annee_acad == $promotion);
            $matchesNiveau = $niveau === '' ? in_array($etudiant->id_niv_etude, $niveauIds) : ($etudiant->id_niv_etude == $niveau);
            return $matchesSearch && $matchesPromotion && $matchesNiveau;
        });
        break;

    case 'responsable_filiere':
        // Filtrer par filières (tous les niveaux, toutes les spécialités)
        $filteredEtudiants = array_filter($etudiants, function ($etudiant) use ($search, $promotion, $niveau) {
            $matchesSearch = $search === '' ||
                strpos(strtolower($etudiant->nom_etu), $search) !== false ||
                strpos(strtolower($etudiant->prenom_etu), $search) !== false ||
                strpos(strtolower($etudiant->email_etu), $search) !== false;
            $matchesPromotion = $promotion === '' || (isset($etudiant->id_annee_acad) && $etudiant->id_annee_acad == $promotion);
            $matchesNiveau = $niveau === '' || (isset($etudiant->id_niv_etude) && $etudiant->id_niv_etude == $niveau);
            return $matchesSearch && $matchesPromotion && $matchesNiveau;
        });
        break;

    case 'enseignant':
        // Filtrer par UE/ECUE enseignés
        $niveauIds = [];
        foreach ($uesEnseignant as $ueItem) {
            if (!in_array($ueItem->id_niveau_etude, $niveauIds)) {
                $niveauIds[] = $ueItem->id_niveau_etude;
            }
        }
        foreach ($ecuesEnseignant as $ecueItem) {
            if (!in_array($ecueItem->id_niveau_etude, $niveauIds)) {
                $niveauIds[] = $ecueItem->id_niveau_etude;
            }
        }

        $filteredEtudiants = array_filter($etudiants, function ($etudiant) use ($niveauIds, $search, $promotion, $ue, $ecue, $ecuesEnseignant, $uesEnseignant) {
            if (!in_array($etudiant->id_niv_etude, $niveauIds)) {
                return false;
            }
            $matchesSearch = $search === '' ||
                strpos(strtolower($etudiant->nom_etu), $search) !== false ||
                strpos(strtolower($etudiant->prenom_etu), $search) !== false ||
                strpos(strtolower($etudiant->email_etu), $search) !== false;
            $matchesPromotion = $promotion === '' || (isset($etudiant->id_annee_acad) && $etudiant->id_annee_acad == $promotion);
            
            // Filtrage par UE
            $matchesUe = $ue === '';
            if ($ue !== '') {
                foreach ($ecuesEnseignant as $ecueEnseignant) {
                    if ($ecueEnseignant->id_ue == $ue && $etudiant->id_niv_etude == $ecueEnseignant->id_niveau_etude) {
                        $matchesUe = true;
                        break;
                    }
                }
                foreach ($uesEnseignant as $ueEnseignant) {
                    if ($ueEnseignant->id_ue == $ue && $etudiant->id_niv_etude == $ueEnseignant->id_niveau_etude) {
                        $matchesUe = true;
                        break;
                    }
                }
            }
            
            // Filtrage par ECUE
            $matchesEcue = $ecue === '';
            if ($ecue !== '') {
                foreach ($ecuesEnseignant as $ecueEnseignant) {
                    if ($ecueEnseignant->id_ecue == $ecue && $etudiant->id_niv_etude == $ecueEnseignant->id_niveau_etude) {
                        $matchesEcue = true;
                        break;
                    }
                }
            }
            
            return $matchesSearch && $matchesPromotion && $matchesUe && $matchesEcue;
        });
        break;

    default:
        // Aucune responsabilité
        ?>
        <div class="min-h-screen flex items-center justify-center bg-gray-50">
            <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
                <div class="text-center">
                    <i class="fas fa-info-circle text-blue-500 text-6xl mb-4"></i>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Aucune responsabilité</h2>
                    <p class="text-gray-600 mb-6">
                        Vous n'êtes actuellement responsable d'aucun niveau, filière ou UE/ECUE.
                    </p>
                    <a href="?page=dashboard" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                        <i class="fas fa-home mr-2"></i>Retour au tableau de bord
                    </a>
                </div>
            </div>
        </div>
        <?php
        exit;
}

// Pagination
$perPage = 15;
$totalEtudiants = count($filteredEtudiants);
$totalPages = ($totalEtudiants > 0) ? ceil($totalEtudiants / $perPage) : 1;
$p = isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p'] > 0 ? (int) $_GET['p'] : 1;
if ($p > $totalPages) $p = $totalPages;
$startIndex = ($p - 1) * $perPage;
$etudiantsPage = array_slice($filteredEtudiants, $startIndex, $perPage);

// Indexer les UE et ECUE pour les filtres (si enseignant simple)
$uesEnseignantIndexed = [];
$ecuesEnseignantIndexed = [];
if ($typeAffichage === 'enseignant') {
    foreach ($uesEnseignant as $ueItem) {
        $uesEnseignantIndexed[$ueItem->id_ue] = $ueItem->lib_ue;
    }
    foreach ($ecuesEnseignant as $ecueItem) {
        $ecuesEnseignantIndexed[$ecueItem->id_ecue] = [
            'lib_ecue' => $ecueItem->lib_ecue,
            'lib_ue' => $ecueItem->lib_ue
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titre) ?></title>
</head>

<body class="p-4 sm:p-6 md:p-8" style="background-color: #DFF2FF;">
    <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden md:p-8 p-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-2 text-center">
            <?= htmlspecialchars($titre) ?>
            <?php if ($sousTitre): ?>
                - <span class="text-3xl text-green-500 font-bold"><?= htmlspecialchars($sousTitre) ?></span>
            <?php endif; ?>
        </h1>
        
        <?php if ($typeAffichage !== 'aucun'): ?>
            <p class="text-center text-gray-600 mb-6">
                <?= $totalEtudiants ?> étudiant<?= $totalEtudiants > 1 ? 's' : '' ?> trouvé<?= $totalEtudiants > 1 ? 's' : '' ?>
            </p>

            <!-- Formulaire de filtres -->
            <form method="get" class="mb-6 flex flex-wrap gap-4 items-center">
                <?php if (isset($_GET['page'])): ?>
                    <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page']) ?>">
                <?php endif; ?>
                
                <!-- Recherche -->
                <input type="text" name="search" placeholder="Rechercher par nom, prénom ou email..."
                    class="outline-green-500 flex-1 min-w-[200px] p-3 pl-4 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm"
                    value="<?= htmlspecialchars($search) ?>">
                
                <!-- Promotion -->
                <select name="promotion"
                    class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                    <option value="">Toutes les Années Académiques</option>
                    <?php foreach ($listeAnnees as $annee): ?>
                        <option value="<?= htmlspecialchars($annee->id_annee_acad) ?>" <?= $promotion == $annee->id_annee_acad ? 'selected' : '' ?>>
                            <?= htmlspecialchars(date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <!-- Niveau (responsable niveau : seulement ses niveaux, autres : tous) -->
                <?php if ($typeAffichage === 'responsable_niveau'): ?>
                    <select name="niveau"
                        class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                        <option value="">Tous mes niveaux</option>
                        <?php foreach ($niveauxResponsable as $niv): ?>
                            <option value="<?= htmlspecialchars($niv->id_niv_etude) ?>" <?= $niveau == $niv->id_niv_etude ? 'selected' : '' ?>>
                                <?= htmlspecialchars($niv->lib_niv_etude) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($typeAffichage === 'responsable_filiere' || $typeAffichage === 'administrateur'): ?>
                    <select name="niveau"
                        class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                        <option value="">Tous les Niveaux</option>
                        <?php foreach ($listeNiveaux as $niv): ?>
                            <option value="<?= htmlspecialchars($niv->id_niv_etude) ?>" <?= $niveau == $niv->id_niv_etude ? 'selected' : '' ?>>
                                <?= htmlspecialchars($niv->lib_niv_etude) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                
                <!-- UE/ECUE (enseignant simple uniquement) -->
                <?php if ($typeAffichage === 'enseignant'): ?>
                    <select name="ue"
                        class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                        <option value="">Toutes les UE</option>
                        <?php foreach ($uesEnseignantIndexed as $id => $lib): ?>
                            <option value="<?= htmlspecialchars($id) ?>" <?= $ue == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lib) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="ecue"
                        class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                        <option value="">Tous les ECUE</option>
                        <?php foreach ($ecuesEnseignantIndexed as $id => $data): ?>
                            <option value="<?= htmlspecialchars($id) ?>" <?= $ecue == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($data['lib_ecue'] . ' (' . $data['lib_ue'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                
                <button type="submit"
                    class="p-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-filter mr-2"></i>Filtrer
                </button>
            </form>

            <!-- Tableau des étudiants -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tl-lg">Nom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prénom</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de naissance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promotion</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Niveau</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                            <?php if (canView() || canEdit() || canDelete()): ?>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tr-lg">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($etudiantsPage)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-users text-4xl mb-2 text-gray-300"></i>
                                    <p>Aucun étudiant trouvé</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($etudiantsPage as $etudiant): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($etudiant->nom_etu) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?= htmlspecialchars($etudiant->prenom_etu) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?= isset($etudiant->date_nais_etu) ? htmlspecialchars(date('d/m/Y', strtotime($etudiant->date_nais_etu))) : 'N/A' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?= isset($etudiant->promotion) ? htmlspecialchars($etudiant->promotion) : 'N/A' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?= isset($etudiant->lib_niv_etude) ? htmlspecialchars($etudiant->lib_niv_etude) : 'N/A' ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?= htmlspecialchars($etudiant->email_etu) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                        <?= isset($etudiant->tel_etu) ? htmlspecialchars($etudiant->tel_etu) : 'N/A' ?>
                                    </td>
                                    <?php if (canView() || canEdit() || canDelete()): ?>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="?page=details_etudiant&id=<?= htmlspecialchars($etudiant->num_carte_etud ?? '') ?>" 
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-500 text-white text-xs font-medium rounded hover:bg-blue-600 transition"
                                                title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($typeAffichage === 'administrateur'): ?>
                                                <?php if (canEdit()): ?>
                                                <a href="?page=modifier_etudiant&id=<?= htmlspecialchars($etudiant->num_carte_etud ?? '') ?>" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-green-500 text-white text-xs font-medium rounded hover:bg-green-600 transition"
                                                    title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if (canDelete()): ?>
                                                <button onclick="confirmerSuppression(<?= htmlspecialchars($etudiant->num_carte_etud ?? '0') ?>, '<?= htmlspecialchars(addslashes($etudiant->nom_etu . ' ' . $etudiant->prenom_etu)) ?>')" 
                                                    class="inline-flex items-center px-3 py-1.5 bg-red-500 text-white text-xs font-medium rounded hover:bg-red-600 transition"
                                                    title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
                    <div class="text-sm text-gray-700">
                        Affichage de <span class="font-medium"><?= $startIndex + 1 ?></span> à
                        <span class="font-medium"><?= min($startIndex + $perPage, $totalEtudiants) ?></span> sur
                        <span class="font-medium"><?= $totalEtudiants ?></span> résultats
                    </div>
                    <div class="flex gap-2">
                        <?php if ($p > 1): ?>
                            <a href="?page=<?= htmlspecialchars($_GET['page'] ?? 'liste_etudiants_resp') ?>&p=<?= $p - 1 ?>&search=<?= htmlspecialchars($search) ?>&promotion=<?= htmlspecialchars($promotion) ?>&niveau=<?= htmlspecialchars($niveau) ?><?= $typeAffichage === 'enseignant' ? '&ue=' . htmlspecialchars($ue) . '&ecue=' . htmlspecialchars($ecue) : '' ?>"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                <i class="fas fa-chevron-left mr-2"></i>Précédent
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $p - 2); $i <= min($totalPages, $p + 2); $i++): ?>
                            <a href="?page=<?= htmlspecialchars($_GET['page'] ?? 'liste_etudiants_resp') ?>&p=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&promotion=<?= htmlspecialchars($promotion) ?>&niveau=<?= htmlspecialchars($niveau) ?><?= $typeAffichage === 'enseignant' ? '&ue=' . htmlspecialchars($ue) . '&ecue=' . htmlspecialchars($ecue) : '' ?>"
                                class="px-4 py-2 <?= $i === $p ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?> rounded-lg transition">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($p < $totalPages): ?>
                            <a href="?page=<?= htmlspecialchars($_GET['page'] ?? 'liste_etudiants_resp') ?>&p=<?= $p + 1 ?>&search=<?= htmlspecialchars($search) ?>&promotion=<?= htmlspecialchars($promotion) ?>&niveau=<?= htmlspecialchars($niveau) ?><?= $typeAffichage === 'enseignant' ? '&ue=' . htmlspecialchars($ue) . '&ecue=' . htmlspecialchars($ecue) : '' ?>"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Suivant<i class="fas fa-chevron-right ml-2"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
    function confirmerSuppression(idEtudiant, nomComplet) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer l'étudiant "${nomComplet}" ?\n\nCette action est irréversible.`)) {
            window.location.href = `?page=supprimer_etudiant&id=${idEtudiant}`;
        }
    }
    </script>
</body>
</html>
