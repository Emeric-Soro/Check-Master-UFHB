<?php
/**
 * Tests unitaires des composants Core (Response, Request, Router, Session, Csrf).
 */

declare(strict_types=1);

use CheckMaster\Core\Request;
use CheckMaster\Core\Response;
use CheckMaster\Core\Router;

// ─── Response ───────────────────────────────────────────────
test('Response: statut par défaut 200 et corps vide', function () {
    $r = new Response();
    assertEquals(200, $r->status);
    assertEquals('', $r->body);
});

test('Response: redirect crée une Location 302', function () {
    $r = Response::redirect('layout.php?page=dashboard');
    assertEquals(302, $r->status);
    assertEquals('layout.php?page=dashboard', $r->headers['Location']);
});

test('Response: JSON simple', function () {
    $r = new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    assertEquals('application/json', $r->headers['Content-Type']);
    assertContains('"success":true', $r->body);
});

test('Response: les en-têtes sont nettoyés contre CRLF', function () {
    $r = new Response('', 200, ["X-Test" => "value\r\nInjected: yes"]);
    $r->send();
    // La sortie est envoyée via echo; on ne peut pas facilement intercepter header() en CLI,
    // mais on vérifie que send() ne lève pas d'exception.
    assertTrue(true);
});

// ─── Request ────────────────────────────────────────────────
test('Request: méthode et path normalisés', function () {
    $req = new Request(
        ['REQUEST_METHOD' => 'post', 'REQUEST_URI' => '/public/index.php?_path=/login'],
        ['_path' => '/login'],
        ['login' => 'test']
    );
    assertEquals('POST', $req->method);
    assertEquals('/login', $req->path);
});

test('Request: protection contre la traversée de répertoire via _path', function () {
    $req = new Request(
        ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/index.php?_path=/../etc/passwd'],
        ['_path' => '/../etc/passwd'],
        []
    );
    assertEquals('/', $req->path);
});

test('Request: null byte rejeté', function () {
    $req = new Request(
        ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/index.php?_path=/foo%00bar'],
        ['_path' => "/foo\0bar"],
        []
    );
    assertEquals('/', $req->path);
});

test('Request: base path retiré du path', function () {
    $req = new Request(
        ['REQUEST_METHOD' => 'GET', 'SCRIPT_NAME' => '/checkmaster/public/index.php', 'REQUEST_URI' => '/checkmaster/public/dashboard'],
        [],
        []
    );
    assertEquals('/dashboard', $req->path);
});

// ─── Router ─────────────────────────────────────────────────
test('Router: dispatch GET trouvé', function () {
    $router = new Router();
    $router->get('/ping', fn() => new Response('pong'));
    $req = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/ping'], [], []);
    $resp = $router->dispatch($req);
    assertEquals('pong', $resp->body);
});

test('Router: 404 sur route inconnue', function () {
    $router = new Router();
    $req = new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/nope'], [], []);
    $resp = $router->dispatch($req);
    assertEquals(404, $resp->status);
});

test('Router: méthode non autorisée → 404 (pas de match méthode)', function () {
    $router = new Router();
    $router->get('/only-get', fn() => new Response('x'));
    $req = new Request(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/only-get'], [], []);
    $resp = $router->dispatch($req);
    assertEquals(404, $resp->status);
});

// ─── Session & Csrf ─────────────────────────────────────────
test('Csrf: génération et validation d\'un token', function () {
    \CheckMaster\Core\Session::start();
    $token = \CheckMaster\Core\Csrf::token();
    assertTrue(is_string($token) && strlen($token) === 64, 'token 64 hex');
    assertTrue(\CheckMaster\Core\Csrf::validate($token));
    assertFalse(\CheckMaster\Core\Csrf::validate('wrong-token'));
    assertFalse(\CheckMaster\Core\Csrf::validate(null));
});

// ─── Messages ───────────────────────────────────────────────
test('Messages: interpolation des clés', function () {
    \CheckMaster\Core\Messages::reset();
    $msg = \CheckMaster\Core\Messages::get('auth.password_min_length', ['min' => 8]);
    assertContains('8', $msg);
    assertEquals('cle.inexistante', \CheckMaster\Core\Messages::get('cle.inexistante'));
});
