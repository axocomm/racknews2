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
}
