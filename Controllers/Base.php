<?php

namespace HexMakina\kadro\Controllers;

use Psr\Container\{ContainerInterface,ContainerExceptionInterface,NotFoundExceptionInterface};
use HexMakina\kadro\Auth\AccessRefusedException;
use HexMakina\Interfaces\Auth\OperatorInterface;
use HexMakina\Interfaces\RouterInterface;
use HexMakina\Interfaces\Controllers\BaseControllerInterface;
use Psr\Log\LoggerInterface;
use HexMakina\LeMarchand\LeMarchand;

class Base implements BaseControllerInterface, ContainerInterface
{
    use \HexMakina\Traitor\Traitor;

    protected $route_back = null;
    protected $errors = [];

    public function errors(): array
    {
        return $this->errors;
    }

    public function container(): ContainerInterface
    {
      return LeMarchand::box();
    }

    public function has($key)
    {
        return $this->container()->has($key);
    }

    public function get($key)
    {
        return $this->container()->get($key);
    }

    public function add_error($message, $context = [])
    {
        $this->errors[] = [$message, $context];
    }

    public function logger(): LoggerInterface
    {
        return $this->get('Psr\Log\LoggerInterface');
    }

  // --------  Router

    public function router(): RouterInterface
    {
        return $this->get('HexMakina\Interfaces\RouterInterface');
    }

    public function prepare()
    {
        return true;
    }

    public function execute()
    {
        $ret = null;

        $method = $this->router()->targetMethod();

      // before and after hooks, should they be in basecontroller ?
      // i think so, but pascal just proposed me pastis.. tomorrow

        foreach (['prepare', "before_$method", $method, "after_$method"] as $step => $chainling) {
            $this->traitor($chainling);

            if (method_exists($this, $chainling) && empty($this->errors())) {
                $res = $this->$chainling();

                if ($this->logger()->hasHaltingMessages()) { // logger handled a critical error during the chailing execution
                    break; // dont go on with other
                }

                if ($chainling === $method) {
                    $ret = $res;
                }
            }
        }

        $this->conclude();

        return $ret;
    }

    public function conclude()
    {
        return true;
    }

    public function has_route_back(): bool
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
    public function route_back($route_name = null, $route_params = []): string
    {
        if (is_null($route_name)) {
            return $this->route_back ?? $this->router()->hyp(RouterInterface::ROUTE_HOME_NAME);
        }

        return $this->route_back = $this->route_factory($route_name, $route_params);
    }

    public function route_factory($route_name = null, $route_params = []): string
    {
        $route = null;

        if (is_string($route_name) && !empty($route_name)) {
            if ($this->router()->routeExists($route_name)) {
                $route = $this->router()->hyp($route_name, $route_params);
            } else {
                $route = $route_name;
            }

            return $route;
        }

        throw new \Exception('ROUTE_FACTORY_PARAM_TYPE_ERROR');
    }
}
