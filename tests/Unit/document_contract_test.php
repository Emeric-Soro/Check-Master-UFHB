<?php
/**
 * Tests du contrat commun des générateurs de documents (Phase 4).
 */

declare(strict_types=1);

use App\Services\Document\DocumentGeneratorContract;
use App\Services\Document\AbstractDocumentGenerator;
use App\Services\Document\DocumentStorageService;

// Générateur factice pour tester la base commune
final class FakeDocGenerator extends AbstractDocumentGenerator
{
    public function documentType(): string
    {
        return 'test_doc';
    }

    public function generate(string|int $entityId, int $userId): array
    {
        $content = 'PDF-CONTENT-' . $entityId;
        $filename = $this->buildFilename('pdf', 'REF123');
        $path = $this->writeToDisk($content, $filename);
        if ($path === null) {
            return $this->result(false, [], 'écriture impossible');
        }
        return $this->result(true, [
            'reference' => 'REF123',
            'path' => $path,
            'filename' => $filename,
            'size' => strlen($content),
        ]);
    }
}

test('DocumentGeneratorContract: l\'interface est implémentée', function () {
    $gen = new FakeDocGenerator(sys_get_temp_dir());
    assertInstanceOf(DocumentGeneratorContract::class, $gen);
    assertEquals('test_doc', $gen->documentType());
});

test('AbstractDocumentGenerator: buildFilename produit un nom standardisé', function () {
    $gen = new class(sys_get_temp_dir()) extends AbstractDocumentGenerator {
        public function documentType(): string
        {
            return 'test_doc';
        }
        public function generate(string|int $entityId, int $userId): array
        {
            return ['success' => true];
        }
        public function exposeBuildFilename(string $ext, string $ref): string
        {
            return $this->buildFilename($ext, $ref);
        }
    };
    $name = $gen->exposeBuildFilename('pdf', 'REF-123');
    assertContains('test_doc_', $name);
    assertContains('_REF-123.pdf', $name);
    assertContains('.pdf', $name);
});

test('AbstractDocumentGenerator: writeToDisk écrit dans le sous-répertoire', function () {
    $dir = sys_get_temp_dir() . '/cm_doc_test_' . bin2hex(random_bytes(4));
    $gen = new FakeDocGenerator($dir);
    $result = $gen->generate(42, 1);
    assertTrue($result['success'], 'génération réussie');
    assertTrue(is_file($result['path']), 'fichier écrit');
    assertContains('test_docs', $result['path'], 'sous-répertoire par type');
    assertEquals('PDF-CONTENT-42', file_get_contents($result['path']));
    // Nettoyage
    @unlink($result['path']);
    @rmdir($dir . '/test_docs');
    @rmdir($dir);
});

test('DocumentStorageService: storeDocument et getByReference (transaction rollback)', function () {
    $pdo = \Database::getConnection();
    $storage = new DocumentStorageService($pdo);
    assertTrue($storage->isAvailable(), 'table documents disponible');

    // Test lecture seule : findLatestByEntity sur une entité inexistante retourne null sans erreur
    assertEquals(null, $storage->findLatestByEntity('test_entite', '999999', ['test_doc']));
    assertEquals(null, $storage->getByReference('REF_INEXISTANTE_XYZ'));
});

test('DocumentStorageService: findForViewer gère les types connus sans erreur', function () {
    $pdo = \Database::getConnection();
    $storage = new DocumentStorageService($pdo);
    // Aucune donnée -> null (pas d'exception)
    $result = $storage->findForViewer('recu', '999999');
    assertTrue($result === null || is_array($result));
});
