<?php
require 'vendor/autoload.php';
require 'init.php';

require 'inc/Helpers.class.php';
require 'inc/ObjectUtils.class.php';
require 'inc/AppController.class.php';

use \Racknews\Helpers as Helpers;
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
  $this->get('', '\Racknews\AppController:getObjects');

  $this->post('', '\Racknews\AppController:addObjects');

  $this->group('/{id-or-name}', function () {
    $this->get('', '\Racknews\AppController:getObject');
  });
});

$app->run();
