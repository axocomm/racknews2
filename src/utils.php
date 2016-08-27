<?php
namespace Racknews;

class ObjectUtils {
  const MATCH_RE = '/^([A-Za-z_]+):(.+)$/';

  public static function query($objects, array $params) {
    return array_reduce(
      array_keys($params),
      function ($acc, $key) use ($params) {
        switch ($key) {
          case 'has':
            return self::withKey($acc, $params[$key]);
          case 'matches':
            $match_map = self::getMatchMap($params[$key]);
            return self::objectsMatching($acc, $match_map);
          default:
            return $acc;
        }
      },
      $objects
    );
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

  public static function objectsMatching($objects, $match_map) {
    return array_filter(
      $objects,
      function ($object) use ($match_map) {
        return self::objectMatches($object, $match_map);
      }
    );
  }

  public static function objectMatches($object, $match_map) {
    $results = array_map(
      function ($field) use ($object, $match_map) {
        $value = $match_map[$field];
        return isset($object[$field]) &&
               ((string) $object[$field]) === $value;

      },
      array_keys($match_map)
    );

    return self::all($results);
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
   * @return array if all values are true
   */
  private static function all($arr) {
    return count(array_unique($arr)) === 1 && current($arr);
  }
}
