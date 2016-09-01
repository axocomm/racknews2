<?php
require 'vendor/autoload.php';
require 'init.php';

require 'inc/Helpers.class.php';
require 'inc/ObjectUtils.class.php';
require 'inc/ObjectsController.class.php';

use \Racknews\Helpers as Helpers;
use \Racknews\ObjectUtils as ObjectUtils;
use \Racknews\ObjectsController as ObjectsController;

$app = new \Slim\App;
$app->get('/', function ($request, $response) {
  $resp = array(
    'success'         => true,
    'racktables_root' => RACKTABLES_ROOT
  );

  $response->withJson($resp);
});

$app->group('/objects', function () {
  $this->get('', '\Racknews\ObjectsController:getObjects');

  $this->post('', '\Racknews\ObjectsController:addObjects');

  $this->group('/{id-or-name}', function () {
    $this->get('', '\Racknews\ObjectsController:getObject');
    $this->delete('', '\Racknews\ObjectsController:deleteObject');
  });
});

$app->run();
