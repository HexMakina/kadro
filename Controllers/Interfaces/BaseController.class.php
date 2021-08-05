<?php

namespace HexMakina\kadro\Controllers\Interfaces;

use \Psr\Container\ContainerInterface;
use \HexMakina\kadro\Auth\OperatorInterface;
use \HexMakina\kadro\Router\RouterInterface;
use \HexMakina\Logger\LoggerInterface;

interface BaseController
{
  public function router() : RouterInterface;
  public function operator() : OperatorInterface;
  public function logger() : LoggerInterface;

  public function container() : ContainerInterface;
  public function set_container(ContainerInterface $container);

  public function execute();
}
