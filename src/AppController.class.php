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

  /**
   * Add new objects.
   *
   * Depending on the Content-type header, this method will try to
   *   parse a CSV or JSON request body.
   *
   * @param object $request
   * @param object $response
   * @param array  $args
   *
   * @return array success and either error or added IDs
   */
  public static function addObjects($request, $response, $args) {
    $data = self::parseNewObjectData($request);

    if ($data === null) {
      $response->withJson(array(
        'success' => false,
        'error'   => 'Could not read request data'
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
   * Read new objects from CSV or JSON request body depending on
   *   the Content-type header (default JSON)
   *
   * @param object $request the request object
   *
   * @return array the parsed request body or null
   */
  private static function parseNewObjectData($request) {
    $content_type = $request->getHeader('Content-type');
    $body = $request->getBody();

    switch ($content_type[0]) {
      case 'text/plain':
        $objects = Helpers::csvToArray($body);
        $objects = array_map(
          function ($object) {
            if (strlen($object['tags'])) {
              $object['tags'] = str_getcsv($object['tags']);
            } else {
              unset($object['tags']);
            }

            return $object;
          },
          $objects
        );

        return array('objects' => $objects);
      case 'application/json':
      default:
        return @json_decode($body, true);
    }
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
