<?php
/**
* huppel konijntje huppel and wiebel
* Hommage to Grace Hopper, programmer & expert in *litteral* duck taping
***/

namespace HexMakina\kadro\Router;

require __DIR__.'/AltoRouter.php';

class hopper extends \AltoRouter implements RouterInterface
{
  private $match=null;
  private $file_root=null;
  private $controller_namespaces = null;

  //----------------------------------------------------------- INITIALISATION
  public function __construct($settings)
  {
    if(!isset($settings['route_home']))
      throw new RouterException('ROUTE_HOME_UNDEFINED');

    parent::__construct();

    $this->set_web_base($settings['web_base'] ?? '');
    $this->set_file_root($settings['file_root'] ?? __DIR__);

    $this->controller_namespaces = $settings['controllers_namespaces'] ?? [];

    list($url, $controller_method, $name) = $settings['route_home'];
    $this->map(self::REQUEST_GET, '', $settings['route_home'], self::ROUTE_HOME_NAME);
  }

  public function __debugInfo()
  {
    $dbg = get_object_vars($this);
    $dbg['routes'] = count($dbg['routes']);
    $dbg['namedRoutes'] = count($dbg['namedRoutes']);
    unset($dbg['matchTypes']);
    return $dbg;
  }
  // ----------------------------------------------------------- MATCHING REQUESTS
  public function match($requestUrl = NULL, $requestMethod = NULL)
  {
    $this->match = parent::match($requestUrl, $requestMethod);

    if($this->match === false)
      throw new RouterException('ROUTE_MATCH_FALSE');

    $res = explode('::', self::target());

    if($res === false || !isset($res[1]) || isset($ret[2]))
      throw new RouterException('INVALID_TARGET');

    if($this->match['name'] === 'akadok_controller_method')
      $res = [ucfirst(self::params('controller')).'Controller', ucfirst(self::params('method'))];


    $target_controller = $res[0];
    $target_method = $res[1];
    $found = false;

    foreach($this->controller_namespaces as $controller_ns)
      if($found = class_exists($controller_class_name = "$controller_ns$target_controller"))
        break;

    if($found === false)
      throw new RouterException('INVALID_CONTROLLER_NAME');

    $this->match['target_controller'] = $controller_class_name;
    $this->match['target_method'] = $target_method;

    return [$controller_class_name, $target_method];
  }

  public function params($param_name=null)
  {
    return $this->extract_request($this->match['params'] ?? [], $param_name);
  }

  public function submitted($param_name=null)
  {
    return $this->extract_request($_POST, $param_name);
  }

  private function extract_request($dat_ass, $key=null)
  {

    // $key is null, returns $dat_ass or empty array
    if(is_null($key))
      return $dat_ass ?? [];

    // $dat_ass[$key] not set, returns null
    if(!isset($dat_ass[$key]))
      return null;

    // $dat_ass[$key] is a string, returns decoded value
    if(is_string($dat_ass[$key]))
      return urldecode($dat_ass[$key]);

    // $dat_ass[$key] is not a string, return match[$key]
    return $dat_ass[$key];

  }

  public function target()
  {
    return $this->match['target'];
  }

  public function target_controller()
  {
    return $this->match['target_controller'];
  }

  public function target_method()
  {
    return $this->match['target_method'];
  }

  public function name()
  {
    return $this->match['name'];
  }

  // ----------------------------------------------------------- ROUTING TOOLS
  public function route_exists($route) : bool
  {
    return isset($this->namedRoutes[$route]);
  }

  public function named_routes()
  {
    return $this->namedRoutes;
  }

  /*
   * @param route_name string  requires
   *  - a valid AltoRouter route name
   *  - OR a Descendant of Model
   * @route_params requires
   *  - an assoc_array of url params (strongly AltoRouter-based)
   * returns: something to put in a href="", action="" or header('Location:');
   */
  public function prehop($route, $route_params=[])
  {
    try{
      $url = $this->generate($route, $route_params);
    }catch(\Exception $e){
      $url = $this->prehop(self::ROUTE_HOME_NAME);
    }

    return $url;
  }

  public function prehop_here($url=null)
  {
    return $url ?? $_SERVER['REQUEST_URI'];
  }

  /*
   * @params $route is
   *    - empty: default is ROUTE_HOME_NAME
   *    - an existing route name: make url with optional [$route_params])
   *    - a url, go there
   * @params $route_params, assoc_data for url creation (i:id, a:format, ..)
   */
  public function hop($route=null, $route_params=[])
  {
    $url = null;

    if(is_null($route))
      $url = $this->prehop(self::ROUTE_HOME_NAME, $route_params);
    elseif(is_string($route) && $this->route_exists($route))
      $url = $this->prehop($route, $route_params);
    else
      $url = $route;

    $this->hop_url($url);
  }

  // hops back to previous page (referer()), or home if no referer
  public function hop_back()
  {
    if(!is_null($back = $this->referer()))
      $this->hop_url($back);

    $this->hop();
  }

  public function hop_url($url)
  {
  	header('Cache-Control: no-cache, must-revalidate');
  	header('Expires: Mon, 01 Jan 1970 00:00:00 GMT');
    header('Location: '.$url);
    exit();
  }

  // returns full URL of the refering URL
  // returns null if same as current URL (prevents endless redirection loop)
  public function referer()
  {
    if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != $this->web_host() .$_SERVER['REQUEST_URI'])
      return $_SERVER['HTTP_REFERER'];

    return null;
  }

  public function send_file($file_path)
  {
    if(!file_exists($file_path))
      throw new RouterException('SENDING_NON_EXISTING_FILE');

    $file_name = basename($file_path);

    //Get file type and set it as Content Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    header('Content-Type: ' . finfo_file($finfo, $file_path));

    finfo_close($finfo);

    //Use Content-Disposition: attachment to specify the filename
    header('Content-Disposition: attachment; filename='.$file_name);

    //No cache
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    //Define file size
    header('Content-Length: ' . filesize($file_path));

    ob_clean();
    flush();
    readfile($file_path);
    die;
  }

  // ----------------------------------------------------------- PROCESSING REQUESTS
  public function requests() : bool
  {
    return $_SERVER['REQUEST_METHOD'] === self::REQUEST_GET;
  }

  public function submits() : bool
  {
    return $_SERVER['REQUEST_METHOD'] === self::REQUEST_POST;
  }

  public function web_host() : string
  {
    return $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
  }

  public function web_root() : string
  {
    return $this->web_host() . $this->web_base();
  }

  public function web_base() : string
  {
    return $this->basePath ?? '';
  }

  public function set_web_base($setter)
  {
    $this->setBasePath($setter);
  }

  public function file_root() : string
  {
    return $this->file_root ?? __DIR__;
  }

  public function set_file_root($setter)
  {
    $this->file_root = realpath($setter).'/';
  }

}
?>
