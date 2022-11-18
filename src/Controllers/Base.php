<?php

namespace HexMakina\kadro\Controllers;

use Psr\Container\{ContainerInterface,ContainerExceptionInterface,NotFoundExceptionInterface};
use HexMakina\BlackBox\RouterInterface;
use HexMakina\BlackBox\Controllers\BaseControllerInterface;
use Psr\Log\LoggerInterface;
use HexMakina\LeMarchand\LeMarchand;

class Base implements BaseControllerInterface, ContainerInterface
{
    use \HexMakina\Traitor\Traitor;

    protected $route_back;

    protected array $errors = [];

    /**
     * @return mixed[][]
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function container(): ContainerInterface
    {
        return LeMarchand::box();
    }

    public function has($key): bool
    {
        return $this->container()->has($key);
    }

    public function get($key)
    {
        return $this->container()->get($key);
    }

    public function addError($message, $context = []): void
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
        return $this->get('HexMakina\BlackBox\RouterInterface');
    }

    public function prepare(): void
    {

    }

    public function execute($method)
    {
        $ret = null;

      // before and after hooks, should they be in basecontroller ?
      // i think so, but pascal just proposed me pastis.. tomorrow

        foreach (['prepare', sprintf('before_%s', $method), $method, sprintf('after_%s', $method)] as $chainling) {
            $this->traitor($chainling);

            if (method_exists($this, $chainling) && empty($this->errors())) {
                $res = $this->$chainling();

                if ($chainling === $method) {
                    $ret = $res;
                }
            }
        }

        $this->conclude();

        return $ret;
    }

    public function conclude(): void
    {
    }

  /*
   * returns string, a URL formatted by RouterInterface::pre_hop()
   *
   * USAGE
   * routeBack($route_name=null) returns previously set $route_back or RouterInterface::ROUTE_HOME_NAME
   * routeBack($route_name [,$route_params]), sets $route_back using routeFactory()
   *
   */
    public function routeBack($route_name = null, $route_params = []): string
    {
        if (is_null($route_name)) {
            return $this->route_back ?? $this->router()->hyp(RouterInterface::ROUTE_HOME_NAME);
        }

        return $this->route_back = $this->routeFactory($route_name, $route_params);
    }

    public function routeFactory($route_name = null, $route_params = []): string
    {
        if (is_string($route_name) && !empty($route_name)) {
            if ($this->router()->routeExists($route_name)) {
                return $this->router()->hyp($route_name, $route_params);
            }

            return $route_name;
        }

        throw new \Exception('ROUTE_FACTORY_PARAM_TYPE_ERROR');
    }
}
