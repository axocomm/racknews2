<?php
define('RACKTABLES_ROOT', dirname(__FILE__) . '/../');
define('APP', dirname(__FILE__) . '/app');

require RACKTABLES_ROOT . '/inc/init.php';

require 'inc/Helpers.class.php';
require APP . '/models/Object.class.php';
require APP . '/models/IPv4Address.class.php';
require APP . '/controllers/Controller.class.php';
require APP . '/controllers/AppController.class.php';
require APP . '/controllers/ObjectsController.class.php';
require APP . '/controllers/IPv4Controller.class.php';

use \Racknews\Helpers as Helpers;

use \Racknews\Object as Object;
use \Racknews\IPv4Address as IPv4Address;

use \Racknews\Controller as Controller;
use \Racknews\AppController as AppController;
use \Racknews\ObjectsController as ObjectsController;
use \Racknews\IPv4Controller as IPv4Controller;

if (!function_exists('loadIPv4AddrList')) {
  function loadIPv4AddrList(&$info) {
    \loadIPAddrList($info);
  }
}
