<?php
namespace Racknews;

abstract class Controller {
  protected $ci;

  public function __construct($ci) {
    $this->ci = $ci;
  }
}
