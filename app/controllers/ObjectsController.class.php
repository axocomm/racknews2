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
    $objects = Object::find($params);

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
    $object = Object::byIdentifier($identifier);
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
   * Update an existing object.
   *
   * Accepts an identifier in `args` and a JSON update payload
   *
   * @param object $request
   * @param object $response
   * @param array  $args
   *
   * @return array success and result
   */
  public function updateObject($request, $response, $args) {
    $identifier = $args['identifier'];
    $object = Object::byIdentifier($identifier);
    if ($object === null) {
      return $response->withJson(array(
        'success' => false,
        'error'   => "Object {$identifier} does not exist"
      ));
    }

    $object_id = $object['id'];
    $attributes = Object::getAttributes();

    $body = $request->getBody();
    $payload = @json_decode($body, true);
    if ($payload === null) {
      return $response->withJson(array(
        'success' => false,
        'error'   => 'Could not parse request body'
      ));
    }

    // Split update keys into attributes and basic fields.
    $updates = array_reduce(
      array_keys($payload),
      function ($acc, $k) use ($payload, $attributes) {
        $v = $payload[$k];
        if (array_key_exists($k, $attributes)) {
          $acc['attrs'][$k] = $v;
        } else if (in_array($k, Object::UPDATE_FIELDS)) {
          $acc['fields'][$k] = $v;
        } else {
          $acc['errors'][] = $k;
        }

        return $acc;
      },
      array(
        'attrs'  => array(),
        'fields' => array(),
        'errors' => array()
      )
    );

    if (count($updates['errors'])) {
      return $response->withJson(array(
        'success' => false,
        'error'   => 'Invalid fields ' . implode($updates['errors'], ', ')
      ));
    }

    // Update attribute values.
    foreach ($updates['attrs'] as $k => $v) {
      $attr_id = $attributes[$k]['id'];
      commitUpdateAttrValue($object_id, $attr_id, $v);
    }

    // Update basic fields.
    $fields = $updates['fields'];
    $update_val = function ($k, $default = null) use ($fields, $object) {
      if (isset($fields[$k])) {
        return $fields[$k];
      } else if (isset($object[$k])) {
        return $object[$k];
      } else {
        return $default;
      }
    };

    $name = $update_val('name');
    $label = $update_val('label');
    $has_problems = $update_val('has_problems', false);
    $asset_no = $update_val('asset_tag');
    $comment = $update_val('comment');

    commitUpdateObject(
      $object_id,
      $name,
      $label,
      $has_problems,
      $asset_no,
      $comment
    );

    return $response->withJson(array(
      'success' => true,
      'updates' => $updates
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
    $object = Object::byIdentifier($identifier);
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
   * Get the attributes map.
   *
   * @param object $request
   * @param object $response
   * @param array  $args
   *
   * @return array
   */
  public function getAttributeMap($request, $response, $args) {
    $response->withJson(array(
      'success'    => true,
      'attributes' => Object::getAttributes()
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
    $object_types = Object::getObjectTypeMap();

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
