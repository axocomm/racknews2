<?php
define('RACKTABLES_ROOT', dirname(__FILE__) . '/../');

require RACKTABLES_ROOT . '/inc/init.php';

function get_objects() {
  $rt_objects = scanRealmByText('object');

  return array_reduce(
    $rt_objects,
    function ($acc, $object) {
      $info = spotEntity('object', $object['id']);
      amplifyCell($info);

      $attrs = array_reduce(
        getAttrValues($object['id']),
        function ($attr_acc, $record) {
          if (!isset($record['name'])) {
            throw new \Exception("Broken record for {$object['id']}");
          }

          $attr_acc[$rec['name']] = $record['value'];
          return $attr_acc;
        },
        array()
      );

      $info = array_merge($info, $attrs);
      $acc[$info['name']] = $info;
      return $acc;
    },
    array()
  );
}
