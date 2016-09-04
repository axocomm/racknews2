<?php
define('RACKTABLES_ROOT', dirname(__FILE__) . '/../');
define('APP', dirname(__FILE__) . '/app');

require RACKTABLES_ROOT . '/inc/init.php';

require 'inc/Helpers.class.php';
require 'inc/IPv4Utils.class.php';

require APP . '/models/Object.class.php';
require APP . '/controllers/Controller.class.php';
require APP . '/controllers/AppController.class.php';
require APP . '/controllers/ObjectsController.class.php';
require APP . '/controllers/IPv4Controller.class.php';

use \Racknews\Helpers as Helpers;

use \Racknews\Object as Object;

use \Racknews\Controller as Controller;
use \Racknews\AppController as AppController;
use \Racknews\ObjectsController as ObjectsController;
use \Racknews\IPv4Controller as IPv4Controller;

function get_objects() {
  $rt_objects = scanRealmByText('object');

  return array_map(
    function ($object) {
      $info = spotEntity('object', $object['id']);
      amplifyCell($info);

      $attrs = array_reduce(
        getAttrValues($object['id']),
        function ($attr_acc, $record) {
          if (!isset($record['name'])) {
            throw new \Exception("Broken record for {$object['id']}");
          }

          $attr_acc[$record['name']] = $record['value'];
          return $attr_acc;
        },
        array()
      );

      $info = array_merge($info, $attrs);
      return $info;
    },
    $rt_objects
  );
}

if (!function_exists('loadIPv4AddrList')) {
  function loadIPv4AddrList(&$info) {
    \loadIPAddrList($info);
  }
}
