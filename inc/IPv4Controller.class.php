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
}
