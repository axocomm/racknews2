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

  public function css($request, $response, $args) {
    $asset_path = self::getAssetPath($request);
    $filename = $args['filename'];
    return $response->withRedirect("$asset_path/css/$filename");
  }

  public function js($request, $response, $args) {
    $asset_path = self::getAssetPath($request);
    $filename = $args['filename'];
    return $response->withRedirect("$asset_path/js/$filename");
  }

  private static function getAssetPath($request) {
    return self::getBaseUri($request) . '/app/public';
  }

  private static function getBaseUri($request) {
    return preg_replace(
      '~^http://[^/]*(.*)/racknews/.*~',
      '\1/racknews',
      $request->getUri()
    );
  }
}
