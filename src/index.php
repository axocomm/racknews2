<?php
require 'vendor/autoload.php';
require 'init.php';
require 'utils.php';

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

use \Racknews\ObjectUtils as ObjectUtils;

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
  $params = $request->getQueryParams();

  $result = ObjectUtils::query($objects, $params);

  $response->withJson($result);
});

$app->run();
