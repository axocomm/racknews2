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

    $matching_objects = array_reduce(
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
          case 'tagged':
            return self::tagged($acc, explode(',', $val));
          case 'ip':
            return self::withIP($acc, explode(',', $val));
          default:
            return $acc;
        }
      },
      $objects
    );

    return array_map(array('self', 'removeIPBin'), $matching_objects);
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
    $types = self::getObjectTypeMap();

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
   * Get objects with the given tags (any type).
   *
   * @param array $objects
   * @param array $tags
   *
   * @return array objects with any of the given tags
   */
  public static function tagged($objects, $tags) {
    return array_filter(
      $objects,
      function ($object) use ($tags) {
        $object_tags = self::getObjectTags($object);
        return count(array_intersect($object_tags, $tags)) > 0;
      }
    );
  }

  /**
   * Get an object with the given IP addresses.
   *
   * @param array $objects
   * @param array $ips
   *
   * @return array
   */
  public static function withIP($objects, $ips) {
    return array_filter(
      $objects,
      function ($object) use ($ips) {
        if (!isset($object['ipv4']) || count($object['ipv4']) === 0) {
          return false;
        }

        $object_ips = array_map(function ($alloc) {
          return $alloc['addrinfo']['ip'];
        }, $object['ipv4']);

        return count(array_intersect($object_ips, $ips)) > 0;
      }
    );
  }

  /**
   * Get atags, etags, and itags of the given object.
   *
   * @param array $object
   *
   * @return array
   */
  public static function getObjectTags($object) {
    $tag_vals = function ($tag_arr) {
      return array_map(function ($tag) {
        return $tag['tag'];
      }, $tag_arr);
    };

    $object_tags = array_map(
      $tag_vals,
      array(
        $object['atags'],
        $object['etags'],
        $object['itags']
      )
    );

    $object_tags = call_user_func_array('array_merge', $object_tags);

    return array_unique($object_tags);
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
        return Helpers::any($results);
      case self::MATCH_ALL:
      default:
        return Helpers::all($results);
    }
  }

  /**
   * Get object type IDs keyed by lowercased name.
   *
   * @return array
   */
  public static function getObjectTypeMap() {
    $types = readChapter(CHAP_OBJTYPE);
    return array_flip(array_map('strtolower', $types));
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
   * Remove converted IP address keys and values to prevent
   *   encoding issues when sending the JSON response.
   *
   * @param array $object
   *
   * @return array the object with fixed IP allocation keys and
   *   no ip_bin
   */
  private static function removeIPBin($object) {
    $allocs = $object['ipv4'];
    $addrs = array_map(function ($id) use ($allocs) {
      $alloc = $allocs[$id];
      unset($alloc['addrinfo']['ip_bin']);
      return $alloc;
    }, array_keys($allocs));

    $object['ipv4'] = $addrs;
    return $object;
  }
}
