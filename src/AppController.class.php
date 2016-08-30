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

  public static function addObjects($request, $response, $args) {
    $json = $request->getBody();
    $data = @json_decode($json, true);

    if ($data === null) {
      $response->withJson(array(
        'success' => false,
        'error'   => 'Could not parse JSON'
      ));

      return $response;
    }

    if (!isset($data['objects']) || !is_array($data['objects'])) {
      $response->withJson(array(
        'success' => false,
        'error'   => "Missing required 'objects'"
      ));

      return $response;
    }

    $new_objects = self::prepareNewObjects($data['objects']);
    if (isset($new_objects['error'])) {
      $response->withJson(array(
        'success' => false,
        'error'   => $new_objects['error']['message'],
        'object'  => $new_objects['error']['object']
      ));

      return $response;
    }

    $results = array_map(function ($object) {
      return commitAddObject(
        $object['name'],
        $object['label'],
        $object['type_id'],
        $object['asset_tag'],
        $object['tags']
      );
    }, $new_objects['objects']);

    $response->withJson(array(
      'success' => true,
      'objects' => $results
    ));
  }

  /**
   * Check incoming objects for validity.
   *
   * Objects must have at least a name and a valid type. Tags
   *   must be passed as an array.
   *
   * @param array $objects the new objects
   *
   * @return array either an array of the new objects or the
   *   first invalid object encountered and an associated message
   */
  private static function prepareNewObjects($objects) {
    $required_keys = array('name', 'type');
    $object_types = ObjectUtils::getObjectTypeMap();

    return array_reduce(
      $objects,
      function ($acc, $object) use ($required_keys, $object_types) {
        if (isset($acc['error'])) {
          return $acc;
        }

        $missing = array_diff($required_keys, array_keys($object));
        if (count($missing) > 0) {
          return array(
            'error' => array(
              'message' => 'Missing required keys ' . implode($missing, ','),
              'object'  => $object
            )
          );
        }

        $type_name = $object['type'];
        if (!isset($object_types[$type_name])) {
          return array(
            'error' => array(
              'message' => "Invalid type {$type_name}",
              'object'  => $object
            )
          );
        }

        $name = $object['name'];
        $type_id = $object_types[$type_name];
        $label = (isset($object['label'])) ? $object['label'] : null;
        $asset_tag = (isset($object['asset_tag'])) ? $object['asset_tag'] : null;
        $tags = (isset($object['tags'])) ? $object['tags'] : array();

        if (!is_array($tags)) {
          return array(
            'error' => array(
              'message' => 'Tags must be an array',
              'object'  => $object
            )
          );
        }

        $new_object = array(
          'name'      => $name,
          'label'     => $label,
          'type_id'   => $type_id,
          'asset_tag' => $asset_tag,
          'tags'      => $tags
        );

        $acc['objects'][] = $new_object;
        return $acc;
      },
      array('objects' => array())
    );
  }
}
