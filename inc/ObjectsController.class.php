<?php
namespace Racknews;

class ObjectsController extends Controller {

  /**
   * Query RackTables objects.
   *
   * @param object $request
   * @param object $response
   * @param array  $args
   *
   * @return array success and matching objects
   */
  public function getObjects($request, $response, $args) {
    $params = $request->getQueryParams();
    $objects = ObjectUtils::getObjects($params);

    $response->withJson(array(
      'success' => true,
      'objects' => $objects
    ));
  }

  /**
   * Get a single object by ID or name.
   *
   * @param object $request
   * @param object $response
   * @param array  $args
   *
   * @return array success and the matching object if found
   */
  public function getObject($request, $response, $args) {
    $identifier = $args['identifier'];
    $object = self::getObjectByIdentifier($identifier);
    if ($object !== null) {
      $response->withJson(array(
        'success' => true,
        'object'  => $object
      ));
    } else {
      $response->withJson(array(
        'success' => false,
        'error'   => "Could not find object matching {$identifier}"
      ));
    }
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
  public function addObjects($request, $response, $args) {
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
   * Update an object.
   *
   * Accepts an identifier in `args` and a JSON update payload
   *
   * @param object $request
   * @param object $response
   * @param array  $args
   *
   * @return array success
   */
  public function updateObject($request, $response, $args) {
    $identifier = $args['identifier'];
    $object = self::getObjectByIdentifier($identifier);
    if ($object === null) {
      return $response->withJson(array(
        'success' => false,
        'error'   => "Object {$identifier} does not exist"
      ));
    }

    $id = $object['id'];
    $body = $request->getBody();
    $updates = @json_decode($body, true);
    if ($updates === null) {
      return $response->withJson(array(
        'success' => false,
        'error'   => 'Could not parse request body'
      ));
    }

    $update_val = function ($k, $default = null) use ($updates, $object) {
      return (isset($updates[$k])) ? $updates[$k] : $default;
    };

    $name = $update_val('name');
    $label = $update_val('label');
    $has_problems = $update_val('has_problems', false);
    $asset_no = $update_val('asset_tag');
    $comment = $update_val('comment');

    commitUpdateObject(
      $id,
      $name,
      $label,
      $has_problems,
      $asset_no,
      $comment
    );

    return $response->withJson(array(
      'success' => true,
      'message' => "Updated {$id}"
    ));
  }

  /**
   * Delete an object by ID or name.
   *
   * @param object $request
   * @param object $response
   * @param array  $args
   *
   * @return array success
   */
  public function deleteObject($request, $response, $args) {
    $identifier = $args['identifier'];
    $object = self::getObjectByIdentifier($identifier);
    if ($object === null) {
      return $response->withJson(array(
        'success' => false,
        'error'   => "Object {$identifier} does not exist"
      ));
    }

    $id = $object['id'];
    commitDeleteObject($id);

    $response->withJson(array(
      'success' => true,
      'message' => "Deleted $id"
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

  private static function getObjectByIdentifier($identifier) {
    $params = array(
      'any' => array(
        'id'   => $identifier,
        'name' => $identifier,
        'FQDN' => $identifier
      )
    );

    $objects = ObjectUtils::getObjects($params);
    return (count($objects) > 0) ? current($objects) : null;
  }
}
