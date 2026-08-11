<?php
/**
 * Tests du ServiceContainer enrichi (briques Phase 2).
 */

declare(strict_types=1);

use CheckMaster\Core\ServiceContainer;
use App\Support\DatabaseService;
use App\Support\EntityLoader;
use App\Support\PaginationService;
use App\Support\BusinessLogger;
use App\Support\Validator;
use App\Services\ReferentielCrudService;

test('ServiceContainer: les briques Phase 2 sont résolubles', function () {
    $container = ServiceContainer::getInstance();
    $container->setPDO(\Database::getConnection());
    $container->registerAppServices();

    $checks = [
        'DatabaseService' => DatabaseService::class,
        'EntityLoader' => EntityLoader::class,
        'PaginationService' => PaginationService::class,
        'BusinessLogger' => BusinessLogger::class,
        'Validator' => Validator::class,
        'ReferentielCrudService' => ReferentielCrudService::class,
    ];

    foreach ($checks as $key => $class) {
        assertTrue($container->has($key), "service $key enregistré");
        $svc = $container->get($key);
        assertInstanceOf($class, $svc, "$key → $class");
    }
});

test('ServiceContainer: RepertoireEnseignantService résoluble (régression layout.php:684)', function () {
    $container = ServiceContainer::getInstance();
    $container->setPDO(\Database::getConnection());
    $container->registerAppServices();

    assertTrue(
        $container->has('RepertoireEnseignantService'),
        'RepertoireEnseignantService enregistré (sinon RuntimeException "Service non trouvé" sur layout.php:684/1418)'
    );
    $svc = $container->get('RepertoireEnseignantService');
    assertInstanceOf(\CheckMaster\Services\RepertoireEnseignantService::class, $svc, 'RepertoireEnseignantService → CheckMaster\Services\RepertoireEnseignantService');
});
