<?php

namespace HexMakina\kadro\Controllers;

use HexMakina\kadro\Auth\{Operator};
use HexMakina\BlackBox\Auth\OperatorInterface;


class Reception extends Kadro
{
    public function requiresOperator(): bool
    {
        return false;
    }

    public function welcome(OperatorInterface $operator) : void
    {
        $this->router()->match(); // throws RouterException if no match

        if ($this->router()->name() === 'identify') {
            $this->identify($operator);
        }

        // MVC Cascade
        $target_controller = $this->get('Controllers\\' . $this->router()->targetController());

        if ($target_controller->requiresOperator()) {

            $operator_id = $this->get('HexMakina\BlackBox\StateAgentInterface')->operatorId();
            if (empty($operator_id)) {
                $this->checkin();
                die;
            }
            else{
                $operator = get_class($operator)::exists($operator_id);
                if (is_null($operator) || !$operator->isActive()) {
                    $this->checkout();
                }
            }
        }
    }

    // GET
    public function checkin(): void
    {
        $this->display('checkin', 'standalone');
        $this->get('HexMakina\BlackBox\StateAgentInterface')->resetMessages();
    }

    // GET
    public function checkout(): void
    {
        $this->get('HexMakina\BlackBox\StateAgentInterface')->destroy();
        $this->router()->hop('checkin');
    }

    // POST
    public function identify($op): void
    {
        try {
            $username = $this->router()->submitted('username');
            $password = $this->router()->submitted('password');
            $operator = get_class($op)::exists('username', $username);

            if (is_null($operator) ) {
                throw new \Exception('OPERATOR_DOES_NOT_EXIST');
            }

            if (!$operator->isActive()) {
                throw new \Exception('OPERATOR_IS_DISABLED');
            }

            if (!$operator->passwordVerify($password)) {
                throw new \Exception('WRONG_LOGIN_OR_PASSWORD');
            }

            $this->get('HexMakina\BlackBox\StateAgentInterface')->operatorId($operator->getId());
            $this->logger()->notice('PAGE_CHECKIN_WELCOME', [$operator->name()]);
            $this->router()->hop();

        } catch (\Exception $exception) {
            $this->logger()->warning('KADRO_operator_' . $exception->getMessage());
            $this->router()->hop('checkin');
        }
    }
}
