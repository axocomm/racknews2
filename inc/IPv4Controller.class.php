<?php
namespace Racknews;

class IPv4Controller {
  public static function getIPv4Allocations($request, $response, $args) {
    $allocations = IPv4Utils::getAddresses();
    $response->withJson(array(
      'success'     => true,
      'allocations' => $allocations
    ));
  }

  public static function allocateIP($request, $response, $args) {
    $ip = $args['ip'];
    $object_id = $args['object'];
    $response->withJson(array(
      'success' => true,
      'result'  => IPv4Utils::ipToInt($ip)
    ));
  }
}
