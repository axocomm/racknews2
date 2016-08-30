<?php
require 'vendor/autoload.php';
require 'init.php';

require 'ObjectUtils.class.php';
require 'AppController.class.php';

use \Racknews\ObjectUtils as ObjectUtils;
use \Racknews\AppController as AppController;

$app = new \Slim\App;
$app->get('/', function ($request, $response) {
  $resp = array(
    'success'         => true,
    'racktables_root' => RACKTABLES_ROOT
  );

  $response->withJson($resp);
});

$app->group('/objects', function () {
  $this->get('', function ($request, $response, $args) {
    AppController::getObjects($request, $response, $args);
  });

  $this->group('/{id-or-name}', function () {
    $this->get('', function ($request, $response, $args) {
      AppController::getObject($request, $response, $args);
    });
  });
});

$app->run();
