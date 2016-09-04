<?php
define('APP', dirname(__FILE__) . '/app');

require 'vendor/autoload.php';
require 'init.php';

require 'inc/Helpers.class.php';
require 'inc/ObjectUtils.class.php';
require 'inc/IPv4Utils.class.php';
require APP . '/controllers/Controller.class.php';
require APP . '/controllers/AppController.class.php';
require APP . '/controllers/ObjectsController.class.php';
require APP . '/controllers/IPv4Controller.class.php';

use \Racknews\Helpers as Helpers;
use \Racknews\ObjectUtils as ObjectUtils;
use \Racknews\Controller as Controller;
use \Racknews\AppController as AppController;
use \Racknews\ObjectsController as ObjectsController;
use \Racknews\IPv4Controller as IPv4Controller;

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

  $this->group('/{identifier}', function () {
    $this->get('', '\Racknews\ObjectsController:getObject');
    $this->put('', '\Racknews\ObjectsController:updateObject');
    $this->delete('', '\Racknews\ObjectsController:deleteObject');
  });
});

$app->group('/ipv4', function () {
  $this->get('', '\Racknews\IPv4Controller:getIPv4Allocations');

  $this->group('/{ip}', function () {
    $this->post('', '\Racknews\IPv4Controller:allocateIP');
    $this->delete('', '\Racknews\IPv4Controller:unallocateIP');
  });
});

$app->run();
