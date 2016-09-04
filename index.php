<?php
require 'vendor/autoload.php';
require 'init.php';

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
