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

    $object = ObjectUtils::getObjects($params);

    $response->withJson(array(
      'success' => true,
      'object'  => $object
    ));
  }
}
