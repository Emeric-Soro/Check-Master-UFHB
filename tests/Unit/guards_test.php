<?php
/**
 * Tests des guards extraits de layout.php (Phase 1).
 */

declare(strict_types=1);

use CheckMaster\Security\RequestContext;
use CheckMaster\Security\AuthenticationGuard;
use CheckMaster\Security\CsrfGuard;
use CheckMaster\Security\AuthorizationGuard;
use CheckMaster\Security\LegacyCompatibilityLayer;

// ─── RequestContext ─────────────────────────────────────────
test('RequestContext: normalise méthode/page/action/ajax', function () {
    $ctx = new RequestContext(
        ['page' => 'dashboard', 'action' => 'voir'],
        ['x' => '1'],
        ['REQUEST_METHOD' => 'POST', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
    );
    assertEquals('POST', $ctx->method);
    assertEquals('dashboard', $ctx->page);
    assertEquals('voir', $ctx->action);
    assertTrue($ctx->isAjax);
    assertTrue($ctx->isPost());
});

test('RequestContext: isOwnProfileUpdate détecte les updates de profil', function () {
    $ctx = new RequestContext(
        ['page' => 'profil'],
        ['update_password' => '1'],
        ['REQUEST_METHOD' => 'POST']
    );
    assertTrue($ctx->isOwnProfileUpdate());

    $ctx2 = new RequestContext(['page' => 'profil'], [], ['REQUEST_METHOD' => 'GET']);
    assertFalse($ctx2->isOwnProfileUpdate());
});

test('RequestContext: csrfRedirectUrl utilise le referer même hôte sinon repli', function () {
    $ctx = new RequestContext(
        ['page' => 'gestion_etudiants'],
        [],
        ['HTTP_REFERER' => 'http://localhost/checkmaster/layout.php?page=profil', 'HTTP_HOST' => 'localhost']
    );
    assertContains('layout.php?page=profil', $ctx->csrfRedirectUrl());

    $ctx2 = new RequestContext(['page' => 'x'], [], ['HTTP_REFERER' => 'http://evil.com/x', 'HTTP_HOST' => 'localhost']);
    assertContains('layout.php?page=x', $ctx2->csrfRedirectUrl());
});

// ─── AuthenticationGuard ────────────────────────────────────
test('AuthenticationGuard: refuse sans session, accepte avec id_utilisateur', function () {
    $guard = new AuthenticationGuard();
    assertTrue($guard->isAuthenticated(['id_utilisateur' => 1]));
    assertFalse($guard->isAuthenticated([]));
    $resp = $guard->requireAuthenticated([], []);
    assertEquals(302, $resp->status);
    assertContains('page_connexion.php', $resp->headers['Location']);
    assertEquals(null, $guard->requireAuthenticated(['id_utilisateur' => 1], []));
});

// ─── CsrfGuard ──────────────────────────────────────────────
test('CsrfGuard: GET non contrôlé, POST sans token refusé (AJAX 403)', function () {
    $guard = new CsrfGuard();
    $get = new RequestContext([], [], ['REQUEST_METHOD' => 'GET']);
    assertEquals(null, $guard->check($get));

    $postAjax = new RequestContext([], [], ['REQUEST_METHOD' => 'POST', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
    $resp = $guard->check($postAjax, ['id_utilisateur' => 1]);
    assertEquals(403, $resp->status);
    assertContains('"success":false', $resp->body);
});

test('CsrfGuard: POST avec token valide accepté', function () {
    \CheckMaster\Core\Session::start();
    $token = \CheckMaster\Core\Csrf::token();
    $guard = new CsrfGuard();
    $post = new RequestContext([], ['csrf_token' => $token], ['REQUEST_METHOD' => 'POST']);
    assertEquals(null, $guard->check($post));
});

test('CsrfGuard: POST HTML invalide → redirection + message session', function () {
    $guard = new CsrfGuard();
    $session = ['id_utilisateur' => 1];
    $post = new RequestContext(['page' => 'profil'], [], ['REQUEST_METHOD' => 'POST']);
    $resp = $guard->check($post, $session);
    assertEquals(302, $resp->status);
    assertContains('layout.php?page=profil', $resp->headers['Location']);
    assertEquals('csrf', $_SESSION['error_type'] ?? null);
});

// ─── AuthorizationGuard ─────────────────────────────────────
test('AuthorizationGuard: pas de page → autorisé; groupe inexistant → refusé', function () {
    $guard = new AuthorizationGuard(\Database::getConnection());
    $ctx = new RequestContext([], [], ['REQUEST_METHOD' => 'GET']);
    $res = $guard->canAccess($ctx, 1, []);
    assertTrue($res['allowed'], 'sans page, autorisé');

    $ctx2 = new RequestContext(['page' => 'dashboard'], [], ['REQUEST_METHOD' => 'GET']);
    $res2 = $guard->canAccess($ctx2, 999999, []);
    assertFalse($res2['allowed'], 'groupe inexistant refusé');
});

test('AuthorizationGuard: exemptions (docviewer) et profil autorisés', function () {
    $guard = new AuthorizationGuard(\Database::getConnection());
    $ctxDoc = new RequestContext(['page' => 'docviewer'], [], ['REQUEST_METHOD' => 'GET']);
    $res = $guard->canAccess($ctxDoc, 999999, ['docviewer']);
    assertTrue($res['allowed'], 'docviewer exempté');

    $ctxProfil = new RequestContext(['page' => 'profil'], ['update_password' => '1'], ['REQUEST_METHOD' => 'POST']);
    $res2 = $guard->canAccess($ctxProfil, 999999, []);
    assertTrue($res2['allowed'], 'update profil autorisé');
});

test('AuthorizationGuard: deniedResponse AJAX 403 / HTML redirect', function () {
    $guard = new AuthorizationGuard(\Database::getConnection());
    $ajax = new RequestContext([], [], ['REQUEST_METHOD' => 'GET', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
    $r1 = $guard->deniedResponse($ajax);
    assertEquals(403, $r1->status);

    $html = new RequestContext(['page' => 'x'], [], ['REQUEST_METHOD' => 'GET']);
    $r2 = $guard->deniedResponse($html, ['id_utilisateur' => 1]);
    assertEquals(302, $r2->status);
    assertContains('access_denied', $r2->headers['Location']);
    assertEquals('permission_denied', $_SESSION['error_type'] ?? null);
});

// ─── LegacyCompatibilityLayer ───────────────────────────────
test('LegacyCompatibilityLayer: alias maj_etudiant → gestion_etudiants', function () {
    $layer = new LegacyCompatibilityLayer();
    $_GET = ['page' => 'maj_etudiant'];
    $_REQUEST = ['page' => 'maj_etudiant'];
    $layer->apply($_GET);
    assertEquals('gestion_etudiants', $_GET['page']);
    assertEquals('ajouter_des_etudiants', $_GET['action']);
    assertEquals('gestion_etudiants', $_REQUEST['page']);
    // Nettoyage
    $_GET = [];
    $_REQUEST = [];
});

test('LegacyCompatibilityLayer: page inconnue non modifiée', function () {
    $layer = new LegacyCompatibilityLayer();
    $_GET = ['page' => 'page_inconnue'];
    $layer->apply($_GET);
    assertEquals('page_inconnue', $_GET['page']);
    $_GET = [];
});
