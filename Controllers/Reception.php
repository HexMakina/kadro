<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\kadro\Auth\{Operator,Permission,ACL};
use HexMakina\kadro\Auth\AccessRefusedException;
use HexMakina\Interfaces\Auth\OperatorInterface;
use HexMakina\LeMarchand\LeMarchand;

class Reception extends Kadro
{
    public function requiresOperator(): bool
    {
        return false;
    }

    public function welcome(OperatorInterface $operator)
    {

        if ($this->router()->name() === 'identify') {
            $this->identify($operator);
        }

        $target_controller = $this->router()->targetController();
        $target_controller = $this->get('Controllers\\'.$target_controller);


        if ($target_controller->requiresOperator()) {
            if (is_null($operator = get_class($operator)::exists($this->get('StateAgent')->operatorId()))) {
                $this->router()->hop('checkin');
            }

            if (!$operator->isActive()) {
                $this->checkout();
                throw new AccessRefusedException();
            }
            LeMarchand::box()->put('HexMakina\Interfaces\Auth\OperatorInterface', $operator);
        }

        return $operator;
    }

    public function checkin()
    {
        $this->display('checkin', 'standalone');
        $this->get('HexMakina\Interfaces\StateAgentInterface')->resetMessages();
    }

    public function checkout()
    {
        $this->get('StateAgent')->destroy();
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

            $this->get('StateAgent')->operatorId($operator->getId());
            $this->logger()->notice($this->l('PAGE_CHECKIN_WELCOME', [$operator->name()]));
            $this->router()->hop();
        } catch (\Exception $e) {
            $this->logger()->warning($this->l('KADRO_operator_' . $e->getMessage()));
            $this->router()->hop('checkin');
        }
    }
}
