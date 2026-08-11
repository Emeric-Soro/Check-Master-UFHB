<?php
/**
 * Tests d'intégration Phase 2 : FicheEnseignantService migré vers DatabaseService.
 * Lecture seule.
 */

declare(strict_types=1);

use CheckMaster\Services\FicheEnseignantService;

test('FicheEnseignantService: getListeEnseignants retourne des enseignants', function () {
    $svc = new FicheEnseignantService(\Database::getConnection());
    $liste = $svc->getListeEnseignants();
    assertTrue(is_array($liste), 'tableau');
    foreach ($liste as $ens) {
        assertTrue(array_key_exists('id_enseignant', $ens), 'id_enseignant');
        assertTrue(array_key_exists('nom_enseignant', $ens), 'nom_enseignant');
        assertTrue(array_key_exists('matricule_enseignant', $ens), 'matricule');
    }
});

test('FicheEnseignantService: getFicheComplete sur id inexistant → tableau vide', function () {
    $svc = new FicheEnseignantService(\Database::getConnection());
    $fiche = $svc->getFicheComplete('999999-inexistant');
    assertEquals([], $fiche, 'fiche vide pour id inconnu');
});

test('FicheEnseignantService: getFicheComplete structure complète pour un vrai enseignant', function () {
    $svc = new FicheEnseignantService(\Database::getConnection());
    $liste = $svc->getListeEnseignants();
    if (count($liste) === 0) {
        // Pas d'enseignant en base → on ne peut pas tester la fiche
        assertTrue(true, 'aucun enseignant en base, test sauté');
        return;
    }

    $fiche = $svc->getFicheComplete((string) $liste[0]['id_enseignant']);
    $expectedKeys = [
        'identite', 'grade_actuel', 'historique_grades', 'fonctions',
        'type_enseignant_lib', 'jurys', 'encadrements', 'stats', 'compte_utilisateur',
    ];
    foreach ($expectedKeys as $k) {
        assertTrue(array_key_exists($k, $fiche), "clé $k présente");
    }
    assertTrue(array_key_exists('nb_soutenances', $fiche['stats']), 'stats.nb_soutenances');
    assertTrue(array_key_exists('note_moyenne', $fiche['stats']), 'stats.note_moyenne');
});
