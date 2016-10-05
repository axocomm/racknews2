<?php
namespace Racknews;

class AppController extends Controller {
  public function index($request, $response, $args) {
    $this->ci->view->render($response, 'readme.php', array(
      'content' => 'foo'
    ));
  }

  public function report($request, $response, $args) {
    return $this->ci->view->render($response, 'report.twig.html');
  }
}
