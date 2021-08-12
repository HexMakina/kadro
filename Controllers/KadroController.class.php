<?php

namespace HexMakina\kadro\Controllers;

use \Psr\Container\{ContainerInterface,ContainerExceptionInterface,NotFoundExceptionInterface};

use \HexMakina\kadro\Auth\{OperatorInterface, AccessRefusedException};
use \HexMakina\Hopper\RouterInterface;
use \HexMakina\LogLaddy\LoggerInterface;
use \HexMakina\Crudites\Interfaces\TracerInterface;

class KadroController extends DisplayController
{
  public function __toString(){ return get_called_class();}


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

  public function execute()
  {
    $this->authorize();

    return parent::execute();
  }

  public function prepare()
  {
    parent::prepare();
    $this->trim_request_data();
  }

  private function trim_request_data()
  {
    array_walk_recursive($_GET, function(&$value){$value = trim($value);});
    array_walk_recursive($_REQUEST, function(&$value){$value = trim($value);});

    if($this->router()->submits())
      array_walk_recursive($_POST, function(&$value){$value = trim($value);});
  }

}
