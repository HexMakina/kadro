<?php

namespace HexMakina\kadro\Router;

class RouterException extends \Exception
{
  public function __construct($message, $code = 0, $previous = null)
  {
    parent::__construct('KADRO_ROUTER_ERR_'.$message, $code, $previous);
  }
}


