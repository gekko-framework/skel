<?php
/*
 * (c) Leonardo Brugnara
 *
 * Full copyright and license information in LICENSE file.
 */

require __DIR__ . "/vendor/autoload.php";

// Create a new HTTP Application to route and dispatch the HTTP request
$app = new \Gekko\App\HttpApplication(realpath(__DIR__));

// magic goes here:
// ...

// Dispatch the request
$app->run();
