<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\kadro\Auth\{Operator,Permission,ACL};
use HexMakina\kadro\Auth\AccessRefusedException;
use HexMakina\BlackBox\Auth\OperatorInterface;

// use HexMakina\LeMarchand\LeMarchand;

class Reception extends Kadro
{
    public function requiresOperator(): bool
    {
        return false;
    }

    public function welcome(OperatorInterface $operator)
    {
        $router = $this->get('HexMakina\BlackBox\RouterInterface');
        $router->match(); // throws RouterException if no match

        if ($router->name() === 'identify') {
            $this->identify($operator);
        }

        // MVC Cascade
        $target_controller = $this->get('Controllers\\' . $router->targetController());

        if ($target_controller->requiresOperator()) {
            if (is_null($operator_id = $this->get('HexMakina\BlackBox\StateAgentInterface')->operatorId())) {
                $this->checkin();
            }

            if (is_null($operator = get_class($operator)::exists($operator_id)) || !$operator->isActive()) {
                $this->checkout();
            }
        }

        return $operator;
    }

    public function checkin()
    {
        $this->display('checkin', 'standalone');
        $this->get('HexMakina\BlackBox\StateAgentInterface')->resetMessages();
    }

    public function checkout()
    {
        $this->get('HexMakina\BlackBox\StateAgentInterface')->destroy();
        $this->router()->hop('checkin');
    }

    public function identify($op)
    {
        try {
            $username = $this->router()->submitted('username');
            $password = $this->router()->submitted('password');

            $operator = get_class($op)::exists(['username' => $username]);

            if (is_null($operator) || !$operator->isActive()) {
                throw new \Exception('ERR_DISABLED');
            }

            if (!$operator->passwordVerify($password)) {
                throw new \Exception('ERR_WRONG_LOGIN_OR_PASSWORD');
            }

            $this->get('HexMakina\BlackBox\StateAgentInterface')->operatorId($operator->getId());
            $this->logger()->notice('PAGE_CHECKIN_WELCOME', [$operator->name()]);
            $this->router()->hop();
        } catch (\Exception $e) {
            $this->logger()->warning('KADRO_operator_' . $e->getMessage());
            $this->router()->hop('checkin');
        }
    }
}
