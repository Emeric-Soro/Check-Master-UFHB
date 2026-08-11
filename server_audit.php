<?php
/**
 * Outil de diagnostic retire du webroot (voir plan_reduction_code_50_pourcent.md).
 * Original conserve dans storage/private/diagnostic_tools/.
 */
http_response_code(403);
header('Content-Type: text/plain; charset=UTF-8');
error_log('Tentative d\'acces a un outil de diagnostic retire: ' . ($_SERVER['REQUEST_URI'] ?? ''));
exit('Acces refuse.');

