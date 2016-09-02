<?php
namespace Racknews;

class Helpers {
  public static function csvToArray($csv) {
    $lines = array_map('trim', explode(PHP_EOL, $csv));
    $lines = array_filter($lines, 'strlen');

    $rows = array_map('str_getcsv', $lines);
    $fields = array_shift($rows);

    return array_map(
      function ($row) use ($fields) {
        return array_combine($fields, $row);
      },
      $rows
    );
  }

  /**
   * Compute the union of two arrays.
   *
   * @param array $a
   * @param array $b
   *
   * @return array
   */
  public static function union($a, $b) {
    return array_unique(array_merge($a, $b));
  }

  /**
   * Return true iff all array values are true.
   *
   * @param array $arr
   *
   * @return bool if all values are true
   */
  public static function all($arr) {
    return count(array_unique($arr)) === 1 && current($arr);
  }


  /**
   * Return true if any of the array values are true.
   *
   * @param array $arr
   *
   * @return bool if any values are true
   */
  public static function any($arr) {
    return count(array_filter($arr)) > 0;
  }
}
