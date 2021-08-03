<?php 
namespace HexMakina\kadro\Container;

use \Psr\Container\{ContainerExceptionInterface};

class LamentException extends \Exception implements ContainerExceptionInterface
{
  public function __construct($configuration)
  {
    $configuration = json_encode(var_export($configuration));
    return parent::__construct("HellBound Error using '$configuration'");
  }  
}

