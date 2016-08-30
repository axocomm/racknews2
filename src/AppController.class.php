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
    $id = $args['id'];
    $params = array(
      'all' => array(
        'id' => $id
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
