<?php
namespace Racknews;

class IPv4Controller {
  public static function getIPv4Allocations($request, $response, $args) {
    $addrs = IPv4Utils::getAddresses(true);
    $addrs = array_walk_recursive($addrs, 'utf8_encode');
    $response->withJson($addrs);
  }
}
