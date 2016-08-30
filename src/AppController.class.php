<?php
namespace Racknews;

class AppController {
  public static function getObjects($request, $response, $args) {
    $params = $request->getQueryParams();
    $objects = ObjectUtils::getObjects($params);

    $response->withJson(array(
      'success' => true,
      'objects' => $objects
    ));
  }

  public static function getObject($request, $response, $args) {
    $id_or_name = $args['id-or-name'];
    $params = array(
      'any' => array(
        'id'   => $id_or_name,
        'name' => $id_or_name
      )
    );

    $objects = ObjectUtils::getObjects($params);
    $object = (count($objects) > 0) ? current($objects) : null;

    $response->withJson(array(
      'success' => true,
      'object'  => $object
    ));
  }
}
