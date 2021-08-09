<?php

namespace HexMakina\kadro\Controllers;

use \Psr\Container\{ContainerInterface,ContainerExceptionInterface,NotFoundExceptionInterface};

use \HexMakina\kadro\Auth\{OperatorInterface, AccessRefusedException};
use \HexMakina\Hopper\RouterInterface;
use \HexMakina\LogLaddy\LoggerInterface;
use \HexMakina\Crudites\Interfaces\TracerInterface;

class KadroController implements Interfaces\DisplayControllerInterface
{
  protected $template_variables = [];
  protected $container = null;

  protected $route_back = null;

  public function __toString(){ return get_called_class();}

  // -------- Controller Container
  public function set_container(ContainerInterface $container)
  {
    $this->container = $container;
  }

  public function container() : ContainerInterface
  {
    return $this->container;
  }

  // shortcut for (un)boxing
  public function box($key, $instance=null)
  {
    if(!is_null($instance))
      $this->container->register($key, $instance);

    // dd($this->container->get($key));
    return $this->container->get($key);
  }

  public function logger() : LoggerInterface
  {
    return $this->box('LoggerInterface');
  }

// -------- Controller Router

  public function router() : RouterInterface
  {
    return $this->box('RouterInterface');
  }

  public function operator() : OperatorInterface
  {
    return $this->box('OperatorInterface');
  }

  public function tracer() : TracerInterface
  {
    return $this->box('TracerInterface');
  }

  public function requires_operator()
  {
    return true; // security by default
  }

  public function authorize($permission=null)
  {
    // if(!$this->requires_operator() || is_null($permission))
    if(!$this->requires_operator())
      return true;

    $operator = $this->operator();
    if(is_null($operator) || $operator->is_new() || !$operator->is_active())
      throw new AccessRefusedException();

    if(!is_null($permission) && !$operator->has_permission($permission))
      throw new AccessRefusedException();

    return true;
  }

  public function viewport($key=null, $value=null, $coercion=false)
  {
    // no key, returns all
    if(is_null($key))
      return $this->template_variables;

    // got key, got null value, returns $[$key]
    if(is_null($value))
    {
      if($coercion === true) // break rule 1 ?
        $this->template_variables[$key] = null;

      return $this->template_variables[$key] ?? null;
    }

    // got key, got value
    // sets or coerces $[$key]=$value and returns $[$key]
    if(!isset($this->template_variables[$key]) || $coercion === true)
      $this->template_variables[$key] = $value;

    return $this->template_variables[$key] ?? null;
  }

  public function execute()
  {
    $this->authorize();

    $custom_template = null;

    if(method_exists($this, 'prepare'))
    	$this->prepare();

  	if(method_exists($this, $method = $this->router()->target_method()))
    {
      $custom_template = $this->$method();
    }

    if(method_exists($this, 'conclude'))
    	$this->conclude();

    if(method_exists($this, 'display'))
    	$template = $this->display($custom_template);
  }

  public function conclude(){}

  public function prepare()
  {
    $this->trim_request_data();
  }

  private function trim_request_data()
  {
    array_walk_recursive($_GET, function(&$value){$value = trim($value);});
    array_walk_recursive($_REQUEST, function(&$value){$value = trim($value);});

    if($this->router()->submits())
      array_walk_recursive($_POST, function(&$value){$value = trim($value);});
  }

  public function display($custom_template = null, $standalone=false)
  {
    $smarty = $this->box('template_engine');

    $template = $this->find_template($smarty, $custom_template); // throws Exception if nothing found

		$this->viewport('controller', $this);

    $this->viewport('user_messages', $this->logger()->get_user_report());


  	$this->viewport('file_root', $this->router()->file_root());
  	$this->viewport('view_path', $this->router()->file_root() . $this->box('settings.smarty.template_path').'app/');
  	$this->viewport('web_root', $this->router()->web_root());
  	$this->viewport('view_url', $this->router()->web_root() . $this->box('settings.smarty.template_path'));
  	$this->viewport('images_url', $this->router()->web_root() . $this->box('settings.smarty.template_path') . 'images/');

    foreach($this->viewport() as $template_var_name => $value)
      $smarty->assign($template_var_name, $value);

    if($standalone === false)
    {
      $smarty->display(sprintf('%s|%s', $this->box('settings.smarty.template_inclusion_path'), $template));
    }
    else
    {
      $smarty->display($template);
    }


    return true;
  }

  private function template_base()
  {
    return strtolower(str_replace('Controller', '', (new \ReflectionClass(get_called_class()))->getShortName()));
  }

  protected function find_template($smarty, $custom_template = null)
  {
    $controller_template_path = $this->template_base();
    $templates = [];

    if(!empty($custom_template))
    {
      // 1. check for custom template in the current controller directory
      $templates ['custom_3']= sprintf('%s/%s.html', $controller_template_path, $custom_template);
      // 2. check for custom template formatted as controller/view
      $templates ['custom_2']= $custom_template.'.html';
      $templates ['custom_1']= '_layouts/'.$custom_template.'.html';
    }

    if(!empty($this->router()->target_method()))
    {
      // 1. check for template in controller-related directory
      $templates ['target_1']= sprintf('%s/%s.html', $controller_template_path, $this->router()->target_method());
      // 2. check for template in app-related directory
      $templates ['target_2']= sprintf('_layouts/%s.html', $this->router()->target_method());
      // 3. check for template in kadro directory
      $templates ['target_3']= sprintf('%s.html', $this->router()->target_method());
    }

    $templates ['default_3']= sprintf('%s/edit.html', $controller_template_path);
    $templates ['default_4']= 'edit.tpl';
    $templates = array_unique($templates);

    while(!is_null($tpl_path = array_shift($templates)))
    {
      if($smarty->templateExists($tpl_path))
        return $tpl_path;
    }

    throw new \Exception('KADRO_ERR_NO_TEMPLATE_TO_DISPLAY');
  }


  public function has_route_back() : bool
  {
    return is_null($this->route_back);
  }

  /*
   * returns string, a URL formatted by RouterInterface::pre_hop()
   *
   * USAGE
   * route_back($route_name=null) returns previously set $route_back or RouterInterface::ROUTE_HOME_NAME
   * route_back($route_name [,$route_params]), sets $route_back using route_factory()
   *
   */
  public function route_back($route_name=null, $route_params=[]) : string
	{
    if(is_null($route_name))
		  return $this->route_back ?? $this->router()->prehop(RouterInterface::ROUTE_HOME_NAME);

    return $this->route_back = $this->route_factory($route_name, $route_params);
	}

  public function route_factory($route_name=null, $route_params=[]) : string
  {
    $route = null;

    if(is_string($route_name) && !empty($route_name))
    {
      if($this->router()->route_exists($route_name))
        $route = $this->router()->prehop($route_name, $route_params);
      else
        $route = $route_name;

      return $route;
    }

    throw new \Exception('ROUTE_FACTORY_PARAM_TYPE_ERROR');
  }
}
