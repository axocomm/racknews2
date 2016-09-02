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
    $identifier = $request->getQueryParam('object');
    $name = $request->getQueryParam('name', '');
    $type = $request->getQueryParam('type', 'regular');

    if (!$identifier) {
      return $response->withJson(array(
        'success' => false,
        'error'   => 'Missing object'
      ));
    }

    $object = ObjectUtils::getObject(array(
      'any' => array(
        'name' => $identifier,
        'id'   => $identifier,
        'FQDN' => $identifier
      )
    ));

    if ($object === null) {
      return $response->withJson(array(
        'success' => false,
        'error'   => "Object {$identifier} does not exist"
      ));
    }

    $object_id = $object['id'];
    $ip_bin = IPv4Utils::ipToBin($ip);

    bindIPv4ToObject(
      $ip_bin,
      $object_id,
      $name,
      $type
    );

    $response->withJson(array(
      'success' => true,
      'message' => "Bound {$ip} to {$identifier}"
    ));
  }

  public static function unallocateIP($request, $response, $args) {
    $ip = $args['ip'];
    $identifier = $request->getQueryParam('object');

    if (!$identifier) {
      return $response->withJson(array(
        'success' => false,
        'error'   => 'Missing object'
      ));
    }

    $object = ObjectUtils::getObject(array(
      'any' => array(
        'name' => $identifier,
        'id'   => $identifier,
        'FQDN' => $identifier
      )
    ));

    if ($object === null) {
      return $response->withJson(array(
        'success' => false,
        'error'   => "Object {$identifier} does not exist"
      ));
    }

    $object_id = $object['id'];
    $ip_bin = IPv4Utils::ipToBin($ip);

    unbindIPFromObject($ip_bin, $object_id);

    $response->withJson(array(
      'success' => true,
      'message' => "Unbound {$ip} from {$identifier}"
    ));
  }
}
