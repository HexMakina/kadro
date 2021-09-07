<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\kadro\Auth\{Operator,Permission,ACL};
use HexMakina\kadro\Auth\AccessRefusedException;
use HexMakina\Interfaces\Auth\OperatorInterface;
use HexMakina\LeMarchand\LeMarchand;

class Reception extends Kadro
{
    public function requires_operator(): bool
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


        if ($target_controller->requires_operator()) {
            if (is_null($operator = get_class($operator)::exists($this->get('StateAgent')->operatorId()))) {
                $this->router()->hop('checkin');
            }

            if (!$operator->is_active()) {
                $this->checkout();
                throw new AccessRefusedException();
            }
            LeMarchand::box()->put('OperatorInterface', $operator);
        }

        return $operator;
    }

    public function checkin()
    {
        $this->display('checkin', 'standalone');
        $this->logger()->cleanUserReport();
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

            if (is_null($operator) || !$operator->is_active()) {
                throw new \Exception('ERR_DISABLED');
            }

            if (!$operator->password_verify($password)) {
                throw new \Exception('ERR_WRONG_LOGIN_OR_PASSWORD');
            }

            $this->get('StateAgent')->operatorId($operator->get_id());
            $this->logger()->notice($this->l('PAGE_CHECKIN_WELCOME', [$operator->name()]));
            $this->router()->hop();
        } catch (\Exception $e) {
            $this->logger()->warning($this->l('KADRO_operator_' . $e->getMessage()));
            $this->router()->hop('checkin');
        }
    }
}
