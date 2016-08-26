<?php
require 'vendor/autoload.php';
require 'init.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app = new \Slim\App;
$app->get('/', function (Request $request, Response $response) {
  $resp = array(
    'success'         => true,
    'racktables_root' => RACKTABLES_ROOT
  );

  $response->withJson($resp);
});

$app->get('/objects', function (Request $request, Response $response) {
  $objects = get_objects();
  $response->withJson($objects);
});

$app->run();
