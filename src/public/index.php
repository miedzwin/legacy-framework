<?php

ini_set('display_errors', E_ALL);

use Core\Framework;
use Symfony\Component\HttpFoundation\Request;

$loader = require __DIR__ . '/../../vendor/autoload.php';

session_start();

$request = Request::createFromGlobals();
$response = (new Framework('legacy'))->handle($request);

$response->send();
