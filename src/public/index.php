<?php

$loader = require __DIR__ . '/../../vendor/autoload.php';
ini_set('display_errors', E_ALL);

use Core\Container;
use Core\Router;
use Symfony\Component\HttpFoundation\Request;

session_start();
$request = Request::createFromGlobals();
$router = new Router();
$container = new Container();

$framework = $container->getContainerBuilder()->get('framework');

$response = $framework->handle($request);

$response->send();