<?php

namespace HexMakina\kadro\Controllers\Interfaces;

use \Psr\Container\ContainerInterface;
use \HexMakina\kadro\Auth\OperatorInterface;
use \HexMakina\Hopper\RouterInterface;
use \HexMakina\LogLaddy\LoggerInterface;

interface BaseController
{
  public function router() : RouterInterface;
  public function operator() : OperatorInterface;
  public function logger() : LoggerInterface;

  public function container() : ContainerInterface;
  public function set_container(ContainerInterface $container);

  public function prepare();
  public function execute();
  public function conclude();
  public function errors() : array;

  public function has_route_back() : bool
  public function route_back($route_name=null, $route_params=[]) : string;
  public function route_factory($route_name=null, $route_params=[]) : string;

}
