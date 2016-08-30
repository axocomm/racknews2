<?php
namespace Racknews;

// NB: probably want to organize this better and have a
// consistent naming scheme for methods

class ObjectUtils {
  const MATCH_RE = '/^([A-Za-z_]+):(.+)$/';
  const MATCH_ANY = 'any';
  const MATCH_ALL = 'all';

  public static function getObjects($params = array()) {
    $objects = get_objects();

    return array_reduce(
      array_keys($params),
      function ($acc, $key) use ($params) {
        $val = $params[$key];

        switch ($key) {
          case 'has':
            return self::withKey($acc, $val);
          case 'all':
          case 'any':
            $match_map = (is_array($val)) ? $val : self::getMatchMap($val);
            return self::objectsMatching($acc, $match_map, $key);
          case 'type':
            return self::ofType($acc, $val);
          case 'comment':
            return self::withComment($acc, $val);
          case 'log':
            return self::withLogMessage($acc, $val);
          default:
            return $acc;
        }
      },
      $objects
    );
  }

  public static function byId($objects, $id) {
    $matches = array_filter(
      $objects,
      function ($object) use ($id) {
        return (string) $object['id'] === $id;
      }
    );

    if (count($matches)) {
      return current($matches);
    }

    return array();
  }

  /**
   * Get objects that contain the given key.
   *
   * @param array  $objects
   * @param string $key
   *
   * @return array objects containing that key
   */
  public static function withKey($objects, $key) {
    return array_filter(
      $objects,
      function ($object) use ($key) {
        return array_key_exists($key, $object);
      }
    );
  }

  /**
   * Get objects of the given type.
   *
   * @param array  $objects
   * @param string $type the type name
   *
   * @return array objects of the given type
   */
  public static function ofType($objects, $type) {
    $types = readChapter(CHAP_OBJTYPE);
    $types = array_flip(array_map('strtolower', $types));

    $type = strtolower($type);
    if (!array_key_exists($type, $types)) {
      return array();
    }

    $type_id = (string) $types[$type];
    return array_filter(
      $objects,
      function ($object) use ($type_id) {
        return $object['objtype_id'] === $type_id;
      }
    );
  }

  /**
   * Get objects whose comments include the given text.
   *
   * @param array  $objects
   * @param string $comment
   *
   * @return array the resulting objects
   */
  public static function withComment($objects, $comment) {
    return array_filter(
      $objects,
      function ($object) use ($comment) {
        return stripos($object['comment'], $comment) !== false;
      }
    );
  }

  /**
   * Get objects whose logs contain the given string.
   *
   * @param array  $objects
   * @param string $log_message
   *
   * @return array objects with the given log message
   */
  public static function withLogMessage($objects, $log_message) {
    return array_filter(
      $objects,
      function ($object) use ($log_message) {
        $logs = getLogRecordsForObject($object['id']);
        $matching = array_filter(
          $logs,
          function ($log) use ($log_message) {
            return stripos($log['content'], $log_message) !== false;
          }
        );

        return count($matching) > 0;
      }
    );
  }

  /**
   * Get objects that match the given fields.
   *
   * @param array  $objects
   * @param array  $match_map desired fields and values
   * @param string $mode whether to match any or all
   *
   * @return array objects matching all given fields
   */
  public static function objectsMatching(
    $objects,
    $match_map,
    $mode = self::MATCH_ALL
  ) {
    return array_filter(
      $objects,
      function ($object) use ($match_map, $mode) {
        return self::objectMatches($object, $match_map, $mode);
      }
    );
  }

  /**
   * Determine if fields of this object match all in the given
   * match map.
   *
   * @param array  $object
   * @param array  $match_map
   * @param string $mode whether to match any or all fields
   *
   * @return bool if the object matches
   */
  public static function objectMatches($object, $match_map, $mode) {
    $results = array_map(
      function ($field) use ($object, $match_map) {
        $value = $match_map[$field];
        return isset($object[$field]) &&
               ((string) $object[$field]) === $value;

      },
      array_keys($match_map)
    );

    switch ($mode) {
      case self::MATCH_ANY:
        return self::any($results);
      case self::MATCH_ALL:
      default:
        return self::all($results);
    }
  }

  /**
   * Convert the matches parameter string into a map of
   * keys to values.
   *
   * The matches parameter has the form 'field:val' and
   * are separated by commas
   *
   * @param string $matches_param the query parameter value
   *
   * @return array a map of field name to expected value
   */
  private static function getMatchMap($matches_param) {
    $exploded = explode(',', $matches_param);
    return array_reduce($exploded, function ($acc, $p) {
      if (preg_match(self::MATCH_RE, $p, $matches) === 1) {
        $field = $matches[1];
        $value = $matches[2];
        $acc[$field] = $value;
      }

      return $acc;
    }, array());
  }

  /**
   * Return true iff all array values are true.
   *
   * @param array $arr
   *
   * @return bool if all values are true
   */
  private static function all($arr) {
    return count(array_unique($arr)) === 1 && current($arr);
  }

  /**
   * Return true if any of the array values are true.
   *
   * @param array $arr
   *
   * @return bool if any values are true
   */
  private static function any($arr) {
    return count(array_filter($arr)) > 0;
  }
}
