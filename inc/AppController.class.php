<?php
namespace Racknews;

class AppController extends Controller {
  public function index($request, $response, $args) {
    $this->ci->view->render($response, 'readme.php', array(
      'content' => 'foo'
    ));
  }
}
