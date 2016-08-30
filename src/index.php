<?php
require 'vendor/autoload.php';
require 'init.php';

require 'ObjectUtils.class.php';

use \Racknews\ObjectUtils as ObjectUtils;

$app = new \Slim\App;
$app->get('/', function ($request, $response) {
  $resp = array(
    'success'         => true,
    'racktables_root' => RACKTABLES_ROOT
  );

  $response->withJson($resp);
});

$app->group('/objects', function () {
  $this->get('', function ($request, $response) {
    $objects = get_objects();
    $params = $request->getQueryParams();

    $result = ObjectUtils::query($objects, $params);

    $response->withJson($result);
  });

  $this->get('/{id:[0-9]+}', function ($request, $response, $args) {
    $objects = get_objects();
    $id = $args['id'];
    $response->withJson(ObjectUtils::byId($objects, $id));
  });
});

$app->run();
