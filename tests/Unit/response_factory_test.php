<?php
/**
 * Tests de la fabrique de réponses HTTP et des exceptions.
 */

declare(strict_types=1);

use CheckMaster\Core\ResponseFactory;
use CheckMaster\Core\HttpException;
use CheckMaster\Core\ForbiddenException;
use CheckMaster\Core\NotFoundException;

test('ResponseFactory: html définit le Content-Type et le statut', function () {
    $r = ResponseFactory::html('<p>ok</p>');
    assertEquals(200, $r->status);
    assertEquals('text/html; charset=UTF-8', $r->headers['Content-Type']);
    assertEquals('<p>ok</p>', $r->body);
});

test('ResponseFactory: json encode proprement', function () {
    $r = ResponseFactory::json(['success' => true, 'message' => 'Café']);
    assertEquals(200, $r->status);
    assertEquals('application/json; charset=UTF-8', $r->headers['Content-Type']);
    assertContains('"success":true', $r->body);
    assertContains('Café', $r->body);
});

test('ResponseFactory: ajaxSuccess / ajaxError', function () {
    $ok = ResponseFactory::ajaxSuccess('Enregistré', ['redirect' => 'layout.php?page=x']);
    assertEquals(200, $ok->status);
    assertContains('"redirect"', $ok->body);

    $err = ResponseFactory::ajaxError('Erreur', 422);
    assertEquals(422, $err->status);
    assertContains('"success":false', $err->body);
});

test('ResponseFactory: redirect 302', function () {
    $r = ResponseFactory::redirect('layout.php?page=dashboard');
    assertEquals(302, $r->status);
    assertEquals('layout.php?page=dashboard', $r->headers['Location']);
});

test('ResponseFactory: statuts d\'erreur standard', function () {
    assertEquals(400, ResponseFactory::badRequest()->status);
    assertEquals(401, ResponseFactory::unauthorized()->status);
    assertEquals(403, ResponseFactory::forbidden()->status);
    assertEquals(404, ResponseFactory::notFound()->status);
    assertEquals(422, ResponseFactory::unprocessable()->status);
    assertEquals(429, ResponseFactory::tooManyRequests()->status);
    assertEquals(500, ResponseFactory::serverError()->status);
});

test('ResponseFactory: file inexistant → 404, fichier existant → 200', function () {
    $missing = ResponseFactory::file('C:/fichier/inexistant.pdf', 'application/pdf');
    assertEquals(404, $missing->status);

    $tmp = tempnam(sys_get_temp_dir(), 'cmtest');
    file_put_contents($tmp, 'PDFDATA');
    $ok = ResponseFactory::file($tmp, 'application/pdf', 'doc.pdf', true);
    assertEquals(200, $ok->status);
    assertEquals('application/pdf', $ok->headers['Content-Type']);
    assertContains('attachment', $ok->headers['Content-Disposition']);
    assertContains('doc.pdf', $ok->headers['Content-Disposition']);
    assertEquals('PDFDATA', $ok->body);
    @unlink($tmp);
});

test('ResponseFactory: isAjax détecte le header X-Requested-With', function () {
    assertTrue(ResponseFactory::isAjax(['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']));
    assertFalse(ResponseFactory::isAjax(['HTTP_X_REQUESTED_WITH' => '']));
    assertFalse(ResponseFactory::isAjax([]));
});

test('HttpException: code statut et réponse associée', function () {
    $e = new ForbiddenException('Interdit');
    assertEquals(403, $e->getStatusCode());
    $resp = $e->toResponse();
    assertEquals(403, $resp->status);
    assertContains('Interdit', $resp->body);

    $nf = new NotFoundException('Rien');
    assertEquals(404, $nf->toResponse()->status);

    $base = new HttpException('X', 429);
    assertEquals(429, $base->toResponse()->status);
});
